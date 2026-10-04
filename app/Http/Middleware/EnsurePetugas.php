<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePetugas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless(
            $user->is_active && $user->role === 'petugas',
            403,
            'Halaman ini hanya untuk petugas aktif.'
        );

        return $next($request);
    }
}