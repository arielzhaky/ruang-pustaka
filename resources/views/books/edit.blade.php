@extends('layouts.app')

@section('title', 'Ubah Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Ubah Buku</h1>
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

    <section class="bg-white rounded-4 shadow-sm p-4">
        <form action="{{ route('books.update', $book) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('books.price-field', ['priceBook' => $book])
            @include('books.cover-field', ['coverBook' => $book])
            @method('PUT')

            <div class="mb-3">
                <label for="title" class="form-label">Judul buku</label>
                <input type="text" id="title" name="title"
                       class="form-control" required maxlength="255"
                       value="{{ old('title', $book->title) }}">
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select id="category_id" name="category_id"
                        class="form-select" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected(old('category_id', $book->category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="author" class="form-label">Penulis</label>
                <input type="text" id="author" name="author"
                       class="form-control" required maxlength="255"
                       value="{{ old('author', $book->author) }}">
            </div>

            <div class="mb-3">
                <label for="isbn" class="form-label">ISBN (opsional)</label>
                <input type="text" id="isbn" name="isbn"
                       class="form-control" maxlength="20"
                       value="{{ old('isbn', $book->isbn) }}">
            </div>

            <div class="mb-3">
                <label for="publisher" class="form-label">Penerbit (opsional)</label>
                <input type="text" id="publisher" name="publisher"
                       class="form-control" maxlength="255"
                       value="{{ old('publisher', $book->publisher) }}">
            </div>

            <div class="mb-3">
                <label for="publication_year" class="form-label">
                    Tahun terbit (opsional)
                </label>
                <input type="number" id="publication_year" name="publication_year"
                       class="form-control" min="1" max="{{ date('Y') }}"
                       value="{{ old('publication_year', $book->publication_year) }}">
            </div>

            <div class="mb-4">
                <label for="description" class="form-label">Deskripsi (opsional)</label>
                <textarea id="description" name="description"
                          class="form-control" rows="4"
                          maxlength="5000">{{ old('description', $book->description) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                Simpan Perubahan
            </button>
        </form>
    </section>
</main>
@endsection