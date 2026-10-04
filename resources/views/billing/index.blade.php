@extends('layouts.app')
@section('title', 'Tagihan & Pembayaran | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <p class="page-eyebrow">RUANG PUSTAKA / TAGIHAN</p><h1>Tagihan & pembayaran</h1>
    <p class="page-intro">Biaya tetap setelah buku dikembalikan atau dinyatakan hilang. Estimasi pinjaman aktif ada di halaman peminjaman.</p>
    @include('shared.service-flash')
    <div class="row g-3 mb-4"><div class="col-md-6"><div class="student-stat stat-tone-violet p-4 border rounded"><span>Belum dibayar · sudah diverifikasi</span><strong class="d-block fs-3">Rp{{ number_format($unpaid, 0, ',', '.') }}</strong></div></div><div class="col-md-6"><div class="p-4 border rounded"><span>Tagihan lama perlu verifikasi</span><strong class="d-block fs-3">{{ $unverified }}</strong><small>Petugas perlu memastikan apakah pembayaran pernah dilakukan.</small></div></div></div>
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-4"><div><label for="billing-status" class="form-label">Status tagihan</label><select id="billing-status" name="status" class="form-select"><option value="">Semua status</option>@foreach(['unverified'=>'Perlu verifikasi','unpaid'=>'Belum dibayar','paid'=>'Lunas'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div><button class="btn btn-primary">Tampilkan</button></form>
    <section class="bg-white p-4 border rounded-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Buku / siswa</th><th>Rincian</th><th>Status</th><th>Pembayaran</th></tr></thead><tbody>
    @forelse($items as $item)
        <tr><td><strong>{{ $item->bookCopy->book->title }}</strong><br><small>{{ $item->bookCopy->inventory_code }} · {{ $item->loan->loan_code }}</small>@if(auth()->user()->role === 'petugas')<p class="mb-0 mt-2">{{ $item->loan->member->user->name }} · {{ $item->loan->member->member_code }}</p>@endif</td>
        <td>Denda: Rp{{ number_format($item->fine_amount, 0, ',', '.') }}<br>Penggantian: Rp{{ number_format($item->replacement_amount, 0, ',', '.') }}<br><strong>Total: Rp{{ number_format(\App\Support\LoanCharges::total($item), 0, ',', '.') }}</strong></td>
        <td><span class="badge {{ $item->billing_status === 'paid' ? 'text-bg-success' : 'text-bg-warning' }}">{{ ['unverified'=>'Perlu verifikasi','unpaid'=>'Belum dibayar','paid'=>'Lunas'][$item->billing_status] ?? $item->billing_status }}</span></td>
        <td>
        @if($item->payment)<a class="btn btn-sm btn-outline-primary" href="{{ route('billing.receipt', $item->payment) }}">Lihat bukti pembayaran</a>
        @elseif(auth()->user()->role === 'petugas')
            <details><summary>{{ $item->billing_status === 'unverified' ? 'Verifikasi pembayaran lama' : 'Catat pelunasan' }}</summary>
            @if($item->billing_status === 'unverified')
                <form action="{{ route('billing.confirm', $item) }}" method="POST" class="my-3">@csrf<label class="d-block small"><input type="checkbox" name="confirmation" value="1" required> Saya sudah memastikan tagihan ini belum dibayar.</label><button class="btn btn-sm btn-outline-primary mt-2">Tandai belum dibayar</button></form>
            @endif
            <form action="{{ route('billing.pay', $item) }}" method="POST" class="my-3">@csrf
            @if($item->billing_status === 'unverified')<input type="hidden" name="method" value="historical"><p class="small">Jika sudah dibayar sebelum fitur ini tersedia, catat konfirmasi pembayaran lama berikut.</p>
            @else<label for="method-{{ $item->id }}" class="form-label">Metode pembayaran</label><select id="method-{{ $item->id }}" class="form-select" name="method"><option value="cash">Tunai</option><option value="transfer">Transfer</option></select>@endif
            <label for="payment-note-{{ $item->id }}" class="form-label mt-2">Catatan / referensi bukti (opsional)</label><input id="payment-note-{{ $item->id }}" name="notes" class="form-control" maxlength="500">
            <label class="d-block small mt-2"><input type="checkbox" name="confirmation" value="1" required> {{ $item->billing_status === 'unverified' ? 'Saya telah memverifikasi bukti pelunasan lama.' : 'Saya sudah menerima dan memeriksa pembayaran penuh.' }}</label><button class="btn btn-sm btn-primary mt-2">{{ $item->billing_status === 'unverified' ? 'Konfirmasi sudah lunas' : 'Simpan pelunasan' }}</button>
            </form></details>
        @else<span class="small text-secondary">{{ $item->billing_status === 'unverified' ? 'Hubungi petugas untuk memeriksa catatan lama.' : 'Bayar melalui petugas dan simpan bukti pembayarannya.' }}</span>@endif
        </td></tr>
    @empty<tr><td colspan="4" class="text-center py-4">Belum ada tagihan sesuai filter.</td></tr>@endforelse
    </tbody></table></div>{{ $items->links('pagination::bootstrap-5') }}</section>
</main>
@endsection
