<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoanReportTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Member $member;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-01 00:30:00', 'Asia/Jakarta'));
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::create([
            'user_id' => User::factory()->create()->id, 'member_code' => 'TEST-MEMBER',
        ]);
        $this->book = Book::create([
            'category_id' => Category::create(['name' => 'Novel'])->id,
            'title' => 'Buku laporan', 'author' => 'Penulis',
        ]);
        $this->actingAs($this->staff);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_date_range_includes_both_full_days_and_uses_borrowing_date(): void
    {
        $this->item('2026-09-19 23:59:59');
        $first = $this->item('2026-09-20 00:00:00');
        $last = $this->item('2026-09-21 23:59:59', '2026-09-22', '2026-09-30 12:00:00');
        $this->item('2026-09-22 00:00:00');
        $filters = ['start_date' => '2026-09-20', 'end_date' => '2026-09-21'];
        foreach (['reports.loans', 'reports.loans.print'] as $route) {
            $this->get(route($route, $filters))->assertOk()
                ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$last->id, $first->id])
                ->assertViewHas('summary', ['total' => 2, 'active' => 1, 'late' => 0, 'returned' => 1])
                ->assertSee('20/09/2026 – 21/09/2026');
        }
    }

    public function test_dates_can_be_used_independently_or_on_the_same_day(): void
    {
        $this->item('2026-09-19 12:00:00');
        $this->item('2026-09-20 12:00:00');
        $this->item('2026-09-21 12:00:00');
        foreach ([
            ['start_date' => '2026-09-20'],
            ['end_date' => '2026-09-20'],
        ] as $filters) {
            $this->get(route('reports.loans', $filters))->assertOk()
                ->assertViewHas('summary', fn ($summary) => $summary['total'] === 2);
        }
        $this->get('/reports/loans?start_date=2026-09-20&end_date=2026-09-20')->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['total'] === 1);
    }

    public function test_status_and_period_share_the_same_summary_and_print_results(): void
    {
        $active = $this->item('2026-09-20 12:00:00', '2026-10-01');
        $late = $this->item('2026-09-20 12:00:00', '2026-09-30');
        $returned = $this->item('2026-09-20 12:00:00', '2026-09-22', '2026-09-24 23:59:59');
        $this->item('2026-09-19 12:00:00', '2026-09-20');
        foreach (['active' => $active, 'late' => $late, 'returned' => $returned] as $status => $item) {
            $filters = ['status' => $status, 'start_date' => '2026-09-20'];
            $expected = ['total' => 1, 'active' => 0, 'late' => 0, 'returned' => 0];
            $expected[$status] = 1;
            foreach (['reports.loans', 'reports.loans.print'] as $route) {
                $this->get(route($route, $filters))->assertOk()->assertViewHas('summary', $expected)
                    ->assertViewHas('items', fn ($items) => $items->count() === 1 && $items->first()->id === $item->id);
            }
        }
        $this->get('/reports/loans?status=returned')->assertOk()
            ->assertViewHas('items', fn ($items) => $items->first()->late_days === 2);
    }

    public function test_print_includes_all_items_and_summary_counts_items_not_transactions(): void
    {
        $first = $this->item('2026-09-20 12:00:00');
        for ($i = 0; $i < 11; $i++) {
            $copy = $this->book->copies()->create(['inventory_code' => 'COPY-'.$i, 'condition' => 'baik']);
            $first->loan->items()->create(['book_copy_id' => $copy->id, 'due_date' => '2026-10-05']);
        }
        $filters = ['status' => 'active', 'start_date' => '2026-09-20', 'end_date' => '2026-09-20'];
        $this->get(route('reports.loans', $filters))->assertOk()
            ->assertViewHas('items', fn ($items) => $items->count() === 10 && $items->total() === 12
                && str_contains($items->url(2), 'start_date=2026-09-20'))
            ->assertViewHas('summary', ['total' => 12, 'active' => 12, 'late' => 0, 'returned' => 0])
            ->assertSee(route('reports.loans.print', $filters));
        $this->get(route('reports.loans', $filters + ['page' => 2]))->assertOk()->assertViewHas('rowStart', 11);
        $this->get(route('reports.loans.print', $filters + ['page' => 2]))->assertOk()
            ->assertViewHas('items', fn ($items) => $items->count() === 12)
            ->assertViewHas('rowStart', 1)->assertSee('COPY-0')->assertSee('COPY-10')
            ->assertSee('Cetak / Simpan PDF')->assertSee('Siap dicetak: 12 eksemplar');
    }

    public function test_empty_results_have_zero_summary(): void
    {
        foreach (['reports.loans', 'reports.loans.print'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertViewHas('summary', ['total' => 0, 'active' => 0, 'late' => 0, 'returned' => 0])
                ->assertSee('Tidak ada peminjaman untuk pilihan ini.');
        }
    }

    public function test_archived_books_and_inactive_members_remain_in_history(): void
    {
        $item = $this->item('2026-09-20 12:00:00');
        $this->book->update(['archived_at' => now()]);
        $item->bookCopy->update(['archived_at' => now()]);
        $this->member->user->forceFill(['is_active' => false])->save();
        $this->get('/reports/loans/print')->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['total'] === 1)->assertSee('Buku laporan');
    }

    public static function invalidFilters(): array
    {
        return [
            ['start_date=2026-09-30&end_date=2026-09-20', 'end_date'],
            ['start_date=2026-02-30', 'start_date'], ['end_date=invalid', 'end_date'],
            ['start_date[]=invalid', 'start_date'], ['status=invalid', 'status'],
            ['page=-1', 'page'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected_on_both_routes(string $query, string $field): void
    {
        foreach (['/reports/loans', '/reports/loans/print'] as $path) {
            $this->from('/reports/loans')->get($path.'?'.$query)
                ->assertRedirect('/reports/loans')->assertSessionHasErrors($field);
        }
    }

    public function test_both_routes_require_active_staff(): void
    {
        auth()->logout();
        foreach (['/reports/loans', '/reports/loans/print'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $this->actingAs($this->member->user);
        foreach (['/reports/loans', '/reports/loans/print'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->staff->forceFill(['is_active' => false])->save();
        foreach (['/reports/loans', '/reports/loans/print'] as $path) {
            $this->actingAs($this->staff)->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_print_escapes_book_titles_and_student_names(): void
    {
        $this->item('2026-09-20 12:00:00');
        $this->book->update(['title' => '<script>alert("book")</script>']);
        $this->member->user->update(['name' => '<img src=x onerror=alert(1)>']);
        $this->get('/reports/loans/print')->assertOk()
            ->assertSee('<script>alert("book")</script>')->assertDontSee('<script>alert("book")</script>', false)
            ->assertSee('<img src=x onerror=alert(1)>')->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    private function item(string $borrowedAt, string $dueDate = '2026-10-05', ?string $returnedAt = null): LoanItem
    {
        $copy = $this->book->copies()->create(['inventory_code' => 'INV-'.Str::random(10), 'condition' => 'baik']);
        $loan = Loan::create([
            'loan_code' => 'PJM-'.Str::random(10), 'member_id' => $this->member->id,
            'created_by' => $this->staff->id, 'borrowed_at' => $borrowedAt,
        ]);

        return $loan->items()->create([
            'book_copy_id' => $copy->id, 'due_date' => $dueDate, 'returned_at' => $returnedAt,
        ]);
    }
}
