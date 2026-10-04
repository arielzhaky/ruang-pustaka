<section aria-labelledby="student-dashboard-heading">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 id="student-dashboard-heading" class="h4 fw-bold mb-1">Dashboard Siswa</h2>
            <p class="text-secondary mb-0">Pantau buku yang kamu pinjam dan jadwal pengembaliannya.</p>
        </div>
    </div>

    <form class="student-search mb-4" action="{{ route('catalog.index') }}" method="GET" role="search">
        <label for="student-book-search">Mau baca apa hari ini?</label>
        <div class="student-search-controls"><x-icon name="search" :size="21"/><input id="student-book-search" name="q" type="search" maxlength="100" placeholder="Cari judul buku atau penulis…"><button type="submit" class="btn btn-primary">Cari Buku <x-icon name="arrow" :size="16"/></button></div>
        <div class="student-search-links"><a href="{{ route('catalog.index') }}">Jelajahi semua buku</a><a href="{{ route('favorites.index') }}"><x-icon name="heart" :size="14"/> {{ $favoriteCount }} favorit</a></div>
    </form>

    @if (! $hasMemberProfile)
        <div class="alert alert-warning" role="status">
            Profil anggota kamu belum tersedia. Hubungi petugas perpustakaan untuk melengkapinya sebelum meminjam buku.
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            [$activeCount, 'Eksemplar dipinjam', 'Termasuk yang terlambat', 'violet', 'loan'],
            [$remainingQuota, 'Sisa kuota', 'Dari batas '.$maxActiveLoans.' eksemplar aktif', 'mint', 'book'],
            [$dueTodayCount, 'Jatuh tempo hari ini', $today->format('d/m/Y'), 'amber', 'clock'],
            [$lateCount, 'Eksemplar terlambat', 'Sudah melewati jatuh tempo', 'rose', 'clock'],
        ] as [$number, $label, $hint, $tone, $icon])
            <div class="col-6 col-lg-3">
                <div class="student-stat stat-tone-{{ $tone }} h-100">
                    <div class="stat-heading"><span>{{ $label }}</span><span class="stat-icon"><x-icon :name="$icon" :size="19"/></span></div>
                    <strong>{{ $number }}</strong>
                    <small>{{ $hint }}</small>
                </div>
            </div>
        @endforeach
    </div>

    @if ($lateCount > 0)
        <div class="alert alert-danger" role="status">
            Ada {{ $lateCount }} eksemplar yang terlambat. Segera kembalikan kepada petugas perpustakaan.
        </div>
    @endif
    @if ($dueTodayCount > 0)
        <div class="alert alert-warning" role="status">
            {{ $dueTodayCount }} eksemplar jatuh tempo hari ini. Siapkan bukunya untuk dikembalikan.
        </div>
    @endif
    @if ($remainingQuota === 0 && $hasMemberProfile)
        <div class="alert alert-info" role="status">
            Kuota pinjaman kamu sudah penuh. Kembalikan buku terlebih dahulu sebelum meminjam lagi.
        </div>
    @endif

    @include('dashboard.services')
    @include('dashboard.student-overview')

    <section aria-labelledby="active-loans-heading" class="mb-5">
        <h3 id="active-loans-heading" class="h5 fw-bold">Buku yang Sedang Dipinjam</h3>
        <p class="text-secondary">Diurutkan dari batas pengembalian paling awal.</p>
        @if ($activeLoans->isEmpty())
            <div class="student-empty border rounded-3 p-4 text-center">
                <p class="mb-1 fw-semibold">Kamu belum memiliki pinjaman aktif.</p>
                <p class="text-secondary mb-0">Temukan buku di katalog, lalu kunjungi petugas untuk meminjamnya.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($activeLoans as $item)
                    <div class="col-12 col-lg-6">
                        <article class="student-loan-card border rounded-3 p-3 h-100">
                            <div class="student-loan-book">
                            @include('books.cover', ['coverBook' => $item->bookCopy->book, 'large' => false])
                            <div>
                            <h4 class="h6 fw-bold mb-1 text-break">
                                @if ($item->bookCopy->book->archived_at)
                                    {{ $item->bookCopy->book->title }}
                                @else
                                    <a href="{{ route('catalog.show', $item->bookCopy->book) }}">{{ $item->bookCopy->book->title }}</a>
                                @endif
                            </h4>
                            <p class="small text-secondary text-break mb-3">
                                {{ $item->bookCopy->book->author }} · {{ $item->bookCopy->inventory_code }}
                            </p>
                            </div>
                            </div>
                            <dl class="row mb-2 small">
                                <dt class="col-6">Tanggal pinjam</dt>
                                <dd class="col-6">{{ $item->loan->borrowed_at->format('d/m/Y') }}</dd>
                                <dt class="col-6">Perkiraan denda</dt>
                                <dd class="col-6">Rp{{ number_format(\App\Support\LoanCharges::total($item), 0, ',', '.') }}</dd>
                                <dt class="col-6">Batas kembali</dt>
                                <dd class="col-6 fw-semibold">{{ $item->due_date->format('d/m/Y') }}</dd>
                            </dl>
                            @if ($item->late_days > 0)
                                <span class="badge text-bg-danger">Terlambat {{ $item->late_days }} hari</span>
                            @elseif ($item->due_date->isSameDay($today))
                                <span class="badge text-bg-warning">Jatuh tempo hari ini</span>
                            @else
                                <span class="badge text-bg-primary">Sedang dipinjam</span>
                                @if($item->due_date->lte(today()->addDays(config('library.reminder_days'))))<p class="small mt-2 mb-0">Jatuh tempo {{ (int) today()->diffInDays($item->due_date) }} hari lagi. Siapkan pengembalianmu.</p>@endif
                            @endif
                            @if(! $item->due_date->lt(today()))<a href="{{ route('my-loans.index') }}" class="d-inline-block small mt-3">Kelola / ajukan perpanjangan →</a>@endif
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
        <p class="small text-secondary mt-3 mb-0">
            Masa pinjam default {{ $loanDays }} hari. Ikuti batas kembali pada setiap buku;
            peminjaman dan pengembalian dicatat oleh petugas perpustakaan.
        </p>
    </section>

    <section aria-labelledby="recent-returns-heading">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h3 id="recent-returns-heading" class="h5 fw-bold mb-0">Pengembalian / Kehilangan Terbaru</h3>
            <a href="{{ route('my-loans.index') }}" class="btn btn-outline-primary btn-sm">Lihat Semua Riwayat</a>
        </div>
        @if ($recentReturns->isEmpty())
            <p class="text-secondary mb-0">Belum ada buku yang kamu kembalikan.</p>
        @else
            <p class="small text-secondary">Menampilkan maksimal 5 eksemplar yang terakhir dikembalikan.</p>
            <div class="table-responsive" role="region" aria-label="Pengembalian terbaru" tabindex="0">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Buku / eksemplar</th>
                            <th scope="col">Batas kembali</th>
                            <th scope="col">Tanggal selesai</th>
                            <th scope="col">Status</th>
                            <th scope="col">Denda / penggantian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentReturns as $item)
                            <tr>
                                <th scope="row" class="fw-normal text-break">
                                    {{ $item->bookCopy->book->title }}<br>
                                    <small class="text-secondary">{{ $item->bookCopy->inventory_code }}</small>
                                </th>
                                <td class="text-nowrap">{{ $item->due_date->format('d/m/Y') }}</td>
                                <td class="text-nowrap">{{ $item->returned_at->format('d/m/Y') }}</td>
                                <td>
                                    @if ($item->return_condition === 'hilang')
                                        <span class="badge text-bg-danger">Hilang</span>
                                    @elseif ($item->late_days > 0)
                                        <span class="badge text-bg-warning">Dikembalikan terlambat {{ $item->late_days }} hari</span>
                                    @else
                                        <span class="badge text-bg-success">Dikembalikan tepat waktu</span>
                                    @endif
                                </td>
                                <td>@include('shared.loan-charge', ['chargeItem' => $item])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    @include('dashboard.student-discovery')
</section>
