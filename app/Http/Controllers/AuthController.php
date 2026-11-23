<?php

namespace App\Http\Controllers;

use App\Models\CounselorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! Auth::user()->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda dinonaktifkan oleh admin.']);
        }

        return redirect()->intended(Auth::user()->homeRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function showRegisterStudent()
    {
        return view('auth.register-student');
    }

    public function registerStudent(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nim' => ['required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data + ['role' => 'mahasiswa']);
        Auth::login($user);

        return redirect()->route('student.dashboard')->with('ok', 'Akun berhasil dibuat.');
    }

    public function showRegisterCounselor()
    {
        return view('auth.register-counselor');
    }

    public function registerCounselor(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'graduate_field' => ['required', 'string', 'max:150'],
            'education' => ['required', 'string', 'max:200'],
            'specializations' => ['required', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1500'],
            'certs' => ['nullable', 'array', 'max:5'],
            'certs.*.name' => ['nullable', 'string', 'max:200'],
            'certs.*.issuer' => ['nullable', 'string', 'max:200'],
            'certs.*.year' => ['nullable', 'integer', 'between:1980,'.now()->year],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'phone' => $data['phone'] ?? null, 'role' => 'konselor',
            ]);

            $profile = CounselorProfile::create([
                'user_id' => $user->id,
                'graduate_field' => $data['graduate_field'],
                'education' => $data['education'],
                'specializations' => $data['specializations'],
                'bio' => $data['bio'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($data['certs'] ?? [] as $c) {
                if (! empty($c['name'])) {
                    $profile->certifications()->create([
                        'name' => $c['name'], 'issuer' => $c['issuer'] ?? null, 'year' => $c['year'] ?? null,
                    ]);
                }
            }

            return $user;
        });

        Auth::login($user);

        return redirect()->route('counselor.dashboard')
            ->with('ok', 'Pendaftaran terkirim. Profil Anda tampil di daftar konselor setelah admin menyetujuinya.');
    }
}
