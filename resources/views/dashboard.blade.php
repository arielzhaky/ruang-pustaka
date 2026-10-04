@extends('layouts.app')
@section('title', 'Dashboard | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <header class="dashboard-welcome mb-4">
        <div>
            <p class="page-eyebrow">{{ config('library.school_name') }} / DASHBOARD</p>
            <h1>Halo, {{ auth()->user()->name }}!</h1>
            <p class="page-intro">{{ auth()->user()->role === 'petugas' ? 'Pantau koleksi, siswa, dan aktivitas perpustakaan dalam satu ruang.' : 'Bacaan berikutnya menantimu. Pantau buku dan jadwal pengembalianmu.' }}</p>
        </div>
        @if (auth()->user()->role === 'petugas')
            <a href="{{ route('loans.create') }}" class="btn welcome-action">Catat Peminjaman <x-icon name="arrow" :size="16"/></a>
        @else
            <a href="{{ route('catalog.index') }}" class="btn welcome-action">Cari Buku di Katalog <x-icon name="arrow" :size="16"/></a>
        @endif
    </header>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if (auth()->user()->role === 'petugas')
        <h2 class="visually-hidden">Dashboard Petugas</h2>
        <div class="dashboard-stats">
            @foreach ([
                [$jumlahJudul, 'Judul buku', 'Koleksi yang belum diarsipkan', 'violet', 'book'],
                [$jumlahSiswa, 'Siswa aktif', 'Anggota yang dapat meminjam', 'blue', 'users'],
                [$tersedia, 'Eksemplar tersedia', 'Siap untuk dipinjam', 'mint', 'book'],
                [$sedangDipinjam, 'Sedang dipinjam', 'Termasuk yang terlambat', 'violet', 'loan'],
                [$terlambat, 'Terlambat', 'Perlu segera dikembalikan', 'rose', 'clock'],
                [$sudahSelesai, 'Transaksi eksemplar selesai', 'Pengembalian dan kehilangan', 'mint', 'report'],
            ] as [$number, $label, $hint, $tone, $icon])
                <div class="dashboard-stat stat-tone-{{ $tone }}">
                    <div class="stat-heading"><span>{{ $label }}</span><span class="stat-icon"><x-icon :name="$icon" :size="19"/></span></div>
                    <strong>{{ $number }}</strong><small>{{ $hint }}</small>
                </div>
            @endforeach
        </div>
        @include('dashboard.services')
        @include('dashboard.staff-books')
        <div class="mt-4">@include('shared.borrowing-rules')</div>
    @else
        @include('dashboard.student')
        <div class="mt-4">@include('shared.borrowing-rules')</div>
    @endif
</main>
@endsection
