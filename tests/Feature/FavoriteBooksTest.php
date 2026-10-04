<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FavoriteBooksTest extends TestCase
{
    use RefreshDatabase;

    public static function favoriteActions(): array
    {
        return [['get'], ['post'], ['delete']];
    }

    #[DataProvider('favoriteActions')]
    public function test_favorites_require_an_active_student(string $method): void
    {
        $book = $this->book();
        $path = $method === 'get' ? '/favorites' : '/favorites/'.$book->id;
        $this->{$method}($path)->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->{$method}($path)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_active' => false]))
            ->{$method}($path)->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseCount('book_user', 0);
    }

    public function test_saving_twice_only_adds_one_favorite_to_the_authenticated_student(): void
    {
        $book = $this->book();
        $student = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($student);

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('favorites.store', $book), [
                'user_id' => $other->id, 'return_to' => 'dashboard',
            ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('book_user', 1);
        $this->assertDatabaseHas('book_user', ['user_id' => $student->id, 'book_id' => $book->id]);
        $this->assertSame(0, $other->favoriteBooks()->count());
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('book_copies', 0);
    }

    public function test_students_only_see_and_remove_their_own_favorites(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $ownBook = $this->book('Favorit milik saya');
        $otherBook = $this->book('Favorit siswa lain');
        $student->favoriteBooks()->attach($ownBook);
        $other->favoriteBooks()->attach([$ownBook->id, $otherBook->id]);

        $this->actingAs($student)->get(route('favorites.index'))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->pluck('id')->all() === [$ownBook->id])
            ->assertSee($ownBook->title)->assertDontSee($otherBook->title);

        $this->delete(route('favorites.destroy', $otherBook), ['user_id' => $other->id])
            ->assertRedirect(route('favorites.index'));
        for ($i = 0; $i < 2; $i++) {
            $this->delete(route('favorites.destroy', $ownBook))->assertRedirect(route('favorites.index'));
        }
        $this->assertSame(0, $student->favoriteBooks()->count());
        $this->assertSame(2, $other->favoriteBooks()->count());
        $this->get(route('favorites.index'))->assertOk()->assertViewHas('books', fn ($books) => $books->isEmpty());
    }

    public function test_archived_favorites_are_hidden_and_cannot_be_saved_but_can_be_removed(): void
    {
        $student = User::factory()->create();
        $book = $this->book();
        $student->favoriteBooks()->attach($book);
        $book->update(['archived_at' => now()]);

        $this->actingAs($student)->get(route('favorites.index'))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->total() === 0);
        $this->post(route('favorites.store', $book))->assertNotFound();
        $this->delete(route('favorites.destroy', $book), ['return_to' => 'detail'])
            ->assertRedirect(route('favorites.index'));
        $this->assertDatabaseCount('book_user', 0);
        $this->post('/favorites/999999')->assertNotFound();
    }

    public function test_favorites_include_cover_category_and_real_available_stock(): void
    {
        $student = User::factory()->create();
        $book = $this->book('Buku dengan cover');
        $book->update(['cover_path' => 'covers/favorit.jpg']);
        $student->favoriteBooks()->attach($book);
        foreach (['baik', 'rusak', 'hilang'] as $condition) {
            $book->copies()->create(['inventory_code' => 'COPY-'.$condition, 'condition' => $condition]);
        }
        $book->copies()->create(['inventory_code' => 'COPY-ARCHIVE', 'condition' => 'baik', 'archived_at' => now()]);

        $this->actingAs($student)->get(route('favorites.index'))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->first()->is_favorite === true
                && $books->first()->available_count === 1
                && $books->first()->category->name === $book->category->name)
            ->assertSee('covers/favorit.jpg');
    }

    public function test_favorites_paginate_in_a_stable_order(): void
    {
        $student = User::factory()->create();
        $ids = [];
        for ($i = 0; $i < 13; $i++) {
            $book = $this->book('Buku '.sprintf('%02d', $i));
            $ids[] = $book->id;
            $student->favoriteBooks()->attach($book, ['created_at' => now()->startOfDay()]);
        }
        $this->actingAs($student)->get(route('favorites.index'))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 12 && $books->total() === 13
                && $books->pluck('id')->all() === array_slice(array_reverse($ids), 0, 12));
        $this->get(route('favorites.index', ['page' => 2]))->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 1
                && $books->first()->id === $ids[0]
                && str_contains($books->url(1), 'page=1'));
    }

    public function test_saving_and_removing_return_to_the_catalog_with_validated_filters(): void
    {
        $book = $this->book();
        $this->actingAs(User::factory()->create());
        $filters = ['q' => 'Buku', 'category_id' => $book->category_id, 'page' => 2];

        $this->post(route('favorites.store', $book), ['return_to' => 'catalog'] + $filters)
            ->assertRedirect(route('catalog.index', $filters));
        $this->delete(route('favorites.destroy', $book), ['return_to' => 'detail', 'copies_page' => 3] + $filters)
            ->assertRedirect(route('catalog.show', ['book' => $book->id] + $filters + ['copies_page' => 3]));
        $this->post(route('favorites.store', $book), ['return_to' => 'favorites', 'page' => 2, 'q' => 'Buku'])
            ->assertRedirect(route('favorites.index', ['page' => 2]));
    }

    public static function invalidReturnFields(): array
    {
        return [
            ['return_to', 'https://example.com'],
            ['q', ['invalid']],
            ['q', str_repeat('a', 101)],
            ['category_id', 999999],
            ['page', 0],
            ['copies_page', ['invalid']],
        ];
    }

    #[DataProvider('invalidReturnFields')]
    public function test_invalid_return_parameters_are_rejected_before_saving(string $field, mixed $value): void
    {
        $book = $this->book();
        $this->actingAs(User::factory()->create())->from(route('catalog.index'))
            ->post(route('favorites.store', $book), [$field => $value])
            ->assertRedirect(route('catalog.index'))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('book_user', 0);
    }

    public function test_removing_a_user_or_book_cleans_up_favorites(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $book = $this->book();
        $student->favoriteBooks()->attach($book);
        $other->favoriteBooks()->attach($book);
        $student->delete();
        $this->assertDatabaseCount('book_user', 1);
        $book->delete();
        $this->assertDatabaseCount('book_user', 0);
    }

    private function book(string $title = 'Buku favorit'): Book
    {
        return Book::create([
            'category_id' => Category::firstOrCreate(['name' => 'Bacaan siswa'])->id,
            'title' => $title,
            'author' => 'Penulis contoh',
        ]);
    }
}
