<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\CsvFile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DataTransferController extends Controller
{
    private const MEMBER_HEADERS = ['nama', 'email', 'password', 'kode_anggota', 'jenis_kelamin', 'usia', 'kelas', 'nis', 'telepon', 'status'];

    private const BOOK_HEADERS = ['judul', 'penulis', 'kategori', 'isbn', 'penerbit', 'tahun_terbit', 'deskripsi'];

    public function memberTemplate()
    {
        return CsvFile::download('template-siswa.csv', self::MEMBER_HEADERS, []);
    }

    public function bookTemplate()
    {
        return CsvFile::download('template-buku.csv', [...self::BOOK_HEADERS, 'harga_buku'], []);
    }

    public function exportMembers()
    {
        $rows = Member::with(['user', 'schoolClass'])->whereHas('user', fn ($q) => $q->where('role', 'anggota'))
            ->orderBy('id')->lazy()->map(fn ($member) => [
                $member->user->name, $member->user->email, '', $member->member_code,
                $member->gender, $member->age, $member->schoolClass?->name, $member->nis,
                $member->phone, $member->user->is_active ? 'aktif' : 'nonaktif',
            ]);

        return CsvFile::download('siswa-'.today()->format('Y-m-d').'.csv', self::MEMBER_HEADERS, $rows);
    }

    public function exportBooks()
    {
        $rows = Book::with('category')->whereNull('archived_at')->orderBy('id')->lazy()->map(fn ($book) => [
            $book->title, $book->author, $book->category->name, $book->isbn,
            $book->publisher, $book->publication_year, $book->description, $book->replacement_price,
        ]);

        return CsvFile::download('buku-'.today()->format('Y-m-d').'.csv', [...self::BOOK_HEADERS, 'harga_buku'], $rows);
    }

    public function importMembers(Request $request)
    {
        $rows = CsvFile::read($request, self::MEMBER_HEADERS);
        $this->import($rows, function ($row, $number) {
            $row['email'] = strtolower($row['email'] ?? '');
            $row['status'] = $row['status'] ?? 'aktif';
            $data = $this->validateRow($row, [
                'nama' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'max:72'],
                'kode_anggota' => ['required', 'string', 'max:30', 'unique:members,member_code'],
                'jenis_kelamin' => ['required', Rule::in(['laki-laki', 'perempuan'])],
                'usia' => ['required', 'integer', 'between:1,120'],
                'kelas' => ['required', Rule::exists('school_classes', 'name')->where('is_active', 1)],
                'nis' => ['nullable', 'string', 'max:30', 'unique:members,nis'],
                'telepon' => ['nullable', 'string', 'max:30'],
                'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            ], $number);
            $user = new User;
            $user->name = $data['nama'];
            $user->email = $data['email'];
            $user->password = $data['password']; // Cast hashed pada User menyimpan hash kata sandi.
            $user->role = 'anggota';
            $user->is_active = $data['status'] === 'aktif';
            $user->save();
            Member::create([
                'user_id' => $user->id, 'member_code' => $data['kode_anggota'], 'gender' => $data['jenis_kelamin'],
                'age' => $data['usia'], 'school_class_id' => SchoolClass::where('name', $data['kelas'])->value('id'),
                'nis' => $data['nis'], 'phone' => $data['telepon'],
            ]);
        });

        return redirect()->route('members.index')->with('success', count($rows).' siswa berhasil diimpor.');
    }

    public function importBooks(Request $request)
    {
        $rows = CsvFile::read($request, self::BOOK_HEADERS, ['harga_buku']);
        $this->import($rows, function ($row, $number) {
            $data = $this->validateRow($row, [
                'judul' => ['required', 'string', 'max:255'],
                'penulis' => ['required', 'string', 'max:255'],
                'kategori' => ['required', 'string', 'exists:categories,name'],
                'isbn' => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
                'penerbit' => ['nullable', 'string', 'max:255'],
                'tahun_terbit' => ['nullable', 'integer', 'min:1', 'max:'.date('Y')],
                'deskripsi' => ['nullable', 'string', 'max:5000'],
                'harga_buku' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            ], $number);
            if (Book::where('title', $data['judul'])->where('author', $data['penulis'])->exists()) {
                throw ValidationException::withMessages(['file' => "Baris {$number}: judul dan penulis tersebut sudah ada. Impor dibatalkan."]);
            }
            Book::create([
                'replacement_price' => $data['harga_buku'] ?? null,
                'category_id' => Category::where('name', $data['kategori'])->value('id'),
                'title' => $data['judul'], 'author' => $data['penulis'], 'isbn' => $data['isbn'],
                'publisher' => $data['penerbit'], 'publication_year' => $data['tahun_terbit'], 'description' => $data['deskripsi'],
            ]);
        });

        return redirect()->route('books.index')->with('success', count($rows).' judul buku berhasil diimpor. Tambahkan cover dan eksemplar melalui menu Ubah / Eksemplar.');
    }

    private function validateRow(array $row, array $rules, int $number): array
    {
        $validator = Validator::make($row, $rules, [
            'required' => 'Kolom :attribute wajib diisi.', 'unique' => 'Nilai :attribute sudah digunakan.',
            'exists' => 'Nilai :attribute tidak ditemukan atau tidak aktif.', 'email' => 'Format email tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.', 'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'between' => 'Nilai :attribute harus antara :min dan :max.',
            'min' => 'Nilai :attribute belum memenuhi batas minimum :min.', 'max' => 'Nilai :attribute melewati batas :max.',
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['file' => "Baris {$number}: ".implode(' ', $validator->errors()->all()).' Seluruh impor dibatalkan.']);
        }

        return $validator->validated();
    }

    private function import(array $rows, callable $save): void
    {
        try {
            DB::transaction(function () use ($rows, $save) {
                foreach ($rows as $number => $row) {
                    $save($row, $number);
                }
            });
        } catch (QueryException $exception) {
            if (str_starts_with((string) $exception->getCode(), '23')) {
                throw ValidationException::withMessages(['file' => 'Data duplikat atau referensi berubah saat impor. Seluruh impor dibatalkan. Periksa kembali file.']);
            }
            throw $exception;
        }
    }
}
