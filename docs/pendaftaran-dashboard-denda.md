# Pendaftaran siswa, dashboard petugas, dan denda

## Cara menggunakan

1. Pada halaman login, pilih **Daftar sebagai siswa**. Isi nama, email, jenis kelamin, usia, kelas, dan password. NIS dan telepon opsional. Petugas perlu menyiapkan kelas aktif terlebih dahulu.
2. Pendaftaran membuat akun siswa aktif beserta profil anggota dan langsung membuka dashboard. Kode anggota dibuat otomatis. Akun petugas tetap dibuat oleh pengelola, bukan melalui pendaftaran siswa.
3. Dashboard petugas menampilkan enam buku terbaru beserta cover dan stok tersedia. Klik **Lihat Semua Buku** untuk mencari berdasarkan judul, penulis, ISBN, kategori, dan ketersediaan.
4. Isi **Harga buku / biaya penggantian** pada Tambah/Ubah Buku. Kolom CSV opsional `harga_buku` juga tersedia, dalam rupiah bulat tanpa tanda titik. Template CSV lama tetap diterima.
5. Pada Daftar Peminjaman, pilih kondisi **baik**, **rusak**, atau **Buku hilang**, lalu **Simpan kondisi**. Sistem menghitung nominal dan mencatat petugas serta waktu penyelesaian.
6. Denda dan penggantian terlihat pada daftar peminjaman, dashboard siswa, riwayat siswa, laporan, cetak, dan PDF. Filter **Buku hilang** tersedia pada laporan.

## Aturan biaya

- Keterlambatan: **Rp2.000 per eksemplar per hari**, tanpa batas maksimum yang ditentukan. Penghitungan memakai selisih tanggal kalender Asia/Jakarta; kembali pada hari jatuh tempo masih Rp0.
- Pinjaman aktif lama ikut memakai tarif Rp2.000 ketika fitur dipasang. Pengembalian yang sudah selesai sebelum fitur ini tidak dikenai perhitungan baru dan ditandai **Denda belum dicatat**.
- Tarif disimpan pada masing-masing pinjaman. Perubahan konfigurasi `LIBRARY_FINE_DAILY_RATE` memengaruhi pinjaman baru; tarif pinjaman yang sudah ada tetap.
- Untuk pinjaman aktif, nominal merupakan perkiraan sampai tanggal hari ini. Saat pengembalian, nominal disimpan dan berhenti bertambah.
- Buku hilang: **hanya mengganti harga buku**, tanpa tambahan denda keterlambatan. Harga diambil dari data buku saat kehilangan dicatat, lalu nominal tersebut disimpan dalam riwayat.
- Harga buku harus diisi sebelum kehilangan dapat dicatat. Sistem menolak harga kosong agar kehilangan tidak tercatat dengan biaya Rp0 secara tidak sengaja.
- Kehilangan menutup pinjaman dan membebaskan kuota siswa; kondisi eksemplar menjadi `hilang`, sehingga tidak tersedia untuk dipinjam. `returned_at` menyimpan waktu penutupan transaksi; tampilan membedakan kehilangan dari pengembalian.
- Ringkasan laporan **Selesai (kembali / hilang)** mencakup kedua jenis penutupan. Status tiap baris tetap ditampilkan terpisah.
- Nominal adalah pencatatan biaya, belum mencakup penerimaan uang atau status pembayaran. Jangan menafsirkannya sebagai pendapatan atau tagihan yang sudah dibayar.

## Daftar fitur

| Fitur | Lokasi |
| --- | --- |
| Login dan role pengguna | Login Siswa / Petugas–Admin; akses pengelolaan dibatasi untuk petugas |
| Data buku, kategori, dan cover | Kelola Buku, Kelola Kategori, katalog dan detail buku |
| Data anggota dan kelas | Kelola Siswa, Kelola Kelas, pendaftaran siswa mandiri |
| Peminjaman dan pengembalian | Catat Peminjaman, Daftar Peminjaman |
| Ketersediaan/stok | Eksemplar, katalog, daftar buku, dashboard petugas |
| Riwayat transaksi | Daftar Peminjaman dan Peminjaman Saya |
| Keterlambatan dan denda | Dashboard siswa, peminjaman, riwayat, laporan |
| Laporan | Filter tanggal/status, cetak, unduh PDF |
| Statistik | Dashboard petugas dan siswa |
| Pencarian/filter buku | Katalog siswa dan Kelola Buku |
| Impor/ekspor | CSV siswa dan buku |

## Pemasangan dan validasi

```sh
php artisan migrate
php artisan test
```

Migration menambahkan harga buku, tarif denda per item, nominal denda, dan nominal penggantian. Kondisi penyelesaian menerima `hilang`. Tidak ada data buku, siswa, atau pinjaman yang dihapus.

`RegistrationAndStaffDashboardTest` memeriksa pendaftaran tanpa NIS, penolakan kelas nonaktif dan data duplikat, pembatasan role, statistik stok, serta filter buku. `LoanChargesTest` memeriksa pergantian hari, tarif tersimpan, nominal final, kehilangan tanpa denda tambahan, penolakan harga kosong, pembatasan akses siswa, dan harga pada CSV.

Uji MySQL terpisah tersedia dengan `php tests/Support/mysql_concurrency.php`. Skrip memakai database acak sementara dan menghapusnya setelah selesai; database aplikasi tidak dipakai untuk fixture tes.
