<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StudentFavoriteUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_favorite_buttons_reflect_only_the_current_students_choices(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create(['name' => 'Nama Favorit Rahasia']);
        $ownBook = $this->book('Favorit pribadi');
        $otherBook = $this->book('Buku untuk dibaca');
        $student->favoriteBooks()->attach($ownBook);
        $other->favoriteBooks()->attach($otherBook);

        $response = $this->actingAs($student)->get(route('catalog.index', ['user_id' => $other->id]))
            ->assertOk()->assertViewHas('books', fn ($books) => $books->keyBy('id')[$ownBook->id]->is_favorite === true
                && $books->keyBy('id')[$otherBook->id]->is_favorite === false)
            ->assertDontSee($other->name)->assertDontSee($other->email);

        $this->assertFavoriteForm($response, $ownBook, true, 'catalog');
        $this->assertFavoriteForm($response, $otherBook, false, 'catalog');
    }

    public function test_detail_button_updates_after_saving_and_removing_a_favorite(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $book = $this->book('Buku detail favorit');
        $other->favoriteBooks()->attach($book);
        $this->actingAs($student);

        $response = $this->get(route('catalog.show', ['book' => $book->id, 'user_id' => $other->id]))->assertOk();
        $this->assertFavoriteForm($response, $book, false, 'detail');
        $this->post(route('favorites.store', $book), ['return_to' => 'detail'])
            ->assertRedirect(route('catalog.show', $book));
        $this->assertFavoriteForm($this->get(route('catalog.show', $book))->assertOk(), $book, true, 'detail');
        $this->delete(route('favorites.destroy', $book), ['return_to' => 'detail'])
            ->assertRedirect(route('catalog.show', $book));
        $this->assertFavoriteForm($this->get(route('catalog.show', $book))->assertOk(), $book, false, 'detail');
        $this->assertSame(1, $other->favoriteBooks()->count());
    }

    public function test_detail_favorite_form_preserves_catalog_and_copy_page_filters(): void
    {
        $book = $this->book('Buku populer');
        $filters = ['q' => 'Buku', 'category_id' => $book->category_id, 'page' => 2, 'copies_page' => 3];
        $response = $this->actingAs(User::factory()->create())
            ->get(route('catalog.show', ['book' => $book->id] + $filters))->assertOk();
        $form = $this->favoriteForm($response, $book);
        $inputs = [];
        foreach ($form->getElementsByTagName('input') as $input) {
            $inputs[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        foreach ($filters as $name => $value) {
            $this->assertSame((string) $value, $inputs[$name]);
        }
    }

    public function test_staff_book_detail_has_no_student_favorite_controls(): void
    {
        $book = $this->book('Buku petugas');
        $this->actingAs(User::factory()->create(['role' => 'petugas']))->get(route('books.show', $book))
            ->assertOk()->assertDontSee('class="favorite-form"', false);
    }

    public function test_book_title_is_escaped_in_favorite_button_labels(): void
    {
        $book = $this->book('<img src=x onerror=alert(1)>');
        $response = $this->actingAs(User::factory()->create())->get(route('catalog.show', $book))
            ->assertOk()->assertDontSee($book->title, false);
        $form = $this->favoriteForm($response, $book);
        $button = $form->getElementsByTagName('button')->item(0);
        $this->assertSame('Simpan '.$book->title.' ke favorit', $button->getAttribute('aria-label'));
        $this->assertSame(0, $button->getElementsByTagName('img')->length);
    }

    private function assertFavoriteForm(TestResponse $response, Book $book, bool $saved, string $returnTo): void
    {
        $form = $this->favoriteForm($response, $book);
        $this->assertSame('POST', $form->getAttribute('method'));
        $button = $form->getElementsByTagName('button')->item(0);
        $this->assertSame($saved ? 'true' : 'false', $button->getAttribute('aria-pressed'));
        $this->assertStringContainsString($saved ? 'Tersimpan' : 'Favorit', $button->textContent);
        $inputs = [];
        foreach ($form->getElementsByTagName('input') as $input) {
            $inputs[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $this->assertSame($returnTo, $inputs['return_to']);
        $this->assertSame($saved ? 'DELETE' : null, $inputs['_method'] ?? null);
        $this->assertArrayHasKey('_token', $inputs);
    }

    private function favoriteForm(TestResponse $response, Book $book): DOMElement
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $forms = (new DOMXPath($dom))->query('//form[@action="'.route('favorites.store', $book).'"]');
        $this->assertSame(1, $forms->length);

        return $forms->item(0);
    }

    private function book(string $title): Book
    {
        return Book::create([
            'category_id' => Category::firstOrCreate(['name' => 'Bacaan siswa'])->id,
            'title' => $title,
            'author' => 'Penulis contoh',
        ]);
    }
}
