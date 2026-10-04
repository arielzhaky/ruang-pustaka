@extends('layouts.app')

@section('title', 'Masuk | Ruang Pustaka')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/login.js') }}" defer></script>
@endpush

@section('content')
<main class="pustaka-login">
    <aside class="login-story" aria-label="Tentang Ruang Pustaka">
        <a href="{{ route('home') }}" class="login-wordmark" aria-label="Ruang Pustaka, beranda">
            <svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5.5C9.4 3.8 5.6 3.5 2 4.5v15c3.6-1 7.4-.7 10 1 2.6-1.7 6.4-2 10-1v-15c-3.6-1-7.4-.7-10 1Z"/>
                <path d="M12 5.5v15M5.5 8.2c1.1-.1 2.2.1 3.2.4M5.5 11.8c1.1-.1 2.2.1 3.2.4M15.3 8.6c1-.3 2.1-.5 3.2-.4M15.3 12.2c1-.3 2.1-.5 3.2-.4"/>
            </svg>
            Ruang Pustaka.
        </a>
        <p class="login-school">{{ config('library.school_name') }}</p>

        <div class="login-story-content">
            <p class="login-eyebrow">Perpustakaan sekolah</p>
            <h2 class="login-story-title">Satu ruang.<br><em>Banyak wawasan.</em></h2>
            <p class="login-story-description">Temukan bacaan, jelajahi pengetahuan, dan mulai cerita barumu. Semua dari satu ruang.</p>

            <section class="login-reading-card" aria-labelledby="reading-card-heading">
                <p class="login-eyebrow">Selalu ada cerita untukmu</p>
                <h3 id="reading-card-heading">Bacaan berikutnya<br>menantimu.</h3>
                <p>Jelajahi koleksi buku dan temukan yang ingin kamu baca hari ini.</p>
                <div class="login-reading-steps" aria-label="Jelajahi, pinjam, baca">
                    <span>Jelajahi</span><span class="step-line" aria-hidden="true"></span>
                    <span>Pinjam</span><span class="step-line" aria-hidden="true"></span><span>Baca</span>
                </div>
            </section>
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

            <form action="{{ route('login.submit') }}" method="POST" @if ($errors->any()) aria-describedby="login-errors" @endif>
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
                    <input type="email" id="email" name="email" class="login-input" value="{{ old('email') }}"
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
