<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserDataFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private const MEMBERS = ['nama', 'email', 'password', 'kode_anggota', 'jenis_kelamin', 'usia', 'kelas', 'nis', 'telepon', 'status'];

    private const BOOKS = ['judul', 'penulis', 'kategori', 'isbn', 'penerbit', 'tahun_terbit', 'deskripsi'];

    private User $staff;

    private SchoolClass $class;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->staff = User::factory()->create(['role' => 'petugas']);
        $this->class = SchoolClass::create(['name' => 'VII A', 'grade_level' => '7', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Novel']);
    }

    public static function roles(): array
    {
        return [['anggota'], ['petugas']];
    }

    #[DataProvider('roles')]
    public function test_login_checks_selected_role_against_the_account(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->get('/login')->assertOk()->assertSee('Petugas / Admin')->assertSee('Siswa');
        $this->post('/login', ['email' => $user->email, 'password' => 'password',
            'role' => $role === 'petugas' ? 'anggota' : 'petugas'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => strtoupper($user->email), 'password' => 'password', 'role' => $role])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_role_and_inactive_account_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('role');
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'role' => 'anggota'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_student_profile_can_be_created_and_edited_without_nis(): void
    {
        $data = $this->memberData();
        $this->actingAs($this->staff)->post('/members', $data)->assertSessionHasNoErrors();
        $member = Member::firstOrFail();
        $this->assertNull($member->nis);
        $this->assertSame('laki-laki', $member->gender);
        $this->assertSame(13, $member->age);
        $this->assertSame('anggota', $member->user->role);
        $this->assertTrue(Hash::check('password123', $member->user->password));
        $this->get('/members')->assertOk()->assertSee('13 tahun');
        $this->get('/members/'.$member->id.'/edit')->assertOk()->assertSee('NIS (opsional)');
        $data['gender'] = 'perempuan';
        $data['age'] = 14;
        $data['is_active'] = 1;
        $data['password'] = '';
        $this->put('/members/'.$member->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('perempuan', $member->fresh()->gender);
        $this->assertSame(14, $member->fresh()->age);
        $this->assertTrue(Hash::check('password123', $member->user->fresh()->password));
    }

    public function test_invalid_demographics_and_duplicate_nis_are_rejected(): void
    {
        $this->actingAs($this->staff)->post('/members', $this->memberData(['age' => 121, 'gender' => 'invalid']))
            ->assertSessionHasErrors(['age', 'gender']);
        $this->assertDatabaseCount('members', 0);
        $this->post('/members', $this->memberData(['nis' => '00123']))->assertSessionHasNoErrors();
        $this->post('/members', $this->memberData(['email' => 'other@example.test', 'member_code' => 'M-002', 'nis' => '00123']))
            ->assertSessionHasErrors('nis');
        $this->assertDatabaseCount('members', 1);
    }

    public function test_csv_imports_students_with_bom_semicolon_and_optional_nis(): void
    {
        $file = $this->csv(self::MEMBERS, [
            ['Siswa CSV', 'SISWA@example.test', 'password123', 'M-CSV', 'perempuan', 13, 'VII A', '', '081234', ''],
            ['Siswa Kedua', 'kedua@example.test', 'password456', 'M-CSV2', 'laki-laki', 14, 'VII A', '00123', '', 'nonaktif'],
        ], ';');
        $this->actingAs($this->staff)->post('/members/import', ['file' => $file])
            ->assertSessionHasNoErrors()->assertRedirect(route('members.index'));
        $this->assertDatabaseCount('members', 2);
        $this->assertDatabaseHas('members', ['member_code' => 'M-CSV', 'nis' => null, 'gender' => 'perempuan', 'age' => 13]);
        $this->assertDatabaseHas('members', ['member_code' => 'M-CSV2', 'nis' => '00123']);
        $this->assertFalse(User::where('email', 'kedua@example.test')->firstOrFail()->is_active);
        $user = User::where('email', 'siswa@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame('anggota', $user->role);
    }

    public function test_bad_csv_row_rolls_back_all_created_users_and_members(): void
    {
        $this->actingAs($this->staff)->post('/members/import', ['file' => $this->csv(self::MEMBERS, [
            ['Valid', 'valid@example.test', 'password123', 'M-1', 'perempuan', 13, 'VII A', '', '', 'aktif'],
            ['Invalid', 'bad@example.test', 'password123', 'M-2', 'perempuan', 13, 'Tidak Ada', '', '', 'aktif'],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('members', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertStringContainsString('Baris 3', session('errors')->first('file'));
    }

    public function test_duplicate_in_csv_is_rejected_and_existing_staff_is_never_modified(): void
    {
        $this->actingAs($this->staff)->post('/members/import', ['file' => $this->csv(self::MEMBERS, [
            ['A', 'a@example.test', 'password123', 'M-1', 'perempuan', 13, 'VII A', '', '', 'aktif'],
            ['B', 'a@example.test', 'password123', 'M-2', 'perempuan', 13, 'VII A', '', '', 'aktif'],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('members', 0);
        $this->post('/members/import', ['file' => $this->csv(self::MEMBERS, [
            ['Override', $this->staff->email, 'password123', 'M-1', 'perempuan', 13, 'VII A', '', '', 'aktif'],
        ])])->assertSessionHasErrors('file');
        $this->assertSame('petugas', $this->staff->fresh()->role);
        $this->assertNotSame('Override', $this->staff->fresh()->name);
    }

    public function test_csv_imports_books_and_rejects_duplicates_without_partial_inserts(): void
    {
        $this->actingAs($this->staff)->post('/books/import', ['file' => $this->csv(self::BOOKS, [
            ['Buku Baru', 'Penulis', 'Novel', '0012345678901', 'Penerbit', 2020, "Deskripsi, baris satu\nbaris dua"],
            ['Buku Lain', 'Penulis', 'Novel', '', '', '', ''],
        ])])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('books', 2);
        $this->assertDatabaseHas('books', ['title' => 'Buku Baru', 'isbn' => '0012345678901']);
        $this->assertDatabaseCount('book_copies', 0);
        $this->post('/books/import', ['file' => $this->csv(self::BOOKS, [
            ['Sempat dibuat', 'Penulis', 'Novel', '', '', '', ''],
            ['Buku Baru', 'Penulis', 'Novel', '', '', '', ''],
        ])])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('books', 2);
        $this->assertDatabaseMissing('books', ['title' => 'Sempat dibuat']);
    }

    public function test_exports_all_rows_without_passwords_and_neutralizes_formulas(): void
    {
        $this->actingAs($this->staff);
        for ($i = 0; $i < 12; $i++) {
            $this->post('/members', $this->memberData(['email' => "s{$i}@example.test", 'member_code' => "M-{$i}"]))
                ->assertSessionHasNoErrors();
        }
        $members = $this->get('/members/export')->assertOk()->assertDownload('siswa-'.today()->format('Y-m-d').'.csv')->streamedContent();
        $this->assertStringContainsString('s11@example.test', $members);
        $this->assertStringNotContainsString('password123', $members);
        $this->assertStringNotContainsString('$2y$', $members);
        Book::create($this->bookData(['title' => '=2+2']));
        Book::create($this->bookData(['title' => 'Rahasia Arsip', 'archived_at' => now()]));
        $books = $this->get('/books/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("\t=2+2", $books);
        $this->assertStringNotContainsString('Rahasia Arsip', $books);
    }

    public function test_templates_have_expected_columns_and_wrong_headers_are_rejected(): void
    {
        $this->actingAs($this->staff);
        $this->assertStringContainsString(implode(',', self::MEMBERS), $this->get('/members/template')->assertOk()->streamedContent());
        $this->assertStringContainsString(implode(',', self::BOOKS), $this->get('/books/template')->assertOk()->streamedContent());
        $this->post('/members/import', ['file' => $this->csv(['wrong'], [['data']])])->assertSessionHasErrors('file');
        $this->post('/books/import', ['file' => $this->csv(self::BOOKS, [])])->assertSessionHasErrors('file');
    }

    public function test_cover_upload_replace_remove_and_catalog_display(): void
    {
        $this->actingAs($this->staff)->post('/books', $this->bookData(['cover' => UploadedFile::fake()->image('cover.png', 100, 160)]))
            ->assertSessionHasNoErrors();
        $book = Book::firstOrFail();
        $path = $book->cover_path;
        Storage::disk('public')->assertExists($path);
        $this->get('/books/'.$book->id.'/edit')->assertOk()->assertSee('Hapus cover saat ini');
        $this->actingAs(User::factory()->create())->get('/catalog')->assertOk()->assertSee('storage/'.$path);
        $this->get('/catalog/'.$book->id)->assertOk()->assertSee('storage/'.$path);
        $this->actingAs($this->staff)->put('/books/'.$book->id, $this->bookData(['cover' => UploadedFile::fake()->image('new.jpg')]))
            ->assertSessionHasNoErrors();
        $newPath = $book->fresh()->cover_path;
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($newPath);
        $this->put('/books/'.$book->id, $this->bookData(['remove_cover' => 1]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($newPath);
        $this->assertNull($book->fresh()->cover_path);
    }

    public function test_invalid_cover_is_rejected_and_existing_cover_survives_validation_failure(): void
    {
        $this->actingAs($this->staff)->post('/books', $this->bookData(['cover' => UploadedFile::fake()->createWithContent('bad.svg', '<svg></svg>')]))
            ->assertSessionHasErrors('cover');
        $this->assertDatabaseCount('books', 0);
        $this->post('/books', $this->bookData(['cover' => UploadedFile::fake()->image('cover.png')]))->assertRedirect();
        $book = Book::firstOrFail();
        $this->put('/books/'.$book->id, $this->bookData(['title' => '', 'cover' => UploadedFile::fake()->image('replacement.png')]))
            ->assertSessionHasErrors('title');
        Storage::disk('public')->assertExists($book->cover_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_import_export_routes_and_pdf_are_staff_only(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['members', 'books'] as $kind) {
            $this->get('/'.$kind.'/export')->assertForbidden();
            $this->get('/'.$kind.'/template')->assertForbidden();
            $this->post('/'.$kind.'/import')->assertForbidden();
        }
        $this->get('/reports/loans/download')->assertForbidden();
    }

    public function test_report_download_returns_a_real_pdf_attachment_and_validates_filters(): void
    {
        $this->actingAs($this->staff)->get('/reports/loans/download?start_date=2026-09-30&end_date=2026-09-20')
            ->assertSessionHasErrors('end_date');
        $response = $this->get('/reports/loans/download')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function memberData(array $extra = []): array
    {
        return array_merge(['name' => 'Siswa Baru', 'email' => 'siswa@example.test', 'password' => 'password123',
            'password_confirmation' => 'password123', 'member_code' => 'M-001', 'school_class_id' => $this->class->id,
            'gender' => 'laki-laki', 'age' => 13], $extra);
    }

    private function bookData(array $extra = []): array
    {
        return array_merge(['category_id' => $this->category->id, 'title' => 'Buku Baru', 'author' => 'Penulis'], $extra);
    }

    private function csv(array $headers, array $rows, string $delimiter = ','): UploadedFile
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ([$headers, ...$rows] as $row) {
            fputcsv($stream, $row, $delimiter, '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return UploadedFile::fake()->createWithContent('data.csv', $content);
    }
}
