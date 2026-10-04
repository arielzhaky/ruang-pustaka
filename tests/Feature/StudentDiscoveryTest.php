<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    private User $staff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-03 00:30:00', 'Asia/Jakarta'));
        $this->member = $this->member();
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->category = Category::create(['name' => 'Bacaan siswa']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_popular_books_use_borrowing_history_with_stable_ranking_and_real_stock(): void
    {
        $popular = $this->book('Terpopuler', ['cover_path' => 'covers/terpopuler.jpg']);
        $oldCopy = $this->copy($popular, ['archived_at' => now()]);
        $this->borrow($oldCopy, $this->member, ['returned_at' => now()->subDays(1)]);
        $this->borrow($oldCopy, $this->member, ['returned_at' => now()->subDays(1)]);
        $this->borrow($this->copy($popular), $this->member());
        $this->copy($popular);
        $this->copy($popular, ['condition' => 'rusak']);
        $this->copy($popular, ['condition' => 'hilang']);
        foreach (['E', 'D', 'C', 'B', 'A'] as $title) {
            $this->borrow($this->copy($this->book($title)), $this->member());
        }
        $archived = $this->book('Arsip populer', ['archived_at' => now()]);
        for ($i = 0; $i < 5; $i++) {
            $this->borrow($this->copy($archived), $this->member());
        }
        $this->book('Tanpa riwayat');
        $this->member->user->favoriteBooks()->attach($popular);

        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertViewHas('popularBooks', fn ($books) => $books->pluck('title')->all() === ['Terpopuler', 'A', 'B', 'C']
                && $books->pluck('loan_items_count')->all() === [3, 1, 1, 1]
                && $books->pluck('available_count')->all() === [1, 0, 0, 0]
                && $books->pluck('is_favorite')->all() === [true, false, false, false]
                && $books->first()->cover_path === 'covers/terpopuler.jpg'
                && $books->first()->relationLoaded('category'));
    }

    public function test_latest_books_include_reference_titles_and_exclude_archived_books(): void
    {
        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            $ids[] = $this->book('Judul '.$i, ['cover_path' => 'covers/judul-'.$i.'.jpg'])->id;
        }
        $this->book('Judul arsip terakhir', ['archived_at' => now()]);

        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()
            ->assertViewHas('latestBooks', fn ($books) => $books->pluck('id')->all() === array_slice(array_reverse($ids), 0, 4)
                && $books->pluck('available_count')->all() === [0, 0, 0, 0]
                && $books->pluck('is_favorite')->all() === [false, false, false, false]
                && $books->first()->cover_path === 'covers/judul-4.jpg'
                && $books->first()->category->name === 'Bacaan siswa')
            ->assertViewHas('popularBooks', fn ($books) => $books->isEmpty());
    }

    public function test_estimated_fine_sums_only_own_active_loans_using_their_saved_daily_rates(): void
    {
        config(['library.fine_daily_rate' => 9000]);
        $this->borrow($this->copy($this->book('Telat dua hari')), $this->member, [
            'due_date' => '2026-10-01', 'fine_daily_rate' => 2000,
        ]);
        $archived = $this->book('Telat satu hari dan diarsipkan', ['archived_at' => now()]);
        $this->borrow($this->copy($archived), $this->member, [
            'due_date' => '2026-10-02', 'fine_daily_rate' => 3500,
        ]);
        $this->borrow($this->copy($this->book('Hari ini')), $this->member, [
            'due_date' => '2026-10-03', 'fine_daily_rate' => 2000,
        ]);
        $this->borrow($this->copy($this->book('Besok')), $this->member, [
            'due_date' => '2026-10-04', 'fine_daily_rate' => 2000,
        ]);
        $this->borrow($this->copy($this->book('Sudah kembali')), $this->member, [
            'due_date' => '2026-09-01', 'returned_at' => now()->subDays(1), 'fine_amount' => 25000,
        ]);
        $this->borrow($this->copy($this->book('Kehilangan selesai')), $this->member, [
            'due_date' => '2026-09-01', 'returned_at' => now()->subDays(1),
            'return_condition' => 'hilang', 'replacement_amount' => 80000,
        ]);
        $other = $this->member();
        $this->borrow($this->copy($this->book('Pinjaman siswa lain')), $other, [
            'due_date' => '2026-09-01', 'fine_daily_rate' => 2000,
        ]);

        $this->actingAs($this->member->user)->get('/dashboard?member_id='.$other->id)->assertOk()
            ->assertViewHas('estimatedFineTotal', 7500)
            ->assertViewHas('activeCount', 4)->assertViewHas('lateCount', 2)
            ->assertViewHas('dueTodayCount', 1);
    }

    public function test_member_card_data_comes_from_the_logged_in_student_and_includes_class(): void
    {
        $class = SchoolClass::create(['name' => 'VII A', 'grade_level' => 7, 'is_active' => true]);
        $this->member->update(['school_class_id' => $class->id, 'nis' => 'NIS-SENDIRI']);
        $other = $this->member();
        $other->update(['nis' => 'NIS-RAHASIA']);

        $this->actingAs($this->member->user)->get('/dashboard?member_id='.$other->id)->assertOk()
            ->assertViewHas('studentProfile', fn ($member) => $member->id === $this->member->id
                && $member->member_code === $this->member->member_code
                && $member->nis === 'NIS-SENDIRI' && $member->schoolClass->name === 'VII A')
            ->assertDontSee('NIS-RAHASIA')->assertDontSee($other->member_code)->assertDontSee($other->user->email);
    }

    public function test_favorites_preview_is_private_limited_and_ordered_by_when_saved(): void
    {
        $books = collect();
        for ($i = 0; $i < 5; $i++) {
            $books->push($this->book('Favorit '.$i));
        }
        // Urutan penyimpanan berbeda dari urutan buku dibuat.
        $saveOrder = [4, 0, 3, 1, 2];
        foreach ($saveOrder as $offset => $index) {
            $this->member->user->favoriteBooks()->attach($books[$index], [
                'created_at' => now()->subMinutes(10 - $offset), 'updated_at' => now(),
            ]);
        }
        $archived = $this->book('Favorit diarsipkan', ['archived_at' => now()]);
        $this->member->user->favoriteBooks()->attach($archived);
        $other = $this->member();
        $otherFavorite = $this->book('Hanya disimpan siswa lain');
        $other->user->favoriteBooks()->attach($otherFavorite);
        $expected = collect([2, 1, 3, 0])->map(fn ($index) => $books[$index]->id)->all();

        $this->actingAs($this->member->user)->get('/dashboard?user_id='.$other->user_id)->assertOk()
            ->assertViewHas('favoriteCount', 5)
            ->assertViewHas('favoriteBooks', fn ($books) => $books->pluck('id')->all() === $expected
                && $books->pluck('is_favorite')->all() === [true, true, true, true]
                && $books->pluck('available_count')->all() === [0, 0, 0, 0])
            ->assertViewHas('latestBooks', fn ($books) => $books->first()->id === $otherFavorite->id
                && $books->first()->is_favorite === false);
    }

    public function test_dashboard_without_member_profile_and_books_has_empty_discovery_and_zero_estimate(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertViewHas('studentProfile', null)->assertViewHas('hasMemberProfile', false)
            ->assertViewHas('estimatedFineTotal', 0)->assertViewHas('favoriteCount', 0)
            ->assertViewHas('favoriteBooks', fn ($books) => $books->isEmpty())
            ->assertViewHas('popularBooks', fn ($books) => $books->isEmpty())
            ->assertViewHas('latestBooks', fn ($books) => $books->isEmpty());
    }

    private function member(): Member
    {
        return Member::create([
            'user_id' => User::factory()->create()->id,
            'member_code' => 'ANG-'.str()->uuid(),
        ]);
    }

    private function book(string $title, array $extra = []): Book
    {
        return Book::create(array_merge([
            'category_id' => $this->category->id, 'title' => $title, 'author' => 'Penulis',
        ], $extra));
    }

    private function copy(Book $book, array $extra = []): BookCopy
    {
        return $book->copies()->create(array_merge([
            'inventory_code' => 'INV-'.str()->uuid(), 'condition' => 'baik',
        ], $extra));
    }

    private function borrow(BookCopy $copy, Member $member, array $extra = []): LoanItem
    {
        $loan = Loan::create([
            'loan_code' => 'PJM-'.str()->uuid(), 'member_id' => $member->id,
            'created_by' => $this->staff->id, 'borrowed_at' => now()->subDays(35),
        ]);

        return $loan->items()->create(array_merge([
            'book_copy_id' => $copy->id, 'due_date' => today(), 'fine_daily_rate' => 2000,
        ], $extra));
    }
}
