# Data sekolah SMPN 26 Tangerang

Pembaruan 10 Oktober 2026 memakai `daftar_buku (1).xlsx` sebagai acuan utama, sesuai pilihan pengguna. Tiga workbook lainnya hanya melengkapi judul yang belum ada. Data siswa berasal dari `senayan_member_export3.csv`.

## Hasil impor

| Data | Jumlah |
| --- | ---: |
| Judul sekolah setelah penggabungan | 231 |
| Judul dari acuan utama | 120 |
| Judul tambahan | 111 |
| Eksemplar sekolah | 447 |
| Eksemplar tersedia saat impor | 405 |
| Siswa dari Senayan | 317 |
| Cover tersedia (11 Oktober 2026) | 231: 133 dari sumber buku + 98 ilustrasi |

Sebanyak 77 buku contoh yang sebelumnya ditambahkan dihapus. Tiga buku contoh yang terkait riwayat transaksi diarsipkan agar riwayat tetap utuh. Data manual sebelumnya tetap disimpan. Angka tabel di atas hanya data sekolah baru, bukan seluruh data aplikasi.

### Penggabungan dan data yang belum lengkap

- Kode, ISBN, serta judul dan penulis dipakai untuk mencocokkan buku. Kode lain untuk buku yang sama disimpan sebagai alias. Impor ulang tidak menambah buku dan siswa yang sudah cocok.
- Stok dari baris ganda memakai jumlah terbesar, bukan dijumlahkan. Daftar pelengkap tanpa jumlah stok diinisialisasi satu eksemplar per judul; petugas perlu menyesuaikannya dengan jumlah fisik.
- Kategori dari acuan utama dipertahankan: `KOMIK`, `NOVEL`, `Pelajaran`, dan `Fiksi`. Sebanyak 111 judul pelengkap tidak memiliki kategori sehingga memakai `Belum dikategorikan`. Kategori lama yang masih digunakan data manual/arsip tetap ada.
- Selisih total dan tersedia ditandai **Belum tersedia (data impor)**. Tidak dibuat transaksi pinjaman fiktif. Petugas dapat mengaktifkannya melalui **Stok / Eksemplar → Atur stok tersedia** atau mengubah eksemplar.
- Koreksi beberapa salah ketik dan sel yang bergeser dicatat dalam `storage/app/private/school-import/merge-report.json`.
- File siswa tidak berisi kelas, tanggal lahir, dan email. Angkatan tetap disimpan sebagai jenis anggota Senayan, tidak dianggap kelas. Lengkapi kelas dan data lain melalui **Kelola Siswa → Ubah**.
- Siswa tanpa email mendapatkan pengenal akun internal dan kata sandi acak. Gunakan **Kartu / Barcode → Buat QR login**, atau antrean cetak, untuk menyiapkan akses siswa. Impor ulang tidak mengganti kata sandi atau QR yang sudah ada.

## Impor dan ekspor CSV

Buka **Data Buku** atau **Kelola Siswa → Impor / Ekspor → Unduh Template CSV**. Ekspor menggunakan susunan kolom yang sama dengan template sekolah. Simpan dari Excel sebagai **CSV UTF-8** sebelum mengunggah; form web menerima CSV, bukan XLSX.

### Buku: 10 kolom

```csv
kode_buku,judul,pengarang,penerbit,tahun_terbit,kategori,isbn,jumlah_eksemplar,tersedia,rak
```

`kode_buku`, `judul`, `kategori`, dan `jumlah_eksemplar` wajib. `tersedia` kosong berarti seluruh jumlah siap dipinjam. Kategori baru dibuat otomatis. Format kode dan ISBN sebagai teks agar Excel tidak mengubah nol awal.

Impor memperbarui informasi buku yang cocok dan menambah kekurangan eksemplar. Impor tidak menghapus stok, menurunkan total eksemplar, atau menimpa riwayat sirkulasi. Untuk mengurangi stok tersedia gunakan menu stok. Harga, cover, dan deskripsi yang sudah ada tetap disimpan.

### Siswa: 19 kolom Senayan

```csv
member_id,member_name,gender,member_type_name,member_email,member_address,postal_code,inst_name,is_new,member_image,pin,member_phone,member_fax,member_since_date,register_date,expire_date,birth_date,member_notes,mpasswd
```

`member_id` dan `member_name` wajib. `gender`: `1` laki-laki, `0` perempuan. Tanggal lahir memakai `YYYY-MM-DD`. Format ID sebagai teks untuk menjaga nol awal. Nomor anggota tidak otomatis dijadikan NIS.

Kolom `pin` dan `mpasswd` tetap ada agar susunan identik, tetapi isinya tidak dipakai dan dikosongkan saat ekspor. Email internal pengganti email kosong juga tidak diekspor. `member_image` disimpan sebagai metadata nama berkas; berkas foto tidak ikut terkirim melalui CSV.

Kedua impor menerima pemisah koma/titik koma, maksimal 2 MB dan 1.000 baris. Kesalahan satu baris membatalkan seluruh impor. Format CSV lama masih diterima.

## Cover dan detail buku

Pada impor awal, 18 cover dicocokkan dari katalog penerbit/Gramedia. Pencarian lanjutan menambah 115 cover buku sekolah sehingga totalnya 133 dari 231 judul. Gambar disimpan lokal. Sebanyak 98 judul lainnya kini memakai sampul ilustrasi yang dibuat dari judul dan penulis data sekolah. Seluruh 231 judul sudah memiliki cover. Sampul ilustrasi ditandai pada gambar dan detail buku; dapat diganti dengan foto buku melalui menu Upload cover JPG. Lihat [laporan pencarian cover](pencarian-cover-buku.md) dan [daftar sumber](sumber-cover-sekolah.json).

Untuk menambahkan satu gambar setelah buku disimpan:

1. Buka **Data Buku → Upload cover JPG** di bawah buku.
2. Pilih JPG/JPEG, maksimal 2 MB dan 3.000 × 3.000 piksel.
3. Tekan **Simpan cover**. Judul dan data lainnya tetap tersimpan.

Menu **Ubah / Cover** juga tersedia. Impor gambar massal dengan kolom `cover` hanya berlaku untuk format CSV lama; template sekolah tetap 10 kolom.

## Antrean cetak

Kartu siswa PDF kini memakai latar biru navy, identitas sekolah, nama, kelas, nomor anggota, barcode, serta QR login bila tersedia. Delapan kartu ukuran 85,6 × 54 mm muat per halaman A4. Label buku berada pada halaman terpisah. Pilih siswa/buku yang diperlukan lalu klik **Download PDF yang dipilih**.

Cetak pada ukuran sebenarnya / 100%. Untuk memindai gambar hasil PDF, ekspor gambar 300 dpi agar garis barcode tidak kehilangan detail.

## Cadangan dan pemeriksaan

Cadangan sebelum penggantian tersedia secara lokal di:

`storage/app/private/school-import/before-school-import-20261010-122310.sql`

Folder privat tersebut juga menyimpan hasil konversi CSV, laporan penggabungan, serta catatan sumber cover. Cadangan memuat data akun dan tidak untuk dipublikasikan.

Validasi: 250 tes aplikasi lulus (3.088 assertion). Simulasi impor ulang menghasilkan nol buku dan nol siswa baru. Antarmuka impor, unggahan JPG, serta pembacaan QR dan Code128 dari PDF 300 dpi diperiksa di Chrome dan WebKit menggunakan data uji terpisah.
