# Laporan peminjaman

## Cara menggunakan

1. Login sebagai petugas dan pilih **Laporan Peminjaman** di dashboard.
2. Isi tanggal pinjam mulai dan/atau sampai, lalu pilih status.
3. Klik **Tampilkan** untuk menerapkan filter. Tombol cetak memakai filter yang sudah diterapkan.
4. Klik **Cetak Laporan** untuk membuka seluruh hasil di tab baru.
5. Klik **Cetak / Simpan PDF**. Pilih printer atau tujuan Simpan sebagai PDF di dialog browser. Tata letak cetak menggunakan A4 landscape.

Tidak diperlukan migration tambahan.

## Arti filter dan ringkasan

- Periode berdasarkan tanggal peminjaman, bukan tanggal pengembalian. Kedua tanggal termasuk dalam rentang; tanggal akhir mencakup seluruh hari sampai sebelum tengah malam berikutnya.
- Boleh mengisi salah satu tanggal saja. Tanpa tanggal berarti seluruh periode.
- Status dipinjam berarti belum kembali dan belum terlambat, termasuk jatuh tempo hari ini.
- Status terlambat berarti belum kembali dan jatuh tempo sudah lewat.
- Status dikembalikan mencakup pengembalian tepat waktu maupun terlambat.
- Ringkasan menghitung eksemplar dalam seluruh hasil filter. Satu transaksi dengan tiga eksemplar dihitung tiga.
- Status menggambarkan keadaan saat laporan dibuat, bukan keadaan historis pada akhir periode.
- Keterlambatan item yang sudah kembali berhenti pada tanggal pengembalian.
- Buku/eksemplar arsip dan anggota nonaktif tetap ada dalam laporan riwayatnya.

## Pengujian

```sh
php artisan test
```

`LoanReportTest` memeriksa batas tanggal, filter satu sisi, kombinasi status/periode, ringkasan, hasil kosong, data arsip, validasi, pembatasan akses, escaping, dan pencetakan seluruh hasil. Uji lebih dari sepuluh item memastikan halaman cetak tidak mengikuti pagination tabel.

Preview PDF di Chrome telah diperiksa menggunakan 40 item contoh: item terakhir ikut tercetak, header tabel berulang, dan tombol navigasi tersembunyi saat cetak. Perbedaan printer dan pengaturan browser tetap dapat memengaruhi pembagian halaman.
