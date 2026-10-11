<?php

namespace App\Support;

class ActivityLabels
{
    public static function label(string $action): string
    {
        $special = ['visits.store' => 'Mencatat kunjungan siswa', 'visits.destroy' => 'Menghapus catatan kunjungan', 'print-queue.store' => 'Menambahkan antrean cetak', 'print-queue.remove' => 'Menghapus pilihan antrean cetak', 'print-queue.download' => 'Mengunduh PDF kartu / label', 'loans.store' => 'Mencatat peminjaman', 'loans.items.return' => 'Mencatat pengembalian / kehilangan', 'billing.pay' => 'Mencatat pelunasan', 'billing.confirm' => 'Memverifikasi tagihan lama', 'reservations.ready' => 'Menyiapkan reservasi', 'reservations.cancel' => 'Membatalkan reservasi', 'renewals.review' => 'Meninjau perpanjangan', 'account.update' => 'Memperbarui profil sendiri', 'account.password' => 'Mengubah kata sandi sendiri'];
        if (isset($special[$action])) {
            return $special[$action];
        }
        [$area, $verb] = array_pad(explode('.', $action), 2, '');
        $areas = ['books' => 'buku', 'copies' => 'eksemplar', 'members' => 'siswa', 'categories' => 'kategori', 'school-classes' => 'kelas'];
        $verbs = ['store' => 'Menambah', 'update' => 'Mengubah', 'destroy' => 'Menghapus / mengarsipkan', 'import' => 'Mengimpor'];

        return isset($areas[$area], $verbs[$verb]) ? $verbs[$verb].' '.$areas[$area] : $action;
    }
}
