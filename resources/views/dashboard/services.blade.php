<section class="mb-4" aria-labelledby="library-services-heading">
    <h2 id="library-services-heading" class="h5 mb-3">{{ auth()->user()->role === 'petugas' ? 'Perlu ditindaklanjuti' : 'Layanan perpustakaanmu' }}</h2>
    <div class="row g-3">
        @foreach([
            ['renewals.index', 'Perpanjangan menunggu', $serviceSummary['renewals'], 'loan'],
            ['reservations.index', 'Reservasi aktif', $serviceSummary['reservations'], 'book'],
            ['billing.index', 'Tagihan belum dibayar', 'Rp'.number_format($serviceSummary['unpaid'], 0, ',', '.'), 'card'],
            [auth()->user()->role === 'petugas' ? 'overdue.index' : 'reminders.index', auth()->user()->role === 'petugas' ? 'Eksemplar terlambat' : 'Pengingat pengembalian', auth()->user()->role === 'petugas' ? $terlambat : $serviceSummary['reminders'], 'clock'],
        ] as [$route, $label, $count, $icon])
        <div class="col-6 col-xl-3"><a class="service-link" href="{{ route($route) }}"><x-icon :name="$icon"/><span>{{ $label }}<strong>{{ $count }}</strong></span><span aria-hidden="true">→</span></a></div>
        @endforeach
    </div>
</section>
