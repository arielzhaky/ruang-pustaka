<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Support\StudentLoginQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StudentQrLoginController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['student_qr' => ['required', 'string', 'max:100']]);
        $token = StudentLoginQr::normalize($data['student_qr']);
        $member = $token ? Member::with('user')->where('login_qr_hash', hash('sha256', $token))->first() : null;
        if (! $member || $member->user?->role !== 'anggota' || ! $member->user->is_active) {
            throw ValidationException::withMessages(['student_qr' => 'QR login tidak berlaku atau akun tidak aktif. Gunakan QR login terbaru dari kartu siswa.']);
        }
        Auth::login($member->user);
        $request->session()->regenerate();
        $request->session()->put('student_login_qr_hash', $member->login_qr_hash);

        return response()->json(['redirect' => route('dashboard')])->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request, ?Member $member = null)
    {
        if ($request->user()->role !== 'petugas') {
            abort_unless($request->user()->role === 'anggota' && $member === null, 403);
            $member = $request->user()->member()->firstOrFail();
        }
        abort_unless($member, 404);
        $data = $request->validate(['action' => ['required', 'in:issue,replace,revoke']]);
        abort_unless($member->user?->role === 'anggota', 404);
        if ($data['action'] === 'revoke') {
            $member->forceFill(['login_qr_token' => null, 'login_qr_hash' => null])->save();
        } else {
            $member = StudentLoginQr::issue($member, $data['action'] === 'replace');
        }
        // A student replacing their own card keeps only this session; other QR sessions expire.
        if ($request->user()->id === $member->user_id && $request->session()->has('student_login_qr_hash')) {
            if ($member->login_qr_hash) $request->session()->put('student_login_qr_hash', $member->login_qr_hash);
            else {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('status', 'QR login dinonaktifkan. Masuk kembali dengan email dan kata sandi.');
            }
        }
        return back()->with('success', match ($data['action']) {
            'replace' => 'QR login diganti. Cetak ulang kartu; QR lama tidak bisa digunakan lagi.',
            'revoke' => 'QR login dinonaktifkan. Siswa masih bisa masuk dengan email dan kata sandi.',
            default => 'QR login siap. Siswa bisa scan QR ini untuk langsung masuk.',
        });
    }
}
