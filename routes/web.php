<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DataTransferController;
use App\Http\Controllers\FavoriteBookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanReportController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MyLoanController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RenewalController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Middleware\EnsurePetugas;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::view('/borrowing-rules', 'borrowing-rules')->name('borrowing-rules');
    Route::get('/member-card', MemberCardController::class)->name('member-card');
    Route::get('/favorites', [FavoriteBookController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{book}', [FavoriteBookController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{book}', [FavoriteBookController::class, 'destroy'])->name('favorites.destroy');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])
        ->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
Route::middleware(['auth', EnsurePetugas::class])->group(function () {
    Route::get('/members/export', [DataTransferController::class, 'exportMembers'])->name('members.export');
    Route::get('/members/template', [DataTransferController::class, 'memberTemplate'])->name('members.template');
    Route::post('/members/import', [DataTransferController::class, 'importMembers'])->name('members.import');
    Route::get('/books/export', [DataTransferController::class, 'exportBooks'])->name('books.export');
    Route::get('/books/template', [DataTransferController::class, 'bookTemplate'])->name('books.template');
    Route::post('/books/import', [DataTransferController::class, 'importBooks'])->name('books.import');
    Route::get('/reports/loans/download', [LoanReportController::class, 'download'])->name('reports.loans.download');

    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('categories.index');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');
});

Route::middleware(['auth', EnsurePetugas::class])->group(function () {
    Route::get('/books', [BookController::class, 'index'])
        ->name('books.index');

    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');

    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');

    Route::get('/books/{book}/copies', [BookCopyController::class, 'index'])
        ->name('copies.index');

    Route::post('/books/{book}/copies', [BookCopyController::class, 'store'])
        ->name('copies.store');

    Route::get('/books/{book}/copies/{copy}/edit', [BookCopyController::class, 'edit'])
        ->name('copies.edit');

    Route::put('/books/{book}/copies/{copy}', [BookCopyController::class, 'update'])
        ->name('copies.update');

    Route::get('/school-classes', [SchoolClassController::class, 'index'])
        ->name('school-classes.index');

    Route::post('/school-classes', [SchoolClassController::class, 'store'])
        ->name('school-classes.store');

    Route::put('/school-classes/{schoolClass}', [SchoolClassController::class, 'update'])
        ->name('school-classes.update');

    Route::get('/members', [MemberController::class, 'index'])
        ->name('members.index');

    Route::post('/members', [MemberController::class, 'store'])
        ->name('members.store');
    Route::delete('/school-classes/{schoolClass}', [SchoolClassController::class, 'destroy'])
        ->name('school-classes.destroy');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])
        ->name('members.edit');
    Route::put('/members/{member}', [MemberController::class, 'update'])
        ->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])
        ->name('members.destroy');

    Route::get('/loans/create', [LoanController::class, 'create'])
        ->name('loans.create');

    Route::post('/loans', [LoanController::class, 'store'])
        ->name('loans.store');
    Route::get('/loans', [LoanController::class, 'index'])
        ->name('loans.index');

    Route::post('/loans/items/{loanItem}/return', [LoanController::class, 'returnItem'])
        ->name('loans.items.return');
    Route::get('/reports/loans', LoanReportController::class)
        ->name('reports.loans');
    Route::get('/reports/loans/print', [LoanReportController::class, 'print'])
        ->name('reports.loans.print');

});

// Route katalog untuk siswa
Route::get('/catalog', [CatalogController::class, 'index'])
    ->middleware('auth')
    ->name('catalog.index');
Route::get('/catalog/{book}', [CatalogController::class, 'show'])
    ->middleware('auth')
    ->name('catalog.show');
Route::get('/my-loans', MyLoanController::class)
    ->middleware('auth')
    ->name('my-loans.index');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->middleware('throttle:10,1')->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->middleware('throttle:5,1')->name('account.password');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/receipts/{payment}', [BillingController::class, 'receipt'])->name('billing.receipt');
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/books/{book}', [ReservationController::class, 'store'])->middleware('throttle:10,1')->name('reservations.store');
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::get('/renewals', [RenewalController::class, 'index'])->name('renewals.index');
    Route::post('/renewals/items/{loanItem}', [RenewalController::class, 'store'])->middleware('throttle:10,1')->name('renewals.store');
    Route::get('/reminders', [OperationsController::class, 'reminders'])->name('reminders.index');
});
Route::middleware(['auth', EnsurePetugas::class])->group(function () {
    Route::post('/billing/{loanItem}/confirm', [BillingController::class, 'confirm'])->name('billing.confirm');
    Route::post('/billing/{loanItem}/pay', [BillingController::class, 'pay'])->name('billing.pay');
    Route::post('/reservations/{reservation}/ready', [ReservationController::class, 'ready'])->name('reservations.ready');
    Route::post('/renewals/{renewal}/review', [RenewalController::class, 'review'])->name('renewals.review');
    Route::get('/overdue', [OperationsController::class, 'overdue'])->name('overdue.index');
    Route::get('/activity', [OperationsController::class, 'activity'])->name('activity.index');
});
