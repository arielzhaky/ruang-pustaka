<details class="panel-toggle" @if ($errors->has('file')) open @endif>
    <summary>Impor / Ekspor {{ $kind === 'members' ? 'Siswa' : 'Buku' }} <span class="text-secondary fw-normal ms-2">CSV</span></summary>
    <div class="panel-toggle-body">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route($kind.'.export') }}" class="btn btn-outline-success">Ekspor CSV</a>
        <a href="{{ route($kind.'.template') }}" class="btn btn-outline-primary">Unduh Template CSV</a>
    </div>
    <p class="small text-secondary">
        Impor menambahkan data baru. Data duplikat akan ditolak; seluruh impor dibatalkan jika ada baris yang salah.
        File CSV UTF-8, pemisah koma atau titik koma, maksimal 2 MB / 1000 baris. Pertahankan nama kolom template.
    </p>
    @if ($kind === 'members')
        <p class="small text-secondary">
            Isi nama, email, password (8–72 karakter), kode_anggota, jenis_kelamin (laki-laki/perempuan), usia, dan kelas.
            NIS dan telepon boleh kosong. Kelas harus sudah ada dan aktif. Status: aktif/nonaktif; kosong berarti aktif.
            Password tidak disertakan dalam ekspor. Gunakan format teks untuk NIS dan kode anggota agar nol di awal tetap tersimpan.
        </p>
    @else
        <p class="small text-secondary">
            Isi judul, penulis, dan kategori yang sudah tersedia. ISBN, penerbit, tahun_terbit, deskripsi, dan harga_buku boleh kosong. Harga buku diisi rupiah bulat tanpa titik untuk biaya penggantian buku hilang.
            Ekspor memuat seluruh judul yang belum diarsipkan. Cover dan eksemplar ditambahkan melalui menu Ubah / Eksemplar.
            Gunakan format teks untuk ISBN.
        </p>
    @endif
    <form action="{{ route($kind.'.import') }}" method="POST" enctype="multipart/form-data"
          onsubmit="return confirm('Impor data baru dari CSV ini?')" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-9">
            <label for="csv-file" class="form-label">Pilih file CSV</label>
            <input type="file" id="csv-file" name="file" accept=".csv,text/csv" class="form-control" required>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-primary">Impor CSV</button></div>
    </form>
</div>
</details>
