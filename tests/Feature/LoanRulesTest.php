<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoanRulesTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        config(['library.max_active_loans' => 3, 'library.loan_days' => 7]);
        $this->actingAs(User::factory()->create(['role' => 'petugas']));
        $this->member = Member::create([
            'user_id' => User::factory()->create()->id,
            'member_code' => 'TEST-MEMBER',
        ]);
        $this->book = Book::create([
            'category_id' => Category::create(['name' => 'Pengujian'])->id,
            'title' => 'Buku pengujian',
            'author' => 'Penulis',
        ]);
    }

    public function test_multiple_copies_share_one_transaction_and_default_due_date(): void
    {
        $ids = $this->copies(3);
        $this->get('/loans/create')->assertOk()->assertSee('book_copy_ids[]', false)
            ->assertSee(today()->addDays(7)->toDateString());
        $this->borrow($ids)->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('loan_items', 3);
        $this->assertSame(1, LoanItem::distinct()->count('loan_id'));
        foreach ($ids as $id) {
            $this->assertDatabaseHas('loan_items', [
                'book_copy_id' => $id, 'due_date' => today()->addDays(7)->toDateString(),
            ]);
        }
    }

    public function test_existing_overdue_loans_count_towards_the_limit(): void
    {
        $ids = $this->copies(4);
        $this->borrow(array_slice($ids, 0, 2))->assertSessionHasNoErrors();
        LoanItem::query()->update(['due_date' => today()->subDay()]);
        $this->borrow(array_slice($ids, 2))->assertSessionHasErrors('book_copy_ids');
        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('loan_items', 2);
        $this->borrow([$ids[2]])->assertRedirect(route('loans.index'));
        $this->assertDatabaseCount('loan_items', 3);
    }

    public function test_partial_return_frees_one_slot_and_cannot_be_recorded_twice(): void
    {
        $ids = $this->copies(4);
        $this->borrow(array_slice($ids, 0, 3))->assertSessionHasNoErrors();
        $item = LoanItem::where('book_copy_id', $ids[0])->firstOrFail();
        $this->post("/loans/items/{$item->id}/return", ['return_condition' => 'rusak'])
            ->assertSessionHasNoErrors();
        $returnedAt = $item->fresh()->returned_at->toDateTimeString();
        $this->assertSame(2, LoanItem::whereNull('returned_at')->count());
        $this->borrow([$ids[3]])->assertSessionHasNoErrors();
        $this->post("/loans/items/{$item->id}/return", ['return_condition' => 'baik'])
            ->assertSessionHasErrors('return_condition');
        $this->assertSame($returnedAt, $item->fresh()->returned_at->toDateTimeString());
        $this->assertDatabaseHas('book_copies', ['id' => $ids[0], 'condition' => 'rusak']);
        $this->assertSame(3, LoanItem::whereNull('returned_at')->count());
    }

    public function test_configuration_changes_limit_and_default_duration(): void
    {
        config(['library.max_active_loans' => 1, 'library.loan_days' => 14]);
        $ids = $this->copies(2);
        $this->get('/loans/create')->assertOk()->assertSee(today()->addDays(14)->toDateString());
        $this->borrow($ids)->assertSessionHasErrors('book_copy_ids');
        $this->assertDatabaseCount('loans', 0);
        $this->borrow([$ids[0]], ['due_date' => ''])->assertRedirect(route('loans.index'));
        $this->assertDatabaseHas('loan_items', ['due_date' => today()->addDays(14)->toDateString()]);
    }

    public function test_staff_can_override_the_default_due_date(): void
    {
        $date = today()->addDays(4)->toDateString();
        $this->borrow($this->copies(1), ['due_date' => $date])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_items', ['due_date' => $date]);
    }

    public static function unavailableStates(): array
    {
        return [['rusak'], ['hilang'], ['archived'], ['borrowed']];
    }

    #[DataProvider('unavailableStates')]
    public function test_one_unavailable_copy_rejects_the_whole_selection(string $state): void
    {
        $ids = $this->copies(2);
        if ($state === 'borrowed') {
            $this->borrow([$ids[1]])->assertSessionHasNoErrors();
        } else {
            BookCopy::findOrFail($ids[1])->update($state === 'archived'
                ? ['archived_at' => now()] : ['condition' => $state]);
        }
        $before = Loan::count();
        $this->borrow($ids)->assertSessionHasErrors('book_copy_ids');
        $this->assertDatabaseCount('loans', $before);
        $this->assertDatabaseMissing('loan_items', ['book_copy_id' => $ids[0]]);
    }

    public static function invalidSelections(): array
    {
        return [[[]], ['invalid'], [[999999]], [[1, 1]], [[1, 2, 3, 4]]];
    }

    #[DataProvider('invalidSelections')]
    public function test_invalid_selection_never_creates_a_transaction(mixed $selection): void
    {
        $this->copies(4);
        $this->borrow($selection)->assertSessionHasErrors();
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_inactive_member_is_rejected(): void
    {
        $this->member->user->forceFill(['is_active' => false])->save();
        $this->borrow($this->copies(1))->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_database_failure_rolls_back_the_entire_transaction(): void
    {
        $ids = $this->copies(2);
        // Gagalkan insert item kedua agar rollback benar-benar diuji setelah penulisan pertama.
        DB::unprepared("CREATE TRIGGER reject_second_item BEFORE INSERT ON loan_items
            WHEN NEW.book_copy_id = {$ids[1]}
            BEGIN SELECT RAISE(ABORT, 'Simulated item failure'); END");
        try {
            $this->withoutExceptionHandling();
            try {
                $this->borrow($ids);
                $this->fail('Insert seharusnya gagal.');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('Simulated item failure', $exception->getMessage());
            }
            $this->assertDatabaseCount('loans', 0);
            $this->assertDatabaseCount('loan_items', 0);
        } finally {
            DB::unprepared('DROP TRIGGER reject_second_item');
        }
    }

    private function copies(int $count): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->book->copies()->create([
                'inventory_code' => 'TEST-'.$i,
                'condition' => 'baik',
            ])->id;
        }

        return $ids;
    }

    private function borrow(mixed $ids, array $extra = []): TestResponse
    {
        return $this->post('/loans', array_merge([
            'member_id' => $this->member->id,
            'book_copy_ids' => $ids,
        ], $extra));
    }
}
