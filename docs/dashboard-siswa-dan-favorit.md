# Dashboard siswa dan buku favorit

## Fitur

- Pencarian langsung dari dashboard mengarah ke katalog dengan kata pencarian judul atau penulis yang sama.
- **Buku Populer** menampilkan maksimal empat judul aktif, berdasarkan jumlah peminjaman seluruh eksemplar sepanjang waktu. Peminjaman yang sudah selesai tetap dihitung. Urutan seri ditentukan oleh judul lalu ID.
- **Buku Terbaru** menampilkan empat judul aktif yang terakhir ditambahkan, termasuk koleksi referensi digital.
- **Estimasi Denda Aktif** menjumlahkan keterlambatan pinjaman aktif milik siswa yang login. Perhitungan memakai tanggal Asia/Jakarta dan tarif yang tersimpan saat masing-masing pinjaman dibuat. Pengembalian dan penggantian buku hilang yang sudah selesai tidak masuk estimasi ini.
- **Kartu Anggotaku** berisi nama, sekolah, kelas, dan kode anggota. Menu Kartu Anggota menyediakan tampilan cetak; tombol Cetak / Simpan PDF membuka dialog cetak browser. Profil yang belum lengkap ditampilkan dengan penjelasan, tanpa membuat kode anggota baru.
- **Buku Favorit** dapat disimpan/dihapus dari dashboard, katalog, detail buku, atau halaman favorit. Dashboard menampilkan empat favorit terbaru; halaman favorit menampilkan 12 buku per halaman.
- Cover memakai file lokal yang sama dengan katalog, dengan sampul pengganti jika belum ada gambar. Animasi muncul singkat saat digulir dan gerakan ringan saat cover diarahkan/ditekan. Pengaturan `prefers-reduced-motion` menonaktifkan gerakan ini. Konten dan formulir tetap bisa digunakan tanpa JavaScript.

Favorit hanya menyimpan daftar bacaan. Proses peminjaman tetap dilakukan petugas. Judul yang diarsipkan disembunyikan dari daftar bacaan dan tidak dapat ditambahkan ke favorit baru. Riwayat dan pinjaman aktif tetap tersedia.

## Database dan akses

Migration `2026_10_03_000001_create_book_user_table.php` menambahkan relasi favorit. Pasangan pengguna–buku bersifat unik sehingga klik simpan berulang tidak menggandakan data. Penghapusan akun atau buku menghapus relasi favorit yang terkait.

```sh
php artisan migrate
php artisan test --compact
```

Rute favorit dan kartu anggota hanya untuk siswa aktif. Semua data pribadi diambil dari sesi login. Parameter `user_id` atau `member_id` tidak bisa dipakai untuk memilih data siswa lain. POST/DELETE favorit menggunakan CSRF dan hanya menerima tujuan kembali yang dibatasi ke dashboard, favorit, katalog, atau detail buku.

## File antarmuka

- `resources/views/dashboard/student*.blade.php`: dashboard, ringkasan, serta koleksi.
- `resources/views/dashboard/book-card.blade.php`: kartu buku bersama.
- `resources/views/shared/favorite-button.blade.php`: formulir favorit.
- `resources/views/favorites/index.blade.php`: seluruh buku favorit.
- `resources/views/members/card.blade.php`: halaman cetak kartu pribadi.
- `public/css/student-dashboard.css` dan `public/js/student-books.js`: tampilan responsif, animasi singkat, dan tombol cetak.
