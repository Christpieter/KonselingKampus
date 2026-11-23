<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Halaman ini tidak tersedia untuk peran Anda.');
        }

        if (! $user->is_active) {
            auth()->logout();
            return redirect('/masuk')->withErrors(['email' => 'Akun Anda dinonaktifkan oleh admin.']);
        }

        return $next($request);
    }
}
