<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualBookStockTest extends TestCase
{
    use RefreshDatabase;

    private Book $book;

    private User $staff;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->book = Book::create(['category_id' => Category::create(['name' => 'Fiksi'])->id, 'title' => 'Stok Manual', 'author' => 'Penulis']);
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'STOCK-MEMBER']);
    }

    public function test_staff_can_add_set_zero_and_restore_stock_without_duplicating_copies(): void
    {
        $this->actingAs($this->staff)->get(route('copies.index', $this->book))->assertOk()->assertSee('Tambah Stok')->assertSee('Atur stok tersedia');
        $this->patch(route('copies.stock', $this->book), ['operation' => 'add', 'quantity' => 3, 'shelf_location' => 'Rak A'])
            ->assertSessionHasNoErrors()->assertRedirect(route('copies.index', $this->book));
        $this->assertSame(3, $this->book->copies()->available()->count());
        $this->assertSame(3, $this->book->copies()->where('shelf_location', 'Rak A')->count());
        $this->assertSame(3, $this->book->copies()->pluck('inventory_code')->unique()->count());
        $ids = $this->book->copies()->pluck('id')->all();
        $this->patch(route('copies.stock', $this->book), ['operation' => 'set', 'quantity' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $this->book->copies()->available()->count());
        $this->assertSame(3, $this->book->copies()->whereNotNull('archived_at')->count());
        $this->get('/books?availability=unavailable')->assertOk()->assertSee($this->book->title);
        $this->get(route('copies.index', $this->book))->assertOk()->assertSee('Nonaktif');
        $this->post('/loans', ['member_id' => $this->member->id, 'book_copy_ids' => [$ids[0]]])->assertSessionHasErrors('book_copy_ids');
        $this->actingAs($this->member->user)->get(route('catalog.show', $this->book))->assertOk()->assertViewHas('availableCount', 0);
        $this->actingAs($this->staff)->patch(route('copies.stock', $this->book), ['operation' => 'set', 'quantity' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->book->copies()->available()->count());
        $this->assertSame($ids, $this->book->copies()->pluck('id')->all());
        $this->patch(route('copies.stock', $this->book), ['operation' => 'set', 'quantity' => 2])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('book_copies', 3);
        $this->patch(route('copies.stock', $this->book), ['operation' => 'add', 'quantity' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('book_copies', 4);
        $this->assertSame(3, $this->book->copies()->available()->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'copies.stock', 'user_id' => $this->staff->id]);
    }

    public function test_stock_zero_preserves_returned_history_active_loans_and_reservation_holds(): void
    {
        $copies = collect(range(1, 4))->map(fn ($i) => $this->book->copies()->create(['inventory_code' => 'STOCK-'.$i, 'condition' => 'baik']));
        $loan = Loan::create(['loan_code' => 'STOCK-LOAN', 'member_id' => $this->member->id, 'created_by' => $this->staff->id, 'borrowed_at' => now()]);
        $loan->items()->create(['book_copy_id' => $copies[0]->id, 'due_date' => today()->addDays(7)]);
        $loan->items()->create(['book_copy_id' => $copies[1]->id, 'due_date' => today(), 'returned_at' => now()]);
        $hold = BookReservation::create(['member_id' => $this->member->id, 'book_id' => $this->book->id, 'book_copy_id' => $copies[2]->id, 'status' => 'ready', 'ready_at' => now(), 'expires_at' => now()->addDay()]);
        $beforeItems = LoanItem::all()->toArray();
        $beforeHold = $hold->fresh()->toArray();
        $this->actingAs($this->staff)->patch(route('copies.stock', $this->book), ['operation' => 'set', 'quantity' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $this->book->copies()->available()->count());
        $this->assertNull($copies[0]->fresh()->archived_at);
        $this->assertNull($copies[2]->fresh()->archived_at);
        $this->assertNotNull($copies[1]->fresh()->archived_at);
        $this->assertNotNull($copies[3]->fresh()->archived_at);
        $this->assertSame($beforeItems, LoanItem::all()->toArray());
        $this->assertSame($beforeHold, $hold->fresh()->toArray());
    }

    public function test_increasing_stock_does_not_restore_damaged_or_lost_copies(): void
    {
        foreach (['rusak', 'hilang'] as $condition) {
            $this->book->copies()->create(['inventory_code' => $condition, 'condition' => $condition, 'archived_at' => now()]);
        }
        $this->actingAs($this->staff)->patch(route('copies.stock', $this->book), ['operation' => 'set', 'quantity' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->book->copies()->available()->count());
        $this->assertSame(2, $this->book->copies()->whereNotNull('archived_at')->count());
        $this->assertDatabaseCount('book_copies', 4);
    }

    public function test_stock_endpoint_rejects_invalid_values_archived_books_and_non_staff(): void
    {
        $url = route('copies.stock', $this->book);
        $this->patch($url, ['operation' => 'set', 'quantity' => 1])->assertRedirect(route('login'));
        $this->actingAs($this->member->user)->patch($url, ['operation' => 'set', 'quantity' => 1])->assertForbidden();
        $this->actingAs($this->staff);
        foreach ([-1, 1.5, 1001, 'abc', null] as $quantity) {
            $this->patch($url, ['operation' => 'set', 'quantity' => $quantity])->assertSessionHasErrors('quantity');
        }
        $this->patch($url, ['operation' => 'add', 'quantity' => 0])->assertSessionHasErrors('quantity');
        $this->patch($url, ['operation' => 'delete', 'quantity' => 1])->assertSessionHasErrors('operation');
        $this->book->update(['archived_at' => now()]);
        $this->patch($url, ['operation' => 'set', 'quantity' => 1])->assertNotFound();
        $this->assertDatabaseCount('book_copies', 0);
    }
}
