@extends('layouts.app')
@section('title', 'Buku Favorit | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <div class="student-section-heading"><div><p class="page-eyebrow">RAK PRIBADIMU</p><h1>Buku Favorit</h1><p>{{ $books->total() }} buku tersimpan untuk dibaca nanti.</p></div><a href="{{ route('catalog.index') }}" class="btn btn-primary">Cari Buku <x-icon name="search" :size="16"/></a></div>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <div class="student-books-grid">
        @forelse ($books as $book)
            @include('dashboard.book-card', ['book' => $book, 'rank' => null, 'returnTo' => 'favorites'])
        @empty
            <div class="student-section-empty"><x-icon name="heart" :size="34"/><h2>Mulai isi rak favoritmu.</h2><p>Tekan tombol Favorit pada buku di dashboard, katalog, atau detail buku untuk menyimpannya di sini.</p><a href="{{ route('catalog.index') }}" class="btn btn-primary">Jelajahi Katalog</a></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $books->links('pagination::bootstrap-5') }}</div>
    <p class="small text-secondary mt-3">Buku yang diarsipkan petugas tidak ditampilkan. Menyimpan favorit tidak memesan atau meminjam eksemplar.</p>
</main>
@endsection
