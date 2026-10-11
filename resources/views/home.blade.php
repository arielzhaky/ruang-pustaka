<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#121c4a">
    <meta name="description" content="Ruang Pustaka, perpustakaan sekolah untuk menemukan buku, menjelajahi pengetahuan, dan memulai cerita baru.">
    <title>Ruang Pustaka | {{ config('library.school_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/perpustakaan-smpn26-web.png') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}?v={{ filemtime(public_path('css/brand.css')) }}">
    <script src="{{ asset('js/home.js') }}" defer></script>
</head>
<body class="landing">
<a class="home-skip" href="#main-content">Lewati ke isi halaman</a>
<header class="home-header">
    <nav class="home-nav home-container" aria-label="Navigasi utama">
        <a href="{{ route('home') }}" class="home-brand"><x-library-logo/>Ruang Pustaka<span class="brand-dot">.</span></a>
        <div class="home-nav-links"><a href="#koleksi">Koleksi buku</a><a href="#cara-meminjam">Cara meminjam</a></div>
        <div class="home-nav-actions">
            @auth
                <a class="home-button home-button-primary" href="{{ route('dashboard') }}">Dashboard <x-icon name="arrow" :size="16"/></a>
            @else
                <a class="home-login-link" href="{{ route('login') }}">Masuk</a>
                <a class="home-button home-button-primary" href="{{ route('register') }}">Daftar <x-icon name="arrow" :size="16"/></a>
            @endauth
        </div>
    </nav>
</header>
<main id="main-content">
    <section class="home-hero" aria-labelledby="hero-heading">
        <img class="hero-school-photo" src="{{ asset('images/school/smpn-26-tangerang-home.jpg') }}" alt="" aria-hidden="true" width="1672" height="941" fetchpriority="high">
        <div class="hero-stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        <div class="hero-content">
            <span class="hero-eyebrow"><span aria-hidden="true"></span> SELAMAT DATANG DI RUANG PUSTAKA</span>
            <p class="hero-school">{{ config('library.school_name') }}</p>
            <h1 id="hero-heading">Buku favoritmu,<br><em>selangkah lebih dekat.</em></h1>
            <p class="hero-description">Dari satu halaman, terbuka banyak kemungkinan. <br>Temukan bacaanmu dan mulai petualangan baru.</p>
            <form class="hero-search" action="{{ route('home') }}#koleksi" method="GET" role="search">
                <label for="home-search" class="sr-only">Cari judul buku, penulis, atau ISBN</label>
                <svg class="hero-search-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                <input id="home-search" name="q" type="search" value="{{ $search }}" placeholder="Cari judul buku, penulis, atau ISBN…" maxlength="100" autocomplete="off">
                <button type="submit">Cari buku <x-icon name="arrow" :size="17"/></button>
            </form>
            @if ($errors->any())
                <div class="hero-errors" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <p class="hero-search-hint">Ada bacaan untuk setiap rasa ingin tahu.</p>
            <div class="hero-numbers" aria-label="Ringkasan perpustakaan">
                <div><strong>{{ number_format($bookCount, 0, ',', '.') }}</strong><span>judul untuk dijelajahi</span></div>
                <span class="hero-number-divider" aria-hidden="true"></span>
                <div><strong>{{ number_format($availableCount, 0, ',', '.') }}</strong><span>eksemplar tersedia</span></div>
            </div>
        </div>
        <div class="hero-bottom home-container">
            <button id="motion-toggle" class="motion-toggle" type="button" aria-pressed="false" hidden>
                <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="5" y="4" width="3" height="12" rx="1"/><rect x="12" y="4" width="3" height="12" rx="1"/></svg>
                <span>Jeda animasi</span>
            </button>
            <a class="hero-scroll" href="#koleksi">Jelajahi koleksi <span aria-hidden="true">↓</span></a>
            <span class="hero-bottom-note">SATU RUANG. BANYAK WAWASAN.</span>
        </div>
    </section>

    <section class="home-popular" id="buku-populer" aria-labelledby="popular-heading">
        <div class="home-container">
            <div class="section-heading">
                <div>
                    <p class="section-eyebrow">FAVORIT PEMBACA</p>
                    <h2 id="popular-heading">Buku Populer</h2>
                    <p>Empat judul paling sering dipinjam, berdasarkan seluruh riwayat peminjaman.</p>
                </div>
                <a href="{{ route('home') }}#koleksi" class="collection-link">Jelajahi semua buku <x-icon name="arrow" :size="17"/></a>
            </div>
            <div class="home-books">
                @forelse ($popularBooks as $book)
                    @include('home.book-card', ['book' => $book, 'rank' => $loop->iteration, 'colorIndex' => $loop->index])
                @empty
                    <div class="home-empty">
                        <x-icon name="book" :size="40"/>
                        <h3>Buku favorit pembaca segera hadir.</h3>
                        <p>Belum ada riwayat peminjaman. Buku populer akan muncul setelah petugas mencatat peminjaman pertama.</p>
                        <a class="collection-link" href="{{ route('home') }}#koleksi">Jelajahi koleksi <x-icon name="arrow" :size="17"/></a>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="home-collection home-container" id="koleksi" aria-labelledby="collection-heading">
        <div class="section-heading">
            <div><p class="section-eyebrow">RAK BUKU KITA</p><h2 id="collection-heading">{{ $search !== '' ? 'Hasil pencarianmu' : 'Temukan cerita berikutnya.' }}</h2>
                <p>{{ $search !== '' ? 'Pencarian untuk “'.$search.'” — '.$books->total().' judul ditemukan.' : 'Pilihan dari koleksi perpustakaan, siap menemani rasa ingin tahumu.' }}</p>
            </div>
            @if ($search !== '')
                <a href="{{ route('home') }}#koleksi" class="collection-link">Reset pencarian <span aria-hidden="true">↗</span></a>
            @elseif (auth()->check())
                <a href="{{ route(auth()->user()->role === 'petugas' ? 'books.index' : 'catalog.index') }}" class="collection-link">Buka katalog <x-icon name="arrow" :size="17"/></a>
            @endif
        </div>
        <div class="home-books">
            @forelse ($books as $book)
                @include('home.book-card', ['book' => $book, 'rank' => null, 'colorIndex' => $loop->index])
            @empty
                <div class="home-empty"><x-icon name="book" :size="40"/><h3>{{ $search !== '' ? 'Buku yang kamu cari belum ditemukan.' : 'Rak buku sedang disiapkan.' }}</h3><p>{{ $search !== '' ? 'Coba judul atau nama penulis lain, atau lihat seluruh koleksi.' : 'Koleksi yang ditambahkan petugas akan tampil di sini. Kamu sudah bisa membuat akun siswa.' }}</p><a class="home-button home-button-primary" href="{{ $search !== '' ? route('home').'#koleksi' : (auth()->check() ? route('dashboard') : route('register')) }}">{{ $search !== '' ? 'Lihat semua buku' : (auth()->check() ? 'Buka dashboard' : 'Buat akun siswa') }}</a></div>
            @endforelse
        </div>
        @if ($books->hasPages())
            <nav class="home-pagination" aria-label="Halaman koleksi buku">
                @if ($books->onFirstPage())<span aria-disabled="true">← Sebelumnya</span>@else<a href="{{ $books->previousPageUrl() }}#koleksi">← Sebelumnya</a>@endif
                <span>{{ $books->currentPage() }} / {{ $books->lastPage() }}</span>
                @if ($books->hasMorePages())<a href="{{ $books->nextPageUrl() }}#koleksi">Berikutnya →</a>@else<span aria-disabled="true">Berikutnya →</span>@endif
            </nav>
        @endif
    </section>

    <section class="home-guide" id="cara-meminjam" aria-labelledby="guide-heading">
        <div class="home-container">
            <div class="section-heading"><div><p class="section-eyebrow">MUDAH MEMULAI</p><h2 id="guide-heading">Baca lebih banyak.<br>Mulai dari tiga langkah.</h2></div><p>Dari layar ke rak buku,<br>kami temani langkahmu.</p></div>
            <div class="guide-steps">
                <article data-reveal><span class="step-number">01</span><x-icon name="users" :size="27"/><h3>Buat akun siswa</h3><p>Daftar dengan biodata dan kelasmu, lalu masuk ke Ruang Pustaka.</p></article>
                <article data-reveal><span class="step-number">02</span><x-icon name="book" :size="27"/><h3>Temukan buku pilihan</h3><p>Jelajahi katalog, baca detailnya, dan periksa ketersediaan serta lokasi rak.</p></article>
                <article data-reveal><span class="step-number">03</span><x-icon name="loan" :size="27"/><h3>Temui petugas, mulai baca</h3><p>Petugas mencatat pinjamanmu. Pantau batas kembali dari dashboard siswa.</p></article>
            </div>
            <div class="home-rules"><div><x-icon name="clock" :size="22"/><span>Masa pinjam default<strong>{{ config('library.loan_days') }} hari</strong></span></div><div><x-icon name="book" :size="22"/><span>Pinjaman aktif maksimal<strong>{{ config('library.max_active_loans') }} eksemplar</strong></span></div><div><x-icon name="help" :size="22"/><span>Denda terlambat per buku / hari<strong>Rp{{ number_format(config('library.fine_daily_rate'), 0, ',', '.') }}</strong></span></div></div>
            <p class="home-rules-note">Ikuti tanggal kembali yang dicatat petugas. Buku hilang cukup mengganti harga buku, tanpa tambahan denda keterlambatan.</p>
        </div>
    </section>
    <section class="home-cta home-container" aria-labelledby="cta-heading"><div><p class="section-eyebrow">HALAMAN BARU MENANTIMU</p><h2 id="cta-heading">Sudah tahu ingin baca apa?</h2><p>Mulai perjalanan membacamu bersama Ruang Pustaka.</p></div><a class="home-button home-button-primary" href="{{ auth()->check() ? route('dashboard') : route('register') }}">{{ auth()->check() ? 'Buka dashboard' : 'Daftar sekarang' }} <x-icon name="arrow"/></a></section>
</main>
<footer class="home-footer home-container"><a href="{{ route('home') }}" class="home-brand"><x-library-logo/>Ruang Pustaka.</a><p>Satu ruang untuk membuka banyak wawasan.</p><a href="#main-content">Kembali ke atas ↑</a></footer>
</body>
</html>
