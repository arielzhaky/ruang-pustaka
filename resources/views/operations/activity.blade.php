@extends('layouts.app')
@section('title', 'Aktivitas Petugas | Ruang Pustaka')
@section('content')
<main class="container py-5"><p class="page-eyebrow">RUANG PUSTAKA / RIWAYAT</p><h1>Aktivitas petugas</h1><p class="page-intro">Catatan tindakan berhasil sejak fitur ini diaktifkan. Kata sandi dan isi formulir pribadi tidak disimpan dalam riwayat ini.</p>
<form method="GET" class="d-flex gap-2 align-items-end mb-4"><div><label for="activity-search" class="form-label">Cari petugas atau tindakan</label><input id="activity-search" name="q" maxlength="100" value="{{ request('q') }}" class="form-control"></div><button class="btn btn-primary">Cari</button></form>
<div class="bg-white p-4 border rounded-3 table-responsive"><table class="table"><thead><tr><th>Waktu WIB</th><th>Petugas</th><th>Tindakan</th><th>Referensi data</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $log->actor_name }}</td><td>{{ \App\Support\ActivityLabels::label($log->action) }}</td><td>@forelse($log->targets ?? [] as $key=>$id)<span>{{ $key }} #{{ $id }}</span> @empty—@endforelse</td></tr>@empty<tr><td colspan="4" class="text-center py-4">Belum ada aktivitas sesuai pencarian.</td></tr>@endforelse
</tbody></table>{{ $logs->links('pagination::bootstrap-5') }}</div></main>
@endsection
