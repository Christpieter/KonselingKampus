<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\Rating;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        $total = Booking::count();
        $byStatus = Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $byType = Booking::join('problem_types', 'problem_types.id', '=', 'bookings.problem_type_id')
            ->selectRaw('problem_types.name as name, count(*) as total')
            ->groupBy('problem_types.name')->orderByDesc('total')->limit(6)->get();

        return view('home', [
            'counselorCount' => CounselorProfile::approved()->count(),
            'studentCount' => User::where('role', 'mahasiswa')->where('is_active', true)->count(),
            'avgRating' => round((float) Rating::avg('stars'), 1),
            'ratingCount' => Rating::count(),
            'total' => $total,
            'byStatus' => $byStatus,
            'byType' => $byType,
        ]);
    }

    public function counselors(Request $request)
    {
        $q = CounselorProfile::approved()->with(['user', 'certifications']);

        if ($s = trim((string) $request->query('cari'))) {
            $q->where(fn ($w) => $w
                ->where('specializations', 'like', "%$s%")
                ->orWhere('graduate_field', 'like', "%$s%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%")));
        }

        $profiles = $q->get()->each(function ($p) {
            $p->avg = round((float) $p->user->ratingsReceived()->avg('stars'), 1);
            $p->count = $p->user->ratingsReceived()->count();
        })->sortByDesc('avg')->values();

        return view('counselors.index', ['profiles' => $profiles, 'cari' => $s ?? '']);
    }

    public function counselor(CounselorProfile $profile, ScheduleService $schedule)
    {
        abort_unless($profile->status === 'approved' && $profile->user->is_active, 404);

        $profile->load(['user', 'certifications']);
        $ratings = $profile->user->ratingsReceived()->with('student')->latest()->take(10)->get();

        return view('counselors.show', [
            'profile' => $profile,
            'ratings' => $ratings,
            'avg' => round((float) $profile->user->ratingsReceived()->avg('stars'), 1),
            'count' => $profile->user->ratingsReceived()->count(),
            'nextOpen' => $schedule->nextOpenDate($profile->user_id),
        ]);
    }
}
