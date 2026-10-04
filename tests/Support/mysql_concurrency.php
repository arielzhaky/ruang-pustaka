<?php

// Jalankan manual: php tests/Support/mysql_concurrency.php
// Selalu buat database acak sendiri; jangan jalankan migration pada database aplikasi.
use App\Http\Controllers\LoanController;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function useTestDatabase(string $name): void
{
    if (! preg_match('/^rp_test_[a-f0-9]{16}$/', $name)) {
        throw new RuntimeException('Nama database pengujian tidak valid.');
    }
    config(['database.default' => 'mysql', 'database.connections.mysql.database' => $name,
        'database.connections.mysql.url' => null, 'library.max_active_loans' => 3, 'library.loan_days' => 7]);
    DB::purge('mysql');
}

function borrow(int $member, array $copies): void
{
    $request = Request::create('/loans', 'POST', ['member_id' => $member, 'book_copy_ids' => $copies]);
    app(LoanController::class)->store($request);
}

if (($argv[1] ?? '') === '--worker') {
    useTestDatabase($argv[2]);
    Auth::loginUsingId((int) $argv[3]);
    // Sinkronisasi lewat stdin: kedua proses telah boot sebelum transaksi dimulai.
    echo "READY\n";
    flush();
    fgets(STDIN);
    try {
        borrow((int) $argv[4], json_decode($argv[5], true, flags: JSON_THROW_ON_ERROR));
        echo "ACCEPTED\n";
    } catch (ValidationException $e) {
        echo "REJECTED\n";
    }
    exit;
}

$name = 'rp_test_'.bin2hex(random_bytes(8));
// Simpan koneksi administratif hanya untuk database acak milik proses ini.
$admin = DB::connection('mysql')->getPdo();
$admin->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$processes = [];
try {
    useTestDatabase($name);
    Artisan::call('migrate', ['--force' => true]);
    $staff = User::factory()->create(['role' => 'petugas']);
    Auth::login($staff);
    $members = [];
    for ($i = 0; $i < 2; $i++) {
        $members[] = Member::create(['user_id' => User::factory()->create()->id, 'member_code' => 'TEST-'.$i]);
    }
    $book = Book::create(['category_id' => Category::create(['name' => 'Test'])->id,
        'title' => 'Test', 'author' => 'Test']);
    $copies = [];
    for ($i = 0; $i < 6; $i++) {
        $copies[] = $book->copies()->create(['inventory_code' => 'TEST-'.$i, 'condition' => 'baik'])->id;
    }

    $race = function (array $jobs, string $lockTable, int $lockId) use ($name, $staff, &$processes): void {
        $processes = [];
        $inputs = [];
        foreach ($jobs as [$member, $ids]) {
            $input = new InputStream;
            $process = new Process([PHP_BINARY, __FILE__, '--worker', $name, (string) $staff->id,
                (string) $member, json_encode($ids)], dirname(__DIR__, 2));
            $process->setInput($input);
            $process->setTimeout(15);
            $process->start();
            $processes[] = $process;
            $inputs[] = $input;
        }
        $deadline = microtime(true) + 10;
        foreach ($processes as $process) {
            while (! str_contains($process->getOutput(), 'READY')) {
                if (! $process->isRunning() || microtime(true) > $deadline) {
                    throw new RuntimeException('Proses pengujian tidak siap: '.$process->getErrorOutput());
                }
                usleep(10000);
            }
        }
        // Tahan baris yang diperebutkan sampai kedua worker memulai request.
        DB::beginTransaction();
        DB::table($lockTable)->where('id', $lockId)->lockForUpdate()->first();
        foreach ($inputs as $input) {
            $input->write("GO\n");
            $input->close();
        }
        usleep(200000);
        DB::commit();
        $accepted = $rejected = 0;
        foreach ($processes as $process) {
            $process->wait();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('Proses pengujian gagal: '.$process->getErrorOutput().$process->getOutput());
            }
            $accepted += str_contains($process->getOutput(), 'ACCEPTED') ? 1 : 0;
            $rejected += str_contains($process->getOutput(), 'REJECTED') ? 1 : 0;
        }
        if ($accepted !== 1 || $rejected !== 1) {
            throw new RuntimeException("Hasil tidak sesuai: {$accepted} diterima, {$rejected} ditolak.");
        }
    };

    $race([[$members[0]->id, [$copies[0]]], [$members[1]->id, [$copies[0]]]], 'book_copies', $copies[0]);
    if (LoanItem::where('book_copy_id', $copies[0])->whereNull('returned_at')->count() !== 1) {
        throw new RuntimeException('Eksemplar dipinjam lebih dari sekali.');
    }
    echo "PASS: satu eksemplar diperebutkan dua anggota.\n";

    // Siapkan anggota pertama dengan tepat dua pinjaman aktif.
    $count = LoanItem::whereHas('loan', fn ($q) => $q->where('member_id', $members[0]->id))
        ->whereNull('returned_at')->count();
    borrow($members[0]->id, array_slice($copies, 1, 2 - $count));
    $before = Loan::count();
    $race([[$members[0]->id, [$copies[3]]], [$members[0]->id, [$copies[4]]]], 'members', $members[0]->id);
    $count = LoanItem::whereHas('loan', fn ($q) => $q->where('member_id', $members[0]->id))
        ->whereNull('returned_at')->count();
    if ($count !== 3 || Loan::count() !== $before + 1) {
        throw new RuntimeException('Kuota atau jumlah transaksi tidak sesuai.');
    }
    echo "PASS: dua petugas memperebutkan satu sisa kuota anggota.\n";
} finally {
    foreach ($processes as $process) {
        if ($process->isRunning()) {
            $process->stop();
        }
    }
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    DB::disconnect('mysql');
    $admin->exec("DROP DATABASE `{$name}`");
    echo "Database pengujian sementara sudah dihapus.\n";
}
