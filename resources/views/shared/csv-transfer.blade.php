<details class="panel-toggle" @if ($errors->has('file') || $errors->has('covers') || $errors->has('covers.*')) open @endif>
    <summary>Impor / Ekspor {{ $kind === 'members' ? 'Siswa' : 'Buku' }} <span class="text-secondary fw-normal ms-2">CSV</span></summary>
    <div class="panel-toggle-body">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route($kind.'.export') }}" class="btn btn-outline-success">Ekspor CSV</a>
        <a href="{{ route($kind.'.template') }}" class="btn btn-outline-primary">Unduh Template CSV</a>
    </div>
    <p class="small text-secondary">CSV UTF-8, pemisah koma atau titik koma, maksimal 2 MB / 1000 baris. Impor format sekolah memperbarui data dengan kode yang sama, tanpa menggandakan data. Baris salah membatalkan seluruh impor. Format lama tetap diterima.</p>
    @if ($kind === 'members')
        <p class="small text-secondary">Template mengikuti 19 kolom ekspor Senayan. Wajib isi <code>member_id</code> dan <code>member_name</code>. Gender: 1 laki-laki, 0 perempuan. Email, tanggal lahir, dan data lain boleh kosong. Siswa tanpa email dapat memakai QR kartu; lengkapi kelas melalui Ubah Siswa. PIN dan password lama tidak dipakai atau diekspor. Gunakan format teks untuk ID agar nol di awal tetap tersimpan.</p>
    @else
        <p class="small text-secondary">Template mengikuti 10 kolom data sekolah: kode_buku, judul, pengarang, penerbit, tahun_terbit, kategori, isbn, jumlah_eksemplar, tersedia, rak. Kategori baru otomatis ditambahkan. Jumlah eksemplar wajib diisi; tersedia kosong berarti semua stok siap dipinjam. Stok yang belum tersedia ditandai untuk diperiksa petugas, tanpa membuat transaksi pinjaman fiktif. Impor ulang tidak menghapus stok atau menimpa transaksi. Kode lain dengan judul dan penulis sama digabung sebagai alias, stok tidak dijumlahkan.</p>
        <p class="small text-secondary">Tambahkan cover JPG satu buku melalui tombol <strong>Ubah / Cover</strong>, termasuk setelah buku selesai diinput. Untuk format CSV lama, kolom opsional <code>cover</code> dan unggahan di bawah tetap didukung. Ekspor mengikuti format sekolah tanpa gambar; gunakan format teks untuk ISBN dan kode buku.</p>
    @endif
    <form action="{{ route($kind.'.import') }}" method="POST" enctype="multipart/form-data"
          onsubmit="return confirm('Impor dan perbarui data sesuai kode dalam CSV ini?')" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-9">
            <label for="csv-file" class="form-label">Pilih file CSV</label>
            <input type="file" id="csv-file" name="file" accept=".csv,text/csv" class="form-control" required>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-primary">Impor CSV</button></div>
        @if ($kind === 'books')
            <div class="col-12">
                <label for="csv-covers" class="form-label">Pilih cover JPG untuk impor (opsional, bisa beberapa file)</label>
                <input type="file" id="csv-covers" name="covers[]" accept=".jpg,.jpeg,image/jpeg" multiple class="form-control" aria-describedby="csv-covers-help">
                <div id="csv-covers-help" class="form-text">Maksimal 10 gambar, masing-masing 2 MB / 3000 x 3000 piksel; total gambar maksimal 5 MB. Nama file harus sesuai kolom cover dan tidak boleh sama. Setelah gagal impor, pilih kembali CSV dan gambarnya.</div>
            </div>
        @endif
    </form>
</div>
</details>
