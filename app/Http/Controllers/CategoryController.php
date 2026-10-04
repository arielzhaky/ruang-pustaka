<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('books')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name'),
            ],
        ], $this->messages());

        Category::create($data);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ], $this->messages());

        $category->update($data);

        return redirect()->route('categories.index')
            ->with('success', 'Nama kategori berhasil diubah.');
    }

    public function destroy(Category $category)
    {
        // Termasuk buku yang sudah diarsipkan.
        if ($category->books()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori masih digunakan oleh buku dan tidak dapat dihapus.');
        }

        try {
            $category->delete();
        } catch (QueryException $exception) {
            // Menangani jika buku baru memakai kategori
            // sesaat setelah pemeriksaan di atas.
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1451) {
                throw $exception;
            }

            return redirect()->route('categories.index')
                ->with('error', 'Kategori masih digunakan oleh buku dan tidak dapat dihapus.');
        }

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori tersebut sudah ada.',
        ];
    }
}