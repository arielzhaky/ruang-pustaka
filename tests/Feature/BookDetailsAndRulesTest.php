<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDetailsAndRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_book_details_with_filtered_back_link_and_copy_pagination(): void
    {
        $book = Book::create(['category_id' => Category::create(['name' => 'Novel'])->id,
            'title' => 'Buku Detail', 'author' => 'Penulis', 'replacement_price' => 65000]);
        for ($i = 0; $i < 12; $i++) {
            $book->copies()->create(['inventory_code' => sprintf('COPY-%02d', $i), 'condition' => 'baik', 'shelf_location' => 'Rak A']);
        }
        $this->actingAs(User::factory()->create(['role' => 'petugas']));
        $query = ['q' => 'Buku', 'availability' => 'available', 'page' => 2];
        $this->get(route('books.show', ['book' => $book->id, ...$query, 'copies_page' => 2]))
            ->assertOk()->assertViewHas('availableCount', 12)->assertSee('Rp65.000')
            ->assertSee('COPY-10')->assertDontSee('COPY-00')->assertSee('Rak A')
            ->assertSee(route('books.index', $query))->assertSee(route('books.edit', $book))
            ->assertSee('Aturan & Tips Peminjaman');
        $this->get(route('books.show', ['book' => $book->id, 'copies_page' => 0]))->assertSessionHasErrors('copies_page');
        $book->update(['archived_at' => now()]);
        $this->get(route('books.show', $book))->assertNotFound();
    }

    public function test_book_management_detail_still_requires_active_staff(): void
    {
        $book = Book::create(['category_id' => Category::create(['name' => 'Novel'])->id, 'title' => 'Buku', 'author' => 'Penulis']);
        $this->get(route('books.show', $book))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('books.show', $book))->assertForbidden();
        $this->get(route('catalog.show', $book))->assertOk()->assertDontSee('Kelola Eksemplar')->assertDontSee('Ubah Buku');
        $this->actingAs(User::factory()->create(['role' => 'petugas', 'is_active' => false]))
            ->get(route('books.show', $book))->assertRedirect(route('login'));
    }

    public function test_rules_use_current_configuration_and_are_accessible_to_both_roles(): void
    {
        config(['library.loan_days' => 14, 'library.max_active_loans' => 5, 'library.fine_daily_rate' => 3000]);
        $this->get(route('borrowing-rules'))->assertRedirect(route('login'));
        foreach (['anggota', 'petugas'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('borrowing-rules'))->assertOk()->assertSee('14 hari')->assertSee('5 eksemplar')
                ->assertSee('Rp3.000')->assertSee('tanpa tambahan denda keterlambatan');
        }
        $this->get(route('loans.create'))->assertOk()->assertSee('14 hari')->assertSee('Rp3.000');
    }

    public function test_sidebar_respects_role_and_member_stats_include_only_student_profiles(): void
    {
        $staff = User::factory()->create(['role' => 'petugas']);
        $student = User::factory()->create();
        $student->member()->create(['member_code' => 'S1']);
        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->member()->create(['member_code' => 'S2']);
        $staff->member()->create(['member_code' => 'STAFF']);
        $this->actingAs($staff)->get('/members')->assertOk()
            ->assertViewHas('memberStats', ['total' => 2, 'active' => 1, 'inactive' => 1]);
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Panduan Peminjaman')
            ->assertSee('Peminjaman Saya')->assertDontSee('Kelola Siswa')->assertDontSee(route('reports.loans'));
    }
}
