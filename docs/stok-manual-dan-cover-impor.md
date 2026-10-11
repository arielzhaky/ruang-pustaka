# Stok manual dan cover JPG

## Mengelola stok

1. Buka **Data Buku > Stok / Eksemplar** pada judul yang ingin diubah.
2. **Tambah Stok** menambahkan jumlah buku fisik baru sekaligus. Setiap eksemplar mendapat kode inventaris otomatis, kondisi baik, dan lokasi rak yang diisi.
3. **Atur stok tersedia menjadi** menetapkan jumlah eksemplar yang siap dipinjam. Isi `0` lalu **Simpan Stok** untuk membuat stok saat ini tidak tersedia. Buku tetap terlihat dalam katalog.

Pengurangan stok menonaktifkan eksemplar yang tersedia, bukan menghapusnya. Riwayat tetap tersimpan. Peningkatan melalui **Simpan Stok** mengaktifkan kembali eksemplar nonaktif yang baik terlebih dahulu, lalu membuat eksemplar baru bila jumlahnya kurang. Lokasi rak eksemplar yang dipulihkan tetap sama; untuk eksemplar baru tanpa rak, isi melalui tombol Ubah.

Eksemplar rusak/hilang tidak diaktifkan otomatis. Eksemplar sedang dipinjam atau ditahan untuk reservasi tidak diubah. Saat peminjaman selesai atau reservasi dilepas, stok tersedia dapat bertambah kembali. Jika ingin menarik seluruh judul dari layanan secara berkelanjutan, selesaikan transaksi aktif lalu gunakan Arsipkan pada Data Buku.

Daftar eksemplar menampilkan status Aktif/Nonaktif. Status Aktif bukan jaminan tersedia karena eksemplar bisa sedang dipinjam, ditahan, rusak, atau hilang. Form Tambah Eksemplar tetap tersedia untuk memasukkan kode inventaris sendiri. Maksimal 1000 tambahan atau target stok per permintaan.

## Upload cover manual

Pada **Tambah Buku** atau **Ubah**, pilih gambar di **Upload cover manual**, kemudian simpan. JPG/JPEG, PNG, dan WebP didukung; maksimal 2 MB dan 3000 x 3000 piksel per gambar. Unggahan baru menggantikan cover lama.

## Impor CSV beserta cover JPG

1. Buka **Data Buku > Impor / Ekspor Buku > Unduh Template CSV**.
2. Isi data buku. Kolom opsional `cover` berisi nama file persis, termasuk huruf besar/kecil dan ekstensi, misalnya `harry-potter.jpg`. Boleh kosong jika tanpa cover.
3. Pilih CSV, lalu pilih gambar-gambarnya pada **Pilih cover JPG untuk impor**. Urutan pemilihan gambar bebas; pencocokan berdasarkan nama file.
4. Klik **Impor CSV**. Setelah berhasil, tambahkan stok lewat **Stok / Eksemplar**.

Maksimal 10 JPG/JPEG per impor, masing-masing 2 MB dan 3000 x 3000 piksel, total gambar 5 MB. Nama file harus unik. File yang tidak dirujuk CSV, file yang belum diunggah, atau gambar tidak valid membatalkan impor. Kesalahan pada baris berikutnya juga membatalkan seluruh data baru dan membersihkan cover baru. Pilih ulang CSV/gambar setelah validasi gagal karena browser tidak mengisi ulang input file.

CSV lama tanpa kolom cover tetap didukung. Ekspor CSV berisi metadata, tanpa berkas gambar. Semua pengaturan stok dan impor dibatasi untuk petugas.
