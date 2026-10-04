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

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Novel']);
    }

    public function test_guests_can_open_an_empty_library_home(): void
    {
        $this->get(route('home'))->assertOk()->assertViewIs('home')
            ->assertViewHas('books', fn ($books) => $books->isEmpty())
            ->assertViewHas('search', '')
            ->assertViewHas('bookCount', 0)->assertViewHas('availableCount', 0)
            ->assertSee(route('login'))->assertSee(route('register'));
    }

    public function test_search_matches_title_author_and_isbn_but_excludes_archived_books(): void
    {
        $author = $this->book('Awan Pagi', ['author' => 'Bintang Penulis']);
        $title = $this->book('Bintang di Langit');
        $isbn = $this->book('Cerita Malam', ['isbn' => 'Bintang-123']);
        $this->book('Tidak Cocok');
        $this->book('Bintang Arsip', ['archived_at' => now()]);
        $this->book('Arsip Penulis', ['author' => 'Bintang', 'archived_at' => now()]);
        $this->book('Arsip ISBN', ['isbn' => 'Bintang-456', 'archived_at' => now()]);

        $this->get(route('home', ['q' => 'Bintang']))->assertOk()
            ->assertViewHas('search', 'Bintang')
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === [$author->id, $title->id, $isbn->id])
            ->assertViewHas('bookCount', 4)
            ->assertSee('Awan Pagi')->assertSee('Bintang di Langit')->assertSee('Cerita Malam')
            ->assertDontSee('Tidak Cocok')->assertDontSee('Bintang Arsip')
            ->assertDontSee('Arsip Penulis')->assertDontSee('Arsip ISBN');
    }

    public function test_available_counts_exclude_borrowed_damaged_lost_and_archived_copies(): void
    {
        $book = $this->book('Buku Utama');
        $this->copy($book, 'READY');
        $returned = $this->copy($book, 'RETURNED');
        $borrowed = $this->copy($book, 'BORROWED');
        $this->copy($book, 'DAMAGED', ['condition' => 'rusak']);
        $this->copy($book, 'LOST', ['condition' => 'hilang']);
        $this->copy($book, 'ARCHIVED', ['archived_at' => now()]);
        $this->copy($this->book('Arsip', ['archived_at' => now()]), 'HIDDEN-BOOK');
        $this->copy($this->book('Buku Lain'), 'OTHER-READY');
        $borrower = User::factory()->create(['name' => 'Nama Rahasia Peminjam']);
        $member = Member::create(['user_id' => $borrower->id, 'member_code' => 'ANG-RAHASIA']);
        $loan = Loan::create([
            'loan_code' => 'PINJAMAN-RAHASIA', 'member_id' => $member->id,
            'created_by' => User::factory()->create(['role' => 'petugas'])->id,
            'borrowed_at' => now()->subDays(7),
        ]);
        foreach ([$returned, $borrowed] as $copy) {
            $loan->items()->create([
                'book_copy_id' => $copy->id, 'due_date' => today(),
                'returned_at' => $copy->id === $returned->id ? now() : null,
            ]);
        }

        $this->get(route('home', ['q' => 'Utama']))->assertOk()
            ->assertViewHas('availableCount', 3)->assertViewHas('bookCount', 2)
            ->assertViewHas('books', fn ($books) => $books->first()->available_count === 2)
            ->assertDontSee('Nama Rahasia Peminjam')->assertDontSee($borrower->email)
            ->assertDontSee('ANG-RAHASIA')->assertDontSee('PINJAMAN-RAHASIA');
    }

    public function test_latest_books_are_shown_first_and_search_pagination_preserves_query(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->book(sprintf('Buku %02d', $i))->id;
        }
        $this->get(route('home'))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === array_slice(array_reverse($ids), 0, 8));
        $this->get(route('home', ['q' => 'Buku', 'page' => 2]))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === array_slice($ids, 8)
                && $books->total() === 10 && str_contains($books->url(1), 'q=Buku'))
            ->assertSee('Buku 08')->assertSee('Buku 09')->assertDontSee('Buku 00');
    }

    public function test_student_and_staff_accounts_can_open_the_public_home(): void
    {
        foreach (['anggota', 'petugas'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('home'))->assertOk()->assertSee(route('dashboard'));
        }
    }

    public function test_book_metadata_and_search_input_are_escaped(): void
    {
        $title = '<script>alert("title")</script>';
        $author = '<img src=x onerror=alert(1)>';
        $this->book($title, ['author' => $author]);
        $this->get(route('home'))->assertOk()->assertSee($title)->assertSee($author)
            ->assertDontSee($title, false)->assertDontSee($author, false);

        $search = '\"><script>alert("search")</script>';
        $this->get(route('home', ['q' => $search]))->assertOk()
            ->assertSee($search)->assertDontSee($search, false)
            ->assertViewHas('books', fn ($books) => $books->isEmpty());
    }

    public static function invalidSearch(): array
    {
        return [
            ['q[]=invalid', 'q'], ['q='.str_repeat('x', 101), 'q'],
            ['page=0', 'page'], ['page[]=invalid', 'page'], ['page=abc', 'page'],
        ];
    }

    #[DataProvider('invalidSearch')]
    public function test_invalid_search_inputs_are_rejected(string $query, string $field): void
    {
        $this->from(route('home'))->get('/?'.$query)
            ->assertRedirect(route('home'))->assertSessionHasErrors($field);
    }

    public function test_invalid_array_search_can_render_the_redirect_with_validation_errors(): void
    {
        $this->from(route('home'))->get('/?q[]=invalid')
            ->assertRedirect(route('home'))->assertSessionHasErrors('q');
        $this->get(route('home'))->assertOk()->assertSee('Kata pencarian harus berupa teks.');
    }

    public function test_popular_books_count_loans_across_copies_and_keep_historical_returns(): void
    {
        $popular = $this->book('Favorit Pembaca');
        $oldCopy = $this->copy($popular, 'OLD', ['archived_at' => now()]);
        $this->borrow($oldCopy, true);
        $this->borrow($oldCopy, true);
        $this->borrow($this->copy($popular, 'OUT'));
        $this->copy($popular, 'AVAILABLE');
        $this->copy($popular, 'DAMAGED', ['condition' => 'rusak']);
        $runnerUp = $this->book('Pilihan Kedua');
        $this->borrow($this->copy($runnerUp, 'SECOND'), true);
        $archived = $this->book('Buku Arsip', ['archived_at' => now()]);
        $this->borrow($this->copy($archived, 'ARCHIVED'), true);
        $this->book('Belum Pernah Dipinjam');

        $this->get(route('home'))->assertOk()
            ->assertViewHas('popularBooks', fn ($books) => $books->pluck('id')->all() === [$popular->id, $runnerUp->id]
                && $books->pluck('loan_items_count')->all() === [3, 1]
                && $books->pluck('available_count')->all() === [1, 1])
            ->assertSee('Buku Populer')->assertSee('3 kali dipinjam')
            ->assertDontSee('Buku Arsip');

        $this->get(route('home', ['q' => 'Pilihan Kedua']))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === [$runnerUp->id])
            ->assertViewHas('popularBooks', fn ($books) => $books->pluck('id')->all() === [$popular->id, $runnerUp->id]);
    }

    public function test_popular_books_are_limited_to_four_with_consistent_tie_order_and_role_links(): void
    {
        $books = collect();
        foreach (['E', 'D', 'C', 'B', 'A'] as $title) {
            $book = $this->book($title, ['cover_path' => 'covers/contoh.jpg']);
            $books->put($title, $book);
            $this->borrow($this->copy($book, $title));
        }
        $expected = ['A', 'B', 'C', 'D'];
        $this->get(route('home'))->assertOk()
            ->assertViewHas('popularBooks', fn ($books) => $books->pluck('title')->all() === $expected)
            ->assertSee('1 kali dipinjam')->assertSee('Belum tersedia')
            ->assertSee(asset('storage/covers/contoh.jpg'))
            ->assertSee('Masuk untuk detail')->assertDontSee(route('catalog.show', $books['A']));

        foreach (['anggota' => 'catalog.show', 'petugas' => 'books.show'] as $role => $route) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('home'))->assertOk()
                ->assertViewHas('popularBooks', fn ($books) => $books->pluck('title')->all() === $expected)
                ->assertSee(route($route, $books['A']));
        }
    }

    public function test_books_without_borrowing_history_are_not_presented_as_popular(): void
    {
        $this->copy($this->book('Buku Baru'), 'NEW');
        $this->get(route('home'))->assertOk()
            ->assertViewHas('popularBooks', fn ($books) => $books->isEmpty())
            ->assertSee('Belum ada riwayat peminjaman.')
            ->assertDontSee('kali dipinjam');
    }

    private function borrow(BookCopy $copy, bool $returned = false): void
    {
        $member = Member::create([
            'user_id' => User::factory()->create()->id,
            'member_code' => 'ANG-'.str()->uuid(),
        ]);
        $loan = Loan::create([
            'loan_code' => 'PJM-'.str()->uuid(), 'member_id' => $member->id,
            'created_by' => User::factory()->create(['role' => 'petugas'])->id,
            'borrowed_at' => now()->subDays(10),
        ]);
        $loan->items()->create([
            'book_copy_id' => $copy->id, 'due_date' => today()->subDays(3),
            'returned_at' => $returned ? now()->subDays(5) : null,
        ]);
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
