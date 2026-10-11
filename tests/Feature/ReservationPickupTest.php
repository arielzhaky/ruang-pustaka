<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanRenewal;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPickupTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Member $member;

    private BookReservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'PICKUP-001']);
        $book = Book::create(['category_id' => Category::create(['name' => 'Cerita'])->id, 'title' => 'Buku Reservasi', 'author' => 'Penulis']);
        $copy = $book->copies()->create(['inventory_code' => 'PICKUP-COPY', 'condition' => 'baik']);
        $this->reservation = BookReservation::create(['member_id' => $this->member->id, 'book_id' => $book->id,
            'book_copy_id' => $copy->id, 'status' => 'ready', 'active_key' => $this->member->id.':'.$book->id,
            'ready_at' => now(), 'expires_at' => now()->addDays(2)]);
    }

    public function test_pickup_prefills_reserved_owner_and_copy_and_completes_reservation(): void
    {
        $this->actingAs($this->staff)->get('/reservations')->assertOk()
            ->assertSee(route('loans.create', ['reservation_id' => $this->reservation->id]), false);
        $other = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'OTHER-PICKUP']);
        $response = $this->get('/loans/create?reservation_id='.$this->reservation->id.'&member_id='.$other->id.'&q=missing-student')
            ->assertOk()->assertSee('Pengambilan reservasi: Buku Reservasi')
            ->assertViewHas('selectedMemberId', $this->member->id)
            ->assertViewHas('selectedCopyIds', [$this->reservation->book_copy_id]);
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//select[@name="member_id"]/option[@value="'.$this->member->id.'" and @selected]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="book_copy_ids[]" and @value="'.$this->reservation->book_copy_id.'" and @checked]')->length);
        $this->post('/loans', ['member_id' => $response->viewData('selectedMemberId'), 'book_copy_ids' => $response->viewData('selectedCopyIds')])
            ->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
        $this->assertSame('fulfilled', $this->reservation->fresh()->status);
        $this->assertDatabaseHas('loans', ['member_id' => $this->member->id]);
        $this->assertDatabaseMissing('loans', ['member_id' => $other->id]);
    }

    public function test_stale_pickup_links_redirect_to_reservations_without_creating_loans(): void
    {
        $this->actingAs($this->staff);
        foreach (['expired', 'cancelled', 'inactive', 'archived', 'damaged'] as $state) {
            $this->reservation->update(['status' => 'ready', 'expires_at' => now()->addDays(2)]);
            $this->member->user->forceFill(['is_active' => true])->save();
            $this->reservation->book->update(['archived_at' => null]);
            $this->reservation->bookCopy->update(['condition' => 'baik']);
            match ($state) {
                'expired' => $this->reservation->update(['expires_at' => now()]),
                'cancelled' => $this->reservation->update(['status' => 'cancelled']),
                'inactive' => $this->member->user->forceFill(['is_active' => false])->save(),
                'archived' => $this->reservation->book->update(['archived_at' => now()]),
                'damaged' => $this->reservation->bookCopy->update(['condition' => 'rusak']),
            };
            $this->get('/loans/create?reservation_id='.$this->reservation->id)->assertRedirect(route('reservations.index'))->assertSessionHasErrors('reservation');
        }
        $this->get('/loans/create?reservation_id=999999')->assertRedirect(route('reservations.index'))->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_student_cannot_open_staff_pickup_form_and_normal_form_still_searches(): void
    {
        $this->actingAs($this->member->user)->get('/loans/create?reservation_id='.$this->reservation->id)->assertForbidden();
        $this->actingAs($this->staff)->get('/loans/create?q=PICKUP-001&member_id='.$this->member->id)->assertOk()
            ->assertViewHas('selectedReservation', null)->assertViewHas('selectedMemberId', $this->member->id)
            ->assertViewHas('selectedCopyIds', [])->assertViewHas('members', fn ($members) => $members->count() === 1);
    }

    public function test_renewal_reason_is_submitted_and_rejection_note_shown_safely(): void
    {
        $this->reservation->update(['status' => 'cancelled', 'active_key' => null]);
        $loan = Loan::create(['loan_code' => 'REASON-LOAN', 'member_id' => $this->member->id, 'created_by' => $this->staff->id, 'borrowed_at' => now()]);
        $item = $loan->items()->create(['book_copy_id' => $this->reservation->book_copy_id, 'due_date' => today()->addDays(2), 'fine_daily_rate' => 2000]);
        $this->actingAs($this->member->user)->get('/my-loans')->assertOk()->assertSee('Alasan perpanjangan (opsional)')->assertSee('name="reason"', false);
        $reason = 'Masih membaca <script>alert(1)</script>';
        $this->post(route('renewals.store', $item), ['reason' => $reason])->assertSessionHasNoErrors();
        $renewal = LoanRenewal::firstOrFail();
        $this->assertSame($reason, $renewal->reason);
        $this->actingAs($this->staff)->get('/renewals')->assertSee(e($reason), false)->assertDontSee('<script>alert(1)</script>', false);
        $note = 'Ada antrean <script>alert(2)</script>';
        $this->post(route('renewals.review', $renewal), ['decision' => 'rejected', 'decision_note' => $note])->assertSessionHasNoErrors();
        $this->actingAs($this->member->user)->get('/my-loans')->assertOk()->assertSee('Perpanjangan ditolak')
            ->assertSee(e($note), false)->assertDontSee('<script>alert(2)</script>', false)->assertSee('Ajukan perpanjangan');
    }
}
