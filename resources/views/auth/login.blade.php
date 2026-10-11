@extends('layouts.app')

@section('title', 'Masuk | Ruang Pustaka')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}" defer></script>
@endpush

@section('content')
<main class="pustaka-login">
    <aside class="login-story" aria-label="Tentang Ruang Pustaka">
        <a href="{{ route('home') }}" class="login-wordmark" aria-label="Ruang Pustaka, beranda">
            <x-library-logo/>
            Ruang Pustaka.
        </a>
        <p class="login-school">{{ config('library.school_name') }}</p>

        <div class="login-story-content">
            <p class="login-eyebrow">Perpustakaan sekolah</p>
            <h2 class="login-story-title">Satu ruang.<br><em>Banyak wawasan.</em></h2>
            <p class="login-story-description">Temukan bacaan, jelajahi pengetahuan, dan mulai cerita barumu. Semua dari satu ruang.</p>

            <figure class="login-campus-card">
                <img src="{{ asset('images/school/smpn-26-tangerang-home.jpg') }}"
                     alt="Tampak depan gedung dan gerbang {{ config('library.school_name') }}"
                     width="1672" height="941" fetchpriority="high">
                <figcaption>
                    <span class="login-campus-icon" aria-hidden="true"><x-icon name="book" :size="21"/></span>
                    <div>
                        <strong>{{ config('library.school_name') }}</strong>
                        <span>Tempat belajar, membaca, dan bertumbuh.</span>
                    </div>
                </figcaption>
            </figure>
        </div>

        <p class="login-story-footer">Satu halaman hari ini. Wawasan baru esok hari.</p>
    </aside>

    <section class="login-form-panel" aria-labelledby="login-heading">
        <div class="login-form-wrap">
            <p class="login-eyebrow">Masuk ke Ruang Pustaka</p>
            <h1 id="login-heading" class="login-title">Selamat datang<br>kembali</h1>
            <p class="login-intro">Masuk untuk melanjutkan aktivitas perpustakaanmu.</p>

            @if (session('status'))
                <div class="login-notice" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="login-notice login-notice-error" id="login-errors" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="login-methods" aria-label="Cara masuk" hidden data-login-methods>
                <button type="button" aria-pressed="true" data-login-method="email">Email & kata sandi</button>
                <button type="button" aria-pressed="false" data-login-method="qr">Scan kartu siswa</button>
            </div>
            <div data-login-qr-panel hidden>
                @include('shared.barcode-scanner', ['scannerId'=>'student-login', 'scanMode'=>'login', 'scanTitle'=>'Masuk dengan QR siswa'])
                <p class="login-help">Scan bagian <strong>QR login</strong> pada kartu terbaru untuk langsung masuk. Belum punya QR login? Minta petugas membuatkannya melalui Kartu Siswa.</p>
            </div>
            <form action="{{ route('login.submit') }}" method="POST" data-login-email-form @if ($errors->any()) aria-describedby="login-errors" @endif>
                @csrf
                <fieldset class="login-role-fieldset">
                    <legend>Masuk sebagai</legend>
                    <div class="login-role-options">
                        <label class="login-role" for="role-student">
                            <input type="radio" id="role-student" name="role" value="anggota" @checked(old('role', 'anggota') === 'anggota') required>
                            <span>Siswa</span>
                        </label>
                        <label class="login-role" for="role-staff">
                            <input type="radio" id="role-staff" name="role" value="petugas" @checked(old('role') === 'petugas') required>
                            <span>Petugas / Admin</span>
                        </label>
                    </div>
                </fieldset>

                <div class="login-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="login-input" value="{{ is_string(old('email')) ? old('email') : '' }}"
                           placeholder="nama@sekolah.sch.id" autocomplete="username" spellcheck="false" autocapitalize="none" required>
                </div>

                <div class="login-field">
                    <label for="password">Kata sandi</label>
                    <div class="login-password-wrap">
                        <input type="password" id="password" name="password" class="login-input"
                               placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <button type="button" id="toggle-password" class="login-password-toggle" aria-label="Tampilkan kata sandi"
                                aria-controls="password" aria-pressed="false" hidden>
                            <svg data-eye-open width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg data-eye-closed width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                <path d="m3 3 18 18M10.6 5.1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.1M6.3 6.3A21 21 0 0 0 2 12s3.5 7 10 7a11 11 0 0 0 5.2-1.3M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-submit">
                    Masuk ke dashboard
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                </button>
            </form>

            <section class="login-register-card" aria-labelledby="register-heading">
                <span class="login-register-tag">SISWA BARU</span>
                <h2 id="register-heading">Belum punya akun?</h2>
                <p>Buat akun siswa untuk menjelajahi katalog dan memantau peminjaman bukumu.</p>
                <a href="{{ route('register') }}" class="login-register-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v16h-6M3 12h12m-4-4 4 4-4 4"/></svg>
                    Daftar sebagai siswa
                </a>
            </section>
            <p class="login-help">Lupa kata sandi? Hubungi petugas perpustakaan.</p>
        </div>
    </section>
</main>
@endsection
