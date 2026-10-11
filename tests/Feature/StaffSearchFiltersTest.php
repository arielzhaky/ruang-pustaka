<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffSearchFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private SchoolClass $class;

    private Member $member;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-04 00:30:00', 'Asia/Jakarta'));
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->class = SchoolClass::create(['name' => 'VII A', 'grade_level' => '7', 'is_active' => true]);
        $this->member = $this->student('SIS-UTAMA', ['name' => 'Alya Putri', 'email' => 'alya@example.test']);
        $this->member->update(['nis' => '001234']);
        $this->book = Book::create([
            'category_id' => Category::create(['name' => 'Novel'])->id,
            'title' => 'Petualangan Pustaka', 'author' => 'Penulis',
        ]);
        $this->actingAs($this->staff);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_student_search_matches_each_identifier_and_combines_class_and_status(): void
    {
        $this->student('SIS-LAIN', ['name' => 'Bima', 'email' => 'bima@example.test']);
        $this->staff->member()->create(['member_code' => 'STAFF-ONLY']);
        foreach ([' Alya ', 'alya@example.test', '001234', 'SIS-UTAMA', '0'] as $search) {
            $this->get(route('members.index', ['q' => $search]))->assertOk()
                ->assertViewHas('members', fn ($members) => $members->pluck('id')->all() === [$this->member->id]);
        }
        $this->get('/members?q=STAFF-ONLY')->assertOk()
            ->assertViewHas('members', fn ($members) => $members->total() === 0);

        $oldClass = SchoolClass::create(['name' => 'IX Lama', 'grade_level' => '9', 'is_active' => false]);
        $this->member->update(['school_class_id' => $oldClass->id]);
        $this->member->user->forceFill(['is_active' => false])->save();
        $filters = ['q' => 'Alya', 'class_id' => $oldClass->id, 'status' => 'inactive'];
        $this->get(route('members.index', $filters))->assertOk()
            ->assertViewHas('members', fn ($members) => $members->pluck('id')->all() === [$this->member->id])
            ->assertViewHas('memberStats', ['total' => 2, 'active' => 1, 'inactive' => 1])
            ->assertViewHas('filterClasses', fn ($classes) => $classes->contains('id', $oldClass->id))
            ->assertViewHas('classes', fn ($classes) => ! $classes->contains('id', $oldClass->id));
        foreach ([['status' => 'active'], ['class_id' => $this->class->id]] as $change) {
            $this->get(route('members.index', array_replace($filters, $change)))->assertOk()
                ->assertViewHas('members', fn ($members) => $members->total() === 0);
        }
    }

    public function test_student_pagination_preserves_filters_and_reset_returns_all_students(): void
    {
        $ids = [];
        for ($i = 0; $i < 12; $i++) {
            $ids[] = $this->student('FILTER-'.$i, ['name' => 'Pilihan '.$i])->id;
        }
        $filters = ['q' => 'Pilihan', 'status' => 'active', 'class_id' => $this->class->id];
        $page = $this->get(route('members.index', $filters))->assertOk()->viewData('members');
        $this->assertSame(12, $page->total());
        $this->assertSame(array_slice(array_reverse($ids), 0, 10), $page->pluck('id')->all());
        parse_str(parse_url($page->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => 2], $query);
        $this->get($page->nextPageUrl())->assertOk()
            ->assertViewHas('members', fn ($members) => $members->pluck('id')->all() === [$ids[1], $ids[0]]);
        $this->get('/members')->assertOk()->assertViewHas('members', fn ($members) => $members->total() === 13);
    }

    public function test_loan_search_matches_student_book_and_transaction_identifiers(): void
    {
        $item = $this->item();
        $other = $this->student('AUTRE', ['name' => 'Bima']);
        $otherItem = $this->item(member: $other);
        $otherBook = Book::create(['category_id' => $this->book->category_id, 'title' => 'Buku lain', 'author' => 'Penulis']);
        $otherItem->bookCopy->update(['book_id' => $otherBook->id, 'inventory_code' => 'AUTRE']);
        $otherItem->loan->update(['loan_code' => 'AUTRE']);
        foreach ([' Alya ', '001234', 'SIS-UTAMA', 'Petualangan', $item->loan->loan_code, $item->bookCopy->inventory_code] as $search) {
            $this->get(route('loans.index', ['q' => $search]))->assertOk()
                ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$item->id]);
        }
        $item->bookCopy->update(['inventory_code' => '0']);
        $this->get('/loans?q=0')->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$item->id]);
    }

    public function test_loan_statuses_separate_due_today_overdue_returns_and_losses(): void
    {
        $active = $this->item(attributes: ['due_date' => '2026-10-04']);
        $late = $this->item(attributes: ['due_date' => '2026-10-03']);
        $returned = $this->item(attributes: ['returned_at' => now(), 'return_condition' => 'rusak']);
        $lost = $this->item(attributes: ['returned_at' => now(), 'return_condition' => 'hilang']);
        foreach (['active' => $active, 'late' => $late, 'returned' => $returned, 'lost' => $lost] as $status => $item) {
            $this->get(route('loans.index', ['status' => $status]))->assertOk()
                ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$item->id]);
        }
        $legacy = $this->item(attributes: ['returned_at' => now(), 'return_condition' => null]);
        $this->get('/loans?status=returned')->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$legacy->id, $returned->id]);
    }

    public function test_loan_filters_combine_and_include_entire_borrowing_dates(): void
    {
        $this->item('2026-09-19 23:59:59');
        $first = $this->item('2026-09-20 00:00:00');
        $last = $this->item('2026-09-21 23:59:59');
        $this->item('2026-09-22 00:00:00');
        $this->item('2026-09-20 12:00:00', ['due_date' => '2026-10-03']);
        $other = $this->student('OTHER', ['name' => 'Alya Lain']);
        $other->update(['school_class_id' => null]);
        $this->item('2026-09-20 12:00:00', member: $other);
        $filters = ['q' => 'Alya', 'class_id' => $this->class->id, 'status' => 'active',
            'start_date' => '2026-09-20', 'end_date' => '2026-09-21'];
        $this->get(route('loans.index', $filters))->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$last->id, $first->id]);
        foreach ([['start_date' => '2026-09-22'], ['end_date' => '2026-09-19']] as $dates) {
            $this->get(route('loans.index', $dates))->assertOk()
                ->assertViewHas('loans', fn ($loans) => $loans->total() === 1);
        }
        $this->get('/loans?start_date=2026-09-21&end_date=2026-09-21')->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$last->id]);
    }

    public function test_loan_history_keeps_archived_books_inactive_students_and_students_without_class(): void
    {
        $item = $this->item();
        $this->class->update(['is_active' => false]);
        $this->member->user->forceFill(['is_active' => false])->save();
        $this->book->update(['archived_at' => now()]);
        $item->bookCopy->update(['archived_at' => now()]);
        $this->get(route('loans.index', ['class_id' => $this->class->id]))->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 1)
            ->assertSee('VII A (tidak aktif)');
        $this->member->update(['school_class_id' => null]);
        $this->get('/loans')->assertOk()->assertSee('Belum ada kelas')
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 1);
    }

    public function test_loan_pagination_preserves_all_filters_with_stable_order(): void
    {
        $ids = [];
        for ($i = 0; $i < 12; $i++) {
            $ids[] = $this->item()->id;
        }
        $filters = ['q' => 'Alya', 'class_id' => $this->class->id, 'status' => 'active',
            'start_date' => '2026-09-20', 'end_date' => '2026-09-20'];
        $page = $this->get(route('loans.index', $filters))->assertOk()->viewData('loans');
        $this->assertSame(12, $page->total());
        $this->assertSame(array_slice(array_reverse($ids), 0, 10), $page->pluck('item_id')->all());
        parse_str(parse_url($page->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => 2], $query);
        $this->get($page->nextPageUrl())->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->pluck('item_id')->all() === [$ids[1], $ids[0]]);
    }

    public static function invalidFilters(): array
    {
        return [
            ['members', 'q[]=bad', 'q'],
            ['members', 'q='.str_repeat('a', 101), 'q'],
            ['members', 'class_id=999999', 'class_id'],
            ['members', 'status=unknown', 'status'],
            ['members', 'page=0', 'page'],
            ['loans', 'q[]=bad', 'q'],
            ['loans', 'q='.str_repeat('a', 101), 'q'],
            ['loans', 'class_id[]=1', 'class_id'],
            ['loans', 'class_id=999999', 'class_id'],
            ['loans', 'status=unknown', 'status'],
            ['loans', 'page=-1', 'page'],
            ['loans', 'start_date=2026-02-30', 'start_date'],
            ['loans', 'end_date=invalid', 'end_date'],
            ['loans', 'start_date[]=bad&end_date=2026-10-04', 'start_date'],
            ['loans', 'start_date=2026-10-04&end_date=2026-10-03', 'end_date'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_show_errors_and_pages_remain_usable(string $path, string $query, string $field): void
    {
        $this->from('/'.$path)->get('/'.$path.'?'.$query)
            ->assertRedirect('/'.$path)->assertSessionHasErrors($field);
        $this->get('/'.$path)->assertOk()->assertSee('Reset filter');
    }

    public function test_search_is_escaped_and_empty_results_have_a_reset_link(): void
    {
        foreach (['members', 'loans'] as $path) {
            $search = '"><script>alert(1)</script>';
            $this->get('/'.$path.'?'.http_build_query(['q' => $search]))->assertOk()
                ->assertSee($search)->assertDontSee($search, false)
                ->assertSee('Reset filter')->assertSee('Tidak ada');
        }
    }

    public function test_search_pages_still_require_active_staff(): void
    {
        auth()->logout();
        foreach (['/members?q=Alya', '/loans?q=Alya'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->actingAs($this->member->user);
        foreach (['/members?q=Alya', '/loans?q=Alya'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->staff->forceFill(['is_active' => false])->save();
        foreach (['/members?q=Alya', '/loans?q=Alya'] as $url) {
            $this->actingAs($this->staff)->get($url)->assertRedirect(route('login'));
        }
    }

    private function student(string $code, array $user = []): Member
    {
        return Member::create(['user_id' => User::factory()->create($user)->id,
            'member_code' => $code, 'school_class_id' => $this->class->id]);
    }

    private function item(string $borrowedAt = '2026-09-20 12:00:00', array $attributes = [], ?Member $member = null): LoanItem
    {
        $copy = $this->book->copies()->create(['inventory_code' => 'INV-'.Str::random(10), 'condition' => 'baik']);
        $loan = Loan::create(['loan_code' => 'PJM-'.Str::random(10), 'member_id' => ($member ?? $this->member)->id,
            'created_by' => $this->staff->id, 'borrowed_at' => $borrowedAt]);

        return $loan->items()->create($attributes + ['book_copy_id' => $copy->id, 'due_date' => '2026-10-05']);
    }
}
