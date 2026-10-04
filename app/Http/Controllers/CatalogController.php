<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Support\BookDetails;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'anggota', 403);
        $filters = $this->filters($request);
        $search = trim($filters['q'] ?? '');
        $categoryId = $filters['category_id'] ?? null;
        $categories = Category::orderBy('name')->get();

        $books = Book::with('category')->whereNull('archived_at')
            ->withExists(['favoritedBy as is_favorite' => fn ($query) => $query->where('users.id', $request->user()->id)])
            ->withCount(['copies as available_count' => fn ($query) => $query->available()])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('author', 'like', '%'.$search.'%');
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('title')->orderBy('id')->paginate(10)->withQueryString();

        return view('catalog.index', compact('books', 'categories', 'search', 'categoryId'));
    }

    public function show(Request $request, Book $book)
    {
        abort_unless($request->user()->role === 'anggota', 403);
        $filters = $this->filters($request);
        $book->loadExists(['favoritedBy as is_favorite' => fn ($query) => $query->where('users.id', $request->user()->id)]);

        return view('catalog.show', BookDetails::data($book) + ['filters' => $filters, 'staffView' => false]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'copies_page' => ['nullable', 'integer', 'min:1'],
        ], [
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
}
