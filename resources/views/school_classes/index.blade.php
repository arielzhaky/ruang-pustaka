@extends('layouts.app')

@section('title', 'Kelola Kelas | Ruang Pustaka')

@section('content')
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="brand mb-1">RUANG PUSTAKA</p>
            <h1 class="h3 fw-bold mb-0">Kelola Kelas</h1>
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

    <section class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <h2 class="h5 mb-3">Tambah Kelas</h2>

        <form action="{{ route('school-classes.store') }}" method="POST">
            @csrf

            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nama kelas</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="form-control"
                        maxlength="30"
                        placeholder="Contoh: 7A"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label for="grade_level" class="form-label">Tingkat</label>
                    <select id="grade_level" name="grade_level"
                            class="form-select" required>
                        <option value="">Pilih tingkat</option>
                        @foreach ([7, 8, 9] as $grade)
                            <option value="{{ $grade }}"
                                @selected(old('grade_level') == $grade)>
                                Kelas {{ $grade }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        Tambah Kelas
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="h5 mb-3">Daftar Kelas</h2>

        @forelse ($classes as $schoolClass)
            <form
                action="{{ route('school-classes.update', $schoolClass) }}"
                method="POST"
                class="border rounded-3 p-3 mb-3"
            >
                @csrf
                @method('PUT')

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Nama kelas</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="{{ $schoolClass->name }}"
                            maxlength="30"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tingkat</label>
                        <select name="grade_level" class="form-select" required>
                            @foreach ([7, 8, 9] as $grade)
                                <option value="{{ $grade }}"
                                    @selected((int) $schoolClass->grade_level === $grade)>
                                    Kelas {{ $grade }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" @selected($schoolClass->is_active)>
                                Aktif
                            </option>
                            <option value="0" @selected(!$schoolClass->is_active)>
                                Tidak aktif
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
            <form
    action="{{ route('school-classes.destroy', $schoolClass) }}"
    method="POST"
    class="mb-3 text-end"
    onsubmit="return confirm('Yakin ingin menghapus kelas ini?')"
>
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-sm btn-outline-danger">
        Hapus Kelas
    </button>
</form>
        @empty
            <p class="text-secondary mb-0">Belum ada kelas.</p>
        @endforelse
    </section>
</main>
@endsection