# Pengunjung dan antrean cetak

Kedua menu tersedia untuk petugas/admin. Siswa tidak dapat membuka data pengunjung maupun antrean cetak petugas.

## Mencatat kunjungan

1. Buka **Daftar Pengunjung → Catat pengunjung hari ini**.
2. Pindai kartu siswa dengan scanner USB/kamera/gambar, atau cari dan pilih siswa secara manual.
3. Periksa nama, lalu klik **Catat kunjungan hari ini**.

Pencatatan memakai tanggal dan waktu aplikasi (Asia/Jakarta). Satu siswa dihitung sekali per hari. Scan atau kirim formulir berulang tidak menambah angka. Siswa yang datang pada hari berbeda dihitung lagi; total bulanan/tahunan merupakan jumlah kunjungan harian, bukan jumlah siswa unik sepanjang tahun. Login siswa tidak mencatat kunjungan.

Ringkasan angka tersedia pada dashboard petugas. Klik angka harian, bulanan, atau tahunan untuk membuka detail nama siswa, kode anggota, kelas, dan waktu datang. Pada halaman pengunjung, tanggal acuan menentukan minggu Senin–Minggu dan tahun rekap Januari–Desember. Angka pada tabel juga bisa diklik. Pilih tanggal pada bulan/tahun lain untuk melihat riwayat periode tersebut.

Nama, kode anggota, dan kelas disimpan sesuai keadaan saat berkunjung. Perubahan profil atau penghapusan akun tidak menghilangkan rekap lama. Petugas dapat menghapus catatan yang keliru melalui tombol **Hapus catatan**, dengan konfirmasi. Fitur mulai mengumpulkan data ketika kunjungan dicatat; riwayat sebelum pencatatan tidak dibuat otomatis.

## Menyiapkan PDF cetak

1. Buka **Antrean Cetak**, atau tautan **Pilih beberapa kartu / Download PDF** di Kelola Siswa dan **Pilih beberapa label / Download PDF** di Data Buku → Eksemplar.
2. Pilih jenis **Kartu siswa** atau **Label eksemplar buku**, lalu cari nama, judul, atau kode.
3. Centang item yang diinginkan dan klik **Tambahkan pilihan ke antrean** sebelum berpindah halaman. Pilihan dari beberapa halaman dan jenis dapat digabungkan.
4. Pada daftar antrean, centang semua atau sebagian item, misalnya 7 siswa dan 5 eksemplar. Klik **Download PDF yang dipilih**.
5. Buka PDF dan cetak di kertas A4 dengan skala **100% / ukuran sebenarnya**. Potong mengikuti garis tepi.

Kartu siswa berukuran 85,6 × 54 mm, maksimal 8 kartu per lembar. Label eksemplar berukuran 90 × 42 mm, maksimal 10 label per lembar. Kartu dan label ditempatkan pada halaman terpisah dalam satu PDF. Setiap kartu/label menyertakan barcode tetap yang dapat dipindai melalui menu Scan Barcode.

Antrean disimpan khusus untuk akun petugas yang menambahkannya, maksimal 200 item. Item yang sama tidak masuk dua kali. Unduhan tidak menghapus antrean, agar PDF bisa diunduh ulang. Setelah selesai, centang item dan klik **Hapus pilihan dari antrean**. Ini hanya menghapus pilihan cetak, bukan data siswa atau buku.

Pemilih label hanya menawarkan eksemplar dan judul yang belum diarsipkan. Jika data diarsipkan setelah masuk antrean, label PDF menampilkan NONAKTIF. Kartu siswa nonaktif juga ditandai. Item yang datanya dihapus akan dikeluarkan melalui relasi database.

## Implementasi dan verifikasi

- Migrasi: `2026_10_09_000001_create_visits_and_print_queue.php`, menambahkan `library_visits` dan `print_queue_items`.
- Unduhan dibuat dengan Dompdf yang sudah tersedia; tidak memerlukan layanan berbayar atau koneksi layanan PDF luar.
- Tes: `php artisan test --filter=VisitorAndPrintQueueTest`.
- Alur browser diuji dengan data terpisah dari database utama: scan/manual pengunjung, pencegahan duplikat, pilihan antrean, unduhan gabungan dan sebagian, serta tampilan ponsel.
- Contoh PDF 7 kartu dan 5 label diperiksa secara visual; seluruh 12 barcode dari hasil render PDF berhasil dibaca kembali dengan decoder Code 128.
