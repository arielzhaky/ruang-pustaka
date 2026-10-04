@php
    $detailLink = auth()->check()
        ? route(auth()->user()->role === 'petugas' ? 'books.show' : 'catalog.show', $book)
        : route('login');
@endphp
<article class="home-book" data-reveal>
    <a href="{{ $detailLink }}" class="home-book-cover cover-color-{{ $colorIndex % 4 }}" aria-label="{{ auth()->check() ? 'Detail buku ' : 'Masuk untuk melihat detail ' }}{{ $book->title }}">
        @if ($book->cover_path)
            <img src="{{ asset('storage/'.$book->cover_path) }}" alt="Cover {{ $book->title }}" loading="lazy" width="220" height="300">
        @else
            <div class="book-cover-placeholder"><x-icon name="book" :size="34"/><span>{{ $book->title }}</span><small>{{ $book->author }}</small><i aria-hidden="true"></i></div>
        @endif
        @if ($rank !== null)
            <span class="book-popular-rank"><span class="sr-only">Peringkat </span>#{{ $rank }} <span>Populer</span></span>
        @endif
        <span class="book-cover-arrow" aria-hidden="true">↗</span>
    </a>
    <div class="home-book-info">
        <span class="home-book-category">{{ $book->category->name }}</span>
        <h3><a href="{{ $detailLink }}">{{ $book->title }}</a></h3>
        <p>{{ $book->author }}</p>
        @if ($book->source_url)
            <a class="book-reference-link" href="{{ $book->source_url }}" target="_blank" rel="noopener noreferrer">Baca di SIBI <span aria-hidden="true">↗</span></a>
        @endif
        @if ($rank !== null)
            <p class="book-popular-count"><x-icon name="loan" :size="14"/> {{ number_format($book->loan_items_count, 0, ',', '.') }} kali dipinjam</p>
        @endif
        <div class="home-book-bottom">
            <span class="book-stock {{ $book->available_count ? '' : 'book-stock-empty' }}"><i aria-hidden="true"></i>{{ $book->available_count ? $book->available_count.' tersedia' : 'Belum tersedia' }}</span>
            <a href="{{ $detailLink }}">{{ auth()->check() ? 'Detail buku' : 'Masuk untuk detail' }} <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>
