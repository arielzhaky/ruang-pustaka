<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'q.string' => 'Kata pencarian harus berupa teks.',
            'q.max' => 'Kata pencarian maksimal 100 karakter.',
            'page.integer' => 'Nomor halaman harus berupa angka bulat.',
            'page.min' => 'Nomor halaman minimal 1.',
        ]);
        $search = trim($validated['q'] ?? '');

        $query = Book::query()->whereNull('archived_at')
            ->with('category')
            ->withCount(['copies as available_count' => fn ($copies) => $copies->available()]);

        if ($search !== '') {
            $query->where(function ($books) use ($search) {
                $books->where('title', 'like', '%'.$search.'%')
                    ->orWhere('author', 'like', '%'.$search.'%')
                    ->orWhere('isbn', 'like', '%'.$search.'%');
            })->orderBy('title')->orderBy('id');
        } else {
            $query->latest('id');
        }

        return view('home', [
            'books' => $query->paginate(8)->withQueryString(),
            'popularBooks' => Book::query()->whereNull('archived_at')
                ->whereHas('loanItems')->with('category')
                ->withCount('loanItems')
                ->withCount(['copies as available_count' => fn ($copies) => $copies->available()])
                ->orderByDesc('loan_items_count')->orderBy('title')->orderBy('id')
                ->limit(4)->get(),
            'search' => $search,
            'bookCount' => Book::whereNull('archived_at')->count(),
            'availableCount' => BookCopy::available()
                ->whereHas('book', fn ($books) => $books->whereNull('archived_at'))->count(),
        ]);
    }
}
