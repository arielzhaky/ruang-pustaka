@extends('layouts.app')

@section('title', 'Eksemplar Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold">Eksemplar Buku</h1>
            <p class="text-secondary mb-0">{{ $book->title }}</p>
        </div>

        <a href="{{ route('books.index') }}" class="btn btn-outline-primary">
            Kembali ke Daftar Buku
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <h2 class="h5 mb-3">Tambah Eksemplar</h2>

        <form action="{{ route('copies.store', $book) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="inventory_code" class="form-label">
                    Kode inventaris
                </label>
                <input type="text"
                       id="inventory_code"
                       name="inventory_code"
                       class="form-control"
                       value="{{ old('inventory_code') }}"
                       placeholder="Contoh: RP-0001"
                       maxlength="50"
                       required>
                <div class="form-text">
                    Setiap buku fisik harus memiliki kode yang berbeda.
                </div>
            </div>

            <div class="mb-3">
                <label for="shelf_location" class="form-label">
                    Lokasi rak (opsional)
                </label>
                <input type="text"
                       id="shelf_location"
                       name="shelf_location"
                       class="form-control"
                       value="{{ old('shelf_location') }}"
                       placeholder="Contoh: Rak A-1"
                       maxlength="100">
            </div>

            <div class="mb-4">
                <label for="condition" class="form-label">Kondisi buku</label>
                <th scope="col">Aksi</th>
                <select id="condition" name="condition"
                        class="form-select" required>
                    <option value="baik"
                        @selected(old('condition', 'baik') === 'baik')>
                        Baik
                    </option>
                    <option value="rusak"
                        @selected(old('condition') === 'rusak')>
                        Rusak
                    </option>
                    <option value="hilang"
                        @selected(old('condition') === 'hilang')>
                        Hilang
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Tambah Eksemplar
            </button>
        </form>
    </section>

    <section class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="h5 mb-3">Daftar Eksemplar</h2>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Kode inventaris</th>
                        <th scope="col">Lokasi rak</th>
                        <th scope="col">Kondisi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($copies as $copy)
                        <tr>
                            <td>{{ $copies->firstItem() + $loop->index }}</td>
                            <td>{{ $copy->inventory_code }}</td>
                            <td>{{ $copy->shelf_location ?? '—' }}</td>
                            <td>{{ ucfirst($copy->condition) }}</td>
                            <td> 
                                <a href="{{ route('copies.edit', ['book' => $book, 'copy' => $copy]) }}"
                                class="btn btn=sm btn-outline-primary">
                                Ubah
                            </a>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                Belum ada eksemplar untuk judul ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $copies->links('pagination::bootstrap-5') }}
    </section>
</main>
@endsection