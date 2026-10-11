<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Support\CsvFile;
use App\Support\SchoolCsvImport;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportSchoolData extends Command
{
    protected $signature = 'library:import-school {books : CSV sekolah yang telah diperiksa} {members : CSV Senayan} {--replace-generated} {--dry-run}';

    protected $description = 'Impor data sekolah secara atomik, dengan opsi mengganti koleksi contoh';

    public function handle(): int
    {
        try {
            $read = function ($file, $headers) {
                if (! is_file($file)) {
                    throw new \RuntimeException('File tidak ditemukan.');
                }$r = Request::create('/', 'POST');
                $r->files->set('file', new UploadedFile($file, basename($file), 'text/csv', null, true));

                return CsvFile::read($r, $headers);
            };
            $books = $read($this->argument('books'), SchoolCsvImport::BOOK_HEADERS);
            $members = $read($this->argument('members'), SchoolCsvImport::MEMBER_HEADERS);
            DB::beginTransaction();
            $removed = 0;
            $archived = 0;
            if ($this->option('replace-generated')) {
                foreach (Book::where(fn ($q) => $q->where('source_key', 'like', 'gramedia:%')->orWhere('source_key', 'like', 'sibi:%'))->get() as $book) {
                    if ($book->loanItems()->exists() || DB::table('book_reservations')->where('book_id', $book->id)->exists()) {
                        $book->update(['archived_at' => now()]);
                        $archived++;

                        continue;
                    }
                    $book->copies()->delete();
                    $book->delete();
                    $removed++;
                }
            }
            $bookResult = SchoolCsvImport::books($books);
            $memberResult = SchoolCsvImport::members($members);
            if ($this->option('dry-run')) {
                DB::rollBack();
            } else {
                DB::commit();
            }
            $this->line(json_encode(['dry_run' => (bool) $this->option('dry-run'), 'removed_generated' => $removed, 'archived_with_history' => $archived, 'books' => $bookResult, 'members' => $memberResult]));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }$this->error($e->getMessage());
            if ($e instanceof ValidationException) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $this->error($message);
                    }
                }
            }

return self::FAILURE;
        }
    }
}
