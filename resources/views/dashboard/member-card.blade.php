<div class="student-member-card" aria-label="Kartu anggota perpustakaan">
    <div class="member-card-brand"><x-icon name="book" :size="25"/><div><strong>Ruang Pustaka</strong><span>{{ config('library.school_name') }}</span></div><span class="member-card-status">Anggota aktif</span></div>
    <p class="member-card-caption">KARTU ANGGOTA PERPUSTAKAAN</p>
    <h3 class="member-card-name">{{ auth()->user()->name }}</h3>
    <div class="member-card-details">
        <div><span>Kelas</span><strong>{{ $studentProfile->schoolClass?->name ?? 'Belum dicantumkan' }}</strong></div>
        <div><span>Kode anggota</span><strong class="member-card-code">{{ $studentProfile->member_code }}</strong></div>
    </div>
    <p class="member-card-footer">Tunjukkan kartu ini kepada petugas saat meminjam buku.</p>
</div>
