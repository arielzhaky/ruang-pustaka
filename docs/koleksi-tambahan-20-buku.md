# Tambahan 20 buku novel dan tutorial

Koleksi ini menambahkan 20 judul baru: 18 buku pada kategori Novel dan 2 buku pada kategori Informatika. Masing-masing memiliki satu eksemplar berkondisi baik, kode inventaris RP-TAM-001-01 sampai RP-TAM-020-01, serta lokasi awal Rak Novel atau Rak Informatika.

Pilihan mencakup lima Harry Potter, tiga Narnia, dua Sherlock Holmes, enam karya penulis Jepang, Timun Jelita dan Timun Jelita Volume 2 karya Raditya Dika, serta tutorial Python dan Excel. Sebanyak 19 buku berbahasa Indonesia; What You are Looking for is in the Library memakai edisi bahasa Inggris.

## Sumber

Metadata dan cover diambil dari katalog publik Gramedia pada 4 Oktober 2026. Rincian mengikuti edisi katalog, bukan selalu tahun terbit pertama karya. Deskripsi ditulis ulang. Nama penulis Sherlock Holmes: Anjing Iblis dari Baskervilles dan Jika Kucing Lenyap Dari Dunia diperbaiki sesuai sampul karena katalog menampilkan penerbit atau salah eja.

Harga katalog sebelum diskon menjadi nilai awal biaya penggantian yang dapat disesuaikan petugas. Harga tersebut bukan harga pembelian sekolah. Tautan metadata, sumber cover, dan checksum SHA-256 tercatat di database/data/physical-books-extra/catalog.json. Cover bawaan disimpan di subdirektori covers dan disalin ke penyimpanan publik Laravel saat impor.

## Impor ulang

```sh
php artisan library:import-physical-books --collection=novels-20 --dry-run
php artisan library:import-physical-books --collection=novels-20
```

Pilihan koleksi bawaan original tetap memuat 40 buku sebelumnya. Impor melewati judul yang sudah ada berdasarkan sumber, ISBN, atau judul; eksemplar tidak digandakan. Seluruh metadata dan cover diperiksa sebelum penulisan. Kegagalan membatalkan transaksi dan membersihkan cover baru.

## Daftar judul

| No. | Judul | Penulis | Tahun edisi | ISBN | Bahasa |
| --- | --- | --- | --- | --- | --- |
| 1 | [Buku Anak Harry Potter Dan Batu Bertuah](https://www.gramedia.com/products/harry-potter-dan-batu-bertuah-ket-cetak-ulang-cover-baru) | J.K. Rowling | 2017 | 9786020337647 | Indonesia |
| 2 | [Harry Potter dan Kamar Rahasia](https://www.gramedia.com/products/harry-potter-dan-kamar-rahasia) | J.K. Rowling | 2017 | 9786020339184 | Indonesia |
| 3 | [Harry Potter dan Tawanan Azkaban (Harry Potter and The Prisoner of Azkaban)](https://www.gramedia.com/products/harry-potter-dan-tawanan-azkaban-harry-potter-and-the-prisoner-of-azkaban) | J.K. Rowling | 2017 | 9786020342733 | Indonesia |
| 4 | [Harry Potter dan Piala Api (Harry Potter and The Goblet of Fire) - Cover Baru](https://www.gramedia.com/products/harry-potter-dan-piala-api-harry-potter-and-the-goblet-of-fire-cover-baru) | J.K. Rowling | 2017 | 9786020342726 | Indonesia |
| 5 | [Harry Potter #5: Harry Potter dan Orde Phoenix](https://www.gramedia.com/products/harry-potter-5-harry-potter-dan-orde-phoenix-cover-baru) | J.K. Rowling | 2017 | 9786020361314 | Indonesia |
| 6 | [The Chronicles Of Narnia #1: The Magician`s Nephew (Keponakan Penyihir)](https://www.gramedia.com/products/the-chronicles-of-narnia-1-the-magicians-nephew-keponakan-penyihir) | C. S. Lewis | 2022 | 9786020336398 | Indonesia |
| 7 | [The Chronicles of Narnia #2: The Lion, the Witch and the Wardrobe (Sang Singa, sang Penyihir, dan Lemari)](https://www.gramedia.com/products/the-chronicles-of-narnia-2-the-lion-the-witch-and-the-wardrobe-sang-singa-sang-penyihir-dan-lemari) | C. S. Lewis | 2022 | 9786020644547 | Indonesia |
| 8 | [The Chronicles of Narnia #3: The Horse & His Boy (Kuda dan Anak Manusia)](https://www.gramedia.com/products/the-chronicles-of-narnia-3-the-horse-his-boy-kuda-dan-anak-manusia) | C. S. Lewis | 2022 | 9786020336411 | Indonesia |
| 9 | [Sherlock Holmes A Study in Scarlet](https://www.gramedia.com/products/sherlock-holmes-a-study-in-scarlet) | Sir Arthur Conan Doyle | 2014 | 9786021785775 | Indonesia |
| 10 | [Sherlock Holmes: Anjing Iblis dari Baskervilles](https://www.gramedia.com/products/sherlock-holmes-anjing-iblis-dari-baskervilles) | Sir Arthur Conan Doyle | 2022 | 9786234003055 | Indonesia |
| 11 | [Keajaiban Toko Kelontong Namiya](https://www.gramedia.com/products/keajaiban-toko-kelontong-namiya-namiya-zakkaten-no-kisekithe-miracles-of-the-namiya-general-store-1) | Keigo Higashino | 2020 | 9786020648293 | Indonesia |
| 12 | [Funiculi Funicula (Kōhī Ga Samenai Uchi Ni---Before the Coffee Gets Cold)](https://www.gramedia.com/products/funiculi-funicula-kohi-ga-samenai-uchi-ni-before-the-coffee-gets-cold) | Toshikazu Kawaguchi | 2021 | 9786020651927 | Indonesia |
| 13 | [Polisi Kenangan (The Memory Police)](https://www.gramedia.com/products/polisi-kenangan-the-memory-police) | Yoko Ogawa | 2020 | 9786020639055 | Indonesia |
| 14 | [The Traveling Cat Chronicles (2025)](https://www.gramedia.com/products/the-traveling-cat-chronicles-2025) | Arikawa Hiro | 2025 | 9786235467283 | Indonesia |
| 15 | [Jika Kucing Lenyap Dari Dunia](https://www.gramedia.com/products/jika-kucing-lenyap-dari-dunia) | Genki Kawamura | 2021 | 9786026486431 | Indonesia |
| 16 | [What You are Looking for is in the Library](https://www.gramedia.com/products/what-you-are-looking-for-is-in-the-library) | Michiko Aoyama | 2025 | 9781804994139 | English |
| 17 | [Timun Jelita](https://www.gramedia.com/products/timun-jelita) | Raditya Dika | 2024 | 9786234933031 | Indonesia |
| 18 | [Timun Jelita Volume 2](https://www.gramedia.com/products/timun-jelita-volume-2) | Raditya Dika | 2025 | 9786234933260 | Indonesia |
| 19 | [Python untuk Programmer Pemula](https://www.gramedia.com/products/python-untuk-programmer-pemula) | Jubilee Enterprise | 2019 | 9786020496894 | Indonesia |
| 20 | [Jago Data dengan Excel: Panduan Lengkap Belajar Excel dari Nol sampe Jago](https://www.gramedia.com/products/jago-data-dengan-excel-panduan-lengkap-belajar-excel-dari-nol-sampe-jago) | Zhofar Murry | 2025 | 9786234736106 | Indonesia |
