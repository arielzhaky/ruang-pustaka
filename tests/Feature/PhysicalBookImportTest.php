<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhysicalBookImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_adds_forty_complete_books_and_one_available_copy_each_and_can_be_repeated(): void
    {
        Storage::fake('public');
        $this->artisan('library:import-physical-books', ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_copies', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->artisan('library:import-physical-books')->assertSuccessful();
        $this->assertDatabaseCount('books', 40);
        $this->assertDatabaseCount('book_copies', 40);
        $this->assertSame(40, BookCopy::available()->count());
        foreach (Category::withCount('books')->get() as $category) {
            $this->assertSame(10, $category->books_count);
        }
        foreach (Book::with('copies')->get() as $book) {
            $this->assertCount(1, $book->copies);
            $this->assertSame('baik', $book->copies->first()->condition);
            $this->assertNotEmpty($book->copies->first()->shelf_location);
            foreach (['author', 'isbn', 'publisher', 'publication_year', 'description', 'replacement_price', 'source_url'] as $field) {
                $this->assertNotEmpty($book->$field);
            }
            $this->assertStringContainsString('Jumlah halaman:', $book->description);
            $this->assertFalse($book->is_sibi_reference);
            Storage::disk('public')->assertExists($book->cover_path);
            $this->assertSame('image/jpeg', getimagesize(Storage::disk('public')->path($book->cover_path))['mime']);
        }
        $before = Book::all()->toArray();
        $this->artisan('library:import-physical-books')->assertSuccessful();
        $this->assertSame($before, Book::all()->toArray());
        $this->assertDatabaseCount('book_copies', 40);
        $this->assertCount(40, Storage::disk('public')->allFiles());
    }

    public function test_existing_book_is_preserved_and_does_not_get_an_extra_copy(): void
    {
        Storage::fake('public');
        $entry = $this->catalog()[0];
        Storage::disk('public')->put('covers/school.jpg', 'school cover');
        $book = Book::create([
            'category_id' => Category::create(['name' => 'Koleksi lama'])->id,
            'title' => 'Edisi milik sekolah', 'author' => 'Penulis sekolah',
            'isbn' => substr($entry['isbn'], 0, 3).'-'.substr($entry['isbn'], 3),
            'cover_path' => 'covers/school.jpg', 'archived_at' => now(),
        ]);
        $book->copies()->create(['inventory_code' => 'OLD-001', 'condition' => 'rusak']);
        $before = $book->fresh()->toArray();
        $this->artisan('library:import-physical-books')->assertSuccessful();
        $this->assertSame($before, $book->fresh()->toArray());
        $this->assertDatabaseCount('books', 40);
        $this->assertDatabaseCount('book_copies', 40);
        $this->assertSame(1, $book->copies()->count());
        $this->assertSame('school cover', Storage::disk('public')->get('covers/school.jpg'));
    }

    public function test_failed_copy_creation_rolls_back_all_books_categories_and_new_covers(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('covers/existing.jpg', 'existing');
        $count = 0;
        BookCopy::creating(function () use (&$count) {
            if (++$count === 2) {
                throw new \RuntimeException('Simulated copy failure');
            }
        });
        try {
            $this->artisan('library:import-physical-books')->assertFailed();
            $this->assertDatabaseCount('books', 0);
            $this->assertDatabaseCount('book_copies', 0);
            $this->assertDatabaseCount('categories', 0);
            $this->assertSame(['covers/existing.jpg'], Storage::disk('public')->allFiles());
        } finally {
            BookCopy::flushEventListeners();
        }
    }

    public function test_inventory_collision_cancels_import_without_modifying_existing_records(): void
    {
        Storage::fake('public');
        $book = Book::create(['category_id' => Category::create(['name' => 'Lama'])->id,
            'title' => 'Buku sekolah', 'author' => 'Penulis']);
        $book->copies()->create(['inventory_code' => $this->catalog()[1]['inventory_code'], 'condition' => 'baik']);
        $this->artisan('library:import-physical-books')->assertFailed();
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseCount('book_copies', 1);
        $this->assertDatabaseCount('categories', 1);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_new_books_render_with_catalog_sources_and_can_be_borrowed_and_returned(): void
    {
        Storage::fake('public');
        $this->artisan('library:import-physical-books')->assertSuccessful();
        $student = User::factory()->create();
        $member = $student->member()->create(['member_code' => 'IMPORT-TEST']);
        $staff = User::factory()->create(['role' => 'petugas']);
        $this->get('/')->assertOk()->assertSee('Sumber katalog')->assertDontSee('Baca di SIBI');
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Sumber katalog');
        $this->get('/catalog')->assertOk()->assertDontSee('Referensi digital');
        foreach (Category::all() as $category) {
            $book = $category->books()->firstOrFail();
            $copy = $book->copies()->firstOrFail();
            $this->actingAs($student)->get(route('catalog.show', $book))->assertOk()
                ->assertSee($book->source_url)->assertSee('Sumber katalog')->assertDontSee('Baca di SIBI')
                ->assertViewHas('availableCount', 1)->assertSee($book->isbn)->assertSee($copy->shelf_location);
            $this->actingAs($staff)->post('/loans', ['member_id' => $member->id, 'book_copy_ids' => [$copy->id]])
                ->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
            $this->assertFalse(BookCopy::available()->whereKey($copy->id)->exists());
            $item = LoanItem::where('book_copy_id', $copy->id)->firstOrFail();
            $this->post(route('loans.items.return', $item), ['return_condition' => 'baik'])->assertSessionHasNoErrors();
            $this->assertTrue(BookCopy::available()->whereKey($copy->id)->exists());
        }
    }

    private function catalog(): array
    {
        return json_decode(file_get_contents(database_path('data/physical-books/catalog.json')), true)['books'];
    }

    public function test_extra_collection_adds_twenty_distinct_books_without_changing_original_collection(): void
    {
        Storage::fake('public');
        $this->artisan('library:import-physical-books')->assertSuccessful();
        $original = Book::with('copies')->orderBy('id')->get()->toArray();
        $this->artisan('library:import-physical-books', ['--collection' => 'novels-20', '--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('books', 40);
        $this->assertCount(40, Storage::disk('public')->allFiles());

        $this->artisan('library:import-physical-books', ['--collection' => 'novels-20'])->assertSuccessful();
        $this->assertDatabaseCount('books', 60);
        $this->assertDatabaseCount('book_copies', 60);
        $this->assertSame($original, Book::with('copies')->orderBy('id')->limit(40)->get()->toArray());
        $this->assertSame(18, Book::whereHas('category', fn ($q) => $q->where('name', 'Novel'))->count());
        $this->assertSame(2, Book::whereHas('category', fn ($q) => $q->where('name', 'Informatika'))->count());
        $entries = json_decode(file_get_contents(database_path('data/physical-books-extra/catalog.json')), true)['books'];
        foreach ($entries as $entry) {
            $book = Book::with('copies')->where('source_key', $entry['source_key'])->firstOrFail();
            $this->assertCount(1, $book->copies);
            $this->assertSame('baik', $book->copies->first()->condition);
            $this->assertTrue(BookCopy::available()->whereKey($book->copies->first()->id)->exists());
            $this->assertSame($entry['cover_sha256'], hash_file('sha256', Storage::disk('public')->path($book->cover_path)));
            foreach (['isbn', 'publisher', 'description', 'author', 'publication_year', 'replacement_price'] as $field) {
                $this->assertNotEmpty($book->$field);
                $this->assertEquals($entry[$field], $book->$field);
            }
        }
        $this->artisan('library:import-physical-books', ['--collection' => 'novels-20'])->assertSuccessful();
        $this->assertDatabaseCount('books', 60);
        $this->assertDatabaseCount('book_copies', 60);
        $this->assertCount(60, Storage::disk('public')->allFiles());
    }

    public function test_unknown_collection_fails_without_writes(): void
    {
        Storage::fake('public');
        $this->artisan('library:import-physical-books', ['--collection' => '../unknown'])->assertFailed();
        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
