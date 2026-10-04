@extends('layouts.app')
@section('title', 'Kartu Anggota | Ruang Pustaka')
@section('content')
<main class="container py-5 member-card-page">
    <div class="student-section-heading"><div><p class="page-eyebrow">IDENTITAS PERPUSTAKAAN</p><h1>Kartu Anggota Digital</h1><p>Simpan sebagai PDF melalui menu cetak, atau tunjukkan dari layar ponsel.</p></div><a href="{{ route('dashboard') }}" class="btn btn-outline-primary">Dashboard</a></div>
    @if ($studentProfile)
        <div class="member-card-print-area">@include('dashboard.member-card')</div>
        <div class="member-card-print-controls"><button type="button" class="btn btn-primary" data-print-member-card hidden><x-icon name="print" :size="18"/> Cetak / Simpan PDF</button><p class="text-secondary small mt-3">Periksa nama dan kelasmu. Hubungi petugas jika ada data yang perlu diperbarui.</p><noscript><p>Gunakan menu Cetak pada browser untuk menyimpan kartu sebagai PDF.</p></noscript></div>
    @else
        <div class="alert alert-warning" role="status">Profil anggota kamu belum tersedia. Hubungi petugas perpustakaan untuk melengkapinya sebelum mencetak kartu.</div>
    @endif
</main>
@endsection
