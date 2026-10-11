<article class="student-book-card" data-book-reveal>
    <a href="{{ route('catalog.show', $book) }}" class="student-book-cover" aria-label="Detail buku {{ $book->title }}">
        @include('books.cover', ['coverBook' => $book, 'large' => true])
        @if ($rank ?? null)<span class="student-book-rank">#{{ $rank }} Populer</span>@endif
        <span class="student-cover-arrow" aria-hidden="true">↗</span>
    </a>
    <div class="student-book-content">
        <p class="student-book-category">{{ $book->category->name }}</p>
        <h3><a href="{{ route('catalog.show', $book) }}">{{ $book->title }}</a></h3>
        <p class="student-book-author">{{ $book->author }}</p>
        @if ($rank ?? null)<p class="student-book-popularity">{{ number_format($book->loan_items_count, 0, ',', '.') }} kali dipinjam</p>@endif
        <p class="student-book-stock {{ $book->available_count ? 'has-stock' : '' }}">{{ $book->available_count ? $book->available_count.' eksemplar tersedia' : 'Stok fisik belum tersedia' }}</p>
        @if ($book->source_url)<a class="student-digital-link" href="{{ $book->source_url }}" target="_blank" rel="noopener noreferrer">{{ $book->is_sibi_reference ? 'Baca di SIBI' : 'Sumber katalog' }} ↗</a>@endif
        <div class="student-book-actions">
            <a href="{{ route('catalog.show', $book) }}" class="student-detail-link">Detail <span aria-hidden="true">→</span></a>
            @include('shared.favorite-button', ['favoriteBook' => $book, 'returnTo' => $returnTo ?? 'dashboard'])
        </div>
    </div>
</article>
