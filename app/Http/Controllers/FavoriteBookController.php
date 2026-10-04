<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FavoriteBookController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'anggota', 403);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']], [
            'page.integer' => 'Nomor halaman tidak valid.',
            'page.min' => 'Nomor halaman minimal 1.',
        ]);

        $books = $request->user()->favoriteBooks()
            ->whereNull('books.archived_at')
            ->with('category')
            ->withCount(['copies as available_count' => fn ($query) => $query->available()])
            ->orderByPivot('created_at', 'desc')->orderByDesc('books.id')
            ->paginate(12)->withQueryString()
            ->through(function (Book $book) {
                $book->setAttribute('is_favorite', true);

                return $book;
            });

        return view('favorites.index', compact('books'));
    }

    public function store(Request $request, Book $book): RedirectResponse
    {
        abort_unless($request->user()->role === 'anggota', 403);
        abort_if($book->archived_at !== null, 404);
        $return = $this->returnFields($request);

        // The unique pair also protects against two simultaneous save requests.
        DB::table('book_user')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->redirectToPage($return, $book)
            ->with('success', 'Buku ditambahkan ke favorit.');
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        abort_unless($request->user()->role === 'anggota', 403);
        $return = $this->returnFields($request);
        $request->user()->favoriteBooks()->detach($book->id);

        return $this->redirectToPage($return, $book)
            ->with('success', 'Buku dihapus dari favorit.');
    }

    private function returnFields(Request $request): array
    {
        return $request->validate([
            'return_to' => ['nullable', Rule::in(['dashboard', 'favorites', 'catalog', 'detail'])],
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'copies_page' => ['nullable', 'integer', 'min:1'],
        ], [
            'return_to.in' => 'Halaman tujuan tidak valid.',
            'q.string' => 'Kata pencarian harus berupa teks.',
            'q.max' => 'Kata pencarian maksimal 100 karakter.',
            'category_id.integer' => 'Kategori tidak valid.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'page.integer' => 'Nomor halaman tidak valid.',
            'page.min' => 'Nomor halaman minimal 1.',
            'copies_page.integer' => 'Nomor halaman eksemplar tidak valid.',
            'copies_page.min' => 'Nomor halaman eksemplar minimal 1.',
        ]);
    }

    private function redirectToPage(array $return, Book $book): RedirectResponse
    {
        $destination = $return['return_to'] ?? 'favorites';
        if ($destination === 'detail' && $book->archived_at !== null) {
            $destination = 'favorites';
        }

        $allowedKeys = match ($destination) {
            'catalog' => ['q', 'category_id', 'page'],
            'detail' => ['q', 'category_id', 'page', 'copies_page'],
            'favorites' => ['page'],
            default => [],
        };
        $parameters = array_intersect_key($return, array_flip($allowedKeys));

        return match ($destination) {
            'dashboard' => redirect()->route('dashboard'),
            'catalog' => redirect()->route('catalog.index', $parameters),
            'detail' => redirect()->route('catalog.show', ['book' => $book->id] + $parameters),
            default => redirect()->route('favorites.index', $parameters),
        };
    }
}
