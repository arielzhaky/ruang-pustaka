<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\LibraryVisit;
use App\Models\Member;
use App\Models\PrintQueueItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorAndPrintQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(10, 30));
        $this->staff = User::factory()->create(['role' => 'petugas']);
    }

    private function member(string $code = 'MEMBER-ONE'): Member
    {
        return Member::create(['user_id' => User::factory()->create(['name' => 'Siswa '.$code])->id, 'member_code' => $code,
            'school_class_id' => SchoolClass::firstOrCreate(['name' => 'VIII A'], ['grade_level' => 8])->id]);
    }

    private function copies(int $count = 1)
    {
        $book = Book::create(['category_id' => Category::firstOrCreate(['name' => 'Cerita'])->id, 'title' => 'Buku <b>Contoh</b>', 'author' => 'Penulis']);

        return collect(range(1, $count))->map(fn ($i) => $book->copies()->create(['inventory_code' => 'TEST-'.$book->id.'-'.$i, 'condition' => 'baik', 'shelf_location' => 'A.1']));
    }

    private function visit(Member $member, string $date): LibraryVisit
    {
        return LibraryVisit::create(['member_id' => $member->id, 'created_by' => $this->staff->id, 'student_name' => $member->user->name, 'member_code' => $member->member_code, 'class_name' => 'VIII A', 'visit_date' => $date, 'visited_at' => $date.' 09:00:00']);
    }

    public function test_visits_and_queue_are_staff_only(): void
    {
        foreach (['visits.index', 'print-queue.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $member = $this->member();
        $visit = $this->visit($member, '2026-10-09');
        $this->actingAs($member->user);
        $this->get('/visits')->assertForbidden();
        $this->post('/visits', ['member_id' => $member->id])->assertForbidden();
        $this->delete('/visits/'.$visit->id)->assertForbidden();
        $this->get('/print-queue')->assertForbidden();
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$member->id]])->assertForbidden();
        $this->post('/print-queue/download', ['queue_ids' => [1]])->assertForbidden();
        $this->post('/print-queue/remove', ['queue_ids' => [1]])->assertForbidden();
    }

    public function test_visits_are_unique_per_student_per_local_day_and_snapshot_identity(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff);
        $this->post('/visits', ['member_id' => $member->id, 'visit_date' => '2030-01-01'])->assertSessionHasNoErrors();
        $original = LibraryVisit::firstOrFail();
        $this->post('/visits', ['member_id' => $member->id])->assertSessionHasNoErrors()->assertSessionHas('success', fn ($message) => str_contains($message, 'sudah tercatat'));
        $this->assertDatabaseCount('library_visits', 1);
        $this->assertSame('2026-10-09', $original->visit_date->toDateString());
        $this->assertSame($this->staff->id, $original->created_by);
        $member->user->update(['name' => 'Nama Baru']);
        $member->update(['member_code' => 'NEW-CODE']);
        $this->assertSame('Siswa MEMBER-ONE', $original->fresh()->student_name);
        $this->travelTo(now()->setTime(23, 59, 59));
        $this->post('/visits', ['member_id' => $member->id]);
        $this->travel(1)->seconds();
        $this->post('/visits', ['member_id' => $member->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('library_visits', 2);
        $member->delete();
        $this->assertDatabaseCount('library_visits', 2);
        $this->assertNull($original->fresh()->member_id);
        $this->assertSame('MEMBER-ONE', $original->fresh()->member_code);
    }

    public function test_visits_validate_active_students_and_reject_non_students(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff);
        $member->user->forceFill(['is_active' => false])->save();
        $this->post('/visits', ['member_id' => $member->id])->assertSessionHasErrors('member_id');
        $member->user->forceFill(['is_active' => true, 'role' => 'petugas'])->save();
        $this->post('/visits', ['member_id' => $member->id])->assertSessionHasErrors('member_id');
        $this->post('/visits', ['member_id' => 999])->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('library_visits', 0);
    }

    public function test_period_totals_week_rows_months_and_clickable_dashboard_match_detail(): void
    {
        $one = $this->member();
        $two = $this->member('MEMBER-TWO');
        foreach (['2025-12-31', '2026-01-01', '2026-10-01', '2026-10-09'] as $date) {
            $this->visit($one, $date);
        }
        $this->visit($two, '2026-10-09');
        $this->actingAs($this->staff)->get('/visits?date=2026-10-09')->assertOk()
            ->assertViewHas('totals', ['day' => 2, 'month' => 3, 'year' => 4])
            ->assertViewHas('visits', fn ($rows) => $rows->total() === 2)
            ->assertViewHas('week', fn ($days) => $days->count() === 7 && $days->first()['date']->toDateString() === '2026-10-05' && $days->last()['date']->toDateString() === '2026-10-11' && $days->sum('total') === 2)
            ->assertViewHas('months', fn ($months) => $months->count() === 12 && $months->sum('total') === 4)
            ->assertSee('Siswa MEMBER-TWO')->assertSee('Senin')->assertSee('Januari');
        $this->get('/visits?date=2026-10-09&period=month')->assertViewHas('visits', fn ($rows) => $rows->total() === 3);
        $this->get('/visits?date=2026-10-09&period=year')->assertViewHas('visits', fn ($rows) => $rows->total() === 4);
        $this->get('/visits?date=2027-01-01')->assertViewHas('totals', ['day' => 0, 'month' => 0, 'year' => 0]);
        $this->get('/dashboard')->assertOk()->assertViewHas('visitTotals', ['day' => 2, 'month' => 3, 'year' => 4])->assertSee(route('visits.index', ['date' => '2026-10-09', 'period' => 'year']));
        $this->get('/visits?date=invalid')->assertSessionHasErrors('date');
        $this->get('/visits?period=bad')->assertSessionHasErrors('period');
    }

    public function test_visit_pagination_keeps_totals_and_staff_can_correct_mistake(): void
    {
        for ($i = 1; $i <= 27; $i++) {
            $this->visit($this->member('MEMBER-'.$i), '2026-10-09');
        }
        $this->actingAs($this->staff)->get('/visits?page=2')->assertOk()
            ->assertViewHas('visits', fn ($rows) => $rows->count() === 2 && $rows->total() === 27)
            ->assertViewHas('totals', ['day' => 27, 'month' => 27, 'year' => 27]);
        $this->delete('/visits/'.LibraryVisit::first()->id)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('library_visits', 26);
    }

    public function test_queue_accumulates_across_pages_and_deduplicates_per_owner(): void
    {
        $members = collect(range(1, 30))->map(fn ($i) => $this->member('PRINT-'.$i));
        $copies = $this->copies(5);
        $this->actingAs($this->staff);
        $this->post('/print-queue', ['type' => 'members', 'ids' => $members->take(7)->pluck('id')->all()])->assertSessionHasNoErrors();
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$members->first()->id]])->assertSessionHasNoErrors();
        $this->post('/print-queue', ['type' => 'copies', 'ids' => $copies->pluck('id')->all()])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('print_queue_items', 12);
        $this->get('/print-queue?page=2')->assertOk()->assertViewHas('queue', fn ($q) => $q->count() === 12)->assertViewHas('candidates', fn ($c) => $c->count() === 5);
        $this->get('/print-queue?type=copies&q=TEST-')->assertOk()->assertViewHas('candidates', fn ($c) => $c->total() === 5);
        $otherStaff = User::factory()->create(['role' => 'petugas']);
        $this->actingAs($otherStaff)->get('/print-queue')->assertViewHas('queue', fn ($q) => $q->isEmpty());
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$members->first()->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('print_queue_items', 13);
        $otherId = PrintQueueItem::where('user_id', $this->staff->id)->first()->id;
        $this->post('/print-queue/download', ['queue_ids' => [$otherId]])->assertNotFound();
        $this->post('/print-queue/remove', ['queue_ids' => [$otherId]])->assertNotFound();
        $this->assertDatabaseCount('print_queue_items', 13);
    }

    public function test_queue_rejects_invalid_deleted_and_archived_targets_and_empty_selection(): void
    {
        $copies = $this->copies();
        $copies->first()->update(['archived_at' => now()]);
        $this->actingAs($this->staff)->post('/print-queue', ['type' => 'copies', 'ids' => [$copies->first()->id]])->assertSessionHasErrors('ids');
        $this->post('/print-queue', ['type' => 'members', 'ids' => [999]])->assertSessionHasErrors('ids');
        $this->post('/print-queue', ['type' => 'bogus', 'ids' => [1]])->assertSessionHasErrors('type');
        $this->post('/print-queue/download')->assertSessionHasErrors('queue_ids');
        $member = $this->member();
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$member->id, $member->id]])->assertSessionHasErrors('ids.0');
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$member->id]])->assertSessionHasNoErrors();
        $id = PrintQueueItem::first()->id;
        $member->delete();
        $this->post('/print-queue/download', ['queue_ids' => [$id]])->assertNotFound();
    }

    public function test_download_selected_seven_students_and_five_copies_is_two_page_pdf_and_keeps_queue(): void
    {
        $members = collect(range(1, 9))->map(fn ($i) => $this->member('PDF-'.$i));
        $copies = $this->copies(11);
        $this->actingAs($this->staff);
        $this->post('/print-queue', ['type' => 'members', 'ids' => $members->pluck('id')->all()]);
        $this->post('/print-queue', ['type' => 'copies', 'ids' => $copies->pluck('id')->all()]);
        $selected = PrintQueueItem::whereIn('member_id', $members->take(7)->pluck('id'))->orWhereIn('book_copy_id', $copies->take(5)->pluck('id'))->pluck('id')->all();
        $response = $this->post('/print-queue/download', ['queue_ids' => $selected])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
        $this->assertDatabaseCount('print_queue_items', 20);
        $all = $this->post('/print-queue/download', ['queue_ids' => PrintQueueItem::pluck('id')->all()])->assertOk();
        $this->assertSame(4, preg_match_all('/\/Type\s*\/Page\b/', $all->getContent()));
        $this->post('/print-queue/remove', ['queue_ids' => $selected])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('print_queue_items', 8);
    }

    public function test_queue_limit_counts_existing_items_and_rejects_whole_overflow_batch(): void
    {
        $members = collect(range(1, 201))->map(fn ($i) => $this->member('LIMIT-'.$i));
        $this->actingAs($this->staff);
        $this->post('/print-queue', ['type' => 'members', 'ids' => $members->take(199)->pluck('id')->all()])->assertSessionHasNoErrors();
        $this->post('/print-queue', ['type' => 'members', 'ids' => $members->slice(199)->pluck('id')->all()])->assertSessionHasErrors('ids');
        $this->assertDatabaseCount('print_queue_items', 199);
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$members[199]->id]])->assertSessionHasNoErrors();
        $this->post('/print-queue', ['type' => 'members', 'ids' => [$members[0]->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('print_queue_items', 200);
    }
}
