# Tahap 2: aturan peminjaman

## Hasil

- Satu transaksi dapat berisi beberapa eksemplar yang dipilih dengan checkbox.
- Maksimal 3 eksemplar aktif per anggota secara default. Buku terlambat yang belum kembali tetap dihitung.
- Jatuh tempo default 7 hari kalender dari tanggal pinjam, berdasarkan zona waktu aplikasi. Petugas boleh menyesuaikan tanggal, minimal hari ini. Jika tanggal dikosongkan, server menghitung default kembali.
- Eksemplar duplikat, tidak ditemukan, sedang dipinjam, rusak, hilang, atau diarsipkan ditolak. Judul buku yang diarsipkan juga ditolak.
- Seluruh pilihan harus valid sebelum transaksi disimpan. Kegagalan penyimpanan membatalkan seluruh transaksi.
- Pengembalian tetap per item. Mengembalikan satu item membebaskan satu kuota, sementara item lainnya tetap aktif.
- Pengembalian kedua untuk item yang sama ditolak.

Tidak diperlukan migration baru: tabel loans dan loan_items yang sudah ada mendukung beberapa item per transaksi.

## Pengaturan

Tambahkan ke `.env` bila ingin mengganti default:

```dotenv
LIBRARY_MAX_ACTIVE_LOANS=3
LIBRARY_LOAN_DAYS=7
```

Nilai dibaca oleh `config/library.php` dan dibatasi minimal 1. Setelah mengubah konfigurasi yang sebelumnya di-cache, jalankan:

```sh
php artisan config:clear
```

Perubahan durasi berlaku untuk peminjaman baru. Jatuh tempo transaksi lama tidak diubah. Menurunkan kuota tidak menghapus pinjaman yang sudah ada; peminjaman baru ditolak sampai jumlah aktif cukup rendah.

## Konsistensi transaksi

Proses peminjaman mengunci anggota sebelum membaca jumlah item aktif. Dengan demikian, dua petugas yang melayani anggota sama harus bergantian memeriksa kuota. Eksemplar diproses berdasarkan urutan ID, lalu judul buku dikunci dan status pinjaman diperiksa ulang menggunakan locking read. Ini menjaga pemeriksaan memakai data terbaru setelah menunggu transaksi lain.

Pengembalian juga mengunci anggota sebelum eksemplar dan item. Transaksi memakai maksimal tiga percobaan ketika terjadi deadlock yang dikenali Laravel. Pengujian penguncian dilakukan pada MySQL; SQLite tidak membuktikan perilaku penguncian baris MySQL.

## Tes otomatis

```sh
php artisan test
```

Konfigurasi `phpunit.xml` menggunakan SQLite dalam memori. Tes mencakup beberapa item dalam satu transaksi, batas pinjaman termasuk keterlambatan, durasi dari konfigurasi, tanggal khusus, pengembalian sebagian/ganda, anggota nonaktif, eksemplar tidak tersedia, input tidak valid, serta rollback saat insert item kedua gagal.

Tes MySQL dengan dua proses terpisah:

```sh
php tests/Support/mysql_concurrency.php
```

Script menggunakan koneksi MySQL lokal dari konfigurasi aplikasi untuk membuat database acak berawalan `rp_test_`. Pengguna MySQL memerlukan izin membuat dan menghapus database tersebut. Migration dan data pengujian hanya dijalankan pada database baru ini; script menghapusnya dalam blok `finally` setelah selesai. Jangan menghentikan proses secara paksa agar pembersihan dapat berjalan.

Dua skenario yang diuji:

1. Dua anggota mencoba meminjam eksemplar sama: satu diterima, satu ditolak.
2. Anggota sudah punya dua pinjaman aktif, lalu dua petugas masing-masing menambahkan satu: satu diterima, satu ditolak, total tetap tiga.

## Pemeriksaan manual

1. Login sebagai petugas, buka **Catat Peminjaman**.
2. Pilih anggota aktif tanpa pinjaman, lalu centang tiga eksemplar yang tersedia.
3. Pastikan tanggal otomatis tujuh hari ke depan, lalu simpan.
4. Di daftar peminjaman, pastikan tiga item memiliki kode transaksi yang sama.
5. Coba pinjamkan eksemplar keempat kepada anggota tersebut: harus ditolak.
6. Kembalikan satu item: dua item lainnya tetap dipinjam.
7. Catat peminjaman satu eksemplar baru: sekarang diperbolehkan.

## Batas tahap ini

Belum mencakup pengarsipan melalui antarmuka, filter periode/cetak laporan, detail buku, atau perubahan desain dashboard. Data tanggal/jam lama dari sebelum perubahan zona waktu pada tahap pertama belum dikonversi.
