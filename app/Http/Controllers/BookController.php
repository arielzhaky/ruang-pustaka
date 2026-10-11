<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\LoanItem;
use App\Support\BookDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'availability' => ['nullable', 'in:all,available,unavailable'],
            'status' => ['nullable', 'in:active,archived'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Book::with('category')->withCount('copies')
            ->withCount(['copies as available_count' => fn ($q) => $q->available()])
            ->when(($filters['status'] ?? 'active') === 'archived',
                fn ($q) => $q->whereNotNull('archived_at'),
                fn ($q) => $q->whereNull('archived_at'));
        if ($search = $filters['q'] ?? null) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")->orWhere('isbn', 'like', "%{$search}%"));
        }
        if ($category = $filters['category_id'] ?? null) {
            $query->where('category_id', $category);
        }
        if (($filters['availability'] ?? 'all') === 'available') {
            $query->whereHas('copies', fn ($q) => $q->available());
        } elseif (($filters['availability'] ?? 'all') === 'unavailable') {
            $query->whereDoesntHave('copies', fn ($q) => $q->available());
        }
        $books = $query->orderByDesc('id')->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('books.index', compact('books', 'categories', 'filters'));
    }

    public function show(Request $request, Book $book)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'availability' => ['nullable', 'in:all,available,unavailable'],
            'page' => ['nullable', 'integer', 'min:1'],
            'copies_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('catalog.show', BookDetails::data($book) + ['filters' => $filters, 'staffView' => true]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('books.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable', 'string', 'max:20', 'unique:books,isbn',
            ],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => [
                'nullable', 'integer', 'min:1', 'max:'.date('Y'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'replacement_price' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
            'remove_cover' => ['nullable', 'boolean'],
        ], [
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.mimes' => 'Gunakan cover JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran cover maksimal 2 MB.',
            'cover.dimensions' => 'Dimensi cover maksimal 3000 × 3000 piksel.',
        ]);

        unset($data['cover'], $data['remove_cover']);
        $path = $request->file('cover')?->store('covers', 'public');
        if ($request->hasFile('cover') && ! $path) {
            throw ValidationException::withMessages(['cover' => 'Cover gagal disimpan. Coba lagi.']);
        }
        try {
            Book::create($data + ['cover_path' => $path]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('books.index')
            ->with('success', 'Judul buku berhasil ditambahkan.');
    }

    public function edit(Book $book)
    {
        $categories = Category::orderBy('name')->get();

        return view('books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('books', 'isbn')->ignore($book),
            ],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => [
                'nullable', 'integer', 'min:1', 'max:'.date('Y'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'replacement_price' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
            'remove_cover' => ['nullable', 'boolean'],
        ], [
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.mimes' => 'Gunakan cover JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran cover maksimal 2 MB.',
            'cover.dimensions' => 'Dimensi cover maksimal 3000 × 3000 piksel.',
        ]);

        unset($data['cover'], $data['remove_cover']);
        $oldPath = $book->cover_path;
        $newPath = $request->file('cover')?->store('covers', 'public');
        if ($request->hasFile('cover') && ! $newPath) {
            throw ValidationException::withMessages(['cover' => 'Cover gagal disimpan. Coba lagi.']);
        }
        if ($newPath || $request->boolean('remove_cover')) {
            $data['cover_path'] = $newPath ?: null;
        }
        try {
            $book->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }
        if ($oldPath && $oldPath !== $book->cover_path) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('books.index')
            ->with('success', 'Data buku berhasil diubah.');
    }

    public function updateCover(Request $request, Book $book)
    {
        $request->validate(['cover' => ['required', 'image', 'mimes:jpg,jpeg', 'extensions:jpg,jpeg', 'max:2048', 'dimensions:max_width=3000,max_height=3000']]);
        $oldPath = $book->cover_path;
        $path = $request->file('cover')->store('covers', 'public');
        if (!$path) throw ValidationException::withMessages(['cover' => 'Cover gagal disimpan.']);
        try { $book->update(['cover_path' => $path]); }
        catch (\Throwable $e) { Storage::disk('public')->delete($path); throw $e; }
        if ($oldPath && $oldPath !== $path) Storage::disk('public')->delete($oldPath);
        return back()->with('success', 'Cover JPG untuk '.$book->title.' berhasil diperbarui.');
    }

    public function archive(Book $book)
    {
        DB::transaction(function () use ($book) {
            // Ikuti urutan penguncian peminjaman: eksemplar, lalu judul buku.
            $copyIds = BookCopy::where('book_id', $book->id)->orderBy('id')->lockForUpdate()->pluck('id');
            $locked = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
            if ($locked->archived_at !== null) {
                return;
            }
            if (LoanItem::whereIn('book_copy_id', $copyIds)->whereNull('returned_at')->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['book' => 'Buku masih dipinjam. Selesaikan pengembalian sebelum mengarsipkan.']);
            }
            if (BookReservation::where('book_id', $book->id)->active()->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['book' => 'Buku masih memiliki reservasi aktif. Selesaikan atau batalkan reservasi sebelum mengarsipkan.']);
            }
            $locked->update(['archived_at' => now()]);
        }, 3);

        return redirect()->route('books.index')
            ->with('success', 'Buku berhasil diarsipkan. Riwayat peminjaman dan eksemplar tetap tersimpan.');
    }

    public function restore(Book $book)
    {
        DB::transaction(function () use ($book) {
            Book::whereKey($book->id)->lockForUpdate()->firstOrFail()->update(['archived_at' => null]);
        }, 3);

        return redirect()->route('books.index', ['status' => 'archived'])
            ->with('success', 'Buku berhasil dipulihkan ke katalog aktif.');
    }

    public function destroy(Book $book)
    {
        if ($book->copies()->exists()) {
            return redirect()->route('books.index')
                ->with(
                    'error',
                    'Buku memiliki eksemplar dan tidak bisa dihapus permanen. Gunakan tombol Arsipkan agar riwayat tetap tersimpan.'
                );
        }

        $oldPath = $book->cover_path;
        $book->delete();
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('books.index')
            ->with('success', 'Buku berhasil dihapus.');
    }
}
