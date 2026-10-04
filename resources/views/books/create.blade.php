@extends('layouts.app')

@section('title', 'Tambah Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 fw-bold mb-0">Tambah Buku</h1>

        <a href="{{ route('books.index') }}" class="btn btn-outline-primary">
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

    @if ($categories->isEmpty())
        <div class="alert alert-warning">
            Tambahkan kategori terlebih dahulu melalui
            <a href="{{ route('categories.index') }}">Kelola Kategori</a>.
        </div>
    @endif

    <section class="bg-white rounded-4 shadow-sm p-4">
        <p class="text-secondary">
            Kolom bertanda * wajib diisi. Isi data sesuai buku yang tersedia.
        </p>

        <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('books.price-field', ['priceBook' => null])
            @include('books.cover-field', ['coverBook' => null])

            <div class="row g-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Judul buku *</label>
                    <input
                        type="text" id="title" name="title"
                        class="form-control"
                        value="{{ old('title') }}"
                        maxlength="255" required
                    >
                </div>

                <div class="col-md-4">
                    <label for="category_id" class="form-label">Kategori *</label>
                    <select id="category_id" name="category_id"
                            class="form-select" required>
                        <option value="">Pilih kategori</option>

                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(old('category_id') == $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="author" class="form-label">Penulis *</label>
                    <input
                        type="text" id="author" name="author"
                        class="form-control"
                        value="{{ old('author') }}"
                        maxlength="255" required
                    >
                </div>

                <div class="col-md-6">
                    <label for="isbn" class="form-label">ISBN</label>
                    <input
                        type="text" id="isbn" name="isbn"
                        class="form-control"
                        value="{{ old('isbn') }}"
                        maxlength="20"
                        aria-describedby="isbn-help"
                    >
                    <div id="isbn-help" class="form-text">
                        Boleh kosong jika buku tidak memiliki ISBN.
                    </div>
                </div>

                <div class="col-md-8">
                    <label for="publisher" class="form-label">Penerbit</label>
                    <input
                        type="text" id="publisher" name="publisher"
                        class="form-control"
                        value="{{ old('publisher') }}"
                        maxlength="255"
                    >
                </div>

                <div class="col-md-4">
                    <label for="publication_year" class="form-label">
                        Tahun terbit
                    </label>
                    <input
                        type="number" id="publication_year"
                        name="publication_year"
                        class="form-control"
                        value="{{ old('publication_year') }}"
                        min="1" max="{{ date('Y') }}"
                    >
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea
                        id="description" name="description"
                        class="form-control"
                        rows="4" maxlength="5000"
                    >{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"
                        @disabled($categories->isEmpty())>
                    Simpan Buku
                </button>

                <a href="{{ route('books.index') }}" class="btn btn-outline-secondary">
                    Batal
                </a>
            </div>
        </form>
    </section>
</main>
@endsection