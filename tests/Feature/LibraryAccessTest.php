<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LibraryAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function protectedPages(): array
    {
        return [
            ['anggota', '/catalog'],
            ['anggota', '/my-loans'],
            ['anggota', '/dashboard'],
            ['petugas', '/books'],
            ['petugas', '/reports/loans'],
        ];
    }

    #[DataProvider('protectedPages')]
    public function test_existing_session_is_rejected_after_account_is_disabled(string $role, string $path): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);
        $this->actingAs($user);
        $user->is_active = false;
        $user->save();

        $this->get($path)->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->get('/login')->assertOk();
    }

    public function test_active_member_can_read_own_pages_but_not_staff_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'anggota']));
        $this->get('/catalog')->assertOk();
        $this->get('/my-loans')->assertOk();
        $this->get('/books')->assertForbidden();
    }

    public function test_archived_books_are_hidden_and_cannot_be_borrowed(): void
    {
        $copy = $this->makeCopy(true);
        $member = $this->makeMember();
        $this->actingAs($member->user)->get('/catalog?q=Buku')
            ->assertOk()->assertDontSee('Buku arsip pengujian');

        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get('/loans/create')->assertOk()->assertDontSee($copy->inventory_code);
        $this->post('/loans', [
            'member_id' => $member->id,
            'book_copy_ids' => [$copy->id],
            'due_date' => today()->addDays(7)->toDateString(),
        ])->assertSessionHasErrors('book_copy_ids');
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_active_book_can_still_be_borrowed(): void
    {
        $copy = $this->makeCopy();
        $member = $this->makeMember();
        $this->actingAs($member->user)->get('/catalog')
            ->assertOk()->assertSee($copy->book->title);
        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->post('/loans', [
                'member_id' => $member->id,
                'book_copy_ids' => [$copy->id],
                'due_date' => today()->addDays(7)->toDateString(),
            ])->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
        $this->assertDatabaseHas('loan_items', ['book_copy_id' => $copy->id, 'returned_at' => null]);
    }

    public function test_archiving_a_book_preserves_history_and_allows_its_return(): void
    {
        $copy = $this->makeCopy();
        $member = $this->makeMember();
        $staff = User::factory()->create(['role' => 'petugas']);
        $item = $this->makeLoanItem($copy, $member, $staff);
        $copy->book->update(['archived_at' => now()]);

        $this->actingAs($member->user)->get('/my-loans')->assertOk()->assertSee($copy->book->title);
        $this->actingAs($staff)->post("/loans/items/{$item->id}/return", [
            'return_condition' => 'baik',
        ])->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
        $this->assertNotNull($item->fresh()->returned_at);
        $this->assertDatabaseCount('loans', 1);
    }

    public function test_overdue_status_is_consistent_after_midnight_in_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        // Di Jakarta sudah 1 Oktober, sementara UTC masih 30 September.
        $this->travelTo(CarbonImmutable::parse('2026-10-01 00:30:00', 'Asia/Jakarta'));
        try {
            $copy = $this->makeCopy();
            $member = $this->makeMember();
            $staff = User::factory()->create(['role' => 'petugas']);
            $this->makeLoanItem($copy, $member, $staff);

            $this->actingAs($member->user)->get('/my-loans')->assertOk()->assertSee('Terlambat');
            $this->actingAs($staff)->get('/loans')->assertOk()->assertSee('Terlambat');
            $this->get('/reports/loans?status=late')->assertOk()->assertSee($copy->book->title);
            $this->get('/reports/loans?status=active')->assertOk()->assertDontSee($copy->book->title);
            $this->post('/loans', [
                'member_id' => $member->id,
                'book_copy_ids' => [$copy->id],
                'due_date' => '2026-09-30',
            ])->assertSessionHasErrors('due_date');
        } finally {
            $this->travelBack();
        }
    }

    private function makeCopy(bool $archived = false): BookCopy
    {
        $category = Category::create(['name' => 'Kategori pengujian']);
        $book = Book::create([
            'category_id' => $category->id,
            'title' => $archived ? 'Buku arsip pengujian' : 'Buku aktif pengujian',
            'author' => 'Penulis pengujian',
            'archived_at' => $archived ? now() : null,
        ]);

        return $book->copies()->create(['inventory_code' => 'TEST-001', 'condition' => 'baik']);
    }

    private function makeMember(): Member
    {
        return Member::create([
            'user_id' => User::factory()->create(['role' => 'anggota'])->id,
            'member_code' => 'ANG-TEST',
        ]);
    }

    private function makeLoanItem(BookCopy $copy, Member $member, User $staff): LoanItem
    {
        $loan = Loan::create([
            'loan_code' => 'PJM-TEST',
            'member_id' => $member->id,
            'created_by' => $staff->id,
            'borrowed_at' => now()->subDays(7),
        ]);

        return $loan->items()->create([
            'book_copy_id' => $copy->id,
            'due_date' => today()->subDay()->toDateString(),
        ]);
    }
}
