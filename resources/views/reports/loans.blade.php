@extends('layouts.app')

@section('title', 'Laporan Peminjaman | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Laporan Peminjaman</h1>
            <p class="page-intro">Tinjau aktivitas perpustakaan sesuai periode dan status peminjaman.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reports.loans.download', $filters) }}" class="btn btn-success">Unduh PDF</a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">Dashboard</a>
            <a href="{{ route('reports.loans.print', $filters) }}" target="_blank" rel="noopener"
               class="btn btn-primary">Cetak Laporan <span class="visually-hidden">(tab baru)</span></a>
        </div>
    </div>

    <section class="bg-white rounded-4 shadow-sm p-3 p-md-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('reports.loans') }}" method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-12 col-md-3">
                <label for="start_date" class="form-label">Tanggal pinjam mulai</label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $startDate) }}" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <label for="end_date" class="form-label">Tanggal pinjam sampai</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $endDate) }}" class="form-control">
            </div>
            <div class="col-12 col-md-4">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit">Tampilkan</button>
                <a href="{{ route('reports.loans') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
        <p class="small text-secondary">Periode berdasarkan tanggal peminjaman, termasuk tanggal awal dan akhir. Kosongkan tanggal untuk tanpa batas.</p>

        <div class="row g-3 my-3">
            @foreach (['total' => 'Total eksemplar', 'active' => 'Dipinjam, belum terlambat', 'late' => 'Terlambat, belum kembali', 'returned' => 'Selesai (kembali / hilang)'] as $key => $label)
                <div class="col-6 col-lg-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="text-secondary">{{ $label }}</div>
                        <div class="fs-3 fw-bold">{{ $summary[$key] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="small text-secondary">
            Ringkasan seluruh hasil filter: {{ $periodLabel }} · {{ $statusLabels[$status] }}.
            Status per {{ $generatedAt->format('d/m/Y H:i') }} ({{ config('app.timezone') }}).
        </p>
        <div class="table-responsive" role="region" aria-label="Data laporan peminjaman" tabindex="0">
            @include('reports.partials.loan-table')
        </div>
        {{ $items->links('pagination::bootstrap-5') }}
        <p class="small text-secondary mb-0">
            Keterlambatan berhenti pada tanggal pengembalian atau pencatatan hilang. Biaya buku hilang hanya harga penggantian. Nominal aktif berupa perkiraan; laporan ini tidak mencatat pembayaran.
            Tombol Cetak Laporan memuat seluruh hasil sesuai filter.
        </p>
    </section>
</main>
@endsection
