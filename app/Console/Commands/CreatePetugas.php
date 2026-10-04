<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreatePetugas extends Command
{
    protected $signature = 'app:create-petugas';

    protected $description = 'Membuat akun petugas Ruang Pustaka';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Nama petugas'));
        $email = strtolower(trim((string) $this->ask('Email petugas')));
        $password = (string) $this->secret('Password minimal 8 karakter');
        $confirmation = (string) $this->secret('Ulangi password');

        // Periksa input sebelum menyimpan akun.
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        // Tetapkan peran secara langsung pada akun baru.
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'petugas';
        $user->is_active = true;
        $user->save();

        $this->info('Akun petugas berhasil dibuat.');
        $this->line('Email: ' . $user->email);

        return self::SUCCESS;
    }
}