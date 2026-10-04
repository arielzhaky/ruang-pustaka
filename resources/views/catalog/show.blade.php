@extends('layouts.app')
@section('title', $book->title . ' | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <p class="page-eyebrow mb-0">PERPUSTAKAAN / DETAIL BUKU</p>
        <nav class="d-flex flex-wrap gap-2" aria-label="Navigasi detail buku">
            <a href="{{ route($staffView ? 'books.index' : 'catalog.index', \Illuminate\Support\Arr::only($filters, $staffView ? ['q', 'category_id', 'availability', 'page'] : ['q', 'category_id', 'page'])) }}"
               class="btn btn-outline-primary">{{ $staffView ? 'Kembali ke Data Buku' : 'Kembali ke Katalog' }}</a>
            @if ($staffView)
                <a href="{{ route('books.edit', $book) }}" class="btn btn-primary">Ubah Buku</a>
            @endif
        </nav>
    </div>
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="book-detail-grid mb-4">
        <article class="bg-white p-4">
            <div class="book-hero">
                <div class="book-cover-stage">@include('books.cover', ['coverBook' => $book, 'large' => true])</div>
                <div>
                    <span class="badge text-bg-success mb-3">{{ $book->category->name }}</span>
                    <h1 class="text-break mb-2">{{ $book->title }}</h1>
                    <p class="text-secondary text-break mb-0">Oleh {{ $book->author }}</p>
                    @unless ($staffView)<div class="mt-3">@include('shared.favorite-button', ['favoriteBook' => $book, 'returnTo' => 'detail'])</div>@endunless
                    <dl class="book-metadata">
                        <div><dt>ISBN</dt><dd>{{ $book->isbn ?: 'Belum dicantumkan' }}</dd></div>
                        <div><dt>Penerbit</dt><dd>{{ $book->publisher ?: 'Belum dicantumkan' }}</dd></div>
                        <div><dt>Tahun terbit</dt><dd>{{ $book->publication_year ?: 'Belum dicantumkan' }}</dd></div>
                        <div><dt>Penggantian jika hilang</dt><dd>{{ $book->replacement_price ? 'Rp'.number_format($book->replacement_price, 0, ',', '.') : 'Hubungi petugas untuk informasi harga' }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="border-top mt-4 pt-4">
                <h2>Tentang Buku</h2>
                <p class="text-secondary text-break mb-0" style="white-space: pre-line; line-height: 1.9">{{ $book->description ?: 'Deskripsi buku belum tersedia.' }}</p>
            </div>
            @if ($book->source_url)
                <div class="reference-book-note mt-4">
                    <div><strong>Koleksi referensi digital</strong><p class="small mb-0">Sumber: SIBI · Pusat Perbukuan Kemendikdasmen. Baca melalui situs resmi; stok fisik dicatat terpisah oleh petugas.</p></div>
                    <a href="{{ $book->source_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Baca di SIBI ↗</a>
                </div>
            @endif
        </article>
        <aside class="book-availability" aria-labelledby="availability-heading">
            <p class="page-eyebrow">SIAP UNTUK DIBACA</p>
            <h2 id="availability-heading">Ketersediaan Buku</h2>
            <strong>{{ $availableCount }}</strong>
            <p class="mb-2">eksemplar tersedia</p>
            <p class="small text-secondary">Dari {{ $copies->total() }} eksemplar yang belum diarsipkan.</p>
            <hr class="my-4">
            @if ($availableCount > 0)
                <span class="badge text-bg-success mb-3">Tersedia untuk dipinjam</span>
                <p class="small text-secondary">Catat lokasi rak di bawah, lalu temui petugas untuk meminjam buku.</p>
            @else
                <span class="badge text-bg-secondary mb-3">Belum tersedia</span>
                <p class="small text-secondary">Buku ini belum tersedia untuk dipinjam. Hubungi petugas untuk informasi lebih lanjut.</p>
            @endif
            @if(! $staffView && $canReserve)
                <form method="POST" action="{{ route('reservations.store', $book) }}" class="mb-3">@csrf<button class="btn btn-primary w-100">Reservasi buku</button><p class="form-text">Ikuti antrean dan ambil setelah petugas menyiapkan. Pantau di menu Reservasi Buku.</p></form>
            @endif
            <a href="#copies-heading" class="btn btn-outline-primary w-100">Lihat Lokasi Rak <x-icon name="arrow" :size="16"/></a>
            @if ($staffView)<a href="{{ route('copies.index', $book) }}" class="btn btn-primary w-100 mt-2">Kelola Eksemplar</a>@endif
        </aside>
    </div>

    <section class="bg-white p-4 mb-4" aria-labelledby="copies-heading">
        <h2 id="copies-heading">Eksemplar dan Lokasi Rak</h2>
        <p class="text-secondary small">Ketersediaan dapat berubah ketika petugas mencatat peminjaman atau pengembalian.</p>
        <div class="table-responsive" role="region" aria-label="Daftar eksemplar buku" tabindex="0">
            <table class="table align-middle">
                <thead><tr><th scope="col">Kode inventaris</th><th scope="col">Lokasi rak</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @forelse ($copies as $copy)
                        <tr>
                            <th scope="row" class="fw-normal">{{ $copy->inventory_code }}</th>
                            <td>{{ $copy->shelf_location ?: 'Tanyakan kepada petugas' }}</td>
                            <td>
                                @if ($copy->condition === 'rusak')<span class="badge text-bg-warning">Rusak — tidak tersedia</span>
                                @elseif ($copy->condition === 'hilang')<span class="badge text-bg-secondary">Hilang — tidak tersedia</span>
                                @elseif ($copy->is_reserved)<span class="badge text-bg-warning">Disiapkan untuk reservasi</span>
                                @elseif ($copy->is_borrowed)<span class="badge text-bg-primary">Sedang dipinjam</span>
                                @else<span class="badge text-bg-success">Tersedia</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada eksemplar yang ditampilkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $copies->links('pagination::bootstrap-5') }}
    </section>
    @include('shared.borrowing-rules')
</main>
@endsection
