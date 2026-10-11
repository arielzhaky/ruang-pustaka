@extends('layouts.app')
@section('title', 'Kartu Anggota | Ruang Pustaka')
@if($staffCard ?? false)
@push('styles')<link rel="stylesheet" href="{{ asset('css/student-dashboard.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/student-books.js') }}" defer></script>@endpush
@endif
@section('content')
<main class="container py-5 member-card-page">
    <div class="student-section-heading"><div><p class="page-eyebrow">IDENTITAS PERPUSTAKAAN</p><h1>Kartu Anggota Digital</h1><p>Simpan sebagai PDF melalui menu cetak, atau tunjukkan dari layar ponsel.</p></div><a href="{{ route(($staffCard ?? false) ? 'members.index' : 'dashboard') }}" class="btn btn-outline-primary">{{ ($staffCard ?? false) ? 'Kelola Siswa' : 'Dashboard' }}</a></div>
    @if ($studentProfile)
        @include('shared.service-flash')
        <div class="member-card-print-area">@include('dashboard.member-card', ['showLoginQr'=>true])</div>
        <section class="member-card-print-controls border rounded-3 p-3 mb-3" style="max-width:510px">
            <h2 class="h5">QR login tanpa kata sandi</h2>
            <p class="small text-secondary">QR login adalah kunci masuk akun. Simpan kartu untuk diri sendiri. Jika kartu hilang, ganti QR lalu cetak ulang; QR lama dan sesi yang memakainya akan dinonaktifkan.</p>
            <form method="POST" action="{{ ($staffCard ?? false) ? route('members.login-qr', $studentProfile) : route('member-card.login-qr') }}" class="d-flex flex-wrap gap-2" data-login-qr-management>
                @csrf
                @if($studentProfile->login_qr_hash)
                    @if($studentProfile->user->is_active)<button class="btn btn-outline-primary" name="action" value="replace">Ganti QR login</button>@endif
                    <button class="btn btn-outline-danger" name="action" value="revoke">Nonaktifkan QR login</button>
                @elseif($studentProfile->user->is_active)
                    <button class="btn btn-primary" name="action" value="issue">Buat QR login</button>
                @else
                    <p class="small mb-0">Aktifkan akun siswa terlebih dahulu untuk membuat QR login.</p>
                @endif
            </form>
        </section>
        <div class="member-card-print-controls"><button type="button" class="btn btn-primary" data-print-member-card hidden><x-icon name="print" :size="18"/> Cetak / Simpan PDF</button><p class="text-secondary small mt-3">Periksa nama dan kelasmu. Hubungi petugas jika ada data yang perlu diperbarui.</p><noscript><p>Gunakan menu Cetak pada browser untuk menyimpan kartu sebagai PDF.</p></noscript></div>
    @else
        <div class="alert alert-warning" role="status">Profil anggota kamu belum tersedia. Hubungi petugas perpustakaan untuk melengkapinya sebelum mencetak kartu.</div>
    @endif
</main>
@endsection
@push('scripts')<script src="{{ asset('js/member-login-qr.js') }}" defer></script>@endpush
