<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ImportPhysicalBooks extends Command
{
    protected $signature = 'library:import-physical-books
        {--collection=original : Koleksi original atau novels-20}
        {--dry-run : Periksa koleksi tanpa menulis data atau cover}';

    protected $description = 'Tambahkan koleksi buku nyata beserta cover lokal dan satu eksemplar per judul';

    public function handle(): int
    {
        $written = [];
        try {
            $collections = [
                'original' => ['directory' => 'physical-books', 'categories' => ['Buku Fiksi' => 10, 'Sejarah' => 10, 'Komik' => 10, 'Biografi' => 10]],
                'novels-20' => ['directory' => 'physical-books-extra', 'categories' => ['Novel' => 18, 'Informatika' => 2]],
            ];
            $collection = $collections[$this->option('collection')] ?? null;
            if (! $collection) {
                throw new RuntimeException('Koleksi tidak dikenal. Pilih original atau novels-20.');
            }
            $directory = database_path('data/'.$collection['directory']);
            $catalog = json_decode(file_get_contents($directory.'/catalog.json'), true, flags: JSON_THROW_ON_ERROR);
            $entries = $catalog['books'];
            $counts = array_count_values(array_column($entries, 'category'));
            $expectedCount = array_sum($collection['categories']);
            if ($counts != $collection['categories']) {
                throw new RuntimeException('Jumlah judul per kategori tidak sesuai dengan koleksi yang dipilih.');
            }
            foreach (['source_key', 'isbn', 'title', 'inventory_code', 'cover_file'] as $key) {
                if (count(array_unique(array_column($entries, $key))) !== $expectedCount) {
                    throw new RuntimeException('Katalog berisi nilai kosong atau duplikat: '.$key);
                }
            }
            // Periksa seluruh aset sebelum transaksi pertama, termasuk saat dry-run.
            foreach ($entries as $entry) {
                Validator::make($entry, [
                    'title' => ['required', 'string', 'max:255'],
                    'author' => ['required', 'string', 'max:255'],
                    'publisher' => ['required', 'string', 'max:255'],
                    'publication_year' => ['required', 'integer', 'between:1,'.now()->year],
                    'isbn' => ['required', 'regex:/^[0-9]{13}$/'],
                    'description' => ['required', 'string', 'max:5000'],
                    'replacement_price' => ['required', 'integer', 'between:1,100000000'],
                    'source_key' => ['required', 'regex:/^gramedia:[0-9]+$/'],
                    'source_url' => ['required', 'url', 'starts_with:https://www.gramedia.com/products/'],
                    'inventory_code' => ['required', 'string', 'max:50'],
                    'shelf_location' => ['required', 'string', 'max:100'],
                    'cover_file' => ['required', 'regex:/^covers\/[a-z0-9-]+\.jpg$/'],
                    'cover_sha256' => ['required', 'regex:/^[a-f0-9]{64}$/'],
                ])->validate();
                $sum = 0;
                foreach (str_split($entry['isbn']) as $i => $digit) {
                    $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
                }
                if ($sum % 10 !== 0) {
                    throw new RuntimeException('Checksum ISBN tidak valid: '.$entry['title']);
                }
                $cover = $directory.'/'.$entry['cover_file'];
                $size = is_file($cover) ? @getimagesize($cover) : false;
                if (! $size || $size['mime'] !== 'image/jpeg' || $size[0] < 100 || $size[1] < 100
                    || $size[0] > 3000 || $size[1] > 3000 || filesize($cover) > 2 * 1024 * 1024
                    || hash_file('sha256', $cover) !== $entry['cover_sha256']) {
                    throw new RuntimeException('Cover tidak valid: '.$entry['title']);
                }
            }

            [$added, $skipped] = DB::transaction(function () use ($entries, $directory, &$written) {
                $existing = Book::get(['source_key', 'isbn', 'title']);
                $added = 0;
                $skipped = 0;
                foreach ($entries as $entry) {
                    $duplicate = $existing->contains(fn ($book) => $book->source_key === $entry['source_key']
                        || preg_replace('/[^0-9X]/', '', strtoupper($book->isbn ?? '')) === $entry['isbn']
                        || mb_strtolower(trim($book->title)) === mb_strtolower(trim($entry['title'])));
                    if ($duplicate) {
                        $skipped++;

                        continue;
                    }
                    if (BookCopy::where('inventory_code', $entry['inventory_code'])->exists()) {
                        throw new RuntimeException('Kode inventaris sudah digunakan: '.$entry['inventory_code']);
                    }
                    if (! $this->option('dry-run')) {
                        $path = 'covers/physical/'.str()->uuid().'.jpg';
                        $written[] = $path;
                        if (! Storage::disk('public')->put($path, file_get_contents($directory.'/'.$entry['cover_file']))) {
                            throw new RuntimeException('Cover gagal disimpan: '.$entry['title']);
                        }
                        $category = Category::firstOrCreate(['name' => $entry['category']]);
                        $book = Book::create([
                            'category_id' => $category->id,
                            'title' => $entry['title'], 'author' => $entry['author'],
                            'isbn' => $entry['isbn'], 'publisher' => $entry['publisher'],
                            'publication_year' => $entry['publication_year'],
                            'description' => $entry['description'],
                            'replacement_price' => $entry['replacement_price'],
                            'cover_path' => $path, 'source_key' => $entry['source_key'],
                            'source_url' => $entry['source_url'],
                        ]);
                        $book->copies()->create([
                            'inventory_code' => $entry['inventory_code'],
                            'shelf_location' => $entry['shelf_location'],
                            'condition' => 'baik',
                        ]);
                    }
                    $added++;
                }

                return [$added, $skipped];
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($written);
            report($exception);
            $this->error('Impor dibatalkan: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($this->option('dry-run')
            ? "{$added} judul dan eksemplar siap ditambahkan; {$skipped} judul sudah ada. Tidak ada data diubah."
            : "{$added} judul, {$added} cover lokal, dan {$added} eksemplar baik ditambahkan; {$skipped} judul sudah ada.");

        return self::SUCCESS;
    }
}
