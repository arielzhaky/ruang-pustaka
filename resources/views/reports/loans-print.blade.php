<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Peminjaman | Ruang Pustaka</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #172033; background: #f3f7fc; margin: 0; line-height: 1.45; }
        main { max-width: 1400px; margin: 24px auto; padding: 24px; background: white; }
        h1 { font-size: 24px; margin: 4px 0 16px; }
        .brand { font-weight: bold; color: #1d4ed8; margin: 0; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 24px; }
        .toolbar a, .toolbar button { font: inherit; padding: 8px 16px; border: 1px solid #1d4ed8; border-radius: 6px; background: white; color: #1d4ed8; cursor: pointer; }
        .toolbar button { background: #1d4ed8; color: white; }
        .summary { display: flex; flex-wrap: wrap; gap: 12px 24px; padding: 12px; border: 1px solid #cbd5e1; margin: 16px 0; }
        .table-wrap { overflow-x: auto; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #94a3b8; padding: 8px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #eff6ff; }
        .text-secondary, .note { color: #475569; }
        .note { font-size: 12px; }
        .text-center { text-align: center; }
        .badge { font-weight: bold; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print {
            body { background: white; color: black; }
            main { max-width: none; margin: 0; padding: 0; }
            .toolbar { display: none; }
            .table-wrap { overflow: visible; }
            .report-table { font-size: 9pt; table-layout: fixed; }
            th:nth-child(1) { width: 3%; }
            th:nth-child(2) { width: 15%; }
            th:nth-child(3) { width: 10%; }
            th:nth-child(4) { width: 17%; }
            th:nth-child(5), th:nth-child(6), th:nth-child(7) { width: 9%; }
            th:nth-child(8) { width: 8%; }
            th:nth-child(9) { width: 8%; }
        th:nth-child(10) { width: 12%; }
        .d-block { display: block; }
            th, td { padding: 5px; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
            h1, .summary { break-after: avoid; }
            .brand, .text-secondary, .note { color: black; }
        }
    </style>
</head>
<body>
<main>
    <div class="toolbar">
        <a href="{{ route('reports.loans.download', $filters) }}">Unduh PDF Langsung</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="{{ route('reports.loans', $filters) }}">Kembali ke Laporan</a>
        <span>Siap dicetak: {{ $summary['total'] }} eksemplar sesuai filter.</span>
    </div>
    <header>
        <p class="brand">RUANG PUSTAKA</p>
        <h1>Laporan Peminjaman</h1>
        <p>Periode tanggal peminjaman: <strong>{{ $periodLabel }}</strong><br>
            Status: {{ $statusLabels[$status] }}<br>
            Dibuat: {{ $generatedAt->format('d/m/Y H:i') }} ({{ config('app.timezone') }})<br>
            Petugas: {{ auth()->user()->name }}</p>
    </header>
    <div class="summary">
        <span>Total: <strong>{{ $summary['total'] }}</strong> eksemplar</span>
        <span>Dipinjam, belum terlambat: <strong>{{ $summary['active'] }}</strong></span>
        <span>Terlambat, belum kembali: <strong>{{ $summary['late'] }}</strong></span>
        <span>Selesai (kembali / hilang): <strong>{{ $summary['returned'] }}</strong></span>
    </div>
    <div class="table-wrap">
        @include('reports.partials.loan-table')
    </div>
    <p class="note">Ringkasan mencakup seluruh hasil sesuai filter. Status sesuai waktu laporan dibuat.
        Keterlambatan berhenti pada tanggal pengembalian atau pencatatan hilang. Biaya buku hilang hanya harga penggantian. Nominal aktif berupa perkiraan; laporan ini tidak mencatat pembayaran.</p>
</main>
</body>
</html>
