<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create()
    {
        $classes = SchoolClass::where('is_active', true)->orderBy('grade_level')->orderBy('name')->get();

        return view('auth.register', compact('classes'));
    }

    public function store(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'gender' => ['required', 'in:laki-laki,perempuan'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')->where('is_active', 1)],
            'nis' => ['nullable', 'string', 'max:30', 'unique:members,nis'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan. Silakan masuk dengan akun tersebut.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password belum sama.',
            'school_class_id.required' => 'Pilih kelas kamu.',
            'school_class_id.exists' => 'Pilih kelas yang masih aktif.',
            'gender.required' => 'Pilih jenis kelamin.',
            'gender.in' => 'Jenis kelamin harus laki-laki atau perempuan.',
            'age.required' => 'Usia wajib diisi.',
            'age.integer' => 'Usia harus berupa angka bulat.',
            'age.min' => 'Usia minimal 1 tahun.',
            'age.max' => 'Usia maksimal 120 tahun.',
            'nis.unique' => 'NIS sudah digunakan. Hubungi petugas jika kamu sudah memiliki akun.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            // Peran dan kode anggota selalu ditentukan server.
            $user->role = 'anggota';
            $user->is_active = true;
            $user->save();

            Member::create([
                'user_id' => $user->id,
                'member_code' => 'SIS-'.Str::upper(Str::random(16)),
                'gender' => $data['gender'],
                'age' => $data['age'],
                'school_class_id' => $data['school_class_id'],
                'nis' => $data['nis'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Akun siswa berhasil dibuat. Selamat datang di Ruang Pustaka!');
    }
}
