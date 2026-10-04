<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationAndStaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'name' => 'Siswa Baru', 'email' => 'SISWA@example.test',
            'password' => 'password-ku', 'password_confirmation' => 'password-ku',
            'gender' => 'perempuan', 'age' => 14,
            'school_class_id' => SchoolClass::create(['name' => 'VIII A', 'grade_level' => '8', 'is_active' => true])->id,
        ];
    }

    public function test_student_can_register_without_nis_and_cannot_choose_staff_role(): void
    {
        $payload = $this->payload();
        $this->get('/login')->assertOk()->assertSee(route('register'));
        $this->get('/register')->assertOk()->assertSee('VIII A');
        $this->post('/register', $payload + ['role' => 'petugas', 'is_active' => false, 'member_code' => 'ADMIN'])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();
        $user = User::where('email', 'siswa@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('anggota', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password-ku', $user->password));
        $this->assertNull($user->member->nis);
        $this->assertSame('perempuan', $user->member->gender);
        $this->assertSame(14, $user->member->age);
        $this->assertStringStartsWith('SIS-', $user->member->member_code);
        $this->get('/dashboard')->assertOk()->assertSee('Dashboard Siswa');
        $this->get('/books')->assertForbidden();
        $this->get('/register')->assertRedirect('/dashboard');
    }

    public function test_registration_rejects_inactive_class_invalid_fields_and_password_confirmation(): void
    {
        $payload = $this->payload();
        SchoolClass::find($payload['school_class_id'])->update(['is_active' => false]);
        $this->get('/register')->assertOk()->assertSee('Pendaftaran belum tersedia');
        $payload['gender'] = 'invalid';
        $payload['age'] = -1;
        $payload['password_confirmation'] = 'berbeda';
        $this->post('/register', $payload)->assertSessionHasErrors(['school_class_id', 'gender', 'age', 'password']);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('members', 0);
        $this->assertGuest();
    }

    public function test_duplicate_email_or_nis_never_creates_partial_profile(): void
    {
        $payload = $this->payload();
        $user = User::factory()->create(['email' => 'siswa@example.test']);
        Member::create(['user_id' => $user->id, 'member_code' => 'OLD', 'nis' => '0012']);
        $this->post('/register', $payload + ['nis' => '0012'])->assertSessionHasErrors(['email', 'nis']);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('members', 1);
    }

    public function test_staff_collection_stock_and_combined_book_filters(): void
    {
        $category = Category::create(['name' => 'Fiksi']);
        $other = Category::create(['name' => 'Sains']);
        $book = Book::create(['title' => 'Laut Biru', 'author' => 'Penulis A', 'category_id' => $category->id, 'cover_path' => 'covers/laut.jpg']);
        $book->copies()->create(['inventory_code' => 'B1', 'condition' => 'baik']);
        $book->copies()->create(['inventory_code' => 'B2', 'condition' => 'rusak']);
        $book->copies()->create(['inventory_code' => 'B3', 'condition' => 'hilang']);
        Book::create(['title' => 'Laut Kosong', 'author' => 'Penulis B', 'category_id' => $category->id]);
        Book::create(['title' => 'Laut Sains', 'author' => 'Penulis B', 'category_id' => $other->id]);
        Book::create(['title' => 'Buku Arsip', 'author' => 'Penulis A', 'category_id' => $category->id, 'archived_at' => now()]);
        $this->actingAs(User::factory()->create(['role' => 'petugas']));
        $this->get('/dashboard')->assertOk()->assertViewHas('jumlahJudul', 3)->assertViewHas('tersedia', 1)
            ->assertSee('Koleksi Buku Terbaru')->assertSee('covers/laut.jpg')->assertDontSee('Buku Arsip');
        $this->get('/books?q=Laut&category_id='.$category->id.'&availability=available')
            ->assertOk()->assertViewHas('books', fn ($books) => $books->count() === 1 && $books->first()->id === $book->id);
        $this->get('/books?availability=unavailable')->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 2);
        $this->get('/books?q[]=bad')->assertSessionHasErrors('q');
    }
}
