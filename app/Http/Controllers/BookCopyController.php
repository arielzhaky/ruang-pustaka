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
            ->orderByRaw('archived_at IS NOT NULL')
            ->orderBy('inventory_code')
            ->paginate(10);
        $availableCount = $book->copies()->available()->count();

        return view('book_copies.index', compact('book', 'copies', 'availableCount'));
    }

    public function updateStock(Request $request, Book $book)
    {
        $data = $request->validate([
            'operation' => ['required', 'in:add,set'],
            'quantity' => ['required', 'integer', $request->input('operation') === 'add' ? 'min:1' : 'min:0', 'max:1000'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
        ]);
        DB::transaction(function () use ($book, $data) {
            // Gunakan urutan kunci yang sama dengan peminjaman dan pengarsipan.
            $book->copies()->orderBy('id')->lockForUpdate()->get();
            $lockedBook = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedBook->archived_at !== null, 404);
            $available = $lockedBook->copies()->available()->orderByDesc('id')->lockForUpdate()->get();
            $difference = $data['operation'] === 'add' ? (int) $data['quantity'] : (int) $data['quantity'] - $available->count();
            if ($difference < 0) {
                BookCopy::whereIn('id', $available->take(-$difference)->pluck('id'))->update(['archived_at' => now()]);
            } else {
                if ($data['operation'] === 'set' && $difference > 0) {
                    $inactive = $lockedBook->copies()->where(fn($q) => $q->whereNotNull('archived_at')->orWhere('import_unavailable', true))->where('condition', 'baik')
                        ->whereDoesntHave('loanItems', fn ($q) => $q->whereNull('returned_at'))
                        ->whereDoesntHave('reservations', fn ($q) => $q->active())
                        ->orderBy('id')->limit($difference)->lockForUpdate()->get();
                    BookCopy::whereIn('id', $inactive->pluck('id'))->update(['archived_at' => null, 'import_unavailable' => false]);
                    $difference -= $inactive->count();
                }
                for ($i = 0; $i < $difference; $i++) {
                    $lockedBook->copies()->create([
                        'inventory_code' => 'RP-'.str()->uuid(),
                        'shelf_location' => $data['shelf_location'] ?? null,
                        'condition' => 'baik',
                    ]);
                }
            }
        }, 3);

        return redirect()->route('copies.index', $book)
            ->with('success', 'Stok berhasil diperbarui. Riwayat peminjaman tetap tersimpan.');
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
            'import_unavailable' => ['sometimes', 'boolean'],
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
            $book = Book::whereKey($locked->book_id)->lockForUpdate()->firstOrFail();
            abort_if($locked->archived_at !== null || $book->archived_at !== null, 404);
            if (($locked->condition !== $data['condition'] || (isset($data['import_unavailable']) && $locked->import_unavailable != $data['import_unavailable'])) && (
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
