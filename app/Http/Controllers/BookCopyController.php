<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookCopyController extends Controller
{
    public function index(Book $book)
    {
        abort_if($book->archived_at !== null, 404);

        $copies = $book->copies()
            ->whereNull('archived_at')
            ->orderBy('inventory_code')
            ->paginate(10);

        return view('book_copies.index', compact('book', 'copies'));
    }

    public function store(Request $request, Book $book)
    {
        abort_if($book->archived_at !== null, 404);

        $data = $request->validate([
            'inventory_code' => [
                'required',
                'string',
                'max:50',
                'unique:book_copies,inventory_code',
            ],
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'condition' => ['required', 'in:baik,rusak,hilang'],
        ], [
            'inventory_code.required' => 'Kode inventaris wajib diisi.',
            'inventory_code.unique' => 'Kode inventaris sudah digunakan.',
            'inventory_code.max' => 'Kode inventaris maksimal 50 karakter.',
            'shelf_location.max' => 'Lokasi rak maksimal 100 karakter.',
            'condition.required' => 'Pilih kondisi buku.',
            'condition.in' => 'Kondisi buku tidak valid.',
        ]);

        $book->copies()->create($data);

        return redirect()->route('copies.index', $book)
            ->with('success', 'Eksemplar berhasil ditambahkan.');
    }

    public function edit(Book $book, BookCopy $copy)
    {
        // Pastikan eksemplar benar-benar milik buku pada alamat ini.
        abort_unless((int) $copy->book_id === (int) $book->id, 404);
        abort_if(
            $book->archived_at !== null || $copy->archived_at !== null,
            404
        );

        return view('book_copies.edit', compact('book', 'copy'));
    }

    public function update(Request $request, Book $book, BookCopy $copy)
    {
        abort_unless((int) $copy->book_id === (int) $book->id, 404);
        abort_if(
            $book->archived_at !== null || $copy->archived_at !== null,
            404
        );

        $data = $request->validate([
            'inventory_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('book_copies', 'inventory_code')->ignore($copy),
            ],
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'condition' => ['required', 'in:baik,rusak,hilang'],
        ], [
            'inventory_code.required' => 'Kode inventaris wajib diisi.',
            'inventory_code.unique' => 'Kode inventaris sudah digunakan.',
            'inventory_code.max' => 'Kode inventaris maksimal 50 karakter.',
            'shelf_location.max' => 'Lokasi rak maksimal 100 karakter.',
            'condition.required' => 'Pilih kondisi buku.',
            'condition.in' => 'Kondisi buku tidak valid.',
        ]);

        DB::transaction(function () use ($copy, $data) {
            $locked = BookCopy::whereKey($copy->id)->lockForUpdate()->firstOrFail();
            if ($locked->condition !== $data['condition'] && (
                $locked->loanItems()->whereNull('returned_at')->exists()
                || $locked->reservations()->where('status', 'ready')->where('expires_at', '>', now())->exists()
            )) {
                throw ValidationException::withMessages(['condition' => 'Eksemplar sedang dipinjam atau disiapkan untuk reservasi. Selesaikan peminjaman atau batalkan reservasi terlebih dahulu.']);
            }
            $locked->update($data);
        }, 3);

        return redirect()->route('copies.index', $book)
            ->with('success', 'Data eksemplar berhasil diubah.');
    }
}
