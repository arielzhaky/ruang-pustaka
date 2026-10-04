@extends('layouts.app')

@section('title', 'Peminjaman Saya | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Peminjaman Saya</h1>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
            Dashboard
        </a>
    </div>

    @include('shared.service-flash')
    <div class="d-flex flex-wrap gap-2 mb-4"><a href="{{ route('renewals.index') }}" class="btn btn-outline-primary">Status perpanjangan</a><a href="{{ route('billing.index') }}" class="btn btn-outline-primary">Tagihan & bukti pembayaran</a></div>
    <div class="mb-4">@include('shared.borrowing-rules')</div>
    <section class="bg-white rounded-4 shadow-sm p-4">
        <p class="small text-secondary">Denda keterlambatan Rp{{ number_format(config('library.fine_daily_rate'), 0, ',', '.') }} per eksemplar per hari untuk pinjaman baru. Buku hilang dikenakan harga penggantian saja. Nominal pada pinjaman aktif masih berupa perkiraan; pelunasan dapat dilihat di menu Tagihan Saya.</p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Buku</th>
                        <th>Kode eksemplar</th>
                        <th>Tanggal pinjam</th>
                        <th>Batas kembali</th>
                        <th>Status</th>
                        <th>Denda / penggantian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td>{{ $loans->firstItem() + $loop->index }}</td>
                            <td>{{ $loan->title }}</td>
                            <td>{{ $loan->inventory_code }}</td>
                            <td>{{ \Carbon\Carbon::parse($loan->borrowed_at)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($loan->due_date)->format('d/m/Y') }}</td>
                            <td>
                                @if ($loan->return_condition === 'hilang')
                                    <span class="badge text-bg-danger">Hilang</span>
                                @elseif ($loan->returned_at)
                                    <span class="badge text-bg-success">Dikembalikan</span>
                                @elseif (\Carbon\Carbon::parse($loan->due_date)->isBefore(today()))
                                    <span class="badge text-bg-danger">Terlambat</span>
                                @else
                                    <span class="badge text-bg-warning">Dipinjam</span>
                                @endif
                                @php($renewal = $renewals->get($loan->item_id))
                                @if($renewal && $renewal->status !== 'rejected')<p class="small mt-2">{{ $renewal->status === 'pending' ? 'Perpanjangan menunggu persetujuan' : 'Sudah diperpanjang' }}</p>@endif
                                @if((! $renewal || $renewal->status === 'rejected') && ! $loan->returned_at && ! \Carbon\Carbon::parse($loan->due_date)->isBefore(today()))
                                    <form method="POST" action="{{ route('renewals.store', $loan->item_id) }}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-primary">Ajukan perpanjangan</button></form>
                                @endif
                            </td>
                            <td>@include('shared.loan-charge', ['chargeItem' => $loan])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                Kamu belum memiliki riwayat peminjaman.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $loans->links('pagination::bootstrap-5') }}
    </section>
</main>
@endsection