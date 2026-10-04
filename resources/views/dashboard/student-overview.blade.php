<div class="student-overview mb-4">
    <section class="student-fine-panel" aria-labelledby="fine-total-heading">
        <div class="student-section-heading"><h3 id="fine-total-heading">Estimasi Denda Aktif</h3><span class="stat-icon stat-tone-rose"><x-icon name="clock" :size="18"/></span></div>
        <strong class="student-fine-total {{ $estimatedFineTotal > 0 ? 'has-fine' : '' }}">Rp{{ number_format($estimatedFineTotal, 0, ',', '.') }}</strong>
        <p class="student-fine-description">{{ $estimatedFineTotal > 0 ? 'Perkiraan denda seluruh pinjaman aktifmu yang terlambat.' : 'Belum ada denda keterlambatan dari pinjaman aktifmu.' }}</p>
        <p class="student-fine-note">Dihitung per {{ $today->format('d/m/Y') }} menurut tarif saat peminjaman. Nilai akhir dicatat petugas saat buku dikembalikan.</p>
        <a href="{{ route('my-loans.index') }}" class="student-detail-link">Lihat rincian pinjaman <x-icon name="arrow" :size="16"/></a>
    </section>
    <section class="student-member-panel" aria-labelledby="member-card-heading">
        <div class="student-section-heading"><h3 id="member-card-heading">Kartu Anggotaku</h3>@if ($studentProfile)<a href="{{ route('member-card') }}" class="student-detail-link"><x-icon name="print" :size="16"/> Buka / cetak</a>@endif</div>
        @if ($studentProfile)
            @include('dashboard.member-card')
        @else
            <div class="student-section-empty"><x-icon name="card" :size="32"/><p>Kartu anggota tersedia setelah profil anggota dilengkapi oleh petugas.</p></div>
        @endif
    </section>
</div>
