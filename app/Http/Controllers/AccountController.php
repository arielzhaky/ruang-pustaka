<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load('member.schoolClass');

        return view('account.edit', compact('user'));
    }

    public function update(Request $request)
    {
        if (is_string($request->email)) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => ['required', 'current_password'],
        ], ['current_password.current_password' => 'Kata sandi saat ini tidak sesuai.']);
        DB::transaction(function () use ($request, $data) {
            $request->user()->update(['name' => $data['name'], 'email' => $data['email']]);
            $request->user()->member?->update(['phone' => $data['phone'] ?? null]);
        });

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:current_password'],
        ], ['current_password.current_password' => 'Kata sandi saat ini tidak sesuai.']);
        $request->user()->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}
