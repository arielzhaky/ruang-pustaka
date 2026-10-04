# Beranda Ruang Pustaka

Buka alamat utama aplikasi (`/`) atau pilih **Beranda** di navigasi untuk melihat halaman publik. Pengunjung dapat mencari buku berdasarkan judul, penulis, atau ISBN tanpa masuk. Detail buku tetap memerlukan akun.

## Tampilan dan perilaku

- Bagian **Buku Populer** menampilkan maksimal empat judul aktif yang paling sering dipinjam sepanjang waktu, lengkap dengan peringkat, jumlah peminjaman, dan stok saat ini. Setiap peminjaman eksemplar dihitung sekali, termasuk yang sudah kembali atau eksemplarnya kemudian diarsipkan. Jumlah yang sama diurutkan berdasarkan judul, lalu ID. Buku tanpa peminjaman tidak masuk peringkat; bila belum ada riwayat, halaman menampilkan penjelasan. Pencarian koleksi tidak mengubah peringkat ini.

- Ilustrasi pembaca, buku, dan bintang bergerak perlahan di bagian pembuka.
- Tombol **Jeda animasi** menghentikan gerakan; **Putar animasi** mengaktifkannya kembali.
- Pengaturan perangkat untuk mengurangi gerakan (`prefers-reduced-motion`) menonaktifkan animasi secara otomatis.
- Koleksi menampilkan sampul yang diunggah atau sampul pengganti berisi judul dan penulis.
- Pencarian menampilkan delapan buku per halaman. Buku yang diarsipkan tidak ditampilkan, dan stok tersedia mengikuti aturan ketersediaan eksemplar aplikasi.
- Pengunjung melihat tombol **Masuk** dan **Daftar**. Pengguna yang sudah masuk dapat menuju dashboard dan detail buku sesuai perannya.
- Panduan peminjaman mengambil durasi, batas jumlah buku, dan denda dari `config/library.php`. Buku hilang cukup diganti seharga buku.

## File utama

- `app/Http/Controllers/HomeController.php`: pencarian, pagination, dan jumlah koleksi.
- `resources/views/home.blade.php`: halaman beranda.
- `resources/views/home/illustrations.blade.php`: ilustrasi SVG.
- `public/css/home.css`: tampilan responsif dan animasi.
- `public/js/home.js`: kontrol animasi dan kemunculan kartu saat digulir.
- `tests/Feature/HomePageTest.php`: pengujian pencarian, stok, akses, validasi, dan privasi data peminjam.

Perubahan ini tidak memerlukan migrasi database atau build aset. CSS dan JavaScript dilayani langsung dari direktori `public`.
