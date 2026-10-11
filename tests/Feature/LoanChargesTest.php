<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use App\Support\LoanCharges;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LoanChargesTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Member $member;

    private Book $book;

    private BookCopy $copy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-01 23:59:59', 'Asia/Jakarta'));
        config(['library.fine_daily_rate' => 2000]);
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'S-1']);
        $this->book = Book::create(['title' => 'Buku Denda', 'author' => 'Penulis', 'category_id' => Category::create(['name' => 'Fiksi'])->id, 'replacement_price' => 75000]);
        $this->copy = $this->book->copies()->create(['inventory_code' => 'INV-1', 'condition' => 'baik']);
    }

    private function borrow(): LoanItem
    {
        $this->actingAs($this->staff)->post('/loans', [
            'member_id' => $this->member->id, 'book_copy_ids' => [$this->copy->id], 'due_date' => '2026-10-01',
        ])->assertRedirect('/loans')->assertSessionHasNoErrors();

        return LoanItem::latest('id')->firstOrFail();
    }

    public function test_daily_fine_starts_after_due_date_and_rate_is_preserved(): void
    {
        $item = $this->borrow();
        $this->assertSame(0, LoanCharges::total($item));
        $this->travelTo(CarbonImmutable::parse('2026-10-02 00:00:00', 'Asia/Jakarta'));
        $this->assertSame(2000, LoanCharges::total($item));
        config(['library.fine_daily_rate' => 9999]);
        $this->travelTo(CarbonImmutable::parse('2026-10-04 10:00:00', 'Asia/Jakarta'));
        $this->assertSame(6000, LoanCharges::total($item));
        $this->get('/loans')->assertOk()->assertSee('Rp6.000');
        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()->assertSee('Rp6.000');
        $this->get('/my-loans')->assertOk()->assertSee('Rp6.000');
        $this->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'baik'])->assertForbidden();
        $this->actingAs($this->staff)->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'baik', 'fine_amount' => 1])
            ->assertSessionHasNoErrors()->assertRedirect('/loans');
        $this->assertSame(6000, $item->fresh()->fine_amount);
        $this->travel(10)->days();
        $this->assertSame(6000, LoanCharges::total($item->fresh()));
        $this->assertTrue($this->copy->fresh()->newQuery()->available()->whereKey($this->copy->id)->exists());
        $this->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'hilang'])->assertSessionHasErrors('return_condition');
        $this->assertSame(6000, $item->fresh()->fine_amount);
    }

    public function test_lost_book_costs_only_its_price_and_is_not_available(): void
    {
        $item = $this->borrow();
        $this->travel(4)->days();
        $this->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'hilang', 'replacement_amount' => 1])
            ->assertRedirect('/loans')->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame(0, $item->fine_amount);
        $this->assertSame(75000, $item->replacement_amount);
        $this->assertNotNull($item->returned_at);
        $this->assertSame('hilang', $this->copy->fresh()->condition);
        $this->assertFalse(BookCopy::available()->whereKey($this->copy->id)->exists());
        $this->book->update(['replacement_price' => 90000]);
        $this->assertSame(75000, LoanCharges::total($item->fresh()));
        $this->get('/reports/loans?status=lost')->assertOk()->assertSee('Hilang')->assertSee('Rp75.000')
            ->assertViewHas('summary', fn ($s) => $s['total'] === 1 && $s['returned'] === 1);
        $this->get('/reports/loans/print?status=lost')->assertOk()->assertSee('Rp75.000');
        $this->get('/reports/loans/download?status=lost')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->member->user)->get('/dashboard')->assertOk()->assertViewHas('activeCount', 0)->assertSee('Hilang')->assertSee('Rp75.000');
        $this->get('/my-loans')->assertOk()->assertSee('Hilang')->assertSee('Rp75.000');
    }

    public function test_missing_replacement_price_rejects_loss_without_closing_loan(): void
    {
        $this->book->update(['replacement_price' => null]);
        $item = $this->borrow();
        $this->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'hilang'])
            ->assertSessionHasErrors('return_condition');
        $this->assertNull($item->fresh()->returned_at);
        $this->assertNull($item->fresh()->fine_amount);
        $this->assertSame('baik', $this->copy->fresh()->condition);
    }

    public function test_on_time_return_is_zero_and_old_returns_are_not_charged_retroactively(): void
    {
        $item = $this->borrow();
        $this->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'rusak'])->assertSessionHasNoErrors();
        $this->assertSame(0, $item->fresh()->fine_amount);
        $this->assertFalse(BookCopy::available()->whereKey($this->copy->id)->exists());
        $item->refresh()->update(['due_date' => '2026-09-01', 'fine_amount' => null]);
        $this->assertSame(0, LoanCharges::total($item->fresh()));
        $this->get('/reports/loans')->assertOk()->assertSee('Denda belum dicatat');
    }

    public function test_student_cannot_read_another_students_charge(): void
    {
        $item = $this->borrow();
        $this->post('/loans/items/'.$item->id.'/return', ['return_condition' => 'hilang'])->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertDontSee('Rp75.000');
        $this->get('/my-loans')->assertOk()->assertDontSee('Rp75.000');
    }

    public function test_book_price_can_be_saved_and_imported_or_exported_with_csv(): void
    {
        $this->actingAs($this->staff)->put('/books/'.$this->book->id, [
            'title' => $this->book->title, 'author' => $this->book->author, 'category_id' => $this->book->category_id, 'replacement_price' => -1,
        ])->assertSessionHasErrors('replacement_price');
        $this->put('/books/'.$this->book->id, [
            'title' => $this->book->title, 'author' => $this->book->author, 'category_id' => $this->book->category_id, 'replacement_price' => 65000,
        ])->assertSessionHasNoErrors();
        $this->assertSame(65000, $this->book->fresh()->replacement_price);
        $csv = "judul,penulis,kategori,isbn,penerbit,tahun_terbit,deskripsi,harga_buku\nBuku Impor,Penulis,Fiksi,,,,,42000\n";
        $this->post('/books/import', ['file' => UploadedFile::fake()->createWithContent('buku.csv', $csv)])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('books', ['title' => 'Buku Impor', 'replacement_price' => 42000]);
        $export = $this->get('/books/export')->assertOk()->streamedContent();
        $this->assertStringContainsString(implode(',', \App\Support\SchoolCsvImport::BOOK_HEADERS), $export);
        $this->assertStringContainsString('Buku Impor', $export);
        $this->assertSame(42000, Book::where('title', 'Buku Impor')->first()->replacement_price);
    }
}
