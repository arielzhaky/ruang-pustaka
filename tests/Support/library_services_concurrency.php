<?php

// Pengujian manual di MySQL/XAMPP; hanya menulis database acak rp_services_test_*.
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RenewalController;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Category;
use App\Models\FinePayment;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\LoanRenewal;
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
function isolatedDatabase(string $name): void
{
    if (! preg_match('/^rp_services_test_[a-f0-9]{16}$/', $name)) { throw new RuntimeException('Invalid test database'); }
    config(['database.default'=>'mysql', 'database.connections.mysql.database'=>$name, 'database.connections.mysql.url'=>null, 'session.driver'=>'array']);
    DB::purge('mysql');
}
if (($argv[1] ?? '') === '--worker') {
    isolatedDatabase($argv[2]);
    Auth::loginUsingId((int) $argv[3]);
    $request = Request::create('/', 'POST', ['method'=>'cash', 'confirmation'=>1, 'decision'=>'approved']);
    $request->setUserResolver(fn () => Auth::user());
    app()->instance('request', $request);
    echo "READY\n"; flush(); fgets(STDIN);
    try {
        match ($argv[4]) {
            'pay' => app(BillingController::class)->pay($request, LoanItem::findOrFail($argv[5])),
            'ready' => app(ReservationController::class)->ready($request, BookReservation::findOrFail($argv[5])),
            'renew' => app(RenewalController::class)->review($request, LoanRenewal::findOrFail($argv[5])),
        };
        echo "ACCEPTED\n";
    } catch (ValidationException $e) { echo "REJECTED\n"; }
    exit;
}
$name = 'rp_services_test_'.bin2hex(random_bytes(8));
$admin = DB::connection('mysql')->getPdo();
$admin->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$processes = [];
try {
    isolatedDatabase($name);
    Artisan::call('migrate', ['--force'=>true]);
    $staff = User::factory()->create(['role'=>'petugas']);
    $members = [];
    for ($i=0; $i<2; $i++) { $members[] = Member::create(['user_id'=>User::factory()->create()->id, 'member_code'=>'TEST-'.$i]); }
    $book = Book::create(['category_id'=>Category::create(['name'=>'Test'])->id,'title'=>'Concurrency','author'=>'Test']);
    $copy = $book->copies()->create(['inventory_code'=>'TEST-COPY','condition'=>'baik']);
    $loan = Loan::create(['loan_code'=>'TEST-LOAN','member_id'=>$members[0]->id,'created_by'=>$staff->id,'borrowed_at'=>now()->subDays(10)]);
    $item = $loan->items()->create(['book_copy_id'=>$copy->id,'due_date'=>today()->subDays(3),'returned_at'=>now(),'fine_amount'=>6000,'fine_daily_rate'=>2000]);
    $item->forceFill(['billing_status'=>'unpaid'])->save();
    $race = function (array $jobs, string $table, int $id, int $expectedAccepted) use ($name, $staff, &$processes): void {
        $processes=[]; $inputs=[];
        foreach ($jobs as [$action,$target]) {
            $input=new InputStream;
            $process=new Process([PHP_BINARY,__FILE__,'--worker',$name,(string)$staff->id,$action,(string)$target], dirname(__DIR__,2));
            $process->setInput($input); $process->setTimeout(20); $process->start();
            $processes[]=$process; $inputs[]=$input;
        }
        $deadline=microtime(true)+15;
        foreach ($processes as $process) {
            while (! str_contains($process->getOutput(),'READY')) {
                if (! $process->isRunning() || microtime(true)>$deadline) { throw new RuntimeException('Worker failed to start: '.$process->getErrorOutput()); }
                usleep(10000);
            }
        }
        DB::beginTransaction();
        DB::table($table)->where('id',$id)->lockForUpdate()->first();
        foreach ($inputs as $input) { $input->write("GO\n"); $input->close(); }
        usleep(200000); DB::commit();
        $accepted=0;
        foreach ($processes as $process) {
            $process->wait();
            if (! $process->isSuccessful()) { throw new RuntimeException($process->getErrorOutput().$process->getOutput()); }
            $accepted += str_contains($process->getOutput(),'ACCEPTED') ? 1 : 0;
        }
        if ($accepted !== $expectedAccepted) { throw new RuntimeException("Expected {$expectedAccepted} accepted; got {$accepted}"); }
    };
    $race([['pay',$item->id],['pay',$item->id]],'loan_items',$item->id,2);
    if (FinePayment::count() !== 1 || FinePayment::sum('amount') !== '6000') {
        if (FinePayment::count() !== 1 || (int) FinePayment::sum('amount') !== 6000) { throw new RuntimeException('Duplicate or incorrect payment'); }
    }
    echo "PASS: two payment submissions produce one receipt and one payment.\n";
    $first=BookReservation::create(['member_id'=>$members[0]->id,'book_id'=>$book->id,'active_key'=>'0:1']);
    $second=BookReservation::create(['member_id'=>$members[1]->id,'book_id'=>$book->id,'active_key'=>'1:1']);
    $race([['ready',$first->id],['ready',$second->id]],'book_copies',$copy->id,1);
    if (BookReservation::where('status','ready')->count() !== 1 || $first->fresh()->status !== 'ready') { throw new RuntimeException('Copy held twice or queue bypassed'); }
    echo "PASS: concurrent reservation preparation holds one copy for the first student only.\n";
    BookReservation::query()->update(['status'=>'cancelled','active_key'=>null]);
    $active=$loan->items()->create(['book_copy_id'=>$copy->id,'due_date'=>today()->addDays(2),'fine_daily_rate'=>2000]);
    $renewal=LoanRenewal::create(['loan_item_id'=>$active->id,'original_due_date'=>$active->due_date,'requested_due_date'=>$active->due_date->copy()->addDays(7)]);
    $race([['renew',$renewal->id],['renew',$renewal->id]],'members',$members[0]->id,1);
    if ($active->fresh()->due_date->toDateString() !== today()->addDays(9)->toDateString()) { throw new RuntimeException('Renewal applied more than once'); }
    echo "PASS: concurrent approvals extend the due date only once.\n";
} finally {
    foreach ($processes as $process) { if ($process->isRunning()) { $process->stop(); } }
    while (DB::transactionLevel()>0) { DB::rollBack(); }
    DB::disconnect('mysql');
    $admin->exec("DROP DATABASE `{$name}`");
    echo "Temporary test database removed.\n";
}
