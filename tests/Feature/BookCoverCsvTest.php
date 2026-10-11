<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookCoverCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Category::create(['name' => 'Novel']);
        $this->actingAs(User::factory()->create(['role' => 'petugas']));
    }

    private function csv(array $rows): UploadedFile
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['judul', 'penulis', 'kategori', 'isbn', 'penerbit', 'tahun_terbit', 'deskripsi', 'cover']);
        foreach ($rows as [$title, $cover]) {
            fputcsv($handle, [$title, 'Penulis', 'Novel', '', '', '', '', $cover]);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return UploadedFile::fake()->createWithContent('buku.csv', $content);
    }

    public function test_import_matches_jpg_by_filename_not_upload_order_and_allows_empty_cover(): void
    {
        $first = UploadedFile::fake()->image('satu.jpg', 100, 200);
        $second = UploadedFile::fake()->image('dua.jpeg', 200, 300);
        $this->post('/books/import', ['file' => $this->csv([['Satu', 'satu.jpg'], ['Dua', 'dua.jpeg'], ['Tanpa cover', '']]), 'covers' => [$second, $first]])
            ->assertSessionHasNoErrors()->assertRedirect(route('books.index'));
        $this->assertDatabaseCount('books', 3);
        foreach (['Satu' => $first, 'Dua' => $second] as $title => $upload) {
            $book = Book::where('title', $title)->firstOrFail();
            $this->assertSame(hash_file('sha256', $upload->getRealPath()), hash_file('sha256', Storage::disk('public')->path($book->cover_path)));
            $this->get(route('books.show', $book))->assertOk()->assertSee($book->cover_path);
        }
        $this->assertNull(Book::where('title', 'Tanpa cover')->firstOrFail()->cover_path);
        $this->assertCount(2, Storage::disk('public')->allFiles());
        $this->get('/books/template')->assertOk()->assertStreamedContent("\xEF\xBB\xBFkode_buku,judul,pengarang,penerbit,tahun_terbit,kategori,isbn,jumlah_eksemplar,tersedia,rak\n");
        $this->get('/books')->assertOk()->assertSee('name="covers[]"', false);
    }

    public function test_missing_duplicate_and_unused_filenames_are_rejected_before_writes(): void
    {
        $this->post('/books/import', ['file' => $this->csv([['Satu', 'missing.jpg']])])->assertSessionHasErrors('file');
        $this->post('/books/import', ['file' => $this->csv([['Satu', 'satu.jpg']]), 'covers' => [UploadedFile::fake()->image('satu.jpg'), UploadedFile::fake()->image('satu.jpg')]])->assertSessionHasErrors('covers');
        $this->post('/books/import', ['file' => $this->csv([['Satu', '']]), 'covers' => [UploadedFile::fake()->image('unused.jpg')]])->assertSessionHasErrors('covers');
        $this->assertDatabaseCount('books', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_invalid_images_extensions_sizes_and_dimensions_are_rejected(): void
    {
        foreach ([
            UploadedFile::fake()->createWithContent('cover.jpg', '<?php echo "not a photo";'),
            UploadedFile::fake()->image('cover.png'),
            UploadedFile::fake()->image('cover.jpg')->size(2049),
            UploadedFile::fake()->image('cover.jpg', 3001, 10),
        ] as $upload) {
            $this->post('/books/import', ['file' => $this->csv([['Satu', $upload->getClientOriginalName()]]), 'covers' => [$upload]])
                ->assertSessionHasErrors('covers.0');
        }
        $this->assertDatabaseCount('books', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_later_row_rolls_back_books_and_removes_only_new_covers(): void
    {
        Storage::disk('public')->put('covers/existing.jpg', 'existing');
        $this->post('/books/import', ['file' => $this->csv([['Duplikat', 'satu.jpg'], ['Duplikat', '']]), 'covers' => [UploadedFile::fake()->image('satu.jpg')]])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('books', 0);
        $this->assertSame(['covers/existing.jpg'], Storage::disk('public')->allFiles());
        $this->assertSame('existing', Storage::disk('public')->get('covers/existing.jpg'));
    }

    public function test_storage_failure_rolls_back_import(): void
    {
        Storage::disk('public')->put('covers/existing.jpg', 'existing');
        $disk = Storage::disk('public');
        Storage::shouldReceive('disk')->with('public')->andReturnSelf();
        Storage::shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('delete')->once()->andReturn(true);
        $this->post('/books/import', ['file' => $this->csv([['Satu', 'satu.jpg']]), 'covers' => [UploadedFile::fake()->image('satu.jpg')]])
            ->assertSessionHasErrors('covers');
        $this->assertDatabaseCount('books', 0);
        $this->assertSame('existing', $disk->get('covers/existing.jpg'));
    }

    public function test_manual_book_creation_accepts_jpg_and_members_cannot_import(): void
    {
        $this->get('/books/create')->assertOk()->assertSee('Upload cover manual');
        $this->post('/books', ['category_id' => Category::firstOrFail()->id, 'title' => 'Buku Manual', 'author' => 'Penulis', 'cover' => UploadedFile::fake()->image('manual.jpg')])
            ->assertSessionHasNoErrors();
        $book = Book::firstOrFail();
        Storage::disk('public')->assertExists($book->cover_path);
        $this->get(route('books.edit', $book))->assertOk()->assertSee('Upload cover manual');
        $this->actingAs(User::factory()->create())->post('/books/import', ['file' => $this->csv([['Baru', '']])])->assertForbidden();
        $this->assertDatabaseCount('books', 1);
    }
}
