# Desain halaman dan panduan peminjaman

Login dan halaman aplikasi memakai warna yang selaras dengan beranda: navy, ungu, dan mint. Judul tetap memakai huruf serif, dengan latar terang dan garis pemisah tipis. Sidebar menampilkan menu sesuai peran; pada ponsel menu dibuka lewat tombol Menu. Menu dapat ditutup dengan Escape atau klik di luar.

## Warna antarmuka

- Panel cerita login dan sambutan dashboard memakai gradasi navy. Teks mint memberi aksen pada judul login dan identitas sekolah.
- Tombol utama, pilihan peran login, menu aktif, tautan, serta fokus formulir memakai ungu. Tombol utama pada panel sambutan memakai mint.
- Statistik siswa dan petugas memakai kartu dengan warna lembut dan ikon: ungu untuk koleksi/pinjaman, biru untuk anggota, mint untuk kuota/ketersediaan, serta kuning dan merah untuk jatuh tempo/keterlambatan.
- Status transaksi tetap disertai teks agar bisa dibedakan tanpa mengandalkan warna saja.
- Warna dasar diatur pada `public/css/workspace.css`; tampilan khusus login berada di `public/css/login.css`. Tema juga diterapkan pada pendaftaran siswa.

## Navigasi dan data

- Dashboard petugas: statistik koleksi dan siswa, buku terbaru, tautan detail buku, dan ringkasan aturan.
- Kelola Siswa: statistik anggota, tabel profil, serta formulir Tambah Siswa dan impor/ekspor yang dapat dibuka. Formulir dibuka kembali jika validasi gagal.
- Katalog, data buku, kategori, kelas, eksemplar, formulir, transaksi, dan laporan menggunakan tema yang sama.
- Cetak dan PDF tetap menggunakan tata letak dokumen agar navigasi aplikasi tidak ikut tercetak.

## Detail buku

Siswa membuka tombol Detail Buku atau judul dari katalog. Petugas membuka Detail dari Data Buku atau koleksi di dashboard.

Detail memuat cover, penulis, kategori, ISBN, penerbit, tahun terbit, deskripsi, harga penggantian, jumlah tersedia, serta status dan lokasi rak setiap eksemplar. Petugas juga mendapat tautan Ubah Buku dan Kelola Eksemplar. Identitas peminjam tidak ditampilkan di halaman detail.

Rute petugas `/books/{book}` tetap memerlukan akun petugas aktif. Rute siswa `/catalog/{book}` tetap memerlukan akun siswa aktif. Buku arsip tidak dapat dibuka dari detail koleksi, tetapi riwayat pinjam tetap tersimpan.

## Aturan dan tips

Menu Panduan Peminjaman tersedia untuk kedua peran. Ringkasan juga tampil pada dashboard, katalog, detail buku, Peminjaman Saya, dan form Catat Peminjaman.

Nilai diambil dari `config/library.php`, sama seperti aturan transaksi:

- Masa pinjam default: `library.loan_days`, saat ini 7 hari.
- Batas pinjaman aktif: `library.max_active_loans`, saat ini 3 eksemplar.
- Denda harian: `library.fine_daily_rate`, saat ini Rp2.000 per eksemplar.

Panduan menjelaskan bahwa petugas dapat menyesuaikan batas kembali, pinjaman terlambat tetap memakai kuota, denda dihitung mulai hari berikutnya, dan buku hilang cukup mengganti harga buku. Tarif yang disimpan pada pinjaman lama tetap berlaku untuk pinjaman tersebut.

Tidak ada migration baru untuk perubahan ini. Data perpustakaan tidak diubah oleh pembaruan desain.

## Pemeriksaan

`BookDetailsAndRulesTest` memeriksa akses detail petugas, buku arsip, pagination dan filter kembali, aturan berdasarkan konfigurasi, menu sesuai peran, dan statistik siswa. Pemeriksaan browser dilakukan pada halaman dashboard, siswa, buku, detail, katalog, form peminjaman, dan panduan untuk ukuran desktop dan ponsel menggunakan data sementara.
