@extends('layouts.app')

@section('title', 'Eksemplar Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold">Stok / Eksemplar Buku</h1>
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

    <section class="bg-white rounded-4 shadow-sm p-4 mb-4" aria-labelledby="stock-heading">
        <h2 id="stock-heading" class="h5">Stok tersedia: {{ $availableCount }}</h2>
        <p class="text-secondary">Stok tersedia adalah eksemplar baik yang siap dipinjam. Angka 0 membuat buku tidak tersedia tanpa menghapus judul atau riwayatnya.</p>
        <div class="row g-4">
            <form action="{{ route('copies.stock', $book) }}" method="POST" class="col-md-6">
                @csrf @method('PATCH')
                <input type="hidden" name="operation" value="add">
                <label for="add-quantity" class="form-label">Tambah stok baru</label>
                <input id="add-quantity" name="quantity" type="number" min="1" max="1000" value="{{ old('operation') === 'add' ? old('quantity', 1) : 1 }}" class="form-control mb-2" required>
                <label for="stock-shelf" class="form-label">Lokasi rak baru (opsional)</label>
                <input id="stock-shelf" name="shelf_location" maxlength="100" value="{{ old('shelf_location') }}" class="form-control mb-2">
                <p class="form-text">Setiap tambahan mendapat kode inventaris otomatis dan kondisi baik.</p>
                <button class="btn btn-primary">Tambah Stok</button>
            </form>
            <form action="{{ route('copies.stock', $book) }}" method="POST" class="col-md-6" onsubmit="return confirm('Sesuaikan stok tersedia? Eksemplar yang dikurangi dinonaktifkan; riwayat tetap tersimpan.')">
                @csrf @method('PATCH')
                <input type="hidden" name="operation" value="set">
                <label for="set-quantity" class="form-label">Atur stok tersedia menjadi</label>
                <input id="set-quantity" name="quantity" type="number" min="0" max="1000" value="{{ old('operation') === 'set' ? old('quantity', $availableCount) : $availableCount }}" class="form-control mb-2" required aria-describedby="stock-help">
                <p id="stock-help" class="form-text">Isi 0 untuk tidak tersedia. Penambahan mengaktifkan kembali eksemplar nonaktif yang baik, lalu membuat eksemplar baru bila perlu. Eksemplar dipinjam atau ditahan reservasi tidak diubah; setelah dikembalikan/dilepas, stok dapat bertambah lagi.</p>
                <button class="btn btn-outline-primary">Simpan Stok</button>
            </form>
        </div>
    </section>

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
<div class="d-flex flex-wrap justify-content-between gap-3 mb-3"><h2 class="h5 mb-0">Daftar Eksemplar</h2><a class="btn btn-outline-primary" href="{{ route('print-queue.index', ['type'=>'copies', 'q'=>$book->title]) }}">Pilih beberapa label / Download PDF</a><a href="{{ route('barcodes.copies.print', $book) }}" class="btn btn-outline-primary">Cetak Barcode Eksemplar Aktif</a></div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Kode inventaris</th>
                        <th scope="col">Lokasi rak</th>
                        <th scope="col">Kondisi</th>
                        <th scope="col">Status stok</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($copies as $copy)
                        <tr>
                            <td>{{ $copies->firstItem() + $loop->index }}</td>
                            <td>{{ $copy->inventory_code }}</td>
                            <td>{{ $copy->shelf_location ?? '—' }}</td>
                            <td>{{ ucfirst($copy->condition) }}@if($copy->import_unavailable)<br><small class="text-secondary">Belum tersedia (data impor)</small>@endif</td>
                            <td>{{ $copy->archived_at ? 'Nonaktif' : 'Aktif' }}</td>
                            <td>
                                <a href="{{ route('barcodes.copies.print', ['book'=>$book, 'copy_id'=>$copy->id]) }}" class="btn btn-sm btn-outline-primary">Label Barcode</a>
                                @unless ($copy->archived_at)
                                <a href="{{ route('copies.edit', ['book' => $book, 'copy' => $copy]) }}"
                                class="btn btn-sm btn-outline-primary">
                                Ubah
                            </a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
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
