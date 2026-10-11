# Login siswa dengan QR kartu

Siswa dapat masuk tanpa email atau kata sandi melalui **Masuk → Scan kartu siswa**, lalu memilih kamera, gambar kartu, atau pemindai USB 2D (QR). Setelah satu QR login yang valid terbaca, aplikasi langsung membuka dashboard siswa. Login email/kata sandi tetap tersedia untuk siswa dan petugas.

## Menyiapkan kartu

- Petugas membuka **Kelola Siswa → Kartu / Barcode → Buat QR login**, lalu **Cetak / Simpan PDF**.
- Siswa yang sudah masuk dengan email dapat membuat QR sendiri melalui **Kartu Anggota**.
- **Antrean Cetak → Download PDF yang dipilih** menyertakan QR login untuk siswa aktif. QR dibuat jika belum tersedia; mencetak ulang mempertahankan QR yang ada. Akun nonaktif tidak mendapatkan QR login pada cetakan.
- Kartu memuat barcode identitas `RPS…` untuk kunjungan/peminjaman dan QR login pribadi. Cetak ulang kartu lama agar memiliki QR login.

## Kartu hilang / penggantian

**Ganti QR login** membuat QR baru dan membatalkan QR sebelumnya. Sesi yang masuk dengan QR lama akan keluar saat permintaan berikutnya. Siswa yang mengganti QR sendiri tetap dapat memakai sesi saat ini. **Nonaktifkan QR login** menghapus akses melalui QR; login email/kata sandi tetap tersedia. Jika dinonaktifkan dari sesi QR siswa sendiri, sesi tersebut langsung keluar.

Setiap orang yang memiliki salinan QR login bisa masuk sebagai siswa tersebut. Berikan kartu/PDF hanya kepada pemiliknya. Jangan memakai kartu berisi QR login asli sebagai gambar promosi atau contoh publik.

## Implementasi

- `StudentLoginQr` membuat token acak 256 bit, berformat `RPL1:` + 64 karakter heksadesimal. Token bukan ID anggota yang bisa ditebak.
- `members.login_qr_token` disimpan terenkripsi memakai kunci aplikasi; `login_qr_hash` menyimpan SHA-256 untuk pencarian. Keduanya disembunyikan dari serialisasi model dan tidak bisa diisi melalui mass assignment.
- Endpoint `POST /login/qr` memakai middleware guest, CSRF, dan batas 10 percobaan per menit. Hanya pengguna aktif dengan role `anggota` yang dapat masuk.
- Pemindai mengirim token dalam body POST, tidak dalam URL. Token tidak disimpan sebagai old input pada validasi gagal. Halaman kartu dan unduhan PDF memakai `Cache-Control: no-store, private`.
- Barcode identitas `RPS…`/`RPC…` dan kode anggota biasa tidak dapat dipakai login. Pemindai pelayanan mengabaikan QR login agar token tidak terkirim ke pencarian GET.
- Membuka kartu tidak membuat token. Penerbitan dilakukan melalui POST tombol pembuatan kartu atau download antrean cetak.
- QR SVG dibuat secara lokal menggunakan BaconQrCode; gambar kartu tidak dikirim ke layanan eksternal.

Untuk instalasi lain: `composer install`, lalu `php artisan migrate` dan `php artisan view:clear`. Sertakan folder `public/vendor/zxing-wasm` dan JavaScript pemindai saat deployment. Kamera memerlukan HTTPS atau localhost.

## Pengujian

`php artisan test --filter=StudentQrLoginTest` memeriksa login, pembatasan peran/akun aktif, penyimpanan terenkripsi, pergantian/pencabutan, pembatasan percobaan, dan akses kartu milik sendiri.

`npx playwright test --config=tests/e2e/scanner.config.js` memeriksa decoder asli melalui gambar dan kamera simulasi di Chromium/WebKit, termasuk kartu yang memiliki kedua jenis kode dan pengiriman token melalui POST.
