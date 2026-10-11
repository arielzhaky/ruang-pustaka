@extends('layouts.app')

@section('title', 'Catat Peminjaman | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Catat Peminjaman</h1>
            <p class="page-intro">Pilih siswa dan eksemplar, lalu tentukan batas pengembaliannya.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
            Dashboard
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('shared.barcode-scanner', ['scannerId'=>'loan', 'scanMode'=>'loan', 'scanTitle'=>'Pindai kartu siswa dan eksemplar'])
    <div class="loan-form-layout">
    <section class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="h5 mb-3">Peminjaman Baru</h2>
        <p class="small text-secondary">Denda keterlambatan Rp{{ number_format(config('library.fine_daily_rate'), 0, ',', '.') }} per eksemplar per hari, mulai hari setelah batas kembali. Buku hilang cukup mengganti harga buku.</p>

        @if($selectedReservation)
        <div class="alert alert-info" role="status">
            <strong>Pengambilan reservasi: {{ $selectedReservation->bookCopy->book->title }}</strong>
            <p class="mb-1">{{ $selectedReservation->member->user->name }} · {{ $selectedReservation->bookCopy->inventory_code }} sudah dipilih. Periksa buku dan batas kembali sebelum menyimpan.</p>
            <small>Ambil paling lambat {{ $selectedReservation->expires_at->format('d/m/Y H:i') }} WIB.</small>
            <a href="{{ route('loans.create') }}" class="d-block small mt-2">Catat peminjaman lain</a>
        </div>
        @else
        <form action="{{ route('loans.create') }}" method="GET" class="mb-4">
            <label for="member-search" class="form-label">Cari siswa berdasarkan nama, NIS, atau kode anggota</label>
            <div class="d-flex gap-2"><input id="member-search" name="q" type="search" maxlength="100" class="form-control" value="{{ request('q') }}"><button class="btn btn-outline-primary">Cari siswa</button><a href="{{ route('loans.create') }}" class="btn btn-outline-primary">Reset</a></div>
            <p class="small text-secondary mt-2">{{ $members->count() }} siswa aktif ditemukan. Pilih hasilnya di bawah sebelum memilih eksemplar.</p>
        </form>
        @endif
        <form action="{{ route('loans.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="member_id" class="form-label">Siswa</label>
                <select id="member_id" name="member_id" class="form-select" required>
                    <option value="">Pilih siswa</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}"
                            @selected(old('member_id', $selectedMemberId) == $member->id)>
                            {{ $member->name }} — {{ $member->member_code }}{{ $member->nis ? ' · NIS '.$member->nis : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <fieldset class="mb-3" aria-describedby="copy-help">
                <legend class="fs-6">Eksemplar buku</legend>
                <p id="copy-help" class="text-secondary">
                    Centang satu atau beberapa eksemplar. Maksimal {{ $maxActiveLoans }} eksemplar aktif
                    per siswa, termasuk buku yang masih dipinjam sebelumnya.
                </p>
                <div data-loan-copy-list class="border rounded p-3 overflow-auto" style="max-height: 320px">
                    @forelse ($copies as $copy)
                        <div class="form-check mb-2">
                            <input id="copy-{{ $copy->id }}" name="book_copy_ids[]"
                                   type="checkbox" value="{{ $copy->id }}" class="form-check-input"
                                   @checked(in_array((string) $copy->id, array_map('strval', (array) old('book_copy_ids', $selectedCopyIds)), true))>
                            <label for="copy-{{ $copy->id }}" class="form-check-label">
                                {{ $copy->title }} — {{ $copy->inventory_code }}
                                @if(isset($heldCopies[$copy->id]))<small class="d-block text-primary">Reservasi siap: {{ $heldCopies[$copy->id] }}</small>@endif
                            </label>
                        </div>
                    @empty
                        <p data-copy-empty class="text-secondary mb-0">Belum ada eksemplar yang tersedia.</p>
                    @endforelse
                </div>
            </fieldset>

            <div class="mb-3">
                <label for="due_date" class="form-label">Batas pengembalian</label>
                <input id="due_date" name="due_date" type="date"
                       class="form-control" value="{{ old('due_date', $defaultDueDate) }}"
                       min="{{ today()->toDateString() }}" aria-describedby="due-date-help">
                <div id="due-date-help" class="form-text">
                    Masa pinjam default {{ $loanDays }} hari. Petugas dapat menyesuaikan tanggal;
                    jika dikosongkan, sistem memakai {{ $loanDays }} hari dari tanggal peminjaman.
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Catatan (opsional)</label>
                <textarea id="notes" name="notes" class="form-control"
                          rows="3">{{ old('notes') }}</textarea>
            </div>

            <button data-loan-submit type="submit" class="btn btn-primary" @disabled($copies->isEmpty() || $members->isEmpty())>
                Simpan Peminjaman
            </button>
        </form>
    </section>
    <div class="loan-form-rules">@include('shared.borrowing-rules', ['fullRules' => true])</div>
    </div>
</main>
@endsection
