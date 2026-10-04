# Koleksi referensi SMPN 26 TANGERANG

Nama sekolah disimpan di `config/library.php` dan ditampilkan pada beranda, dashboard, serta identitas perpustakaan di sidebar.

## Sumber koleksi

20 judul buku siswa kelas VII–IX diambil dari [SIBI, Pusat Perbukuan Kemendikdasmen](https://buku.kemendikdasmen.go.id/), mencakup Bahasa Indonesia, Bahasa Inggris, Matematika, IPA, IPS, Informatika, PJOK, serta Koding dan Kecerdasan Artifisial. Metadata dan cover diakses pada 2 Oktober 2026 melalui katalog publik yang digunakan situs resmi.

Daftar judul, ISBN, penulis, penerbit, URL halaman resmi, URL cover, dan checksum cover tersimpan di `database/data/reference-books/catalog.json`. Cover asli disertakan di direktori `covers` di sebelahnya. Host lama `static-sc.cloudapp.web.id` tidak melayani sebagian gambar; cover tersebut diambil dari host publik `static.sc.cloudapp.web.id` yang juga digunakan katalog SIBI. Cover ditampilkan utuh, dengan bingkai dan bayangan melalui CSS.

Tahun terbit dikosongkan karena nilai tanggal pada katalog belum diverifikasi sebagai tahun cetak. Harga penggantian juga tidak diisi. Petugas dapat melengkapi keduanya dari buku fisik. Deskripsi singkat disusun berdasarkan kelas dan mata pelajaran; isi buku/PDF tidak disalin ke aplikasi.

## Impor ke database

```sh
php artisan migrate
php artisan library:import-reference-books --dry-run
php artisan library:import-reference-books
```

Impor menggunakan berkas lokal sehingga tidak bergantung pada jaringan saat dijalankan. Cover diperiksa dengan checksum dan format gambar sebelum disimpan. Judul yang sudah ada menurut ID sumber, ISBN (tanpa tanda pemisah), atau pasangan judul dan penulis dilewati. Data lama, cover unggahan, dan buku yang diarsipkan tidak ditimpa. Kegagalan penyimpanan membatalkan transaksi dan menghapus cover yang baru disalin.

Koleksi ini **tidak membuat stok fisik**. Petugas menambahkan eksemplar melalui Data Buku ketika bukunya memang tersedia di perpustakaan. Tombol **Baca di SIBI** pada kartu beranda dan detail buku membuka halaman resmi di tab baru. Buku Populer tetap dihitung dari transaksi peminjaman perpustakaan.

Setelah dipindah ke komputer/server lain, jalankan `php artisan storage:link` jika tautan `public/storage` belum tersedia.
