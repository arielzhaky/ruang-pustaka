@extends('layouts.app')

@section('title', 'Ubah Siswa | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Ubah Siswa</h1>
        </div>

        <a href="{{ route('members.index') }}" class="btn btn-outline-primary">
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="bg-white rounded-4 shadow-sm p-4">
        <form action="{{ route('members.update', $member) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                @include('members.profile-fields', ['student' => $member])
                <div class="col-md-6">
                    <label for="name" class="form-label">Nama siswa</label>
                    <input id="name" name="name" type="text"
                           class="form-control"
                           value="{{ old('name', $member->user->name) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label for="nis" class="form-label">NIS (opsional)</label>
                    <input id="nis" name="nis" type="text" maxlength="30"
                           class="form-control"
                           value="{{ old('nis', $member->nis) }}">
                </div>

                <div class="col-md-6">
                    <label for="member_code" class="form-label">
                        Kode anggota
                    </label>
                    <input id="member_code" name="member_code" type="text"
                           maxlength="30" class="form-control"
                           value="{{ old('member_code', $member->member_code) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label for="school_class_id" class="form-label">Kelas</label>
                    <select id="school_class_id" name="school_class_id"
                            class="form-select" required>
                        @foreach ($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}"
                                @selected(old('school_class_id', $member->school_class_id) == $schoolClass->id)>
                                {{ $schoolClass->name }}
                                {{ $schoolClass->is_active ? '' : '(tidak aktif)' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email untuk login</label>
                    <input id="email" name="email" type="email"
                           class="form-control"
                           value="{{ old('email', $member->user->email) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">Nomor telepon</label>
                    <input id="phone" name="phone" type="text" maxlength="30"
                           class="form-control"
                           value="{{ old('phone', $member->phone) }}">
                </div>

                <div class="col-md-6">
                    <label for="is_active" class="form-label">Status akun</label>
                    <select id="is_active" name="is_active" class="form-select">
                        <option value="1"
                            @selected(old('is_active', (int) $member->user->is_active) == 1)>
                            Aktif
                        </option>
                        <option value="0"
                            @selected(old('is_active', (int) $member->user->is_active) == 0)>
                            Tidak aktif
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">
                        Kata sandi baru (opsional)
                    </label>
                    <input id="password" name="password" type="password"
                           class="form-control" minlength="8">
                    <div class="form-text">
                        Kosongkan jika kata sandi tidak diubah.
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">
                        Ulangi kata sandi baru
                    </label>
                    <input id="password_confirmation"
                           name="password_confirmation" type="password"
                           class="form-control" minlength="8">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </section>
</main>
@endsection