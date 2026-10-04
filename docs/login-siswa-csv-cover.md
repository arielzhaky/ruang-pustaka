# Login, profil siswa, CSV, dan cover buku

## Perubahan yang tersedia

- Login memiliki pilihan Siswa dan Petugas / Admin. Pilihan harus cocok dengan peran akun. Akun petugas lama tetap memakai peran `petugas`; tidak ada peran admin baru. Siswa dapat mendaftar mandiri melalui tautan Daftar sebagai siswa pada halaman login.
- Petugas mengisi jenis kelamin (`laki-laki` / `perempuan`), usia, dan kelas siswa. NIS boleh kosong; NIS yang diisi harus unik.
- Usia disimpan sebagai angka tahun saat input dan perlu diperbarui saat data siswa berubah. Profil lama mempunyai nilai kosong sampai dilengkapi oleh petugas.
- Kelola Siswa dan Kelola Buku menyediakan template, impor, dan ekspor CSV.
- Tambah/Ubah Buku menyediakan unggahan cover JPG, PNG, WebP maksimal 2 MB dan 3000 × 3000 piksel. Cover tampil pada katalog dan detail buku; buku tanpa cover memakai placeholder.
- Laporan menyediakan tombol Unduh PDF yang langsung mengirim file sebagai lampiran, termasuk di Safari. Tombol Cetak tetap tersedia.

## Pemasangan di perangkat lain

```sh
composer install
php artisan migrate
php artisan storage:link
```

Migration baru hanya menambahkan `gender` dan `age` yang nullable pada members. NIS sebelumnya sudah nullable. Pada workspace ini migration dan storage:link sudah dijalankan.

Cover disimpan pada `storage/app/public/covers` dengan nama acak. Cover pengganti disimpan sebelum referensi database diubah; file lama dibersihkan setelah penyimpanan berhasil. Cover tidak disimpan dalam CSV.

PDF memakai [Laravel Dompdf](https://github.com/barryvdh/laravel-dompdf), dengan akses remote, PHP, dan JavaScript dinonaktifkan pada proses unduhan. CSS PDF dibuat terpisah dari tampilan browser.

## CSV siswa

Header template:

```csv
nama,email,password,kode_anggota,jenis_kelamin,usia,kelas,nis,telepon,status
```

Wajib: nama, email unik, password 8–72 karakter, kode anggota unik, jenis kelamin, usia 1–120, nama kelas aktif yang sudah ada.

Opsional: NIS, telepon, status (`aktif` / `nonaktif`; kosong berarti aktif).

Password CSV hanya dipakai untuk membuat akun dan langsung di-hash. Ekspor tidak berisi password atau hash. Kolom password pada hasil ekspor selalu kosong.

## CSV buku

Header template:

```csv
judul,penulis,kategori,isbn,penerbit,tahun_terbit,deskripsi,harga_buku
```

Wajib: judul, penulis, nama kategori yang sudah ada.

Opsional: ISBN unik, penerbit, tahun terbit, deskripsi, harga_buku (rupiah bulat tanpa titik, 1–100.000.000). Template lama tanpa kolom harga_buku tetap dapat diimpor. Judul dan penulis yang sama dengan buku yang sudah ada ditolak saat impor. Ekspor mencakup seluruh judul yang belum diarsipkan, bukan hanya satu halaman daftar.

Impor buku membuat data judul. Tambahkan eksemplar fisik dan cover melalui halaman pengelolaan buku.

## Cara impor / ekspor

1. Login sebagai Petugas / Admin.
2. Siapkan kelas atau kategori terlebih dahulu.
3. Buka Kelola Siswa atau Kelola Buku, klik Unduh Template CSV.
4. Isi baris di bawah header. Gunakan kolom teks untuk NIS, ISBN, kode anggota, dan telepon agar nol di awal tidak dihapus Excel.
5. Simpan sebagai CSV UTF-8. Pemisah koma dan titik koma didukung, termasuk UTF-8 BOM.
6. Pilih file dan klik Impor CSV. File maksimal 2 MB dan 1000 baris data. Untuk banyak siswa, gunakan beberapa file kecil agar proses hashing password tidak melebihi batas waktu server.
7. Jika ada baris tidak valid atau duplikat, seluruh impor dibatalkan. Pesan menunjukkan nomor record CSV (header dihitung sebagai baris 1). Perbaiki file sebelum mengulangi.
8. Klik Ekspor CSV untuk mengunduh data seluruh daftar.

Impor hanya menambah data baru, bukan menimpa akun/buku yang sudah ada. Hasil ekspor tidak dapat diimpor ulang begitu saja ke database yang sama karena data tersebut akan dianggap duplikat. Nilai yang berpotensi menjadi formula spreadsheet diberi awalan tab saat ekspor.

## Cara mencoba fitur

- Login: pilih Siswa untuk akun siswa dan Petugas / Admin untuk akun petugas. Pilihan yang salah harus ditolak.
- Siswa: buat siswa tanpa NIS, isi jenis kelamin, usia, dan kelas; buka Ubah untuk memastikan data tersimpan.
- Cover: unggah cover melalui Ubah Buku, lalu login siswa dan lihat katalog serta detailnya. Uji ganti/hapus cover.
- Laporan: pilih filter dan klik Tampilkan, kemudian Unduh PDF. File mengikuti filter yang sudah diterapkan.

## Tes

```sh
php artisan test
```

`UserDataFeaturesTest` mencakup pemilihan peran, profil siswa, CSV, rollback impor, perlindungan akun petugas, unduhan CSV tanpa password, pembatasan akses, validasi gambar, pergantian cover, dan lampiran PDF.
