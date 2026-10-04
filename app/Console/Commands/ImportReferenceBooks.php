<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportReferenceBooks extends Command
{
    protected $signature = 'library:import-reference-books {--dry-run : Periksa katalog tanpa menyimpan data}';

    protected $description = 'Tambahkan koleksi referensi SIBI beserta cover, tanpa membuat stok fisik';

    public function handle(): int
    {
        $directory = database_path('data/reference-books');
        $catalog = json_decode(file_get_contents($directory.'/catalog.json'), true, flags: JSON_THROW_ON_ERROR);
        $existing = Book::get(['source_key', 'isbn', 'title', 'author']);
        $normalizeIsbn = fn ($isbn) => preg_replace('/[^0-9X]/', '', strtoupper($isbn ?? ''));
        $pending = [];
        $skipped = 0;

        // Check every bundled cover before changing either storage or the database.
        foreach ($catalog['books'] as $entry) {
            $duplicate = $existing->contains(fn ($book) => $book->source_key === $entry['source_key']
                || ($normalizeIsbn($entry['isbn']) !== '' && $normalizeIsbn($book->isbn) === $normalizeIsbn($entry['isbn']))
                || (mb_strtolower(trim($book->title)) === mb_strtolower(trim($entry['title']))
                    && mb_strtolower(trim($book->author)) === mb_strtolower(trim($entry['author']))));
            if ($duplicate) {
                $skipped++;

                continue;
            }
            $cover = $directory.'/'.$entry['cover_file'];
            if (! is_file($cover) || hash_file('sha256', $cover) !== $entry['cover_sha256']
                || ! in_array(@getimagesize($cover)['mime'] ?? '', ['image/png', 'image/jpeg'], true)) {
                $this->error('Cover tidak valid: '.$entry['title']);

                return self::FAILURE;
            }
            $pending[] = $entry;
            $existing->push(new Book($entry));
        }

        if ($this->option('dry-run')) {
            $this->info(count($pending).' judul siap ditambahkan; '.$skipped.' judul sudah ada. Stok fisik tidak berubah.');

            return self::SUCCESS;
        }

        $written = [];
        try {
            DB::transaction(function () use ($pending, $directory, &$written) {
                foreach ($pending as $entry) {
                    // A unique path keeps existing uploads safe even if the import fails.
                    $path = 'covers/reference/'.str()->uuid().'.png';
                    $written[] = $path;
                    if (! Storage::disk('public')->put($path, file_get_contents($directory.'/'.$entry['cover_file']))) {
                        throw new RuntimeException('Cover gagal disimpan.');
                    }
                    $category = Category::firstOrCreate(['name' => $entry['category']]);
                    Book::create([
                        'category_id' => $category->id,
                        'title' => $entry['title'], 'author' => $entry['author'],
                        'isbn' => $entry['isbn'], 'publisher' => $entry['publisher'],
                        'publication_year' => $entry['publication_year'],
                        'description' => $entry['description'], 'cover_path' => $path,
                        'source_key' => $entry['source_key'], 'source_url' => $entry['source_url'],
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($written);
            report($exception);
            $this->error('Impor dibatalkan. Data lama tetap tersimpan; periksa log aplikasi.');

            return self::FAILURE;
        }

        $this->info(count($pending).' buku referensi ditambahkan; '.$skipped.' judul dilewati karena sudah ada.');
        $this->line('Cover disimpan lokal. Tidak ada eksemplar fisik atau harga penggantian yang dibuat.');

        return self::SUCCESS;
    }
}
