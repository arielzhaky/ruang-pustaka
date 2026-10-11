<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $qrHash = $request->session()->get('student_login_qr_hash');
        if ($request->user() && $qrHash && ($request->user()->role !== 'anggota'
            || ! hash_equals($qrHash, $request->user()->member?->login_qr_hash ?? ''))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'QR login sudah diganti atau dinonaktifkan. Gunakan kartu terbaru atau masuk dengan email.']);
        }
        // Periksa juga sesi yang sudah ada ketika petugas menonaktifkan akun.
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun kamu sudah dinonaktifkan.']);
        }

        return $next($request);
    }
}
