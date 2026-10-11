<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\CsvFile;
use App\Support\SchoolCsvImport;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DataTransferController extends Controller
{
    private const MEMBER_HEADERS = ['nama', 'email', 'password', 'kode_anggota', 'jenis_kelamin', 'usia', 'kelas', 'nis', 'telepon', 'status'];

    private const BOOK_HEADERS = ['judul', 'penulis', 'kategori', 'isbn', 'penerbit', 'tahun_terbit', 'deskripsi'];

    public function memberTemplate()
    {
        return CsvFile::download('template-siswa-senayan.csv', SchoolCsvImport::MEMBER_HEADERS, []);
    }

    public function bookTemplate()
    {
        return CsvFile::download('template-buku-sekolah.csv', SchoolCsvImport::BOOK_HEADERS, []);
    }

    public function exportMembers()
    {
        $rows = Member::with(['user','schoolClass'])->whereHas('user', fn($q)=>$q->where('role','anggota'))->orderBy('id')->lazy()->map(function($member) {
            $data = $member->senayan_data ?? [];
            $data['member_id'] = $member->member_code;
            $data['member_name'] = $member->user->name;
            $data['gender'] = match($member->gender) {'laki-laki'=>'1','perempuan'=>'0',default=>''};
            $data['member_email'] = str_ends_with($member->user->email, '@anggota.invalid') ? '' : $member->user->email;
            $data['member_phone'] = $member->phone;
            $data['inst_name'] ??= config('library.school_name');
            $data['pin'] = $data['mpasswd'] = '';
            return array_map(fn($key)=>$data[$key] ?? '', SchoolCsvImport::MEMBER_HEADERS);
        });
        return CsvFile::download('siswa-'.today()->format('Y-m-d').'.csv', SchoolCsvImport::MEMBER_HEADERS, $rows);
    }

    public function exportBooks()
    {
        $rows = Book::with('category')->whereNull('archived_at')
            ->withCount(['copies'=>fn($q)=>$q->whereNull('archived_at'), 'copies as available_count'=>fn($q)=>$q->available()])
            ->with(['copies'=>fn($q)=>$q->whereNull('archived_at')->orderBy('id')])
            ->orderBy('id')->lazy()->map(fn($book)=>[
                $book->catalog_code ?? 'RP-'.$book->id, $book->title, $book->author, $book->publisher,
                $book->publication_year, $book->category->name, $book->isbn, $book->copies_count,
                $book->available_count, $book->copies->first()?->shelf_location,
            ]);
        return CsvFile::download('buku-'.today()->format('Y-m-d').'.csv', SchoolCsvImport::BOOK_HEADERS, $rows);
    }

    public function importMembers(Request $request)
    {
        if (CsvFile::hasHeader($request, 'member_id')) {
            $counts = SchoolCsvImport::members(CsvFile::read($request, SchoolCsvImport::MEMBER_HEADERS));
            return redirect()->route('members.index')->with('success', "Impor Senayan selesai: {$counts['created']} siswa baru, {$counts['updated']} diperbarui, {$counts['skipped']} duplikat dilewati. Siswa tanpa email dapat masuk memakai QR kartu.");
        }
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
        if (CsvFile::hasHeader($request, 'kode_buku')) {
            if ($request->hasFile('covers')) throw ValidationException::withMessages(['covers'=>'Format sekolah memakai 10 kolom asli. Tambahkan cover setelah impor melalui Ubah / Cover.']);
            $counts = SchoolCsvImport::books(CsvFile::read($request, SchoolCsvImport::BOOK_HEADERS));
            return redirect()->route('books.index')->with('success', "Impor sekolah selesai: {$counts['created']} judul baru, {$counts['updated']} diperbarui, {$counts['skipped']} duplikat dilewati. Cover dapat ditambahkan lewat Ubah / Cover buku.");
        }
        $rows = CsvFile::read($request, self::BOOK_HEADERS, ['harga_buku', 'cover']);
        $request->validate([
            'covers' => ['nullable', 'array', 'max:10'],
            'covers.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg', 'extensions:jpg,jpeg', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
        ], [
            'covers.max' => 'Maksimal 10 cover per impor.',
            'covers.*.image' => 'Cover harus berupa gambar JPG.',
            'covers.*.mimes' => 'Cover impor harus JPG/JPEG.',
            'covers.*.extensions' => 'Gunakan berkas .jpg atau .jpeg.',
            'covers.*.max' => 'Setiap cover maksimal 2 MB.',
            'covers.*.dimensions' => 'Dimensi cover maksimal 3000 x 3000 piksel.',
        ]);
        $covers = [];
        $totalSize = 0;
        foreach ($request->file('covers', []) as $cover) {
            $name = $cover->getClientOriginalName();
            if (isset($covers[$name])) {
                throw ValidationException::withMessages(['covers' => 'Nama file cover harus unik: '.$name]);
            }
            $covers[$name] = $cover;
            $totalSize += $cover->getSize();
        }
        if ($totalSize > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['covers' => 'Total ukuran cover maksimal 5 MB per impor.']);
        }
        $used = [];
        foreach ($rows as $number => $row) {
            $name = $row['cover'] ?? null;
            if ($name !== null) {
                if (! isset($covers[$name])) {
                    throw ValidationException::withMessages(['file' => "Baris {$number}: unggah file cover dengan nama persis {$name}."]);
                }
                $used[$name] = true;
            }
        }
        if (array_diff_key($covers, $used)) {
            throw ValidationException::withMessages(['covers' => 'Ada file cover yang belum dicantumkan pada kolom cover di CSV.']);
        }
        $written = [];
        try {
            $this->import($rows, function ($row, $number) use ($covers, &$written) {
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
                $path = null;
                if ($name = $row['cover'] ?? null) {
                    $filename = str()->uuid().'.jpg';
                    $path = 'covers/'.$filename;
                    $written[] = $path;
                    if (! Storage::disk('public')->putFileAs('covers', $covers[$name], $filename)) {
                        throw ValidationException::withMessages(['covers' => 'Cover gagal disimpan. Seluruh impor dibatalkan.']);
                    }
                }
                Book::create([
                    'cover_path' => $path,
                    'replacement_price' => $data['harga_buku'] ?? null,
                    'category_id' => Category::where('name', $data['kategori'])->value('id'),
                    'title' => $data['judul'], 'author' => $data['penulis'], 'isbn' => $data['isbn'],
                    'publisher' => $data['penerbit'], 'publication_year' => $data['tahun_terbit'], 'description' => $data['deskripsi'],
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($written);
            throw $exception;
        }

        return redirect()->route('books.index')->with('success', count($rows).' judul buku berhasil diimpor beserta cover yang dipilih. Tambahkan stok melalui menu Stok / Eksemplar.');
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
