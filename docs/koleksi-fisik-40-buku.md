# Koleksi 40 buku fisik

Koleksi tambahan terdiri dari 40 judul nyata: 10 Buku Fiksi, 10 Sejarah, 10 Komik, dan 10 Biografi. Setiap judul dicatat dengan satu eksemplar berkondisi `baik`, kode inventaris unik, dan lokasi rak awal. Eksemplar baru tersedia untuk dipinjam melalui alur peminjaman yang sudah ada.

## Sumber dan detail

- Metadata dan sampul berasal dari katalog publik Gramedia, diakses pada 4 Oktober 2026. ISBN, penerbit, tahun/tanggal terbit, jumlah halaman, bahasa, dan format mengikuti edisi yang dipilih, bukan selalu edisi pertama karya.
- Deskripsi berisi ringkasan yang disusun ulang beserta rincian edisi. Sampul asli dikonversi ke JPEG dan disimpan lokal agar tampilan tidak bergantung pada layanan gambar eksternal.
- Harga katalog sebelum diskon dipakai sebagai nilai awal biaya penggantian. Nilai ini bukan harga pembelian sekolah dan dapat disesuaikan petugas.
- Lokasi `Rak Fiksi`, `Rak Sejarah`, `Rak Komik`, dan `Rak Biografi` adalah label penataan awal yang dapat disesuaikan.
- `database/data/physical-books/catalog.json` mencatat metadata, tautan sumber per judul, sumber gambar, dan checksum SHA-256. Aset bawaan ada di `database/data/physical-books/covers/`.

## Impor

Periksa dahulu tanpa mengubah data atau berkas:

```sh
php artisan library:import-physical-books --dry-run
```

Tambahkan buku dan eksemplar:

```sh
php artisan library:import-physical-books
```

Perintah memeriksa kelengkapan metadata, checksum ISBN, dan integritas sampul sebelum menulis. Buku yang sudah ada berdasarkan sumber, ISBN yang dinormalisasi, atau judul dilewati tanpa mengubah data/eksemplarnya, termasuk buku yang diarsipkan. Menjalankan ulang tidak menggandakan koleksi. Benturan kode inventaris membatalkan impor; kegagalan menyimpan data mengembalikan transaksi dan membersihkan sampul baru.

Sampul disalin ke disk publik Laravel pada `covers/physical/`. Pastikan tautan `public/storage` tersedia (`php artisan storage:link` pada instalasi baru). Tautan sumber pada kartu/detail buku diberi label **Sumber katalog**; label SIBI tetap khusus referensi SIBI.

## Daftar buku

### Buku Fiksi

| Kode inventaris | Judul | Penulis | ISBN |
| --- | --- | --- | --- |
| RP-FIK-001-01 | [Laskar Pelangi Edisi ke-50](https://www.gramedia.com/products/laskar-pelangi-edisi-original) | Andrea Hirata | 9786022916628 |
| RP-FIK-002-01 | [Sang Pemimpi](https://www.gramedia.com/products/sang-pemimpi-edisi-original) | Andrea Hirata | 9786022916635 |
| RP-FIK-003-01 | [Negeri 5 Menara (2025)](https://www.gramedia.com/products/negeri-5-menara-2025) | AHMAD FUADI | 9789792248616 |
| RP-FIK-004-01 | [Perahu Kertas](https://www.gramedia.com/products/perahu-kertas-2) | Dee Lestari | 9786231864253 |
| RP-FIK-005-01 | [Bumi](https://www.gramedia.com/products/bumi-2) | Tere Liye | 9786239726263 |
| RP-FIK-006-01 | [Bulan](https://www.gramedia.com/products/bulan-1) | Tere Liye | 9786239726287 |
| RP-FIK-007-01 | [Matahari](https://www.gramedia.com/products/matahari-1) | Tere Liye | 9786239726270 |
| RP-FIK-008-01 | [Keluarga Cemara#1 (Edisi Cover Spesial HUT GPU 50 Tahun)](https://www.gramedia.com/products/gpu-50-keluarga-cemara-1) | Arswendo Atmowiloto | 9789792292633 |
| RP-FIK-009-01 | [Si Anak Pintar Tere Liye](https://www.gramedia.com/products/si-anak-pintar-tere-liye) | Tere Liye | 9786025734502 |
| RP-FIK-010-01 | [Hujan](https://www.gramedia.com/products/hujan-2) | Tere Liye | 9786239987879 |

### Sejarah

| Kode inventaris | Judul | Penulis | ISBN |
| --- | --- | --- | --- |
| RP-SEJ-001-01 | [Sejarah Indonesia Modern](https://www.gramedia.com/products/sejarah-indonesia-modern-2) | M.C. Ricklefs | 9786231345677 |
| RP-SEJ-002-01 | [Sejarah Dunia Abad Pertengahan - Dari Pertobatan Konstantinus Sampai Perang Salib Pertama](https://www.gramedia.com/products/sejarah-dunia-abad-pertengahan-dari-pertobatan-konstantinus-sampai-perang-salib-pertama) | Susan Wise Bauer | 9786020292915 |
| RP-SEJ-003-01 | [Kaladesa. Awal Sejarah Nusantara. Edisi Revisi](https://www.gramedia.com/products/kaladesa-awal-sejarah-nusantara-edisi-revisi) | Agus Aris Munandar | 9786022730378 |
| RP-SEJ-004-01 | [Majapahit](https://www.gramedia.com/products/majapahit) | Herald van der Linde | 9786231344816 |
| RP-SEJ-005-01 | [Jalur Rempah Nusantara: Kisah Aroma Nusantara Pemikat Dunia](https://www.gramedia.com/products/jalur-rempah-nusantara-kisah-aroma-nusantara-pemikat-dunia) | Aloysius Budi Kurniawan | 9786232414723 |
| RP-SEJ-006-01 | [Sejarah Perang Kerajaan Kerajaan Di Nusantara](https://www.gramedia.com/products/sejarah-perang-kerajaan-kerajaan-di-nusantara) | Sri Wintala Achmad | 9786023004645 |
| RP-SEJ-007-01 | [Atlas Sejarah Indonesia dan Dunia](https://www.gramedia.com/products/atlas-sejarah-indonesia-dan-dunia) | Tim Redaksi | 9786028989411 |
| RP-SEJ-008-01 | [Sejarah Peradaban Islam](https://www.gramedia.com/products/sejarah-peradaban-islam-3) | Drs. Samsul Munir Amin, MA. | 9786028689007 |
| RP-SEJ-009-01 | [Sejarah dan Peradaban Mesir Kuno](https://www.gramedia.com/products/sejarah-dan-peradaban-mesir-kuno) | Pdt. Dr. Jonar T.H. Situmorang, M.A., M.Th. | 9786233140461 |
| RP-SEJ-010-01 | [Sejarah Singkat Dunia](https://www.gramedia.com/products/sejarah-singkat-dunia) | H.G. Wells | 9786235153476 |

### Komik

| Kode inventaris | Judul | Penulis | ISBN |
| --- | --- | --- | --- |
| RP-KOM-001-01 | [Doraemon 01 (2024)](https://www.gramedia.com/products/doraemon-01-2024) | Fujiko F. Fujio | 9789792789133 |
| RP-KOM-002-01 | [Detektif Conan Premium 01](https://www.gramedia.com/products/detektif-conan-premium-01) | Aoyama Gosho | 9786230014918 |
| RP-KOM-003-01 | [One Piece 01](https://www.gramedia.com/products/one-piece-01-1) | Eiichiro Oda | 9789792039504 |
| RP-KOM-004-01 | [Naruto Bind Up Edition 01](https://www.gramedia.com/products/naruto-bind-up-edition-01) | Masashi Kishimoto | 9786230054952 |
| RP-KOM-005-01 | [Dragon Ball Ultimate 01](https://www.gramedia.com/products/dragon-ball-ultimate-01) | Akira Toriyama | 9786230071706 |
| RP-KOM-006-01 | [Haikyu!!: Fly High! Volleyball! 01 - Bookpaper](https://www.gramedia.com/products/haikyu-fly-high-volleyball-01--bookpaper) | Haruichi Furudate | 9786230316388 |
| RP-KOM-007-01 | [Slam Dunk New Edition 01](https://www.gramedia.com/products/slam-dunk-new-edition-01) | Inoue Takehiko | 9786230030598 |
| RP-KOM-008-01 | [Spy x Family 01](https://www.gramedia.com/products/spy-x-family-01) | Endo Tatsuya | 9786230021312 |
| RP-KOM-009-01 | [Yotsuba&! 01](https://www.gramedia.com/products/yotsuba-01) | Kiyohiko Azuma | 9786020266800 |
| RP-KOM-010-01 | [Dr. Stone 01](https://www.gramedia.com/products/dr-stone-01) | Riichiro Inagaki | 9786230033926 |

### Biografi

| Kode inventaris | Judul | Penulis | ISBN |
| --- | --- | --- | --- |
| RP-BIO-001-01 | [B.J. Habibie : Sebuah Biografi](https://www.gramedia.com/products/bj-habibie-sebuah-biografi) | Fatimah Fayrus, S.H., M.Kn. | 9786231643094 |
| RP-BIO-002-01 | [Bung Karno Penyambung Lidah Rakyat Indonesia (Edisi Revisi)](https://www.gramedia.com/products/bung-karno-penyambung-lidah-rakyat-indonesia-edisi-revisi-2) | Cindy Adams | 9786237254140 |
| RP-BIO-003-01 | [Mohammad Hatta : Biografi Singkat 1902 - 1980](https://www.gramedia.com/products/mohammad-hatta-biografi-singkat-1902-1980) | Salman Alfarizi | 9786237219637 |
| RP-BIO-004-01 | [Ki Hadjar Dewantara: Putra Keraton Pahlawan Bangsa](https://www.gramedia.com/products/ki-hadjar-dewantara-putra-keraton-pahlawan-bangsa) | Syaiful Hermawan | 9786237333241 |
| RP-BIO-005-01 | [Kartini Guru Emansipasi Perempuan Nusantara](https://www.gramedia.com/products/kartini-guru-emansipasi-perempuan-nusantara) | Ready Susanto | 9786026297013 |
| RP-BIO-006-01 | [Buya Hamka](https://www.gramedia.com/products/buya-hamka-1) | A Fuadi | 9786026714732 |
| RP-BIO-007-01 | [Gus Dur: Jejak Bijak Sang Guru Bangsa](https://www.gramedia.com/products/gus-dur-jejak-bijak-sang-guru-bangsa) | Anom Whani Wicaksono | 9786025448324 |
| RP-BIO-008-01 | [Kisah, Perjuangan, & Inspirasi Susi Pudjiastuti](https://www.gramedia.com/products/kisah-perjuangan-inspirasi-susi-pudjiastuti) | Achmad Farid | 9786025479854 |
| RP-BIO-009-01 | [Autobiografi Tan Malaka: Dari Penjara Ke Penjara](https://www.gramedia.com/products/autobiografi-tan-malaka-dari-penjara-ke-penjara) | Tan Malaka | 9789791685344 |
| RP-BIO-010-01 | [Seri Pahlawan Nasional: Jenderal Soedirman](https://www.gramedia.com/products/seri-pahlawan-nasional-jenderal-soedirman) | Watiek Ideo & Nindia Maya | 9786230417146 |
