@extends('layouts.app')

@section('title', 'Katalog Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-1">Katalog Buku</h1>
            <p class="text-secondary mb-0">Temukan bacaan dan lihat ketersediaannya di perpustakaan.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">Dashboard</a>
    </div>

    <section class="bg-white rounded-4 shadow-sm p-3 p-md-4" aria-label="Pencarian dan daftar buku">
        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('catalog.index') }}" method="GET" class="row g-3 align-items-end mb-4" role="search">
            <div class="col-12 col-md-6">
                <label for="q" class="form-label">Cari judul atau penulis</label>
                <input id="q" name="q" type="search" maxlength="100" value="{{ $search }}"
                       class="form-control" placeholder="Contoh: Malin Kundang">
            </div>
            <div class="col-12 col-md-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select id="category_id" name="category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <p class="text-secondary small" role="status">{{ $books->total() }} judul ditemukan.</p>
        <div class="table-responsive" role="region" aria-label="Daftar buku" tabindex="0">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Cover</th>
                        <th scope="col">Judul</th>
                        <th scope="col">Penulis</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tersedia</th>
                        <th scope="col">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($books as $book)
                        <tr>
                            <td>{{ $books->firstItem() + $loop->index }}</td>
                            <td>@include('books.cover', ['coverBook' => $book, 'large' => false])</td>
                            <th scope="row" class="fw-normal">
                                <a href="{{ route('catalog.show', array_merge(['book' => $book->id], request()->only(['q', 'category_id', 'page']))) }}"
                                   class="fw-semibold">{{ $book->title }}</a>
                                @if ($book->source_url)<span class="d-block small text-secondary mt-1">Referensi digital · SIBI</span>@endif
                            </th>
                            <td>{{ $book->author }}</td>
                            <td>{{ $book->category->name }}</td>
                            <td>
                                @if ($book->available_count > 0)
                                    <span class="badge text-bg-success">{{ $book->available_count }} eksemplar</span>
                                @else
                                    <span class="badge text-bg-secondary">Tidak tersedia</span>
                                @endif
                            </td>
                            <td><div class="catalog-book-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('catalog.show', array_merge(['book' => $book->id], request()->only(['q', 'category_id', 'page']))) }}">Detail Buku</a>@include('shared.favorite-button', ['favoriteBook' => $book, 'returnTo' => 'catalog'])</div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                @if ($search !== '' || $categoryId)
                                    Tidak ada buku yang cocok. Coba kata pencarian atau kategori lain.
                                @else
                                    Belum ada buku dalam katalog.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $books->links('pagination::bootstrap-5') }}
        <p class="small text-secondary mb-0">Klik judul buku untuk melihat informasi lengkap dan lokasi raknya.</p>
    </section>
    <div class="mt-4">@include('shared.borrowing-rules')</div>
</main>
@endsection
