@php($cardUser = $studentProfile->user)
<div class="student-member-card" aria-label="Kartu anggota perpustakaan">
    <div class="member-card-brand"><x-icon name="book" :size="25"/><div><strong>Ruang Pustaka</strong><span>{{ config('library.school_name') }}</span></div><span class="member-card-status">{{ $cardUser->is_active ? 'Anggota aktif' : 'Anggota nonaktif' }}</span></div>
    <p class="member-card-caption">KARTU ANGGOTA PERPUSTAKAAN</p>
    <h3 class="member-card-name">{{ $cardUser->name }}</h3>
    <div class="member-card-details">
        <div><span>Kelas</span><strong>{{ $studentProfile->schoolClass?->name ?? 'Belum dicantumkan' }}</strong></div>
        <div><span>Kode anggota</span><strong class="member-card-code">{{ $studentProfile->member_code }}</strong></div>
    </div>
    <div class="member-card-codes">
        @include('shared.barcode', ['barcodeCode'=>\App\Support\LibraryBarcode::member($studentProfile)])
        @if(($showLoginQr ?? false) && $studentProfile->login_qr_hash && $cardUser->is_active)
            <div class="member-login-qr"><img width="112" height="112" alt="QR login pribadi siswa" src="data:image/svg+xml;base64,{{ base64_encode(\App\Support\StudentLoginQr::svg($studentProfile)) }}"><span>QR login · Rahasia</span></div>
        @endif
    </div>
    <p class="member-card-footer">Tunjukkan kartu ini kepada petugas saat meminjam buku.</p>
</div>
