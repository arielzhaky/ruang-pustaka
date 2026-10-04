<section class="student-discovery" aria-labelledby="student-popular-heading">
    <div class="student-section-heading"><div><p class="page-eyebrow">FAVORIT PEMBACA</p><h2 id="student-popular-heading">Buku Populer</h2><p>Judul yang paling sering dipinjam di perpustakaan.</p></div><a class="student-detail-link" href="{{ route('catalog.index') }}">Lihat katalog <x-icon name="arrow" :size="16"/></a></div>
    <div class="student-books-grid">
        @forelse ($popularBooks as $book)
            @include('dashboard.book-card', ['book' => $book, 'rank' => $loop->iteration, 'returnTo' => 'dashboard'])
        @empty
            <div class="student-section-empty"><x-icon name="book" :size="28"/><p>Buku populer akan muncul setelah ada riwayat peminjaman.</p></div>
        @endforelse
    </div>
</section>
<section class="student-discovery" aria-labelledby="student-latest-heading">
    <div class="student-section-heading"><div><p class="page-eyebrow">BARU DI RAK KITA</p><h2 id="student-latest-heading">Buku Terbaru</h2><p>Temukan koleksi yang baru ditambahkan petugas.</p></div></div>
    <div class="student-books-grid">
        @forelse ($latestBooks as $book)
            @include('dashboard.book-card', ['book' => $book, 'rank' => null, 'returnTo' => 'dashboard'])
        @empty
            <div class="student-section-empty"><x-icon name="book" :size="28"/><p>Koleksi buku sedang disiapkan. Kunjungi kembali untuk melihat bacaan terbaru.</p></div>
        @endforelse
    </div>
</section>
<section class="student-discovery" id="buku-favorit" aria-labelledby="student-favorites-heading">
    <div class="student-section-heading"><div><p class="page-eyebrow">RAK PRIBADIMU</p><h2 id="student-favorites-heading">Buku Favorit <span class="student-count">{{ $favoriteCount }}</span></h2><p>Simpan buku yang ingin kamu baca berikutnya.</p></div><a class="student-detail-link" href="{{ route('favorites.index') }}">Semua favorit <x-icon name="arrow" :size="16"/></a></div>
    <div class="student-books-grid">
        @forelse ($favoriteBooks as $book)
            @include('dashboard.book-card', ['book' => $book, 'rank' => null, 'returnTo' => 'dashboard'])
        @empty
            <div class="student-section-empty"><x-icon name="heart" :size="28"/><p>Belum ada buku favorit yang tersedia. Tekan tombol Favorit pada buku yang ingin kamu simpan.</p><a href="{{ route('catalog.index') }}" class="btn btn-outline-primary">Jelajahi katalog</a></div>
        @endforelse
    </div>
</section>
