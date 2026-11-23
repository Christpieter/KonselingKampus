<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\ProblemType;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'students' => User::where('role', 'mahasiswa')->count(),
            'counselors' => CounselorProfile::where('status', 'approved')->count(),
            'waiting' => CounselorProfile::where('status', 'pending')->count(),
            'bookings' => Booking::count(),
            'byStatus' => Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'avg' => round((float) Rating::avg('stars'), 1),
        ]);
    }

    public function counselors(Request $request)
    {
        $status = $request->query('status', 'pending');
        $profiles = CounselorProfile::with(['user', 'certifications'])
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest()->get();

        return view('admin.counselors', compact('profiles', 'status'));
    }

    public function setCounselorStatus(Request $request, CounselorProfile $profile)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected,pending']]);
        $profile->update($data);

        return back()->with('ok', 'Status konselor diperbarui.');
    }

    public function users(Request $request)
    {
        $role = $request->query('peran');
        $users = User::when($role, fn ($q) => $q->where('role', $role))->orderBy('role')->orderBy('name')->get();

        return view('admin.users', compact('users', 'role'));
    }

    public function toggleUser(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Anda tidak bisa menonaktifkan akun sendiri.');
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('ok', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function problemTypes()
    {
        return view('admin.problem-types', [
            'types' => ProblemType::orderBy('name')->get()->each(fn ($t) => $t->used = Booking::where('problem_type_id', $t->id)->count()),
        ]);
    }

    public function storeProblemType(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:problem_types,name'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);
        ProblemType::create($data);

        return back()->with('ok', 'Tipe masalah ditambahkan.');
    }

    public function destroyProblemType(ProblemType $type)
    {
        if (Booking::where('problem_type_id', $type->id)->exists()) {
            return back()->with('err', 'Tipe ini sudah dipakai di booking, jadi tidak bisa dihapus.');
        }
        $type->delete();

        return back()->with('ok', 'Tipe masalah dihapus.');
    }

    public function bookings()
    {
        $bookings = Booking::with(['student', 'counselor', 'problemType'])->latest()->take(100)->get();

        return view('admin.bookings', compact('bookings'));
    }
}
