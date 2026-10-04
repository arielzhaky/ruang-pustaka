<aside class="rules-card" aria-label="Aturan dan tips peminjaman">
    <div class="rules-card-header">
        <h2 class="h6 mb-0"><x-icon name="help"/> Aturan &amp; Tips Peminjaman</h2>
        @unless (request()->routeIs('borrowing-rules'))<a class="small" href="{{ route('borrowing-rules') }}">Panduan lengkap →</a>@endunless
    </div>
    <dl class="rules-metrics mb-0">
        <div><dt>Masa pinjam default</dt><dd>{{ config('library.loan_days') }} hari</dd></div>
        <div><dt>Batas pinjaman aktif</dt><dd>{{ config('library.max_active_loans') }} eksemplar</dd></div>
        <div><dt>Denda per hari / buku</dt><dd>Rp{{ number_format(config('library.fine_daily_rate'), 0, ',', '.') }}</dd></div>
    </dl>
    @if ($fullRules ?? false)
        <ul class="rules-list">
            <li><strong>Perhatikan batas kembali.</strong> Masa pinjam default {{ config('library.loan_days') }} hari. Petugas dapat menyesuaikan tanggal; ikuti batas kembali yang tercatat pada setiap buku.</li>
            <li><strong>Hitung kuota aktif.</strong> Maksimal {{ config('library.max_active_loans') }} eksemplar per siswa, termasuk buku yang terlambat dan belum dikembalikan.</li>
            <li><strong>Kembalikan tepat waktu.</strong> Denda mulai dihitung pada hari setelah batas kembali, per tanggal kalender Asia/Jakarta. Tarif di atas berlaku untuk pinjaman baru; tarif pinjaman lama mengikuti catatan saat peminjaman.</li>
            <li><strong>Buku hilang.</strong> Cukup mengganti harga buku yang tercatat, tanpa tambahan denda keterlambatan. Laporkan kepada petugas untuk mencatat penyelesaiannya.</li>
            <li><strong>Jaga kondisi buku.</strong> Periksa bersama petugas sebelum meminjam. Jika ada kerusakan atau kehilangan, segera laporkan.</li>
            <li><strong>Pinjam dan kembali melalui petugas.</strong> Cek katalog serta lokasi rak, lalu temui petugas. Pantau tanggal kembali dan riwayat di Peminjaman Saya.</li>
            <li><strong>Perpanjangan.</strong> Maksimal satu kali selama {{ config('library.renewal_days') }} hari, sebelum terlambat dan setelah disetujui petugas. Buku dengan reservasi aktif tidak dapat diperpanjang.</li>
            <li><strong>Reservasi.</strong> Maksimal {{ config('library.max_reservations') }} judul aktif. Antrean berdasarkan urutan pendaftaran. Setelah siap, ambil dalam {{ config('library.reservation_hold_days') }} hari atau reservasi berakhir.</li>
            <li><strong>Pembayaran.</strong> Denda ditetapkan saat pengembalian atau pencatatan kehilangan. Bayar penuh melalui petugas; bukti tersedia di menu Tagihan Saya.</li>
        </ul>
    @else
        <p class="small text-secondary mt-3 mb-0">Ikuti batas kembali pada setiap buku. Denda mulai hari berikutnya; buku hilang cukup mengganti harga buku. Peminjaman dan pengembalian dicatat oleh petugas.</p>
    @endif
</aside>
