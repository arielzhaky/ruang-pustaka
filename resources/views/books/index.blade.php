@extends('layouts.app')

@section('title', 'Data Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Data Buku</h1>
            <p class="page-intro">Kelola koleksi, cover, harga penggantian, dan ketersediaan buku.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                Dashboard
            </a>

            <a href="{{ route('books.create') }}" class="btn btn-primary">
                Tambah Buku
            </a>
        </div>
    </div>
    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul></div>
    @endif
    @include('shared.csv-transfer', ['kind' => 'books'])

    <section class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="h5">Daftar Judul Buku</h2>
        <form method="GET" action="{{ route('books.index') }}" class="row g-2 align-items-end my-3">
            <div class="col-md-4"><label for="q" class="form-label">Judul, penulis, atau ISBN</label><input id="q" name="q" class="form-control" maxlength="100" value="{{ $filters['q'] ?? '' }}"></div>
            <div class="col-md-3"><label for="category_id" class="form-label">Kategori</label><select name="category_id" id="category_id" class="form-select">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach
            </select></div>
            <div class="col-md-3"><label for="availability" class="form-label">Ketersediaan</label><select name="availability" id="availability" class="form-select">
                @foreach (['all' => 'Semua stok', 'available' => 'Tersedia', 'unavailable' => 'Tidak tersedia'] as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['availability'] ?? 'all') === $key)>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="col-md-2 d-flex gap-2 flex-wrap"><button class="btn btn-primary">Cari</button><a href="{{ route('books.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Judul</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Penulis</th>
                        <th scope="col">ISBN</th>
                        <th scope="col">Eksemplar tercatat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
    @forelse ($books as $book)
        <tr>
            <td>{{ $books->firstItem() + $loop->index }}</td>
            <td><div class="d-flex align-items-center gap-2">@include('books.cover', ['coverBook' => $book])<a class="fw-semibold" href="{{ route('books.show', array_merge(['book' => $book->id], request()->only(['q', 'category_id', 'availability', 'page']))) }}">{{ $book->title }}</a></div></td>
            <td>{{ $book->category->name }}</td>
            <td>{{ $book->author }}</td>
            <td>{{ $book->isbn ?? '—' }}</td>
            <td>{{ $book->copies_count }} tercatat<br><small class="text-success">{{ $book->available_count }} tersedia</small></td>
            <td>
                <div class="d-flex gap-2">
                    <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    <a href="{{ route('copies.index', $book) }}
                    "class="btn btn-sm btn-outline-secondary">
    Eksemplar
</a>
                    <a href="{{ route('books.edit', $book) }}"
                    class="btn btn-sm btn-outline-primary">
                        Ubah
                    </a>

                    <form action="{{ route('books.destroy', $book) }}" method="POST"onsubmit="return confirm('Yakin ingin menghapus buku ini?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            Hapus
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center text-secondary py-4">
                Tidak ada buku yang sesuai. Ubah filter atau tambahkan buku baru.
            </td>
        </tr>
    @endforelse
</tbody>

            </table>
        </div>

        {{ $books->links('pagination::bootstrap-5') }}
    </section>
</main>
@endsection