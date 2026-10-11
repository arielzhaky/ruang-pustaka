<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookArchiveTest extends TestCase
{
    use RefreshDatabase;

    private Book $book;

    private BookCopy $copy;

    private Member $member;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::disk('public')->put('covers/archive.jpg', 'cover');
        $this->book = Book::create([
            'category_id' => Category::create(['name' => 'Fiksi'])->id,
            'title' => 'Maling Kundang Arsip', 'author' => 'Penulis', 'cover_path' => 'covers/archive.jpg',
        ]);
        $this->copy = $this->book->copies()->create(['inventory_code' => 'ARCH-001', 'condition' => 'baik']);
        $this->member = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'ARCH-MEMBER']);
        $this->staff = User::factory()->create(['role' => 'petugas']);
    }

    public function test_archive_preserves_history_cover_and_copies_and_restore_makes_book_available_again(): void
    {
        $item = $this->loanItem(true);
        $before = $item->fresh()->toArray();
        $this->actingAs($this->staff)->get('/books')->assertOk()->assertSee('Arsipkan');
        $this->patch(route('books.archive', $this->book))->assertSessionHasNoErrors()->assertRedirect(route('books.index'));
        $this->assertNotNull($this->book->fresh()->archived_at);
        $this->assertSame($before, $item->fresh()->toArray());
        $this->assertDatabaseCount('book_copies', 1);
        Storage::disk('public')->assertExists('covers/archive.jpg');
        $this->assertFalse(BookCopy::available()->whereKey($this->copy->id)->exists());
        $this->assertDatabaseHas('activity_logs', ['action' => 'books.archive', 'user_id' => $this->staff->id]);
        $this->get('/books')->assertOk()->assertDontSee($this->book->title);
        $this->get('/books?status=archived&q=Kundang')->assertOk()->assertSee($this->book->title)->assertSee('Pulihkan');
        $this->get('/loans')->assertOk()->assertSee($this->book->title);
        $this->actingAs($this->member->user)->get('/catalog')->assertOk()->assertDontSee($this->book->title);
        $this->get('/catalog/'.$this->book->id)->assertNotFound();
        $this->get('/my-loans')->assertOk()->assertSee($this->book->title);
        $this->actingAs($this->staff)->post('/loans', ['member_id' => $this->member->id, 'book_copy_ids' => [$this->copy->id]])
            ->assertSessionHasErrors('book_copy_ids');
        $this->patch(route('books.restore', $this->book))->assertRedirect(route('books.index', ['status' => 'archived']));
        $this->assertNull($this->book->fresh()->archived_at);
        $this->assertTrue(BookCopy::available()->whereKey($this->copy->id)->exists());
        $this->get('/books')->assertOk()->assertSee($this->book->title);
        $this->assertSame($before, $item->fresh()->toArray());
    }

    public function test_outstanding_loan_blocks_archive_until_returned(): void
    {
        $item = $this->loanItem(false);
        $this->actingAs($this->staff)->from('/books')->patch(route('books.archive', $this->book))
            ->assertRedirect('/books')->assertSessionHasErrors('book');
        $this->assertNull($this->book->fresh()->archived_at);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'books.archive']);
        $this->post(route('loans.items.return', $item), ['return_condition' => 'baik'])->assertSessionHasNoErrors();
        $this->patch(route('books.archive', $this->book))->assertSessionHasNoErrors();
        $this->assertNotNull($this->book->fresh()->archived_at);
    }

    public function test_waiting_and_ready_reservations_block_archive_but_expired_reservation_does_not(): void
    {
        $reservation = BookReservation::create(['book_id' => $this->book->id, 'member_id' => $this->member->id, 'status' => 'waiting']);
        $this->actingAs($this->staff)->patch(route('books.archive', $this->book))->assertSessionHasErrors('book');
        $this->assertNull($this->book->fresh()->archived_at);
        $reservation->update(['status' => 'ready', 'book_copy_id' => $this->copy->id, 'ready_at' => now(), 'expires_at' => now()->addDay()]);
        $this->patch(route('books.archive', $this->book))->assertSessionHasErrors('book');
        $this->assertNull($this->book->fresh()->archived_at);
        $reservation->update(['expires_at' => now()->subMinute()]);
        $this->patch(route('books.archive', $this->book))->assertSessionHasNoErrors();
        $this->assertNotNull($this->book->fresh()->archived_at);
        $this->assertDatabaseCount('book_reservations', 1);
    }

    public function test_only_staff_can_archive_or_restore(): void
    {
        $this->patch(route('books.archive', $this->book))->assertRedirect(route('login'));
        $this->actingAs($this->member->user)->patch(route('books.archive', $this->book))->assertForbidden();
        $this->book->update(['archived_at' => now()]);
        $this->patch(route('books.restore', $this->book))->assertForbidden();
        $this->assertNotNull($this->book->fresh()->archived_at);
    }

    public function test_permanent_deletion_of_a_book_with_history_stays_blocked(): void
    {
        $this->loanItem(true);
        $this->actingAs($this->staff)->delete(route('books.destroy', $this->book))->assertSessionHas('error');
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseCount('loan_items', 1);
        $this->assertDatabaseCount('book_copies', 1);
    }

    private function loanItem(bool $returned): LoanItem
    {
        return Loan::create(['loan_code' => 'ARCH-LOAN', 'member_id' => $this->member->id,
            'created_by' => $this->staff->id, 'borrowed_at' => now()->subDays(2)])
            ->items()->create(['book_copy_id' => $this->copy->id, 'due_date' => today()->addDays(5),
                'returned_at' => $returned ? now() : null]);
    }
}
