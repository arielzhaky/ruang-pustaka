@extends('layouts.app')

@section('title', 'Kelola Siswa | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Kelola Siswa</h1>
            <p class="page-intro">Kelola akun dan profil anggota perpustakaan sekolah.</p>
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

    @if (session('error'))
    <div class="alert alert-danger" role="alert">
        {{ session('error') }}
    </div>
@endif
    <div class="member-stats">
        <div><span>Seluruh anggota</span><strong>{{ $memberStats['total'] }} siswa</strong><small>Profil siswa yang terdaftar</small></div>
        <div><span>Siswa aktif</span><strong>{{ $memberStats['active'] }} aktif</strong><small>Dapat mengakses perpustakaan</small></div>
        <div><span>Siswa nonaktif</span><strong>{{ $memberStats['inactive'] }} nonaktif</strong><small>Riwayat tetap tersimpan</small></div>
    </div>
    @include('shared.csv-transfer', ['kind' => 'members'])

    <details class="panel-toggle" id="add-student" @if ($errors->any() && ! $errors->has('file')) open @endif>
        <summary>+ Tambah Siswa <span class="text-secondary fw-normal ms-2">Buat akun dan profil anggota</span></summary>
        <div class="panel-toggle-body">

        <form action="{{ route('members.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                @include('members.profile-fields', ['student' => null])
                <div class="col-md-6">
                    <label for="name" class="form-label">Nama siswa</label>
                    <input id="name" name="name" type="text"
                           class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="col-md-6">
                    <label for="nis" class="form-label">NIS (opsional)</label>
                    <input id="nis" name="nis" type="text" maxlength="30"
                           class="form-control" value="{{ old('nis') }}">
                </div>

                <div class="col-md-6">
                    <label for="member_code" class="form-label">
                        Kode anggota
                    </label>
                    <input id="member_code" name="member_code" type="text"
                           maxlength="30" class="form-control"
                           value="{{ old('member_code') }}" required>
                </div>

                <div class="col-md-6">
                    <label for="school_class_id" class="form-label">Kelas</label>
                    <select id="school_class_id" name="school_class_id"
                            class="form-select" required>
                        <option value="">Pilih kelas</option>
                        @foreach ($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}"
                                @selected(old('school_class_id') == $schoolClass->id)>
                                {{ $schoolClass->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email untuk login</label>
                    <input id="email" name="email" type="email"
                           class="form-control" value="{{ old('email') }}" required>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">
                        Nomor telepon (opsional)
                    </label>
                    <input id="phone" name="phone" type="text" maxlength="30"
                           class="form-control" value="{{ old('phone') }}">
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">
                        Kata sandi (minimal 8 karakter)
                    </label>
                    <input id="password" name="password" type="password"
                           class="form-control" minlength="8" required>
                </div>

                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">
                        Ulangi kata sandi
                    </label>
                    <input id="password_confirmation"
                           name="password_confirmation" type="password"
                           class="form-control" minlength="8" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        Tambah Siswa
                    </button>
                </div>
            </div>
        </form>
        </div>
    </details>

    <section class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="h5 mb-3">Daftar Siswa</h2>

        <div class="table-responsive">
    <table class="table align-middle">
        <thead><tr>
            <th>No.</th><th>Anggota</th><th>Kelas</th><th>Profil</th><th>Kontak</th><th>Status</th><th>Tindakan</th>
        </tr></thead>
        <tbody>
            @forelse ($members as $member)
                <tr>
                    <td>{{ $members->firstItem() + $loop->index }}</td>
                    <td><div class="d-flex align-items-center gap-2 flex-nowrap">
                        <span class="user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->user?->name ?? 'S', 0, 1)) }}</span>
                        <div><strong>{{ $member->user?->name ?? '—' }}</strong><small class="d-block text-secondary">{{ $member->member_code }}</small><small class="text-secondary">NIS: {{ $member->nis ?: '—' }}</small></div>
                    </div></td>
                    <td>{{ $member->schoolClass?->name ?? '—' }}</td>
                    <td>{{ $member->gender ? ucfirst($member->gender) : 'Belum diisi' }}<small class="d-block text-secondary">{{ $member->age ? $member->age.' tahun' : 'Usia belum diisi' }}</small></td>
                    <td>{{ $member->user?->email ?? '—' }}<small class="d-block text-secondary">{{ $member->phone ?: 'Telepon belum diisi' }}</small></td>
                    <td><span class="badge {{ $member->user?->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $member->user?->is_active ? 'Aktif' : 'Tidak aktif' }}</span></td>
                    <td><div class="d-flex gap-2">
                        <a href="{{ route('members.edit', $member) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                        <form action="{{ route('members.destroy', $member) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus siswa ini beserta akun loginnya?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada siswa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

        {{ $members->links('pagination::bootstrap-5') }}
    </section>
</main>
@endsection