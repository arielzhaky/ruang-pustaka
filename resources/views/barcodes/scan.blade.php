@extends('layouts.app')
@section('title', 'Scan Barcode | Ruang Pustaka')
@section('content')
<main class="container py-5"><p class="page-eyebrow">RUANG PUSTAKA / PELAYANAN</p><h1>Scan barcode</h1><p class="page-intro">Temukan identitas siswa dan data buku fisik dari kartu atau label eksemplarnya.</p>
@include('shared.service-flash')
@include('shared.barcode-scanner', ['scannerId'=>'station', 'scanMode'=>'lookup'])
<div class="row g-3"><div class="col-md-6"><div class="bg-white border rounded-3 p-4"><h2 class="h5">Peminjaman dengan scan</h2><p>Pindai kartu siswa lalu setiap eksemplar untuk mengisi formulir peminjaman. Periksa pilihan dan batas kembali sebelum menyimpan.</p><a href="{{ route('loans.create') }}" class="btn btn-primary">Catat peminjaman</a></div></div><div class="col-md-6"><div class="bg-white border rounded-3 p-4"><h2 class="h5">Pengembalian dengan scan</h2><p>Pindai label eksemplar untuk menemukan pinjaman aktifnya. Pilih kondisi buku lalu konfirmasi pengembalian.</p><a href="{{ route('loans.index') }}#return-scanner" class="btn btn-outline-primary">Catat pengembalian</a></div></div></div>
<p class="small text-secondary mt-4">Cetak kartu siswa melalui Kelola Siswa → Kartu / Barcode. Cetak label buku melalui Data Buku → Eksemplar → Cetak Barcode. Setiap eksemplar memiliki barcode sendiri.</p></main>
@endsection
