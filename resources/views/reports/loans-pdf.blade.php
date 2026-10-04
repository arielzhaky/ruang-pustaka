<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #172033; background: white; }
        h1 { font-size: 19px; margin: 4px 0 12px; }
        .brand { color: #1d4ed8; font-weight: bold; }
        .summary { border: 1px solid #94a3b8; padding: 8px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8pt; }
        th, td { border: 1px solid #94a3b8; padding: 5px; vertical-align: top; word-wrap: break-word; }
        th { text-align: left; background: #eff6ff; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th:nth-child(1) { width: 3%; }
        th:nth-child(2) { width: 15%; }
        th:nth-child(3) { width: 10%; }
        th:nth-child(4) { width: 17%; }
        th:nth-child(5), th:nth-child(6), th:nth-child(7) { width: 9%; }
        th:nth-child(8) { width: 8%; }
        th:nth-child(9) { width: 8%; }
        th:nth-child(10) { width: 12%; }
        .d-block { display: block; }
        .badge { font-weight: bold; }
        .text-secondary { color: #475569; }
    </style>
</head>
<body>
    <div class="brand">RUANG PUSTAKA</div>
    <h1>Laporan Peminjaman</h1>
    <p>Periode tanggal peminjaman: {{ $periodLabel }}<br>
        Status: {{ $statusLabels[$status] }}<br>
        Dibuat: {{ $generatedAt->format('d/m/Y H:i') }} ({{ config('app.timezone') }})<br>
        Petugas: {{ auth()->user()->name }}</p>
    <div class="summary">
        Total: {{ $summary['total'] }} eksemplar · Dipinjam, belum terlambat: {{ $summary['active'] }} ·
        Terlambat: {{ $summary['late'] }} · Selesai (kembali / hilang): {{ $summary['returned'] }}
    </div>
    @include('reports.partials.loan-table')
    <p>Seluruh hasil sesuai filter. Keterlambatan berhenti pada tanggal pengembalian atau pencatatan hilang. Biaya buku hilang hanya harga penggantian. Nominal aktif berupa perkiraan; laporan ini tidak mencatat pembayaran.</p>
</body>
</html>
