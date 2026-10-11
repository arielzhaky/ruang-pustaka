# Pencarian dan filter siswa / peminjaman

Tersedia untuk petugas melalui Kelola Siswa dan Peminjaman. Tidak memerlukan migration atau perubahan data lama.

## Kelola Siswa

- Cari sebagian nama, email, NIS, atau kode anggota.
- Gabungkan pencarian dengan kelas dan status akun aktif/tidak aktif.
- Kelas nonaktif tersedia pada filter untuk mencari siswa lama, tetapi tidak ditambahkan ke pilihan kelas pada formulir Tambah Siswa.
- Jumlah hasil menghitung seluruh siswa yang cocok, termasuk hasil di halaman berikutnya. Kartu statistik tetap mencakup seluruh siswa dan ekspor CSV tetap mengekspor seluruh siswa.

## Daftar Peminjaman

- Cari sebagian nama siswa, NIS, kode anggota, judul buku, kode peminjaman, atau kode eksemplar.
- Gabungkan pencarian dengan kelas siswa saat ini, status, dan periode tanggal peminjaman.
- Dipinjam (belum terlambat) mencakup pinjaman yang jatuh tempo hari ini. Terlambat hanya mencakup buku yang belum kembali dan batas kembalinya sudah lewat.
- Dikembalikan mencakup buku kembali baik/rusak dan pengembalian lama tanpa kondisi, tetapi tidak termasuk buku hilang. Hilang mempunyai pilihan sendiri.
- Tanggal awal dan akhir termasuk dalam periode, sampai akhir hari tanggal terakhir. Salah satu tanggal boleh dikosongkan. Periode berdasarkan tanggal pinjam, bukan tanggal pengembalian; status berdasarkan keadaan saat halaman dibuka.
- Buku/eksemplar arsip dan siswa nonaktif tetap tampil pada riwayat. Siswa tanpa kelas tetap tampil ketika Semua kelas dipilih.
- Jumlah hasil menghitung eksemplar, bukan transaksi. Nama siswa dilengkapi kode anggota, NIS, dan kelas untuk memudahkan identifikasi.

Kedua daftar menampilkan 10 hasil per halaman dengan urutan stabil. Filter tetap terbawa melalui pagination. Pencarian baru kembali ke halaman pertama; Reset filter menghapus semua pilihan. Pembatasan akses petugas tetap berlaku.

## Pengujian

`tests/Feature/StaffSearchFiltersTest.php` memeriksa pencarian setiap identitas, kombinasi filter, batas tanggal, status, data lama, pagination, input tidak valid, escaping, dan pembatasan akses.
