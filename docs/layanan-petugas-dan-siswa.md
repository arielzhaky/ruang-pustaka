# Layanan petugas dan siswa

Ditambahkan 4 Oktober 2026. Data lama tetap dipertahankan.

## Petugas

- **Peminjaman → Catat Peminjaman**: cari siswa aktif berdasarkan nama, NIS, atau kode anggota. Hasil pencarian mengisi pilihan siswa. Eksemplar yang sudah disiapkan menampilkan pemilik reservasi.
- **Siswa Terlambat**: pinjaman aktif lewat jatuh tempo, filter siswa dan kelas, jumlah hari dan estimasi denda sesuai tarif saat peminjaman.
- **Tagihan & Pembayaran**: pelunasan penuh tunai/transfer dicatat setelah uang diterima. Jumlah berasal dari biaya pengembalian/kehilangan; tidak bisa diubah lewat formulir. Klik bukti pembayaran untuk cetak atau Simpan PDF dari dialog browser. Pembayaran sebagian belum disediakan.
- **Aktivitas Petugas**: catatan tindakan web yang berhasil sejak fitur aktif, nama petugas, waktu, tindakan, ID sasaran bila tersedia. Tidak menyimpan isi formulir, sandi, atau token. Ini riwayat operasional, bukan catatan aktivitas sebelum fitur dipasang atau perubahan langsung melalui phpMyAdmin/CLI.
- **Perpanjangan**: setujui atau tolak permintaan, dengan catatan wajib untuk penolakan.
- **Reservasi Buku**: siapkan antrean paling awal ketika ada stok. Satu eksemplar ditahan selama dua hari sejak disiapkan. Klik Catat pengambilan: siswa dan kode eksemplar sudah dipilih otomatis. Periksa buku dan batas kembali, lalu simpan peminjaman untuk menyelesaikan reservasi. Tautan yang sudah kedaluwarsa, dibatalkan, atau tidak lagi bisa diproses kembali ke daftar reservasi dengan penjelasan.

## Siswa

- **Pengaturan Akun**: ubah nama, email, nomor telepon, serta kata sandi. Perubahan profil memerlukan kata sandi saat ini. NIS, kelas, peran, dan status anggota tetap ditangani petugas.
- **Peminjaman Saya**: buka Ajukan perpanjangan, isi alasan opsional (maksimal 500 karakter), lalu kirim pengajuan. Maksimal satu persetujuan per eksemplar, tambahan tujuh hari dihitung dari batas kembali semula. Batas kembali lama tetap berlaku selama menunggu. Permintaan ditolak otomatis ketika pinjaman ditutup. Catatan penolakan petugas tampil di Peminjaman Saya. Permintaan yang ditolak dapat diajukan ulang jika syarat sudah terpenuhi; halaman menampilkan permintaan terakhir untuk eksemplar tersebut.
- **Detail Buku → Reservasi buku**: maksimal tiga judul aktif, satu antrean aktif per siswa/judul. Judul harus memiliki eksemplar fisik berkondisi baik, termasuk yang sedang dipinjam. Koleksi referensi tanpa stok fisik tidak bisa direservasi. Siswa tidak dapat mereservasi judul yang masih dipinjamnya.
- **Pengingat**: daftar pribadi untuk buku yang jatuh tempo dua hari lagi, hari ini, atau terlambat. Pengingat tampil dalam web dan diperbarui saat halaman dibuka. Tidak mengirim email/WhatsApp atau notifikasi ketika aplikasi ditutup.
- **Tagihan Saya**: tagihan pribadi dan bukti pelunasan. Siswa lain tidak dapat membuka bukti tersebut.

## Denda dan catatan lama

Tarif tetap Rp2.000 per eksemplar per hari, menggunakan snapshot tarif pinjaman. Buku hilang hanya mengganti harga buku, tanpa tambahan keterlambatan.

Pengembalian baru berbiaya menghasilkan status `unpaid`; tanpa biaya `none`. Catatan lama memakai `unverified`: **bukan berarti belum dibayar**. Petugas harus memilih:

1. Sudah dipastikan belum dibayar → tandai belum dibayar, baru catat pelunasan ketika uang diterima.
2. Sudah dibayar sebelum pencatatan digital → konfirmasi pelunasan lama. Bukti menyatakan waktu verifikasi, bukan waktu menerima uang baru.

Satu pembayaran per eksemplar. Pengiriman ulang formulir mengembalikan bukti yang sama. Estimasi biaya pada pinjaman aktif tidak bisa dibayar melalui halaman ini.

## Aturan teknis

- Konfigurasi `config/library.php`: `renewal_days=7`, `reservation_hold_days=2`, `max_reservations=3`, `reminder_days=2`.
- Tenggat dihitung Asia/Jakarta. Reservasi siap berakhir tepat ketika `expires_at <= now()`; stok tidak lagi ditahan meski belum ada proses terjadwal. Riwayat menampilkan kedaluwarsa langsung. Pada mutasi berikutnya untuk judul itu, status tersimpan dinormalisasi menjadi `expired`.
- Peminjaman, reservasi, dan perpanjangan memakai transaksi dan penguncian baris. Pemilik reservasi siap harus memakai eksemplar yang ditetapkan. Eksemplar bebas mengikuti antrean menunggu. Kuota pinjaman tetap diperiksa saat pengambilan.
- Perpanjangan memeriksa lagi kepemilikan, akun aktif, batas kembali, status pengembalian/arsip, dan antrean saat pengajuan maupun persetujuan.
- Kondisi eksemplar tidak dapat diubah dari Data Eksemplar ketika masih dipinjam atau ditahan reservasi; gunakan alur pengembalian atau pembatalan reservasi.
- Tabel baru: `fine_payments`, `loan_renewals`, `book_reservations`, `activity_logs`; kolom baru `loan_items.billing_status`.
- Pasang pada salinan proyek lain dengan `php artisan migrate --force`. Jangan memakai `migrate:fresh` pada database asli.

## Akses melalui VS Code Ports

Tunnel memakai HTTPS, sementara server PHP lokal menerima HTTP. `bootstrap/app.php` mempercayai header protokol/host/port dari proxy loopback `127.0.0.1` dan `::1` agar URL CSS, JavaScript, gambar, formulir, dan redirect mengikuti HTTPS tunnel. Akses lokal HTTP tetap dapat dipakai.

Muat ulang halaman menggunakan ⌘⇧R setelah perubahan. Ports adalah akses tunnel ke aplikasi yang tetap berjalan di komputer, sehingga server lokal dan tunnel perlu aktif. Untuk hosting permanen, konfigurasi proxy harus mengikuti alamat proxy hosting yang digunakan; jangan menganggap semua proxy tepercaya.

Rujukan: [Laravel trusted proxies](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies) dan [VS Code port forwarding](https://code.visualstudio.com/docs/debugtest/port-forwarding).

## Verifikasi

- Suite pengujian aplikasi: 173 pengujian lulus, 1.430 assertion.
- `php tests/Support/library_services_concurrency.php`: pengiriman pembayaran bersamaan tetap satu bukti, penyiapan reservasi bersamaan tetap satu pemegang eksemplar, persetujuan bersamaan hanya memperpanjang sekali. Skrip membuat dan menghapus database MySQL sementara sendiri.
- Alur browser diuji dengan akun dan database pratinjau terpisah: login petugas/siswa, pembayaran dengan CSRF, bukti cetak PDF, persetujuan perpanjangan, penyiapan reservasi, dan aktivitas. Halaman kedua peran diperiksa pada lebar 1440, 390, dan 320 piksel.
- URL aset tunnel HTTPS, akses lokal HTTP, serta penolakan header proxy dari IP luar loopback memiliki pengujian sendiri.
