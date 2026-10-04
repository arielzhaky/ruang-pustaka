<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReferenceBookImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_import_adds_verified_covers_and_sources_without_physical_stock(): void
    {
        Storage::fake('public');
        $this->artisan('library:import-reference-books', ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('books', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->artisan('library:import-reference-books')->assertSuccessful();
        $this->assertDatabaseCount('books', 20);
        $this->assertDatabaseCount('book_copies', 0);
        foreach (Book::all() as $book) {
            Storage::disk('public')->assertExists($book->cover_path);
            $this->assertNotNull(getimagesize(Storage::disk('public')->path($book->cover_path)));
            $this->assertStringStartsWith('https://buku.kemendikdasmen.go.id/katalog/', $book->source_url);
            $this->assertNull($book->replacement_price);
        }
        $this->get('/')->assertOk()->assertSee('SMPN 26 TANGERANG')->assertSee('Baca di SIBI');
        $book = Book::first();
        foreach (['anggota' => 'catalog.show', 'petugas' => 'books.show'] as $role => $route) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route($route, $book))->assertOk()->assertSee($book->source_url)->assertSee('Koleksi referensi digital');
            $this->get('/dashboard')->assertOk()->assertSee('SMPN 26 TANGERANG');
        }
    }

    public function test_import_is_repeatable_and_preserves_existing_editions_and_covers(): void
    {
        Storage::fake('public');
        $entry = json_decode(file_get_contents(database_path('data/reference-books/catalog.json')), true)['books'][0];
        Storage::disk('public')->put('covers/original.png', 'original');
        $book = Book::create([
            'category_id' => Category::create(['name' => 'Koleksi sekolah'])->id,
            'title' => 'Judul milik sekolah', 'author' => 'Penulis sekolah',
            'isbn' => str_replace('-', '', $entry['isbn']),
            'cover_path' => 'covers/original.png', 'archived_at' => now(),
        ]);
        $this->artisan('library:import-reference-books')->assertSuccessful();
        $this->artisan('library:import-reference-books')->assertSuccessful();
        $this->assertDatabaseCount('books', 20);
        $this->assertSame('Judul milik sekolah', $book->fresh()->title);
        $this->assertSame('covers/original.png', $book->fresh()->cover_path);
        $this->assertNotNull($book->fresh()->archived_at);
        $this->assertCount(20, Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('book_copies', 0);
    }

    public function test_database_failure_rolls_back_books_categories_and_imported_cover_files(): void
    {
        Storage::fake('public');
        $created = 0;
        Book::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new \RuntimeException('Simulated database failure');
            }
        });
        try {
            $this->artisan('library:import-reference-books')->assertFailed();
            $this->assertDatabaseCount('books', 0);
            $this->assertDatabaseCount('categories', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        } finally {
            Book::flushEventListeners();
        }
    }
}
