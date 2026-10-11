<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use App\Models\User;
use App\Support\LibraryBarcode;
use App\Support\SchoolCsvImport;
use App\Support\StudentLoginQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private function csv(array $headers, array $rows): UploadedFile
    {
        $f = fopen('php://temp', 'r+');
        fputcsv($f, $headers);
        foreach ($rows as $r) {
            fputcsv($f, $r);
        }rewind($f);

        return UploadedFile::fake()->createWithContent('school.csv', stream_get_contents($f));
    }

    private function staff(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'petugas']));
    }

    public function test_school_books_reimport_without_duplicates_and_keep_stock_snapshot(): void
    {
        $this->staff();
        $rows = [['F001', 'Buku sekolah', 'Penulis', 'Penerbit', 2024, 'Fiksi', '', 3, 1, 'A']];
        for ($i = 0; $i < 2; $i++) {
            $this->post('/books/import', ['file' => $this->csv(SchoolCsvImport::BOOK_HEADERS, $rows)])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseCount('book_copies', 3);
        $book = Book::first();
        $this->assertSame(1, $book->copies()->available()->count());
        $this->assertSame('Fiksi', $book->category->name);
        $out = $this->get('/books/export')->streamedContent();
        $this->assertStringContainsString(implode(',', SchoolCsvImport::BOOK_HEADERS), $out);
        $this->post('/books/import', ['file' => UploadedFile::fake()->createWithContent('roundtrip.csv', $out)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseCount('book_copies', 3);
        $copy = $book->copies()->where('import_unavailable', true)->first();
        $this->getJson('/scan/lookup?kind=copy&code='.LibraryBarcode::copy($copy))->assertOk()->assertJsonPath('can_borrow', false);
        $this->patch(route('copies.stock', $book), ['operation' => 'set', 'quantity' => 3])->assertSessionHasNoErrors();
        $this->assertSame(3, $book->copies()->available()->count());
        $this->assertDatabaseCount('book_copies', 3);
    }

    public function test_invalid_rows_roll_back_and_code_conflicts_fail(): void
    {
        $this->staff();
        $rows = [['F001', 'Satu', 'Penulis', '', 2024, 'Fiksi', '', 1, 1, ''], ['F002', 'Dua', 'Penulis', '', 2024, 'Fiksi', '', 1, 3, '']];
        $this->post('/books/import', ['file' => $this->csv(SchoolCsvImport::BOOK_HEADERS, $rows)])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('categories', 0);
        $rows[1] = ['F001', 'Lain', 'Penulis', '', 2024, 'Fiksi', '', 1, 1, ''];
        $this->post('/books/import', ['file' => $this->csv(SchoolCsvImport::BOOK_HEADERS, $rows)])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('books', 0);
    }

    public function test_alias_does_not_double_stock_and_different_authors_stay_separate(): void
    {
        $this->staff();
        $rows = [['A', 'Cerita', 'Satu', '', 2024, 'Fiksi', '', 3, 2, ''], ['B', 'CERITA', 'Satu', '', 2024, 'Fiksi', '', 3, 3, ''], ['C', 'Cerita', 'Dua', '', 2024, 'Fiksi', '', 1, 1, '']];
        $this->post('/books/import', ['file' => $this->csv(SchoolCsvImport::BOOK_HEADERS, $rows)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('books', 2);
        $this->assertDatabaseCount('book_copies', 4);
        $this->assertSame(['B'], Book::where('catalog_code', 'A')->first()->import_aliases);
    }

    public function test_senayan_roundtrip_does_not_expose_or_reset_credentials(): void
    {
        $this->staff();
        $data = array_fill_keys(SchoolCsvImport::MEMBER_HEADERS, '');
        $data = array_merge($data, ['member_id' => '00017', 'member_name' => 'Siswa Sekolah', 'gender' => '0', 'member_type_name' => 'Angkatan 2024/2025', 'pin' => 'secret-pin', 'mpasswd' => 'legacy-secret']);
        $this->post('/members/import', ['file' => $this->csv(SchoolCsvImport::MEMBER_HEADERS, [array_values($data)])])->assertSessionHasNoErrors();
        $member = StudentLoginQr::issue(Member::first());
        $hash = $member->user->password;
        $qr = $member->login_qr_hash;
        $this->assertSame('perempuan', $member->gender);
        $this->assertNull($member->age);
        $this->assertNull($member->school_class_id);
        $this->assertArrayNotHasKey('pin', $member->senayan_data);
        $out = $this->get('/members/export')->streamedContent();
        $this->assertStringNotContainsString('secret', $out);
        $this->assertStringNotContainsString('@anggota.invalid', $out);
        $this->post('/members/import', ['file' => UploadedFile::fake()->createWithContent('roundtrip.csv', $out)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('members', 1);
        $this->assertSame($hash, $member->fresh()->user->password);
        $this->assertSame($qr, $member->fresh()->login_qr_hash);
    }

    public function test_single_jpg_upload_updates_only_cover_and_is_staff_only(): void
    {
        Storage::fake('public');
        $book = Book::create(['title' => 'Sekolah', 'author' => 'Penulis', 'category_id' => Category::create(['name' => 'Fiksi'])->id]);
        $this->staff();
        $this->post(route('books.cover', $book), ['cover' => UploadedFile::fake()->image('cover.jpg', 200, 300)])->assertSessionHasNoErrors();
        $this->assertNotNull($book->fresh()->cover_path);
        $this->assertSame('Sekolah', $book->fresh()->title);
        $this->post(route('books.cover', $book), ['cover' => UploadedFile::fake()->createWithContent('fake.jpg', 'not an image')])->assertSessionHasErrors('cover');
        $this->actingAs(User::factory()->create(['role' => 'anggota']));
        $this->post(route('books.cover', $book))->assertForbidden();
    }

    public function test_student_cannot_import_school_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'anggota']));
        $this->post('/books/import')->assertForbidden();
        $this->post('/members/import')->assertForbidden();
    }
}
