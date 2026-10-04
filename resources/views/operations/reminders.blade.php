@extends('layouts.app')
@section('title', 'Pengingat Pengembalian | Ruang Pustaka')
@section('content')
<main class="container py-5"><p class="page-eyebrow">RUANG PUSTAKA / PENGINGAT</p><h1>Jangan lupa kembali</h1><p class="page-intro">Buku yang jatuh tempo dalam {{ config('library.reminder_days') }} hari, hari ini, atau sudah terlambat. Pengingat diperbarui saat kamu membuka halaman.</p>
<div class="row g-3">@forelse($items as $item)<div class="col-md-6"><article class="bg-white border rounded-3 p-4 h-100"><div class="d-flex gap-3">@include('books.cover', ['coverBook'=>$item->bookCopy->book, 'large'=>false])<div><h2 class="h5">{{ $item->bookCopy->book->title }}</h2><p>{{ $item->bookCopy->inventory_code }}</p><strong>Batas kembali {{ $item->due_date->format('d/m/Y') }}</strong></div></div>
@if($item->due_date->lt(today()))<p class="alert alert-danger mt-3 mb-0">Terlambat {{ \App\Support\LoanCharges::lateDays($item) }} hari. Estimasi denda Rp{{ number_format(\App\Support\LoanCharges::lateFine($item), 0, ',', '.') }}.</p>@elseif($item->due_date->isToday())<p class="alert alert-warning mt-3 mb-0">Jatuh tempo hari ini. Kembalikan melalui petugas.</p>@else<p class="alert alert-info mt-3 mb-0">Jatuh tempo {{ (int) today()->diffInDays($item->due_date) }} hari lagi. Siapkan buku atau ajukan perpanjangan.</p>@endif
</article></div>@empty<div class="col-12"><p class="bg-white border rounded-3 p-4">Belum ada buku yang perlu segera dikembalikan.</p></div>@endforelse</div><a href="{{ route('my-loans.index') }}" class="btn btn-primary mt-4">Lihat peminjaman saya</a></main>
@endsection
