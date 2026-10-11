@extends('layouts.app')
@section('title', 'Label Barcode Eksemplar | Ruang Pustaka')
@push('styles')<link rel="stylesheet" href="{{ asset('css/barcodes.css') }}">@endpush
@section('content')
<main class="container py-5 copy-label-page">
    <div class="label-print-controls mb-4"><p class="page-eyebrow">RUANG PUSTAKA / LABEL BUKU</p><h1>Label barcode eksemplar</h1><p>{{ $book->title }} · {{ $copies->count() }} label pada halaman ini, dari {{ $copies->total() }} eksemplar sesuai pilihan.</p><div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('copies.index', $book) }}" class="btn btn-outline-primary">Kembali ke eksemplar</a></div><p class="small text-secondary mt-3">Cetak pada skala 100%, matikan header/footer browser. Label berukuran 90 × 42 mm pada kertas A4; potong mengikuti garis tepi. Halaman ini memuat maksimal 100 label.</p>{{ $copies->links('pagination::bootstrap-5') }}</div>
    <div class="copy-label-grid">
    @forelse($copies as $copy)
        <article class="copy-barcode-label">
            <p class="label-school">RUANG PUSTAKA · {{ config('library.school_name') }}</p>
            <h2>{{ $book->title }}</h2>
            @include('shared.barcode', ['barcodeCode'=>\App\Support\LibraryBarcode::copy($copy)])
            <p class="label-inventory">{{ $copy->inventory_code }}</p>
            <p class="label-shelf">{{ $copy->shelf_location ?: 'Rak belum diisi' }}{{ $copy->archived_at || $book->archived_at ? ' · NONAKTIF' : '' }}</p>
        </article>
    @empty<div class="alert alert-info">Belum ada eksemplar aktif yang dapat dicetak.</div>@endforelse
    </div>
</main>
@endsection
