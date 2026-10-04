<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Novel']);
        $this->actingAs(User::factory()->create());
    }

    public function test_search_and_category_filter_work_together(): void
    {
        $matching = $this->book('Cerita Hutan');
        $authorMatch = $this->book('Kisah Pegunungan', ['author' => 'Hutan Penulis']);
        $this->book('Judul Tidak Cocok');
        $this->book('Hutan Kategori Lain', ['category_id' => Category::create(['name' => 'Sains'])->id]);
        $this->book('Hutan Arsip', ['archived_at' => now()]);
        $this->get('/catalog?q=Hutan&category_id='.$this->category->id)->assertOk()
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === [$matching->id, $authorMatch->id])
            ->assertSee('2 judul ditemukan.')->assertSee('Cerita Hutan')->assertSee('Kisah Pegunungan')
            ->assertDontSee('Judul Tidak Cocok')->assertDontSee('Hutan Kategori Lain')->assertDontSee('Hutan Arsip');
    }

    public function test_pagination_and_detail_back_link_preserve_search_filters(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $book = $this->book(sprintf('Cerita %02d', $i));
        }
        $query = ['q' => 'Cerita', 'category_id' => $this->category->id, 'page' => 2];
        $this->get(route('catalog.index', $query))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 2
                && str_contains($books->url(1), 'category_id='.$this->category->id)
                && str_contains($books->url(1), 'q=Cerita'))
            ->assertSee(route('catalog.show', array_merge(['book' => $book->id], $query)));
        $this->get(route('catalog.show', array_merge(['book' => $book->id], $query)))
            ->assertOk()->assertSee(route('catalog.index', $query));
    }

    public function test_detail_and_catalog_agree_on_availability_without_revealing_borrowers(): void
    {
        $book = $this->book('Buku Lengkap', [
            'isbn' => '9781234567890', 'publisher' => 'Penerbit Contoh',
            'publication_year' => 2020, 'description' => 'Deskripsi contoh buku.',
        ]);
        $this->copy($book, 'AVAILABLE', ['shelf_location' => 'Rak A-1']);
        $borrowed = $this->copy($book, 'BORROWED');
        $returned = $this->copy($book, 'RETURNED');
        $this->copy($book, 'DAMAGED', ['condition' => 'rusak']);
        $this->copy($book, 'LOST', ['condition' => 'hilang']);
        $this->copy($book, 'HIDDEN', ['archived_at' => now()]);
        $borrower = User::factory()->create(['name' => 'Nama Rahasia Peminjam']);
        $member = Member::create(['user_id' => $borrower->id, 'member_code' => 'MEMBER-TEST']);
        $loan = Loan::create([
            'loan_code' => 'KODE-RAHASIA', 'member_id' => $member->id,
            'created_by' => User::factory()->create(['role' => 'petugas'])->id,
            'borrowed_at' => now()->subDays(7),
        ]);
        foreach ([$borrowed, $returned] as $copy) {
            $loan->items()->create([
                'book_copy_id' => $copy->id, 'due_date' => today(),
                'returned_at' => $copy->id === $returned->id ? now() : null,
            ]);
        }
        $this->get('/catalog')->assertOk()->assertViewHas('books', fn ($books) => $books->first()->available_count === 2);
        $this->get(route('catalog.show', $book))->assertOk()->assertViewHas('availableCount', 2)
            ->assertViewHas('copies', fn ($copies) => $copies->total() === 5)
            ->assertSee('9781234567890')->assertSee('Penerbit Contoh')->assertSee('2020')
            ->assertSee('Deskripsi contoh buku.')->assertSee('Rak A-1')->assertSee('Sedang dipinjam')
            ->assertSee('Rusak — tidak tersedia')->assertSee('Hilang — tidak tersedia')
            ->assertSee('Tanyakan kepada petugas')->assertDontSee('HIDDEN')
            ->assertDontSee('Nama Rahasia Peminjam')->assertDontSee($borrower->email)->assertDontSee('KODE-RAHASIA');
    }

    public function test_unavailable_book_and_empty_search_have_helpful_messages(): void
    {
        $book = $this->book('Buku tanpa eksemplar');
        $this->get(route('catalog.show', $book))->assertOk()->assertViewHas('availableCount', 0)
            ->assertSee('Belum dicantumkan')->assertSee('Deskripsi buku belum tersedia.')
            ->assertSee('Buku ini belum tersedia untuk dipinjam.')
            ->assertSee('Belum ada eksemplar yang ditampilkan.');
        $this->get('/catalog?q=TidakCocok')->assertOk()->assertSee('Tidak ada buku yang cocok.');
    }

    public function test_archived_or_missing_books_cannot_be_opened(): void
    {
        $book = $this->book('Arsip', ['archived_at' => now()]);
        $this->get(route('catalog.show', $book))->assertNotFound();
        $this->get('/catalog/999999')->assertNotFound();
    }

    public function test_detail_requires_an_active_member_account(): void
    {
        $book = $this->book('Buku');
        auth()->logout();
        $this->get(route('catalog.show', $book))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get(route('catalog.show', $book))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_active' => false]))
            ->get(route('catalog.show', $book))->assertRedirect(route('login'));
    }

    public function test_description_and_title_are_escaped(): void
    {
        $title = '<script>alert("title")</script>';
        $description = '<img src=x onerror=alert(1)>';
        $book = $this->book($title, ['description' => $description]);
        $this->get(route('catalog.show', $book))->assertOk()
            ->assertSee($title)->assertSee($description)
            ->assertDontSee($title, false)->assertDontSee($description, false);
    }

    public function test_copy_pagination_uses_its_own_page_parameter(): void
    {
        $book = $this->book('Banyak eksemplar');
        for ($i = 0; $i < 12; $i++) {
            $this->copy($book, sprintf('COPY-%02d', $i));
        }
        $this->get(route('catalog.show', ['book' => $book->id, 'page' => 3, 'copies_page' => 2]))
            ->assertOk()->assertViewHas('availableCount', 12)
            ->assertViewHas('copies', fn ($copies) => $copies->count() === 2 && $copies->total() === 12)
            ->assertSee('COPY-10')->assertDontSee('COPY-00');
    }

    public static function invalidFilters(): array
    {
        return [
            ['category_id=abc', 'category_id'], ['category_id=99999', 'category_id'],
            ['q[]=invalid', 'q'], ['q='.str_repeat('a', 101), 'q'],
            ['page=0', 'page'], ['copies_page[]=invalid', 'copies_page'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected(string $query, string $field): void
    {
        $this->from('/catalog')->get('/catalog?'.$query)->assertRedirect('/catalog')->assertSessionHasErrors($field);
    }

    private function book(string $title, array $extra = []): Book
    {
        return Book::create(array_merge([
            'title' => $title, 'author' => 'Penulis Contoh', 'category_id' => $this->category->id,
        ], $extra));
    }

    private function copy(Book $book, string $code, array $extra = []): BookCopy
    {
        return $book->copies()->create(array_merge(['inventory_code' => $code, 'condition' => 'baik'], $extra));
    }
}
