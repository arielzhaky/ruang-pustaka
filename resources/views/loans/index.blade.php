@extends('layouts.app')

@section('title', 'Daftar Peminjaman | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Daftar Peminjaman</h1>
            <p class="page-intro">Pantau pinjaman aktif, pengembalian, dan biaya setiap eksemplar.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                Dashboard
            </a>
            <a href="{{ route('loans.create') }}" class="btn btn-primary">
                Catat Peminjaman
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="bg-white rounded-4 shadow-sm p-4">
        <p class="small text-secondary">Denda keterlambatan Rp{{ number_format(config('library.fine_daily_rate'), 0, ',', '.') }} per eksemplar per hari untuk pinjaman baru. Buku hilang dikenakan harga penggantian saja. Nominal pada pinjaman aktif masih berupa perkiraan; pelunasan dicatat melalui menu Tagihan & Pembayaran.</p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode</th>
                        <th>Siswa</th>
                        <th>Buku / Eksemplar</th>
                        <th>Tanggal pinjam</th>
                        <th>Batas kembali</th>
                        <th>Status</th>
                        <th>Denda / penggantian</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td>{{ $loans->firstItem() + $loop->index }}</td>
                            <td>{{ $loan->loan_code }}</td>
                            <td>{{ $loan->student_name }}</td>
                            <td>
                                {{ $loan->book_title }}<br>
                                <small class="text-secondary">
                                    {{ $loan->inventory_code }}
                                </small>
                            </td>
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
                            </td>
                            <td>@include('shared.loan-charge', ['chargeItem' => $loan])</td>
                            <td>
                                @if (! $loan->returned_at)
                                    <form action="{{ route('loans.items.return', $loan->item_id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Simpan kondisi dan biaya eksemplar ini? Pilihan hilang akan menutup pinjaman dan mencatat biaya penggantian.')">
                                        @csrf

                                        <select name="return_condition"
                                                class="form-select form-select-sm mb-2" required>
                                            <option value="baik">Kondisi baik</option>
                                            <option value="rusak">Kondisi rusak</option>
                                            <option value="hilang">Buku hilang</option>
                                        </select>

                                        <small class="d-block text-secondary mb-2">Jika hilang: {{ $loan->replacement_price ? 'Rp'.number_format($loan->replacement_price, 0, ',', '.') : 'isi harga di Kelola Buku dahulu' }}.</small>
                                        <label class="visually-hidden" for="return-notes-{{ $loan->item_id }}">Catatan pengembalian / kehilangan</label>
                                        <input id="return-notes-{{ $loan->item_id }}" name="return_notes" class="form-control form-control-sm mb-2" placeholder="Catatan (opsional)" maxlength="2000">
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-primary">
                                            Simpan kondisi
                                        </button>
                                    </form>
                                @else
                                    <small class="text-secondary">
                                        {{ \Carbon\Carbon::parse($loan->returned_at)->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">
                                Belum ada peminjaman.
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