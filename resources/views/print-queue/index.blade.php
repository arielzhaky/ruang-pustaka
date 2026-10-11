@extends('layouts.app')
@section('title', 'Antrean Cetak | Ruang Pustaka')
@push('scripts')<script src="{{ asset('js/print-queue.js') }}" defer></script>@endpush
@section('content')
<main class="container py-5">
    <p class="page-eyebrow">PERPUSTAKAAN / KARTU & LABEL</p><h1>Antrean cetak</h1><p class="page-intro">Kumpulkan kartu siswa dan label barcode eksemplar, lalu unduh PDF sesuai pilihanmu.</p>
    @include('shared.operation-messages')
    <p class="small text-secondary">Kartu siswa aktif menyertakan QR login tanpa kata sandi. QR dibuat saat PDF diunduh jika belum tersedia; simpan PDF dan berikan kartu hanya kepada pemiliknya.</p>
    <section class="bg-white border rounded p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3"><div><h2 class="h5">Pilihan dalam antrean · {{ $queue->count() }}/200</h2><p class="text-secondary small">{{ $queue->whereNotNull('member_id')->count() }} kartu siswa · {{ $queue->whereNotNull('book_copy_id')->count() }} label eksemplar. Antrean ini khusus akun petugasmu.</p></div><a href="#add-print-items" class="btn btn-outline-primary align-self-start">Tambah pilihan</a></div>
        @if($queue->isNotEmpty())
        <form method="POST" action="{{ route('print-queue.download') }}" data-selection-form>
            @csrf
            <div class="d-flex flex-wrap gap-3 align-items-center my-3"><label><input type="checkbox" data-select-all checked> Pilih semua antrean</label><span class="small text-secondary" data-selected-count aria-live="polite">{{ $queue->count() }} dipilih</span></div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pilih</th><th>Jenis</th><th>Nama / judul</th><th>Identitas</th></tr></thead><tbody>
            @foreach($queue as $item)<tr><td><input type="checkbox" name="queue_ids[]" value="{{ $item->id }}" checked data-select-item aria-label="Pilih {{ $item->member ? $item->member->user->name : $item->bookCopy->inventory_code }}"></td><td>{{ $item->member_id ? 'Kartu siswa' : 'Label buku' }}</td><td>{{ $item->member ? $item->member->user->name : $item->bookCopy->book->title }}</td><td>@if($item->member){{ $item->member->member_code }} · {{ $item->member->schoolClass?->name ?? 'Kelas belum diisi' }}@if(!$item->member->user->is_active)<span class="badge text-bg-secondary">Nonaktif</span>@endif @else{{ $item->bookCopy->inventory_code }}@if($item->bookCopy->archived_at || $item->bookCopy->book->archived_at)<span class="badge text-bg-secondary">Nonaktif</span>@endif @endif</td></tr>@endforeach
            </tbody></table></div>
            <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" data-selection-submit>Download PDF yang dipilih</button><button class="btn btn-outline-danger" formaction="{{ route('print-queue.remove') }}" data-selection-submit onclick="return confirm('Hapus item yang dicentang dari antrean cetak?')">Hapus pilihan dari antrean</button></div>
        </form>
        <p class="small text-secondary mt-3 mb-0">PDF memakai kertas A4: kartu siswa 85,6 × 54 mm dan label buku 90 × 42 mm. Cetak pada skala 100% / ukuran sebenarnya. Kartu dan label berada pada halaman terpisah. Unduhan tidak menghapus antrean; hapus pilihan setelah selesai mencetak.</p>
        @else<p class="text-secondary my-4">Antrean masih kosong. Pilih siswa atau eksemplar di bawah untuk mulai mencetak.</p>@endif
    </section>
    <section id="add-print-items" class="bg-white border rounded p-4">
        <h2 class="h5">Tambahkan ke antrean</h2>
        <form method="GET" class="row g-3 mb-4"><div class="col-md-3"><label for="print-type" class="form-label">Jenis cetakan</label><select id="print-type" name="type" class="form-select"><option value="members" @selected($type==='members')>Kartu siswa</option><option value="copies" @selected($type==='copies')>Label eksemplar buku</option></select></div><div class="col-md-6"><label for="print-search" class="form-label">Cari nama / judul / kode</label><input id="print-search" name="q" value="{{ $search }}" class="form-control" maxlength="100"></div><div class="col-md-3 d-flex align-items-end gap-2"><button class="btn btn-primary">Cari</button><a class="btn btn-outline-primary" href="{{ route('print-queue.index', ['type'=>$type]) }}">Reset</a></div></form>
        <form method="POST" action="{{ route('print-queue.store') }}" data-selection-form>
            @csrf <input type="hidden" name="type" value="{{ $type }}">
            <div class="d-flex flex-wrap gap-3 my-3"><label><input type="checkbox" data-select-all> Pilih semua pada halaman ini</label><span class="small text-secondary" data-selected-count aria-live="polite">0 dipilih</span></div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pilih</th><th>{{ $type==='members' ? 'Nama siswa' : 'Judul buku' }}</th><th>{{ $type==='members' ? 'Kode anggota / kelas' : 'Kode inventaris / rak' }}</th></tr></thead><tbody>
            @forelse($candidates as $candidate)
            @php($alreadyQueued = $queue->contains($type==='members' ? 'member_id' : 'book_copy_id', $candidate->id))
            <tr><td>@if($alreadyQueued)<span class="badge text-bg-success">Dalam antrean</span>@else<input type="checkbox" name="ids[]" value="{{ $candidate->id }}" data-select-item aria-label="Tambahkan {{ $type==='members' ? $candidate->user->name : $candidate->inventory_code }}">@endif</td><td>{{ $type==='members' ? $candidate->user->name : $candidate->book->title }}@if($type==='members' && !$candidate->user->is_active)<span class="badge text-bg-secondary">Nonaktif</span>@endif</td><td>{{ $type==='members' ? $candidate->member_code.' · '.($candidate->schoolClass?->name ?? 'Kelas belum diisi') : $candidate->inventory_code.' · '.($candidate->shelf_location ?? 'Rak belum diisi') }}</td></tr>
            @empty<tr><td colspan="3" class="text-center py-4 text-secondary">Tidak ada hasil. Coba kata pencarian lain.</td></tr>@endforelse
            </tbody></table></div>
            <button class="btn btn-primary" data-selection-submit>Tambahkan pilihan ke antrean</button><p class="small text-secondary mt-2">Tambahkan pilihan sebelum berpindah halaman. Label memakai identitas setiap eksemplar fisik.</p>
        </form>
        {{ $candidates->fragment('add-print-items')->links('pagination::bootstrap-5') }}
    </section>
</main>
@endsection
