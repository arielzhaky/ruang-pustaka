@extends('layouts.app')
@section('title', 'Reservasi Buku | Ruang Pustaka')
@section('content')
<main class="container py-5"><p class="page-eyebrow">RUANG PUSTAKA / ANTREAN</p><h1>Reservasi buku</h1><p class="page-intro">Antrean mengikuti urutan pendaftaran. Maksimal {{ config('library.max_reservations') }} reservasi aktif per siswa. Ambil buku dalam {{ config('library.reservation_hold_days') }} hari setelah petugas menyiapkannya.</p>
@include('shared.service-flash')
<div class="d-flex flex-wrap gap-2 mb-4"><a href="{{ route('reservations.index') }}" class="btn {{ request('status', 'active') === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">Antrean aktif</a><a href="{{ route('reservations.index', ['status'=>'history']) }}" class="btn {{ request('status') === 'history' ? 'btn-primary' : 'btn-outline-primary' }}">Riwayat</a>@if(auth()->user()->role === 'anggota')<a href="{{ route('catalog.index') }}" class="btn btn-outline-primary">Cari buku untuk reservasi</a>@endif</div>
<div class="bg-white p-4 border rounded-3 table-responsive"><table class="table align-middle"><thead><tr><th>Buku</th><th>Status</th><th>Batas pengambilan</th><th>Tindakan</th></tr></thead><tbody>
@forelse($reservations as $reservation)<tr><td><div class="d-flex gap-3">@include('books.cover', ['coverBook'=>$reservation->book, 'large'=>false])<div><strong>{{ $reservation->book->title }}</strong><p class="small">Diajukan {{ $reservation->created_at->format('d/m/Y H:i') }}</p>@if(auth()->user()->role === 'petugas')<p>{{ $reservation->member->user->name }} · {{ $reservation->member->member_code }}</p>@endif</div></div></td><td><span class="badge {{ $reservation->display_status === 'Siap diambil' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $reservation->display_status }}</span>@if($reservation->queue_position)<p class="small mt-2">Antrean ke-{{ $reservation->queue_position }}</p>@endif</td><td>{{ $reservation->expires_at?->format('d/m/Y H:i') ?? 'Menunggu petugas menyiapkan' }}@if($reservation->bookCopy)<p class="small">{{ $reservation->bookCopy->inventory_code }}</p>@endif</td><td>
@if(in_array($reservation->display_status, ['Mengantre','Siap diambil']))
    @if(auth()->user()->role === 'petugas')
        @if($reservation->status === 'waiting')<form method="POST" action="{{ route('reservations.ready', $reservation) }}" class="mb-2">@csrf<button class="btn btn-sm btn-primary" @disabled($reservation->queue_position !== 1)>Siapkan buku</button></form>
        @else<a href="{{ route('loans.create', ['reservation_id'=>$reservation->id]) }}" class="btn btn-sm btn-primary mb-2">Catat pengambilan</a>@endif
    @else @if($reservation->status === 'ready')<p class="small">Temui petugas sebelum batas waktu di atas.</p>@endif @endif
    <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" onsubmit="return confirm('Batalkan reservasi ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Batalkan reservasi</button></form>
@else<span class="text-secondary">Selesai</span>@endif
</td></tr>@empty<tr><td colspan="4" class="py-4 text-center">Belum ada reservasi dalam daftar ini.</td></tr>@endforelse
</tbody></table>{{ $reservations->links('pagination::bootstrap-5') }}</div>
<p class="small text-secondary mt-3">Reservasi yang melewati batas pengambilan otomatis tidak menahan stok. Peminjaman tetap dicatat petugas saat buku diambil. Koleksi referensi tanpa eksemplar fisik belum dapat direservasi.</p></main>
@endsection
