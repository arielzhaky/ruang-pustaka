@extends('layouts.app')
@section('title', 'Pengaturan Akun | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <p class="page-eyebrow">RUANG PUSTAKA / AKUN</p><h1>Pengaturan akun</h1>
    <p class="page-intro">Perbarui kontak dan jaga keamanan akunmu.</p>
    @include('shared.service-flash')
    <div class="row g-4">
        <section class="col-lg-7"><div class="bg-white p-4 rounded-3 border h-100"><h2 class="h5">Profil saya</h2>
            <form action="{{ route('account.update') }}" method="POST">@csrf @method('PUT')
                <label for="account-name" class="form-label mt-3">Nama lengkap</label><input id="account-name" name="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="255" required autocomplete="name">
                <label for="account-email" class="form-label mt-3">Email</label><input id="account-email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email">
                @if($user->member)<label for="account-phone" class="form-label mt-3">Nomor telepon (opsional)</label><input id="account-phone" name="phone" type="tel" class="form-control" value="{{ old('phone', $user->member->phone) }}" maxlength="30" autocomplete="tel">
                <div class="p-3 mt-3 bg-light rounded"><strong>{{ $user->member->member_code }}</strong><br>Kelas: {{ $user->member->schoolClass?->name ?? 'Belum diisi' }} · NIS: {{ $user->member->nis ?: 'Belum diisi' }}<p class="small text-secondary mb-0 mt-2">Perubahan kelas, NIS, dan data keanggotaan dilakukan oleh petugas.</p></div>@endif
                <label for="profile-password" class="form-label mt-3">Kata sandi saat ini</label><input id="profile-password" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                <button class="btn btn-primary mt-4">Simpan profil</button>
            </form>
        </div></section>
        <section class="col-lg-5"><div class="bg-white p-4 rounded-3 border"><h2 class="h5">Ubah kata sandi</h2>
            <form action="{{ route('account.password') }}" method="POST">@csrf @method('PUT')
                <label for="old-password" class="form-label mt-3">Kata sandi saat ini</label><input id="old-password" type="password" name="current_password" class="form-control" required autocomplete="current-password">
                <label for="new-password" class="form-label mt-3">Kata sandi baru</label><input id="new-password" type="password" name="password" class="form-control" minlength="8" maxlength="72" required autocomplete="new-password"><p class="form-text">Gunakan 8–72 karakter dan berbeda dari kata sandi lama.</p>
                <label for="confirm-password" class="form-label mt-3">Ulangi kata sandi baru</label><input id="confirm-password" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                <button class="btn btn-primary mt-4">Ubah kata sandi</button>
            </form>
        </div></section>
    </div>
</main>
@endsection
