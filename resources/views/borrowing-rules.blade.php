@extends('layouts.app')
@section('title', 'Panduan Peminjaman | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <p class="page-eyebrow">PERPUSTAKAAN / PANDUAN</p>
    <h1>Panduan Peminjaman</h1>
    <p class="page-intro mb-4">Kenali aturan peminjaman agar membaca tetap nyaman untuk semua.</p>
    @include('shared.borrowing-rules', ['fullRules' => true])
    <div class="row g-4 mt-1">
        <div class="col-md-6"><section class="bg-white p-4 h-100">
            <h2>Sebelum meminjam</h2>
            <p class="text-secondary mb-0">Buka detail buku untuk memeriksa cover, deskripsi, ketersediaan, dan lokasi rak. Siapkan akun atau kode anggota ketika menemui petugas.</p>
        </section></div>
        <div class="col-md-6"><section class="bg-white p-4 h-100">
            <h2>Saat mengembalikan</h2>
            <p class="text-secondary mb-0">Serahkan buku kepada petugas dan pastikan pengembaliannya sudah tercatat. Nominal denda berhenti bertambah setelah pinjaman ditutup; pencatatan nominal belum berarti pembayaran sudah diterima.</p>
        </section></div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-4">
        @if (auth()->user()->role === 'petugas')
            <a class="btn btn-primary" href="{{ route('loans.create') }}">Catat Peminjaman <x-icon name="arrow" :size="16"/></a>
            <a class="btn btn-outline-primary" href="{{ route('books.index') }}">Lihat Buku</a>
        @else
            <a class="btn btn-primary" href="{{ route('catalog.index') }}">Jelajahi Katalog <x-icon name="arrow" :size="16"/></a>
            <a class="btn btn-outline-primary" href="{{ route('my-loans.index') }}">Peminjaman Saya</a>
        @endif
    </div>
</main>
@endsection
