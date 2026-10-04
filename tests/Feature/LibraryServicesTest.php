<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\FinePayment;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\LoanRenewal;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LibraryServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Member $member;

    private Member $other;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 4)->setTime(9, 0));
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::create(['user_id' => User::factory()->create(['password' => 'password123'])->id, 'member_code' => 'SISWA-001', 'nis' => 'NIS-1101']);
        $this->other = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'SISWA-002']);
        $this->book = Book::create(['category_id' => Category::create(['name' => 'Fiksi'])->id, 'title' => 'Buku Layanan', 'author' => 'Penulis', 'replacement_price' => 80000]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function copy(): BookCopy
    {
        return $this->book->copies()->create(['inventory_code' => 'COPY-'.BookCopy::count(), 'condition' => 'baik']);
    }

    private function loan(?Member $member = null, array $attributes = []): LoanItem
    {
        $loan = Loan::create(['loan_code' => 'PJM-'.Loan::count(), 'member_id' => ($member ?? $this->member)->id, 'created_by' => $this->staff->id, 'borrowed_at' => now()->subDays(3)]);

        return $loan->items()->create(array_merge(['book_copy_id' => $this->copy()->id, 'due_date' => today()->addDays(2), 'fine_daily_rate' => 2000], $attributes));
    }

    private function reserve(Member $member): BookReservation
    {
        $this->actingAs($member->user)->post(route('reservations.store', $this->book))->assertSessionHasNoErrors();

        return BookReservation::where('member_id', $member->id)->latest('id')->firstOrFail();
    }

    public function test_pages_render_for_both_roles_and_private_operations_are_protected(): void
    {
        foreach ([$this->staff, $this->member->user] as $user) {
            $this->actingAs($user);
            foreach (['/dashboard', '/account', '/billing', '/reservations', '/renewals'] as $url) {
                $this->get($url)->assertOk();
            }
        }
        foreach (['/activity', '/overdue'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/reminders')->assertOk();
        $this->actingAs($this->staff)->get('/activity')->assertOk();
        $this->get('/overdue')->assertOk();
        $this->get('/reminders')->assertForbidden();
    }

    public function test_guest_and_inactive_accounts_cannot_access_services(): void
    {
        foreach (['/account', '/billing', '/reservations', '/renewals', '/reminders', '/activity', '/overdue'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->member->user->forceFill(['is_active' => false])->save();
        $this->actingAs($this->member->user)->get('/billing')->assertRedirect(route('login'));
    }

    public function test_profile_requires_current_password_and_ignores_protected_fields(): void
    {
        $this->actingAs($this->member->user);
        $this->put('/account', ['name' => 'Diubah', 'email' => 'changed@example.test', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertNotSame('Diubah', $this->member->user->fresh()->name);
        $this->put('/account', ['name' => 'Diubah', 'email' => 'CHANGED@example.test', 'phone' => '0812345', 'current_password' => 'password123', 'role' => 'petugas', 'member_code' => 'FORGED', 'user_id' => $this->other->user_id])->assertSessionHasNoErrors();
        $user = $this->member->user->fresh();
        $this->assertSame('changed@example.test', $user->email);
        $this->assertSame('anggota', $user->role);
        $this->assertSame('SISWA-001', $this->member->fresh()->member_code);
        $this->assertSame('0812345', $this->member->fresh()->phone);
        $this->assertNotSame('Diubah', $this->other->user->fresh()->name);
    }

    public function test_password_validation_and_change(): void
    {
        $this->actingAs($this->member->user)->put('/account/password', ['current_password' => 'wrong', 'password' => 'new-password123', 'password_confirmation' => 'new-password123'])->assertSessionHasErrors('current_password');
        $this->put('/account/password', ['current_password' => 'password123', 'password' => 'new-password123', 'password_confirmation' => 'new-password123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password123', $this->member->user->fresh()->password));
        $this->assertFalse(Hash::check('password123', $this->member->user->fresh()->password));
        $this->put('/account', ['name' => 'Tidak boleh', 'email' => $this->other->user->email, 'current_password' => 'new-password123'])->assertSessionHasErrors('email');
    }

    public function test_return_creates_fixed_unpaid_bill_and_lost_book_only_replacement(): void
    {
        $late = $this->loan(attributes: ['due_date' => today()->subDays(2)]);
        $lost = $this->loan(attributes: ['due_date' => today()->subDays(5)]);
        $this->actingAs($this->staff)->post(route('loans.items.return', $late), ['return_condition' => 'baik'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_items', ['id' => $late->id, 'billing_status' => 'unpaid', 'fine_amount' => 4000]);
        $this->post(route('loans.items.return', $lost), ['return_condition' => 'hilang'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_items', ['id' => $lost->id, 'billing_status' => 'unpaid', 'fine_amount' => 0, 'replacement_amount' => 80000]);
        $this->get('/billing')->assertOk()->assertViewHas('unpaid', 84000);
    }

    public function test_payment_is_exact_full_amount_idempotent_and_receipt_private(): void
    {
        $item = $this->loan(attributes: ['returned_at' => now(), 'fine_amount' => 4000]);
        $item->forceFill(['billing_status' => 'unpaid'])->save();
        $this->actingAs($this->member->user)->post(route('billing.pay', $item), ['method' => 'cash', 'confirmation' => 1])->assertForbidden();
        $this->actingAs($this->staff)->post(route('billing.pay', $item), ['method' => 'cash', 'confirmation' => 1, 'amount' => 1])->assertSessionHasNoErrors();
        $payment = FinePayment::firstOrFail();
        $this->assertSame(4000, $payment->amount);
        $this->assertSame('paid', $item->fresh()->billing_status);
        $this->post(route('billing.pay', $item), ['method' => 'cash', 'confirmation' => 1])->assertRedirect(route('billing.receipt', $payment));
        $this->assertDatabaseCount('fine_payments', 1);
        $this->get(route('billing.receipt', $payment))->assertOk()->assertSee('Rp4.000');
        $this->actingAs($this->member->user)->get(route('billing.receipt', $payment))->assertOk();
        $this->actingAs($this->other->user)->get(route('billing.receipt', $payment))->assertNotFound();
        $this->get('/billing')->assertOk()->assertDontSee('PJM-0')->assertViewHas('unpaid', 0);
    }

    public function test_active_estimates_cannot_be_paid_and_legacy_bills_require_verification(): void
    {
        $active = $this->loan(attributes: ['due_date' => today()->subDays(2)]);
        $legacy = $this->loan(attributes: ['returned_at' => now(), 'fine_amount' => 8000]);
        $this->actingAs($this->staff)->get('/billing')->assertViewHas('unpaid', 0)->assertViewHas('unverified', 1);
        foreach ([$active, $legacy] as $item) {
            $this->post(route('billing.pay', $item), ['method' => 'cash', 'confirmation' => 1])->assertSessionHasErrors('billing');
        }
        $this->post(route('billing.confirm', $legacy), ['confirmation' => 1])->assertSessionHasNoErrors();
        $this->get('/billing')->assertViewHas('unpaid', 8000);
        $this->post(route('billing.pay', $legacy), ['method' => 'transfer', 'confirmation' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('fine_payments', 1);
    }

    public function test_old_paid_bill_can_be_marked_without_recording_new_cash(): void
    {
        $item = $this->loan(attributes: ['returned_at' => now()->subDay(), 'fine_amount' => 6000]);
        $this->actingAs($this->staff)->post(route('billing.pay', $item), ['method' => 'historical', 'confirmation' => 1])->assertSessionHasNoErrors();
        $this->get(route('billing.receipt', FinePayment::first()))->assertOk()->assertSee('Konfirmasi Pembayaran Lama');
        $this->assertSame('paid', $item->fresh()->billing_status);
        $this->assertSame('historical', FinePayment::first()->method);
    }

    public function test_renewal_request_does_not_change_due_date_until_reviewed(): void
    {
        $item = $this->loan();
        $original = $item->due_date->toDateString();
        $this->actingAs($this->member->user)->post(route('renewals.store', $item), ['reason' => 'Belum selesai membaca'])->assertSessionHasNoErrors();
        $this->assertSame($original, $item->fresh()->due_date->toDateString());
        $renewal = LoanRenewal::firstOrFail();
        $this->post(route('renewals.store', $item))->assertSessionHasErrors('renewal');
        $this->post(route('renewals.review', $renewal), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($this->staff)->post(route('renewals.review', $renewal), ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame($item->due_date->addDays(7)->toDateString(), $item->fresh()->due_date->toDateString());
        $this->assertSame(2000, $item->fresh()->fine_daily_rate);
        $this->post(route('renewals.review', $renewal), ['decision' => 'approved'])->assertSessionHasErrors('renewal');
        $this->actingAs($this->member->user)->post(route('renewals.store', $item))->assertSessionHasErrors('renewal');
        $this->actingAs($this->other->user)->get('/renewals')->assertOk()->assertDontSee('Belum selesai membaca');
    }

    public function test_renewal_rechecks_queue_and_overdue_at_approval_time(): void
    {
        $item = $this->loan();
        $this->actingAs($this->member->user)->post(route('renewals.store', $item))->assertSessionHasNoErrors();
        $reservation = $this->reserve($this->other);
        $renewal = LoanRenewal::firstOrFail();
        $this->actingAs($this->staff)->post(route('renewals.review', $renewal), ['decision' => 'approved'])->assertSessionHasErrors('renewal');
        $this->post(route('renewals.review', $renewal), ['decision' => 'rejected'])->assertSessionHasErrors('decision_note');
        $this->post(route('renewals.review', $renewal), ['decision' => 'rejected', 'decision_note' => 'Ada antrean'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $renewal->fresh()->status);
        $this->assertSame(today()->addDays(2)->toDateString(), $item->fresh()->due_date->toDateString());
        $this->delete(route('reservations.cancel', $reservation))->assertSessionHasNoErrors();
        $this->actingAs($this->member->user)->post(route('renewals.store', $item))->assertSessionHasNoErrors();
        $this->travel(3)->days();
        $this->actingAs($this->staff)->post(route('renewals.review', $renewal), ['decision' => 'approved'])->assertSessionHasErrors('renewal');
    }

    public function test_renewal_rejects_other_borrower_returned_and_overdue_items(): void
    {
        $item = $this->loan();
        $this->actingAs($this->other->user)->post(route('renewals.store', $item))->assertNotFound();
        $late = $this->loan(attributes: ['due_date' => today()->subDay()]);
        $returned = $this->loan(attributes: ['returned_at' => now()]);
        $this->actingAs($this->member->user);
        foreach ([$late, $returned] as $loan) {
            $this->post(route('renewals.store', $loan))->assertSessionHasErrors('renewal');
        }
        $this->assertDatabaseCount('loan_renewals', 0);
    }

    public function test_return_closes_pending_renewal(): void
    {
        $item = $this->loan();
        $this->actingAs($this->member->user)->post(route('renewals.store', $item))->assertSessionHasNoErrors();
        $this->actingAs($this->staff)->post(route('loans.items.return', $item), ['return_condition' => 'baik'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_renewals', ['loan_item_id' => $item->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('loan_items', ['id' => $item->id, 'billing_status' => 'none']);
    }

    public function test_reservation_is_private_idempotent_and_requires_physical_stock(): void
    {
        $this->actingAs($this->member->user)->post(route('reservations.store', $this->book))->assertSessionHasErrors('reservation');
        $this->copy();
        $reservation = $this->reserve($this->member);
        $this->reserve($this->member);
        $this->assertDatabaseCount('book_reservations', 1);
        $this->actingAs($this->other->user)->get('/reservations')->assertViewHas('reservations', fn ($r) => $r->isEmpty());
        $this->delete(route('reservations.cancel', $reservation))->assertNotFound();
        $this->post(route('reservations.ready', $reservation))->assertForbidden();
        $this->actingAs($this->member->user)->delete(route('reservations.cancel', $reservation))->assertSessionHasNoErrors();
        $this->reserve($this->member);
        $this->assertDatabaseCount('book_reservations', 2);
    }

    public function test_reservation_queue_blocks_bypassing_and_fulfills_with_loan(): void
    {
        $copy = $this->copy();
        $first = $this->reserve($this->member);
        $second = $this->reserve($this->other);
        $this->actingAs($this->staff)->post(route('reservations.ready', $second))->assertSessionHasErrors('reservation');
        $this->post('/loans', ['member_id' => $this->other->id, 'book_copy_ids' => [$copy->id]])->assertSessionHasErrors('book_copy_ids');
        $this->post(route('reservations.ready', $first))->assertSessionHasNoErrors();
        $this->assertSame('ready', $first->fresh()->status);
        $this->assertSame(0, BookCopy::available()->count());
        $this->actingAs($this->member->user)->get(route('catalog.show', $this->book))->assertOk()->assertSee('Disiapkan untuk reservasi');
        $this->actingAs($this->staff)->post('/loans', ['member_id' => $this->other->id, 'book_copy_ids' => [$copy->id]])->assertSessionHasErrors('book_copy_ids');
        $this->post('/loans', ['member_id' => $this->member->id, 'book_copy_ids' => [$copy->id]])->assertSessionHasNoErrors();
        $this->assertSame('fulfilled', $first->fresh()->status);
        $this->assertNull($first->fresh()->active_key);
        $this->assertDatabaseCount('loan_items', 1);
        $this->assertSame('waiting', $second->fresh()->status);
    }

    public function test_ready_reservation_expires_at_exact_deadline_and_releases_stock(): void
    {
        $copy = $this->copy();
        $reservation = $this->reserve($this->member);
        $this->actingAs($this->staff)->post(route('reservations.ready', $reservation))->assertSessionHasNoErrors();
        $this->travelTo($reservation->fresh()->expires_at);
        $this->assertSame(1, BookCopy::available()->count());
        $this->actingAs($this->member->user)->get('/reservations?status=history')->assertOk()->assertSee('Kedaluwarsa');
        $this->actingAs($this->staff)->post('/loans', ['member_id' => $this->other->id, 'book_copy_ids' => [$copy->id]])->assertSessionHasNoErrors();
        $this->assertSame('expired', $reservation->fresh()->status);
        $this->assertNull($reservation->fresh()->active_key);
    }

    public function test_cancelling_ready_reservation_releases_copy_and_sold_out_cannot_be_prepared(): void
    {
        $item = $this->loan($this->other);
        $reservation = $this->reserve($this->member);
        $this->actingAs($this->staff)->post(route('reservations.ready', $reservation))->assertSessionHasErrors('reservation');
        $this->post(route('loans.items.return', $item), ['return_condition' => 'baik'])->assertSessionHasNoErrors();
        $this->post(route('reservations.ready', $reservation))->assertSessionHasNoErrors();
        $this->assertSame(0, BookCopy::available()->count());
        $this->actingAs($this->member->user)->delete(route('reservations.cancel', $reservation))->assertSessionHasNoErrors();
        $this->assertSame(1, BookCopy::available()->count());
    }

    public function test_active_borrower_cannot_reserve_same_title_and_reservation_limit_is_enforced(): void
    {
        $this->loan();
        $this->actingAs($this->member->user)->post(route('reservations.store', $this->book))->assertSessionHasErrors('reservation');
        config(['library.max_reservations' => 1]);
        $this->reserve($this->other);
        $book = Book::create(['category_id' => $this->book->category_id, 'title' => 'Judul lain', 'author' => 'Penulis']);
        $book->copies()->create(['inventory_code' => 'OTHER-COPY', 'condition' => 'baik']);
        $this->post(route('reservations.store', $book))->assertSessionHasErrors('reservation');
        $this->assertSame(1, BookReservation::active()->count());
    }

    public function test_search_uses_name_nis_and_code_and_excludes_inactive_students(): void
    {
        $this->other->user->forceFill(['is_active' => false])->save();
        $this->actingAs($this->staff)->get('/loans/create?q=NIS-1101')->assertOk()->assertViewHas('members', fn ($m) => $m->pluck('id')->all() === [$this->member->id]);
        $this->get('/loans/create?q=SISWA-002')->assertViewHas('members', fn ($m) => $m->isEmpty());
        $this->get('/loans/create?q='.urlencode($this->member->user->name))->assertViewHas('members', fn ($m) => $m->count() === 1);
    }

    public function test_overdue_filter_and_reminders_obey_dates_ownership_and_returns(): void
    {
        $class = SchoolClass::create(['name' => '7A', 'grade_level' => 7, 'is_active' => true]);
        $this->member->update(['school_class_id' => $class->id]);
        $late = $this->loan(attributes: ['due_date' => today()->subDay()]);
        $soon = $this->loan(attributes: ['due_date' => today()->addDays(2)]);
        $this->loan(attributes: ['due_date' => today()->addDays(3)]);
        $this->loan(attributes: ['due_date' => today()->subDay(), 'returned_at' => now()]);
        $this->loan($this->other, ['due_date' => today()->subDays(10)]);
        $this->actingAs($this->staff)->get('/overdue?q=NIS-1101&class_id='.$class->id)->assertOk()->assertViewHas('items', fn ($i) => $i->pluck('id')->all() === [$late->id]);
        $this->actingAs($this->member->user)->get('/reminders')->assertOk()->assertViewHas('items', fn ($i) => $i->pluck('id')->all() === [$late->id, $soon->id]);
        $this->get('/dashboard')->assertOk()->assertViewHas('serviceSummary', fn ($s) => $s['reminders'] === 2);
    }

    public function test_activity_records_successful_staff_mutations_without_sensitive_payloads(): void
    {
        $this->actingAs($this->staff)->post('/categories', ['name' => 'Kategori audit'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['user_id' => $this->staff->id, 'action' => 'categories.store']);
        $before = ActivityLog::count();
        $this->post('/categories', ['name' => ''])->assertSessionHasErrors();
        $this->assertSame($before, ActivityLog::count());
        $this->assertStringNotContainsString('password', ActivityLog::all()->toJson());
        $this->actingAs($this->member->user)->get('/activity')->assertForbidden();
    }
}
