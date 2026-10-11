@extends('layouts.app')

@section('title', 'Ubah Eksemplar | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold">Ubah Eksemplar</h1>
            <p class="text-secondary mb-0">{{ $book->title }}</p>
        </div>

        <a href="{{ route('copies.index', $book) }}"
           class="btn btn-outline-primary">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="bg-white rounded-4 shadow-sm p-4">
        <form action="{{ route('copies.update', ['book' => $book, 'copy' => $copy]) }}"
              method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="inventory_code" class="form-label">
                    Kode inventaris
                </label>
                <input type="text"
                       id="inventory_code"
                       name="inventory_code"
                       class="form-control"
                       value="{{ old('inventory_code', $copy->inventory_code) }}"
                       maxlength="50"
                       required>
            </div>

            <div class="mb-3">
                <label for="shelf_location" class="form-label">
                    Lokasi rak (opsional)
                </label>
                <input type="text"
                       id="shelf_location"
                       name="shelf_location"
                       class="form-control"
                       value="{{ old('shelf_location', $copy->shelf_location) }}"
                       maxlength="100">
            </div>

            <div class="mb-4">
                <label for="condition" class="form-label">Kondisi buku</label>
                <select id="condition" name="condition"
                        class="form-select" required>
                    @foreach (['baik' => 'Baik', 'rusak' => 'Rusak', 'hilang' => 'Hilang'] as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('condition', $copy->condition) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="import_unavailable" class="form-label">Ketersediaan dari data sekolah</label>
                <select id="import_unavailable" name="import_unavailable" class="form-select">
                    <option value="0" @selected(!old('import_unavailable', $copy->import_unavailable))>Sudah diperiksa, boleh dipinjam</option>
                    <option value="1" @selected(old('import_unavailable', $copy->import_unavailable))>Belum tersedia menurut data impor</option>
                </select>
                <div class="form-text">Periksa keberadaan buku sebelum mengubah menjadi tersedia. Riwayat peminjaman tetap berlaku.</div>
            </div>
            <button type="submit" class="btn btn-primary">
                Simpan Perubahan
            </button>
        </form>
    </section>
</main>
@endsection