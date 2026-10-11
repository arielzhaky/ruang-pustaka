<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Support\LibraryBarcode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarcodeTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Member $member;

    private Book $book;

    private BookCopy $copy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::create(['user_id' => User::factory()->create(['name' => 'Siswa Barcode'])->id, 'member_code' => 'M-SCAN']);
        $this->book = Book::create(['category_id' => Category::create(['name' => 'Fiksi'])->id, 'title' => 'Buku Barcode', 'author' => 'Penulis']);
        $this->copy = $this->book->copies()->create(['inventory_code' => 'INV-SCAN', 'condition' => 'baik']);
    }

    private function lookup(string $code, string $kind = 'any', ?int $memberId = null)
    {
        return $this->getJson(route('barcodes.lookup', ['code' => $code, 'kind' => $kind, 'member_id' => $memberId]));
    }

    public function test_scanning_and_staff_print_routes_require_staff(): void
    {
        $urls = [route('barcodes.index'), route('members.card', $this->member), route('barcodes.copies.print', $this->book)];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->lookup('M-SCAN')->assertUnauthorized();
        $this->actingAs($this->member->user);
        foreach ($urls as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->lookup('M-SCAN')->assertForbidden();
        $this->actingAs($this->staff);
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        $this->lookup('M-SCAN')->assertOk();
    }

    public function test_student_card_ignores_other_member_id_and_staff_card_shows_selected_student(): void
    {
        $other = Member::create(['user_id' => User::factory()->create(['name' => 'Siswa Lain'])->id, 'member_code' => 'M-OTHER']);
        $this->actingAs($this->member->user)->get(route('member-card', ['member_id' => $other->id]))->assertOk()
            ->assertSee(LibraryBarcode::member($this->member))->assertDontSee(LibraryBarcode::member($other));
        $this->actingAs($this->staff)->get(route('members.card', $other))->assertOk()
            ->assertSee('Siswa Lain')->assertSee(LibraryBarcode::member($other))->assertDontSee(LibraryBarcode::member($this->member));
        $staffProfile = Member::create(['user_id' => $this->staff->id, 'member_code' => 'STAFF']);
        $this->get(route('members.card', $staffProfile))->assertNotFound();
        $this->lookup('STAFF')->assertNotFound();
    }

    public function test_generated_barcodes_survive_editing_business_codes_and_scanner_prefix(): void
    {
        $memberCode = LibraryBarcode::member($this->member);
        $copyCode = LibraryBarcode::copy($this->copy);
        $this->member->update(['member_code' => 'M-NEW']);
        $this->copy->update(['inventory_code' => 'INV-NEW']);
        $this->actingAs($this->staff);
        $response = $this->lookup(' ]C0'.$memberCode.' ')->assertOk()->assertJsonPath('id', $this->member->id)->assertJsonPath('member_code', 'M-NEW');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->lookup($copyCode)->assertOk()->assertJsonPath('id', $this->copy->id)->assertJsonPath('inventory_code', 'INV-NEW');
        $this->lookup('M-NEW', 'member')->assertOk();
        $this->lookup('INV-NEW', 'copy')->assertOk();
        $this->lookup('M-SCAN')->assertNotFound();
    }

    public function test_ambiguous_legacy_codes_kind_mismatch_and_invalid_inputs(): void
    {
        $this->copy->update(['inventory_code' => $this->member->member_code]);
        $this->actingAs($this->staff);
        $this->lookup('M-SCAN')->assertStatus(409);
        $this->lookup('M-SCAN', 'member')->assertJsonPath('kind', 'member');
        $this->lookup('M-SCAN', 'copy')->assertJsonPath('kind', 'copy');
        $this->lookup(LibraryBarcode::member($this->member), 'copy')->assertUnprocessable();
        $this->lookup(LibraryBarcode::copy($this->copy), 'member')->assertUnprocessable();
        $this->lookup('UNKNOWN')->assertNotFound();
        $this->lookup('')->assertUnprocessable();
        $this->lookup('M-SCAN', 'invalid')->assertUnprocessable();
        $this->lookup(str_repeat('a', 101))->assertUnprocessable();
    }

    public function test_copy_availability_and_inactive_member_details(): void
    {
        $this->actingAs($this->staff);
        $this->lookup('INV-SCAN')->assertJsonPath('status', 'available')->assertJsonPath('can_borrow', true)->assertJsonPath('loan', null);
        $this->member->user->forceFill(['is_active' => false])->save();
        $this->lookup('M-SCAN')->assertJsonPath('active', false);
        foreach (['rusak', 'hilang'] as $condition) {
            $this->copy->update(['condition' => $condition]);
            $this->lookup('INV-SCAN')->assertJsonPath('status', $condition)->assertJsonPath('can_borrow', false);
        }
        $this->copy->update(['condition' => 'baik', 'archived_at' => now()]);
        $this->lookup('INV-SCAN')->assertJsonPath('status', 'archived')->assertJsonPath('can_borrow', false);
        $this->copy->update(['archived_at' => null]);
        $this->book->update(['archived_at' => now()]);
        $this->lookup('INV-SCAN')->assertJsonPath('status', 'archived')->assertJsonPath('can_borrow', false);
    }

    public function test_borrowed_copy_returns_current_loan_and_member_counts_without_mutation(): void
    {
        $item = Loan::create(['loan_code' => 'SCAN-LOAN', 'member_id' => $this->member->id, 'created_by' => $this->staff->id, 'borrowed_at' => now()->subDays(5)])
            ->items()->create(['book_copy_id' => $this->copy->id, 'due_date' => today()->subDays(2), 'fine_daily_rate' => 1000]);
        $before = $item->fresh()->toArray();
        $this->actingAs($this->staff);
        $this->lookup('INV-SCAN')->assertOk()->assertJsonPath('status', 'borrowed')->assertJsonPath('can_borrow', false)
            ->assertJsonPath('loan.id', $item->id)->assertJsonPath('loan.late_days', 2)->assertJsonPath('loan.estimated_fine', 2000);
        $this->lookup('M-SCAN')->assertJsonPath('active_count', 1)->assertJsonPath('late_count', 1);
        $this->assertSame($before, $item->fresh()->toArray());
        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_reservation_lookup_respects_owner_queue_and_expiry_without_mutations(): void
    {
        $reservation = BookReservation::create(['book_id' => $this->book->id, 'member_id' => $this->member->id, 'status' => 'waiting']);
        $this->actingAs($this->staff);
        $this->lookup('INV-SCAN')->assertJsonPath('can_borrow', false);
        $this->lookup('INV-SCAN', 'copy', $this->member->id)->assertJsonPath('can_borrow', true);
        $reservation->update(['status' => 'ready', 'book_copy_id' => $this->copy->id, 'expires_at' => now()->addDay()]);
        $before = $reservation->fresh()->toArray();
        $this->lookup('INV-SCAN')->assertJsonPath('status', 'reserved')->assertJsonPath('can_borrow', false);
        $this->lookup('INV-SCAN', 'copy', $this->member->id)->assertJsonPath('can_borrow', true);
        $other = $this->book->copies()->create(['inventory_code' => 'INV-OTHER', 'condition' => 'baik']);
        $this->lookup($other->inventory_code, 'copy', $this->member->id)->assertJsonPath('can_borrow', false);
        $this->assertSame($before, $reservation->fresh()->toArray());
        $reservation->update(['expires_at' => now()->subMinute()]);
        $before = $reservation->fresh()->toArray();
        $this->lookup('INV-SCAN')->assertJsonPath('status', 'available')->assertJsonPath('can_borrow', true);
        $this->assertSame($before, $reservation->fresh()->toArray());
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_labels_are_scoped_to_book_paginated_and_exclude_archived_bulk_copies(): void
    {
        $otherBook = Book::create(['category_id' => $this->book->category_id, 'title' => 'Lain', 'author' => 'Penulis']);
        $otherCopy = $otherBook->copies()->create(['inventory_code' => 'OTHER', 'condition' => 'baik']);
        $this->actingAs($this->staff)->get(route('barcodes.copies.print', ['book' => $this->book, 'copy_id' => $otherCopy->id]))->assertNotFound();
        $this->copy->update(['archived_at' => now()]);
        $rows = [];
        for ($i = 0; $i < 101; $i++) {
            $rows[] = ['book_id' => $this->book->id, 'inventory_code' => sprintf('BULK-%03d', $i), 'condition' => 'baik'];
        }
        DB::table('book_copies')->insert($rows);
        $this->get(route('barcodes.copies.print', $this->book))->assertOk()->assertViewHas('copies', fn ($copies) => $copies->total() === 101 && $copies->count() === 100)
            ->assertDontSee(LibraryBarcode::copy($this->copy));
        $this->get(route('barcodes.copies.print', ['book' => $this->book, 'page' => 2]))->assertOk()->assertViewHas('copies', fn ($copies) => $copies->count() === 1);
        $this->get(route('barcodes.copies.print', ['book' => $this->book, 'copy_id' => $this->copy->id]))->assertOk()->assertSee(LibraryBarcode::copy($this->copy));
    }

    public function test_canonical_parser_rejects_overflow_zero_and_noncanonical_padding(): void
    {
        $this->assertSame(['kind' => 'member', 'id' => 1], LibraryBarcode::parse('RPS00000001'));
        foreach (['RPS00000000', 'RPC000000001', 'RPC9999999999999999999', 'RPS1', '<script>'] as $code) {
            $this->assertNull(LibraryBarcode::parse($code));
        }
        $this->assertStringContainsString('<svg', LibraryBarcode::svg('RPC00000001'));
    }
}
