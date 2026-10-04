<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        // Contoh daftar kelas. Sesuaikan dengan sekolah.
        $classes = [
            ['name' => 'VII A', 'grade_level' => '7'],
            ['name' => 'VII B', 'grade_level' => '7'],
            ['name' => 'VII C', 'grade_level' => '7'],
            ['name' => 'VIII A', 'grade_level' => '8'],
            ['name' => 'VIII B', 'grade_level' => '8'],
            ['name' => 'VIII C', 'grade_level' => '8'],
            ['name' => 'IX A', 'grade_level' => '9'],
            ['name' => 'IX B', 'grade_level' => '9'],
            ['name' => 'IX C', 'grade_level' => '9'],
        ];

        foreach ($classes as $class) {
            // Tambahkan hanya jika nama kelas belum ada.
            SchoolClass::firstOrCreate(
                ['name' => $class['name']],
                [
                    'grade_level' => $class['grade_level'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Daftar kelas berhasil disiapkan.');
    }
}