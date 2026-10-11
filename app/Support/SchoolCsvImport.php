<?php

namespace App\Support;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolCsvImport
{
    public const BOOK_HEADERS = ['kode_buku', 'judul', 'pengarang', 'penerbit', 'tahun_terbit', 'kategori', 'isbn', 'jumlah_eksemplar', 'tersedia', 'rak'];

    public const MEMBER_HEADERS = ['member_id', 'member_name', 'gender', 'member_type_name', 'member_email', 'member_address', 'postal_code', 'inst_name', 'is_new', 'member_image', 'pin', 'member_phone', 'member_fax', 'member_since_date', 'register_date', 'expire_date', 'birth_date', 'member_notes', 'mpasswd'];

    public static function key(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::ascii(mb_strtolower($value ?? '')));
    }

    private static function validate(array $row, array $rules, int $line): array
    {
        $v = Validator::make($row, $rules);
        if ($v->fails()) {
            throw ValidationException::withMessages(['file' => "Baris {$line}: ".implode(' ', $v->errors()->all()).' Seluruh impor dibatalkan.']);
        }

        return $v->validated();
    }

    public static function books(array $rows): array
    {
        return DB::transaction(function () use ($rows) {
            $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
            $seen = [];
            foreach ($rows as $line => $row) {
                $d = self::validate($row, [
                    'kode_buku' => 'required|string|max:50', 'judul' => 'required|string|max:255',
                    'pengarang' => 'nullable|string|max:255', 'penerbit' => 'nullable|string|max:255',
                    'tahun_terbit' => 'nullable|integer|min:1|max:'.date('Y'), 'kategori' => 'required|string|max:100',
                    'isbn' => ['nullable', 'string', 'max:20', 'regex:/^[0-9Xx\- ]+$/'],
                    'jumlah_eksemplar' => 'required|integer|min:0|max:1000', 'tersedia' => 'nullable|integer|min:0|lte:jumlah_eksemplar',
                    'rak' => 'nullable|string|max:100',
                ], $line);
                $code = trim($d['kode_buku']);
                if (isset($seen[$code])) {
                    if ($seen[$code] !== $d) {
                        throw ValidationException::withMessages(['file' => "Baris {$line}: kode {$code} dipakai untuk data berbeda."]);
                    }
                    $result['skipped']++;

                    continue;
                }
                $seen[$code] = $d;
                $isbn = preg_replace('/[^0-9X]/', '', strtoupper($d['isbn'] ?? '')) ?: null;
                $book = Book::where('catalog_code', $code)->first();
                if (! $book) {
                    $book = Book::all()->first(fn ($b) => in_array($code, $b->import_aliases ?? [], true));
                }
                if (! $book && $isbn) {
                    $book = Book::all()->first(fn ($b) => preg_replace('/[^0-9X]/', '', strtoupper($b->isbn ?? '')) === $isbn);
                }
                if (! $book) {
                    $book = Book::all()->first(fn ($b) => self::key($b->title) === self::key($d['judul']) && self::key($b->author) === self::key($d['pengarang'] ?? 'Belum diketahui'));
                }
                $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($d['kategori'])])->first() ?? Category::create(['name' => $d['kategori']]);
                $new = ! $book;
                $book ??= new Book;
                $alias = $book->catalog_code && $book->catalog_code !== $code;
                if ($alias) {
                    $book->import_aliases = array_values(array_unique([...($book->import_aliases ?? []), $code]));
                } else {
                    if ($isbn && Book::where('isbn', $isbn)->when($book->exists, fn ($q) => $q->where('id', '!=', $book->id))->exists()) {
                        throw ValidationException::withMessages(['file' => "Baris {$line}: ISBN sudah digunakan judul lain."]);
                    }
                    $book->fill(['catalog_code' => $code, 'title' => $d['judul'], 'author' => $d['pengarang'] ?? 'Belum diketahui', 'publisher' => $d['penerbit'], 'publication_year' => $d['tahun_terbit'], 'category_id' => $category->id, 'isbn' => $isbn]);
                }
                $book->save();
                // Reimport never deletes stock or changes an active loan. Different codes for
                // the same title are aliases; their quantities are not added together.
                $copies = $book->copies()->whereNull('archived_at')->orderBy('id')->lockForUpdate()->get();
                $target = (int) $d['jumlah_eksemplar'];
                for ($i = $copies->count(); $i < $target; $i++) {
                    $inventory = $book->catalog_code.($target > 1 ? '-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT) : '');
                    if (strlen($inventory) > 50 || BookCopy::where('inventory_code', $inventory)->exists()) {
                        $inventory = 'IMP-'.$book->id.'-'.Str::upper(Str::random(10));
                    }
                    $copies->push(BookCopy::create(['book_id' => $book->id, 'inventory_code' => $inventory, 'shelf_location' => $d['rak'], 'condition' => 'baik']));
                }
                if (! $alias) {
                    $available = $d['tersedia'] === null ? $target : (int) $d['tersedia'];
                    foreach ($copies as $i => $copy) {
                        // Existing circulation always takes precedence over a CSV snapshot.
                        if (! $copy->loanItems()->exists() && ! $copy->reservations()->exists()) {
                            $copy->update(['import_unavailable' => $i >= $available, 'shelf_location' => $d['rak']]);
                        }
                    }
                }
                $result[$new ? 'created' : 'updated']++;
            }

            return $result;
        });
    }

    public static function members(array $rows): array
    {
        return DB::transaction(function () use ($rows) {
            $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
            $seen = [];
            foreach ($rows as $line => $row) {
                self::validate($row, [
                    'member_id' => 'required|string|max:30', 'member_name' => 'required|string|max:255',
                    'gender' => 'nullable|in:0,1', 'member_email' => 'nullable|email|max:255',
                    'member_phone' => 'nullable|string|max:30', 'birth_date' => 'nullable|date_format:Y-m-d|before_or_equal:today',
                    'member_type_name' => 'nullable|string|max:255',
                ], $line);
                $id = $row['member_id'];
                $safe = array_intersect_key($row, array_flip(self::MEMBER_HEADERS));
                unset($safe['pin'],$safe['mpasswd']); // Never store or export legacy credentials.
                foreach ($safe as $value) {
                    if (strlen((string) $value) > 5000) {
                        throw ValidationException::withMessages(['file' => "Baris {$line}: isi kolom terlalu panjang."]);
                    }
                }
                if (isset($seen[$id])) {
                    if ($seen[$id] !== $safe) {
                        throw ValidationException::withMessages(['file' => "Baris {$line}: member_id {$id} berisi data berbeda."]);
                    }
                    $result['skipped']++;

                    continue;
                }
                $seen[$id] = $safe;
                $member = Member::where('member_code', $id)->lockForUpdate()->first();
                $new = ! $member;
                $email = mb_strtolower($row['member_email'] ?? '');
                $user = $member?->user;
                if ($user && $user->role !== 'anggota') {
                    throw ValidationException::withMessages(['file' => "Baris {$line}: kode anggota milik akun petugas."]);
                }
                if ($email && User::where('email', $email)->when($user, fn ($q) => $q->where('id', '!=', $user->id))->exists()) {
                    throw ValidationException::withMessages(['file' => "Baris {$line}: email sudah terhubung akun lain."]);
                }
                if (! $user) {
                    $user = new User;
                    $user->role = 'anggota';
                    $user->is_active = true;
                    $user->password = Str::random(64);
                    $user->email = $email ?: 'siswa-'.Str::uuid().'@anggota.invalid';
                } elseif ($email) {
                    $user->email = $email;
                }
                $user->name = $row['member_name'];
                $user->save();
                $member ??= new Member(['user_id' => $user->id, 'member_code' => $id]);
                $member->gender = match ((string) ($row['gender'] ?? '')) {
                    '1' => 'laki-laki','0' => 'perempuan',default => null
                };
                $member->phone = $row['member_phone'];
                if ($row['birth_date']) {
                    $member->age = (int) Carbon::parse($row['birth_date'])->age;
                }
                $member->senayan_data = $safe;
                $member->save();
                $result[$new ? 'created' : 'updated']++;
            }

            return $result;
        });
    }
}
