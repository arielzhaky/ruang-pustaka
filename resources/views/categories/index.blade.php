@extends('layouts.app')

@section('title', 'Kategori Buku | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Kategori Buku</h1>
            <p class="page-intro">Kelompokkan koleksi agar bacaan lebih mudah ditemukan.</p>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
            Kembali ke Dashboard
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <p class="mb-0 mt-2">Periksa nama kategori, lalu coba kembali.</p>
        </div>
    @endif

    <section class="bg-white rounded-4 shadow-sm p-4 mb-4"
             aria-labelledby="add-heading">
        <h2 id="add-heading" class="h5 mb-3">Tambah Kategori</h2>

        <form action="{{ route('categories.store') }}" method="POST">
            @csrf

            <div class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label for="new-name" class="form-label">
                        Nama kategori
                    </label>

                    <input
                        type="text"
                        id="new-name"
                        name="name"
                        class="form-control"
                        maxlength="100"
                        placeholder="Contoh: Buku Pelajaran"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        Tambah
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="bg-white rounded-4 shadow-sm p-4"
             aria-labelledby="list-heading">
        <h2 id="list-heading" class="h5 mb-2">Daftar Kategori</h2>

        <p id="category-help" class="text-secondary small">
            Ubah nama lalu klik Simpan. Kategori hanya bisa dihapus
            jika belum digunakan oleh buku.
        </p>

        <div class="table-responsive">
            <table class="table align-middle" aria-describedby="category-help">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Nama kategori</th>
                        <th scope="col">Jumlah judul</th>
                        <th scope="col">Hapus</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td style="min-width: 280px">
                                <form
                                    action="{{ route('categories.update', $category) }}"
                                    method="POST"
                                    class="d-flex gap-2"
                                >
                                    @csrf
                                    @method('PUT')

                                    <label for="category-{{ $category->id }}"
                                           class="visually-hidden">
                                        Nama kategori {{ $category->name }}
                                    </label>

                                    <input
                                        type="text"
                                        id="category-{{ $category->id }}"
                                        name="name"
                                        class="form-control"
                                        value="{{ $category->name }}"
                                        maxlength="100"
                                        required
                                    >

                                    <button type="submit"
                                            class="btn btn-outline-primary btn-sm">
                                        Simpan
                                    </button>
                                </form>
                            </td>

                            <td>{{ $category->books_count }}</td>

                            <td>
                                @if ($category->books_count > 0)
                                    <span class="badge text-bg-secondary">
                                        Digunakan
                                    </span>
                                @else
                                    <form
                                        action="{{ route('categories.destroy', $category) }}"
                                        method="POST"
                                        onsubmit="return confirm('Hapus kategori ini?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                            aria-label="Hapus kategori {{ $category->name }}"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                Belum ada kategori buku.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection