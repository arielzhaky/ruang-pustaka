<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Ruang Pustaka')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/perpustakaan-smpn26-web.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/workspace.css') }}" rel="stylesheet">
    @if (auth()->check() && auth()->user()->role === 'anggota')
        <link href="{{ asset('css/student-dashboard.css') }}" rel="stylesheet">
    @endif
    @stack('styles')
    <link href="{{ asset('css/brand.css') }}?v={{ filemtime(public_path('css/brand.css')) }}" rel="stylesheet">
</head>
<body class="{{ auth()->check() ? 'workspace' : (request()->routeIs('register*') ? 'registration-page' : '') }}">
@auth
    @php
        $isStaff = auth()->user()->role === 'petugas';
        $navigation = $isStaff ? [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'patterns' => ['dashboard'], 'icon' => 'dashboard'],
            ['label' => 'Daftar Pengunjung', 'route' => 'visits.index', 'patterns' => ['visits.*'], 'icon' => 'users'],
            ['label' => 'Antrean Cetak', 'route' => 'print-queue.index', 'patterns' => ['print-queue.*'], 'icon' => 'print'],
            ['label' => 'Scan Barcode', 'route' => 'barcodes.index', 'patterns' => ['barcodes.*'], 'icon' => 'barcode'],
            ['label' => 'Data Buku', 'route' => 'books.index', 'patterns' => ['books.*', 'copies.*'], 'icon' => 'book'],
            ['label' => 'Kategori Buku', 'route' => 'categories.index', 'patterns' => ['categories.*'], 'icon' => 'category'],
            ['label' => 'Kelola Siswa', 'route' => 'members.index', 'patterns' => ['members.*'], 'icon' => 'users'],
            ['label' => 'Data Kelas', 'route' => 'school-classes.index', 'patterns' => ['school-classes.*'], 'icon' => 'class'],
            ['label' => 'Peminjaman', 'route' => 'loans.index', 'patterns' => ['loans.*'], 'icon' => 'loan'],
            ['label' => 'Siswa Terlambat', 'route' => 'overdue.index', 'patterns' => ['overdue.*'], 'icon' => 'clock'],
            ['label' => 'Perpanjangan', 'route' => 'renewals.index', 'patterns' => ['renewals.*'], 'icon' => 'loan'],
            ['label' => 'Reservasi Buku', 'route' => 'reservations.index', 'patterns' => ['reservations.*'], 'icon' => 'book'],
            ['label' => 'Tagihan & Pembayaran', 'route' => 'billing.index', 'patterns' => ['billing.*'], 'icon' => 'card'],
            ['label' => 'Aktivitas Petugas', 'route' => 'activity.index', 'patterns' => ['activity.*'], 'icon' => 'report'],
            ['label' => 'Laporan', 'route' => 'reports.loans', 'patterns' => ['reports.*'], 'icon' => 'report'],
        ] : [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'patterns' => ['dashboard'], 'icon' => 'dashboard'],
            ['label' => 'Katalog Buku', 'route' => 'catalog.index', 'patterns' => ['catalog.*'], 'icon' => 'book'],
            ['label' => 'Buku Favorit', 'route' => 'favorites.index', 'patterns' => ['favorites.*'], 'icon' => 'heart'],
            ['label' => 'Kartu Anggota', 'route' => 'member-card', 'patterns' => ['member-card'], 'icon' => 'card'],
            ['label' => 'Pengingat', 'route' => 'reminders.index', 'patterns' => ['reminders.*'], 'icon' => 'clock'],
            ['label' => 'Perpanjangan', 'route' => 'renewals.index', 'patterns' => ['renewals.*'], 'icon' => 'loan'],
            ['label' => 'Reservasi Buku', 'route' => 'reservations.index', 'patterns' => ['reservations.*'], 'icon' => 'book'],
            ['label' => 'Tagihan Saya', 'route' => 'billing.index', 'patterns' => ['billing.*'], 'icon' => 'card'],
            ['label' => 'Peminjaman Saya', 'route' => 'my-loans.index', 'patterns' => ['my-loans.*'], 'icon' => 'loan'],
        ];
        $pageLabel = html_entity_decode(trim(explode('|', $__env->yieldContent('title', 'Ruang Pustaka'))[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    @endphp
    <a class="skip-link" href="#workspace-content">Lewati ke isi halaman</a>
    <aside class="workspace-sidebar" aria-label="Navigasi utama">
        <a href="{{ route('dashboard') }}" class="workspace-brand"><x-library-logo/><span>Ruang Pustaka.</span></a>
        <div class="workspace-identity"><x-icon name="class"/><div><strong>{{ config('library.school_name') }}</strong><small>{{ $isStaff ? 'Ruang pengelolaan' : 'Ruang baca siswa' }}</small></div></div>
        @include('layouts.partials.navigation')
    </aside>
    <div class="workspace-main">
        <header class="workspace-topbar">
            <details class="mobile-navigation">
                <summary><x-icon name="menu"/><span>Menu</span></summary>
                <div class="mobile-navigation-panel">@include('layouts.partials.navigation')</div>
            </details>
            <div class="workspace-breadcrumb"><a href="{{ route('dashboard') }}">Perpustakaan</a><span aria-hidden="true">›</span><span>{{ $pageLabel }}</span></div>
            <span class="workspace-role">{{ $isStaff ? 'PETUGAS / ADMIN' : 'SISWA' }}</span>
        </header>
        <div id="workspace-content" class="workspace-content" tabindex="-1">@yield('content')</div>
        <footer class="workspace-footer"><span>Ruang Pustaka · Satu ruang, banyak wawasan.</span><span>Waktu Indonesia Barat · Asia/Jakarta</span></footer>
    </div>
    <script src="{{ asset('js/workspace.js') }}" defer></script>
    @if (! $isStaff)<script src="{{ asset('js/student-books.js') }}" defer></script>@endif
@else
    @yield('content')
@endauth
    @stack('scripts')
</body>
</html>
