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
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    private User $staff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        config(['library.max_active_loans' => 3, 'library.loan_days' => 7]);
        $this->travelTo(CarbonImmutable::parse('2026-10-01 00:30:00', 'Asia/Jakarta'));
        $this->member = $this->makeMember();
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->category = Category::create(['name' => 'Kategori tes']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_dashboard_shows_only_current_members_items_and_counts(): void
    {
        $late = $this->makeItem($this->member, 'Buku terlambat milikku', '2026-09-29');
        $dueToday = $this->makeItem($this->member, 'Buku hari ini', '2026-10-01');
        $future = $this->makeItem($this->member, 'Buku minggu depan', '2026-10-07');
        $returned = $this->makeItem($this->member, 'Riwayat milikku', '2026-09-28', '2026-09-30 23:00:00');
        $other = $this->makeMember();
        $otherActive = $this->makeItem($other, 'Buku yang dipinjam siswa lain', '2026-09-20');
        $otherReturned = $this->makeItem($other, 'Buku yang dikembalikan siswa lain', '2026-09-20', '2026-09-30 23:59:00');
        // Arsip tidak boleh menghilangkan pinjaman yang masih perlu dikembalikan.
        $late->bookCopy->book->update(['archived_at' => now()]);

        $this->actingAs($this->member->user)->get('/dashboard?member_id='.$other->id)
            ->assertOk()->assertViewHas('activeCount', 3)->assertViewHas('lateCount', 1)
            ->assertViewHas('dueTodayCount', 1)->assertViewHas('remainingQuota', 0)
            ->assertViewHas('activeLoans', fn ($items) => $items->pluck('id')->all() === [$late->id, $dueToday->id, $future->id])
            ->assertViewHas('recentReturns', fn ($items) => $items->pluck('id')->all() === [$returned->id])
            ->assertSee('Buku terlambat milikku')->assertSee('Terlambat 2 hari')
            ->assertSee('Jatuh tempo hari ini')->assertSee('Kuota pinjaman kamu sudah penuh')
            ->assertSee('Riwayat milikku')->assertSee('Dikembalikan terlambat 2 hari')
            ->assertDontSee($other->member_code)->assertDontSee($other->user->email)
            ->assertDontSee($otherActive->loan->loan_code)->assertDontSee($otherReturned->loan->loan_code)
            ->assertDontSee('Kelola Siswa');
    }

    public function test_returned_items_free_quota_and_lateness_stops_on_return_date(): void
    {
        $this->makeItem($this->member, 'Masih dipinjam', '2026-10-03');
        $this->makeItem($this->member, 'Kembali terlambat', '2026-09-20', '2026-09-22 12:00:00');
        $this->makeItem($this->member, 'Kembali tepat waktu', '2026-09-25', '2026-09-25 23:59:59');
        config(['library.max_active_loans' => 5, 'library.loan_days' => 14]);

        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertViewHas('activeCount', 1)->assertViewHas('remainingQuota', 4)
            ->assertViewHas('lateCount', 0)->assertViewHas('dueTodayCount', 0)
            ->assertSee('Dari batas 5 eksemplar aktif')->assertSee('Masa pinjam default 14 hari')
            ->assertSee('Dikembalikan terlambat 2 hari')->assertSee('Dikembalikan tepat waktu');
    }

    public function test_quota_never_becomes_negative_after_limit_is_lowered(): void
    {
        $this->makeItem($this->member, 'Buku pertama', '2026-10-02');
        $this->makeItem($this->member, 'Buku kedua', '2026-10-02');
        config(['library.max_active_loans' => 1]);
        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertViewHas('remainingQuota', 0)->assertSee('Kuota pinjaman kamu sudah penuh');
    }

    public function test_history_contains_only_five_most_recent_returns(): void
    {
        $ids = [];
        for ($i = 0; $i < 6; $i++) {
            $ids[] = $this->makeItem($this->member, 'Riwayat urutan '.$i, '2026-09-20', '2026-09-'.(21 + $i).' 10:00:00')->id;
        }
        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertViewHas('recentReturns', fn ($items) => $items->pluck('id')->all() === array_slice(array_reverse($ids), 0, 5))
            ->assertSee('Lihat Semua Riwayat');
    }

    public function test_empty_dashboard_and_missing_member_profile_have_clear_messages(): void
    {
        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertViewHas('activeCount', 0)->assertViewHas('remainingQuota', 3)
            ->assertSee('Kamu belum memiliki pinjaman aktif.')
            ->assertSee('Belum ada buku yang kamu kembalikan.')
            ->assertDontSee('Profil anggota kamu belum tersedia.');

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertViewHas('activeCount', 0)->assertSee('Profil anggota kamu belum tersedia.');
    }

    public function test_user_supplied_book_titles_are_escaped(): void
    {
        $title = '<script>alert("test")</script>';
        $this->makeItem($this->member, $title, '2026-10-02');
        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertSee($title)->assertDontSee($title, false);
    }

    public function test_staff_dashboard_and_guest_redirect_still_work(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->actingAs($this->staff)->get('/dashboard')->assertOk()
            ->assertSee('Dashboard Petugas')->assertDontSee('Sisa kuota');
    }

    private function makeMember(): Member
    {
        return Member::create([
            'user_id' => User::factory()->create()->id,
            'member_code' => 'M-'.Str::random(10),
        ]);
    }

    private function makeItem(Member $member, string $title, string $dueDate, ?string $returnedAt = null): LoanItem
    {
        $book = Book::create(['category_id' => $this->category->id, 'title' => $title, 'author' => 'Penulis']);
        $copy = $book->copies()->create(['inventory_code' => 'INV-'.Str::random(10), 'condition' => 'baik']);
        $loan = Loan::create([
            'loan_code' => 'PJM-'.Str::random(10), 'member_id' => $member->id,
            'created_by' => $this->staff->id, 'borrowed_at' => '2026-09-15 10:00:00',
        ]);

        return $loan->items()->create([
            'book_copy_id' => $copy->id, 'due_date' => $dueDate,
            'returned_at' => $returnedAt, 'returned_by' => $returnedAt ? $this->staff->id : null,
            'return_condition' => $returnedAt ? 'baik' : null,
        ]);
    }
}
