@extends('layouts.app')
@section('title', 'Daftar Pengunjung | Ruang Pustaka')
@section('content')
<main class="container py-5">
    <p class="page-eyebrow">PERPUSTAKAAN / KUNJUNGAN</p>
    <h1>Daftar pengunjung</h1>
    <p class="page-intro">Catat kedatangan siswa dan lihat aktivitas perpustakaan dari hari ke hari.</p>
    @include('shared.operation-messages')
    <details class="bg-white border rounded p-4 mb-4" @if($errors->any() || request()->filled('q')) open @endif>
        <summary class="fw-semibold">Catat pengunjung hari ini · {{ today()->translatedFormat('d F Y') }}</summary>
        <div class="mt-4">
            @include('shared.barcode-scanner', ['scannerId'=>'visit', 'scanMode'=>'visit', 'scanTitle'=>'Pindai kartu siswa untuk memilih pengunjung'])
            <form method="GET" action="{{ route('visits.index') }}" class="d-flex gap-2 mb-3">
                <input class="form-control" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari nama, NIS, atau kode anggota" aria-label="Cari siswa untuk pencatatan manual">
                <button class="btn btn-outline-primary">Cari siswa</button>
            </form>
            <form method="POST" action="{{ route('visits.store') }}" class="row g-3">
                @csrf
                <div class="col-md-8"><label for="visit-member-id" class="form-label">Siswa yang datang</label><select id="visit-member-id" class="form-select" name="member_id" required><option value="">Pilih siswa atau scan kartu</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>{{ $member->user->name }} · {{ $member->member_code }} · {{ $member->schoolClass?->name ?? 'Kelas belum diisi' }}</option>@endforeach</select><p class="small text-secondary mt-2">Maksimal 100 hasil pencarian. Scan kartu bisa memilih siswa di luar hasil ini.</p></div>
                <div class="col-md-4 d-flex align-items-start pt-md-5"><button class="btn btn-primary">Catat kunjungan hari ini</button></div>
            </form>
        </div>
    </details>
    <form class="d-flex flex-wrap align-items-end gap-3 mb-4" method="GET"><div><label for="visit-date" class="form-label">Tanggal acuan rekap</label><input id="visit-date" type="date" name="date" class="form-control" min="2000-01-01" max="2100-12-31" value="{{ $date->toDateString() }}" required></div><input type="hidden" name="period" value="{{ $period }}"><button class="btn btn-primary">Tampilkan</button><a class="btn btn-outline-primary" href="{{ route('visits.index') }}">Hari ini</a></form>
    @include('visits.summary', ['visitTotals'=>$totals, 'visitDate'=>$date])
    <p class="small text-secondary mt-3">Satu siswa dihitung sekali per hari. Total bulanan/tahunan menjumlahkan kunjungan harian, sehingga siswa yang datang pada hari berbeda dihitung lagi. Klik angka untuk melihat nama siswa.</p>
    <div class="row g-4 my-2">
        <section class="col-lg-6"><div class="bg-white border rounded p-4 h-100"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h2 class="h5">Senin–Minggu</h2><div><a aria-label="Minggu sebelumnya" href="{{ route('visits.index', ['date'=>$date->subWeek()->toDateString()]) }}">← Sebelumnya</a> · <a aria-label="Minggu berikutnya" href="{{ route('visits.index', ['date'=>$date->addWeek()->toDateString()]) }}">Berikutnya →</a></div></div><div class="table-responsive"><table class="table"><thead><tr><th>Hari</th><th>Tanggal</th><th class="text-end">Pengunjung</th></tr></thead><tbody>@foreach($week as $day)<tr><td>{{ $day['date']->translatedFormat('l') }}</td><td>{{ $day['date']->format('d/m/Y') }}</td><td class="text-end"><a class="fw-bold" aria-label="Lihat {{ $day['total'] }} pengunjung tanggal {{ $day['date']->format('d/m/Y') }}" href="{{ route('visits.index', ['date'=>$day['date']->toDateString(), 'period'=>'day']) }}#visit-details">{{ $day['total'] }}</a></td></tr>@endforeach</tbody><tfoot><tr><th colspan="2">Total minggu ini</th><th class="text-end">{{ $week->sum('total') }}</th></tr></tfoot></table></div></div></section>
        <section class="col-lg-6"><div class="bg-white border rounded p-4 h-100"><h2 class="h5 mb-3">Rekap bulanan · {{ $date->year }}</h2><div class="table-responsive"><table class="table"><thead><tr><th>Bulan</th><th class="text-end">Pengunjung</th></tr></thead><tbody>@foreach($months as $month)<tr><td>{{ $month['date']->translatedFormat('F') }}</td><td class="text-end"><a class="fw-bold" aria-label="Lihat pengunjung {{ $month['date']->translatedFormat('F Y') }}" href="{{ route('visits.index', ['date'=>$month['date']->toDateString(), 'period'=>'month']) }}#visit-details">{{ $month['total'] }}</a></td></tr>@endforeach</tbody><tfoot><tr><th>Total tahun {{ $date->year }}</th><th class="text-end"><a href="{{ route('visits.index', ['date'=>$date->toDateString(), 'period'=>'year']) }}#visit-details">{{ $totals['year'] }}</a></th></tr></tfoot></table></div></div></section>
    </div>
    <section id="visit-details" class="bg-white border rounded p-4 mt-4" style="scroll-margin-top:25px">
        <h2 class="h5">Detail pengunjung · {{ $start->format('d/m/Y') }}{{ $end->ne($start) ? ' – '.$end->format('d/m/Y') : '' }}</h2><p class="text-secondary">{{ $visits->total() }} kunjungan dalam periode ini.</p>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>No.</th><th>Siswa</th><th>Kode anggota</th><th>Kelas</th><th>Waktu datang (WIB)</th><th>Tindakan</th></tr></thead><tbody>@forelse($visits as $visit)<tr><td>{{ $visits->firstItem()+$loop->index }}</td><td>{{ $visit->student_name }}</td><td>{{ $visit->member_code }}</td><td>{{ $visit->class_name ?? '—' }}</td><td>{{ $visit->visited_at->format('d/m/Y H:i') }}</td><td><form method="POST" action="{{ route('visits.destroy', $visit) }}" onsubmit="return confirm('Hapus catatan kunjungan yang keliru ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus catatan</button></form></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">Belum ada pengunjung tercatat pada periode ini.</td></tr>@endforelse</tbody></table></div>
        {{ $visits->links('pagination::bootstrap-5') }}
    </section>
</main>
@endsection
