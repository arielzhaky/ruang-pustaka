<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Buku Fiksi',
            'Novel',
            'Komik',
            'Buku Nonfiksi',
            'Biografi',
            'Ensiklopedia / Kamus',
            'Buku Ilmiah',
            'Buku Pelajaran',
        ];

        foreach ($names as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        $this->command->info('Kategori awal berhasil disiapkan.');
    }
}