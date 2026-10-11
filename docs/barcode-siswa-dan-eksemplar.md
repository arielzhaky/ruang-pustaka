# Barcode siswa dan eksemplar

## Identitas barcode

Kartu siswa memakai Code 128 dengan awalan `RPS`, misalnya `RPS00000001`. Label setiap eksemplar fisik memakai awalan `RPC`, misalnya `RPC00000001`. Angka berasal dari ID data yang tersimpan, sehingga barcode tetap berlaku saat kode anggota atau kode inventaris diubah. Setiap eksemplar dari judul yang sama memiliki barcode sendiri.

Kolom pemindai juga menerima kode anggota dan kode inventaris biasa. Jika kode yang sama dipakai siswa dan eksemplar, pilih **Jenis data → Siswa** atau **Eksemplar**.

## Cara memindai

Petugas membuka menu **Scan Barcode** untuk mencari identitas siswa, data eksemplar, atau pinjaman aktif.

- **Alat scan USB:** klik kolom kode; gunakan pemindai yang mengirim Enter setelah barcode. Jika tidak mengirim Enter, tekan Enter atau klik **Cari / Scan**.
- **Manual:** ketik barcode, kode anggota, atau kode inventaris lalu tekan Enter.
- **Kamera:** klik **Gunakan kamera**, izinkan akses, lalu arahkan kamera ke kartu/label. Seluruh gambar kamera dipindai sehingga kode boleh berada di luar tengah layar. Pemindai meminta kamera belakang, resolusi 1080p, serta fokus terus-menerus jika tersedia. Pilihan kamera, zoom, dan lampu muncul sesuai kemampuan perangkat. Kamera memerlukan HTTPS atau localhost dan berhenti setelah kode terbaca, saat dibatalkan, atau ketika halaman disembunyikan.
- **Gambar:** klik **Pilih gambar** dan pilih JPG, PNG, atau WebP maksimal **20 MB**. HEIC yang tidak didukung browser dan PDF perlu diubah ke gambar terlebih dahulu. Pembacaan menggunakan detail gambar asli, hingga sisi terpanjang 4.400 piksel / 16 megapiksel, serta mencoba rotasi, pencerminan, dan peningkatan kontras jika perlu. Ukuran pratinjau tidak menentukan resolusi pembacaan. Gambar di atas 50 megapiksel perlu dipotong dahulu. Gunakan **Batalkan pembacaan** untuk menghentikan proses.
- **QR:** pemindai juga menerima QR yang isinya kode `RPS…`, `RPC…`, kode anggota, atau kode inventaris. QR berisi tautan atau data lain tidak otomatis dianggap identitas perpustakaan. Kartu dan label yang dicetak aplikasi tetap memakai Code 128.
- **Beberapa kode:** jika ada beberapa kode terdeteksi dalam satu gambar/tampilan kamera, pilih hasil yang ingin dipakai sebelum data siswa/eksemplar dicari.

Gambar diproses di perangkat melalui Web Worker agar halaman tetap dapat digunakan. Hanya kode hasil pembacaan dikirim untuk mencari data. Jika belum terbaca, potong gambar mendekati kartu/label, sertakan seluruh kode beserta ruang putih di tepinya, dan hindari foto buram atau pantulan cahaya. Pesan “kode tidak ditemukan” berarti kode sudah dibaca tetapi tidak cocok dengan data perpustakaan.

## Peminjaman dan pengembalian

Pada **Catat peminjaman**, pindai kartu siswa lalu label setiap eksemplar. Scan memilih siswa dan mencentang eksemplar di formulir. Eksemplar yang sama tidak ditambahkan dua kali. Periksa siswa, eksemplar, dan batas kembali sebelum menyimpan peminjaman.

Pada **Catat pengembalian**, atau halaman **Scan Barcode**, pindai label eksemplar yang masih dipinjam. Periksa peminjam dan perkiraan denda, pilih kondisi, lalu klik **Konfirmasi pengembalian** dan setujui konfirmasi. Denda aktual dihitung saat disimpan.

Mencari atau memindai barcode tidak mencatat transaksi maupun mengubah reservasi. Penyimpanan tetap memeriksa akun aktif, kuota, ketersediaan, dan antrean reservasi.

## Mencetak kartu dan label

Kartu terbaru juga dapat memuat **QR login pribadi** untuk masuk tanpa kata sandi. QR login berbeda dari barcode identitas `RPS…`; pemindai pelayanan mengabaikan QR login dan memakai barcode identitas pada kartu. Lihat [panduan login QR siswa](login-qr-siswa.md) untuk membuat, mengganti, atau menonaktifkannya.

- **Kartu siswa:** petugas membuka **Kelola Siswa → Kartu / Barcode → Cetak / Simpan PDF**. Siswa dapat membuka dan mencetak kartu miliknya sendiri.
- **Label buku:** buka **Data Buku → Eksemplar → Cetak Barcode**. Label dapat dicetak sekaligus atau untuk satu eksemplar. Cetak massal memuat eksemplar aktif, maksimal 100 label per halaman aplikasi; pindah halaman untuk label berikutnya.
- Gunakan kertas A4, skala **100%**, dan matikan header/footer browser. Ukuran tiap label **90 × 42 mm**; potong mengikuti garis tepi. Satu halaman aplikasi dapat menghasilkan beberapa lembar kertas.

## Akses dan implementasi

Pencarian barcode, identitas siswa lain, kartu siswa yang dipilih petugas, dan label eksemplar hanya tersedia bagi petugas. Siswa hanya dapat mengakses kartu miliknya melalui halaman kartu anggota.

Fitur ini tidak memerlukan migrasi database atau dependensi PHP baru. SVG Code 128 dibuat oleh `app/Support/LibraryBarcode.php`. Pemindai kamera/gambar memakai salinan lokal **ZXing-WASM 3.1.5**; versi, sumber, hash paket, dan lisensi dicatat di [folder vendornya](../public/vendor/zxing-wasm/README.md). Worker, JavaScript, dan berkas `.wasm` harus ikut disalin saat deployment. Tabel pola simbol Code 128 bersumber dari **JsBarcode 3.11.6** dengan [lisensi MIT](licenses/JsBarcode-MIT.txt).

Pengujian backend: `php artisan test --filter=BarcodeTest` (konfigurasi pengujian menggunakan SQLite dalam memori).

Pengujian browser: `npx playwright test --config=tests/e2e/scanner.config.js` untuk Chromium dan WebKit. Jalankan `npx playwright install chromium webkit` jika browser pengujian belum terpasang; pada macOS, pengujian Chromium memakai Google Chrome lokal jika tersedia. Harness berjalan di localhost:8099 tanpa mengakses database. Decoder asli diuji dengan QR dan Code 128, orientasi miring/vertikal/tercermin, kontras rendah, gambar besar, beberapa kode, pembatalan, serta kamera simulasi di luar tengah gambar. Fokus, zoom, dan lampu fisik tetap perlu dicoba pada ponsel yang akan digunakan.
