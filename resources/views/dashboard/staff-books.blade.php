<section class="bg-white p-4" aria-labelledby="collection-heading">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <h2 class="h5 fw-bold" id="collection-heading">Koleksi Buku Terbaru</h2>
        <a href="{{ route('books.index') }}">Lihat Semua Buku</a>
    </div>
    <form action="{{ route('books.index') }}" method="GET" class="d-flex gap-2 mb-4">
        <label for="dashboard-book-search" class="visually-hidden">Cari judul, penulis, atau ISBN</label>
        <input id="dashboard-book-search" name="q" class="form-control" placeholder="Cari judul, penulis, atau ISBN" maxlength="100">
        <button class="btn btn-outline-primary">Cari</button>
    </form>
    <div class="row g-3">
        @forelse ($latestBooks as $book)
            <div class="col-md-6 col-xl-4">
                <article class="border rounded-3 p-3 h-100 d-flex gap-3">
                    <div class="flex-shrink-0">@include('books.cover', ['coverBook' => $book])</div>
                    <div class="text-break">
                        <h3 class="h6 fw-bold"><a href="{{ route('books.show', $book) }}">{{ $book->title }}</a></h3>
                        <p class="small text-secondary mb-1">{{ $book->author }} · {{ $book->category->name }}</p>
                        <p class="small mb-2 {{ $book->available_count ? 'text-success' : 'text-secondary' }}">{{ $book->available_count }} eksemplar tersedia</p>
                        <a href="{{ route('books.show', $book) }}" class="small">Detail Buku →</a>
                        <a href="{{ route('books.edit', $book) }}" class="small ms-2">Ubah Buku</a>
                    </div>
                </article>
            </div>
        @empty
            <p class="text-secondary">Belum ada buku. <a href="{{ route('books.create') }}">Tambahkan buku pertama</a>.</p>
        @endforelse
    </div>
</section>
