@extends('layouts.app')
@section('title', 'Daftar Siswa | Ruang Pustaka')
@section('content')
<main class="container py-5" style="max-width: 760px">
    <section class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <a href="{{ route('home') }}" class="brand registration-brand mb-3 text-decoration-none"><x-library-logo/><span>RUANG PUSTAKA</span></a>
        <h1 class="h3 fw-bold">Buat Akun Siswa</h1>
        <p class="text-secondary">Daftar untuk melihat katalog dan memantau peminjamanmu. Kode anggota dibuat otomatis.</p>
        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        @endif
        @if ($classes->isEmpty())
            <div class="alert alert-warning">Pendaftaran belum tersedia karena kelas belum disiapkan. Hubungi petugas perpustakaan.</div>
        @endif
        <form action="{{ route('register.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="name">Nama lengkap *</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="email">Email *</label>
                    <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="gender">Jenis kelamin *</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="laki-laki" @selected(old('gender') === 'laki-laki')>Laki-laki</option>
                        <option value="perempuan" @selected(old('gender') === 'perempuan')>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="age">Usia (tahun) *</label>
                    <input class="form-control" type="number" id="age" name="age" value="{{ old('age') }}" min="1" max="120" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="school_class_id">Kelas *</label>
                    <select class="form-select" id="school_class_id" name="school_class_id" required>
                        <option value="">Pilih kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nis">NIS (opsional)</label>
                    <input class="form-control" id="nis" name="nis" value="{{ old('nis') }}" maxlength="30">
                </div>
                <div class="col-12">
                    <label class="form-label" for="phone">Nomor telepon (opsional)</label>
                    <input class="form-control" type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">Password *</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <div class="form-text">Minimal 8 karakter.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Konfirmasi password *</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
            </div>
            <button class="btn btn-primary w-100 mt-4" @disabled($classes->isEmpty())>Daftar sebagai Siswa</button>
        </form>
        <p class="text-center mt-4 mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </section>
</main>
@endsection
