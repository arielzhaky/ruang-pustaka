<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\LoanItem;
use App\Models\Member;
use App\Support\LoanCharges;
use App\Support\ServiceSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Samakan format email dengan saat membuat akun.
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:petugas,anggota'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'password.required' => 'Password wajib diisi.',
            'role.required' => 'Pilih masuk sebagai petugas/admin atau siswa.',
            'role.in' => 'Pilihan peran tidak valid.',
        ]);

        // Hanya akun aktif yang boleh masuk.
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email, password, atau pilihan peran tidak sesuai, atau akun tidak aktif.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function dashboard(Request $request)
    {
        $serviceSummary = ServiceSummary::for($request->user());
        if ($request->user()->role === 'petugas') {
            $jumlahJudul = Book::whereNull('archived_at')->count();
            $jumlahSiswa = Member::whereHas('user', fn ($q) => $q->where('role', 'anggota')->where('is_active', true))->count();
            $sedangDipinjam = LoanItem::whereNull('returned_at')->count();
            $terlambat = LoanItem::whereNull('returned_at')->whereDate('due_date', '<', today())->count();
            $tersedia = BookCopy::available()->whereHas('book', fn ($q) => $q->whereNull('archived_at'))->count();
            $sudahSelesai = LoanItem::whereNotNull('returned_at')->count();
            $latestBooks = Book::with('category')->whereNull('archived_at')
                ->withCount(['copies as available_count' => fn ($q) => $q->available()])
                ->latest('id')->limit(6)->get();

            return view('dashboard', compact('serviceSummary', 'jumlahJudul', 'jumlahSiswa', 'sedangDipinjam', 'terlambat', 'tersedia', 'sudahSelesai', 'latestBooks'));
        }

        abort_unless($request->user()->role === 'anggota', 403);

        $today = today();
        // Selalu tentukan pemilik dari sesi login, bukan dari parameter URL.
        $items = LoanItem::query()->whereHas('loan.member', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        });

        $activeLoans = (clone $items)->with(['bookCopy.book', 'loan'])
            ->whereNull('returned_at')->orderBy('due_date')->orderBy('id')->get();
        $recentReturns = (clone $items)->with(['bookCopy.book', 'loan'])
            ->whereNotNull('returned_at')->orderByDesc('returned_at')->orderByDesc('id')
            ->limit(5)->get();

        foreach ($activeLoans as $item) {
            $item->late_days = $item->due_date->lt($today)
                ? (int) $item->due_date->diffInDays($today) : 0;
        }
        foreach ($recentReturns as $item) {
            // Keterlambatan riwayat berhenti pada tanggal buku dikembalikan.
            $returnDate = $item->returned_at->copy()->startOfDay();
            $item->late_days = $item->due_date->lt($returnDate)
                ? (int) $item->due_date->diffInDays($returnDate) : 0;
        }

        $activeCount = $activeLoans->count();
        $lateCount = $activeLoans->where('late_days', '>', 0)->count();
        $dueTodayCount = $activeLoans->filter(fn ($item) => $item->due_date->isSameDay($today))->count();
        $maxActiveLoans = config('library.max_active_loans');
        $remainingQuota = max(0, $maxActiveLoans - $activeCount);
        $loanDays = config('library.loan_days');
        $studentProfile = $request->user()->member()->with('schoolClass')->first();
        $hasMemberProfile = $studentProfile !== null;
        $estimatedFineTotal = (int) $activeLoans->sum(fn ($item) => LoanCharges::lateFine($item, $today));

        $discovery = Book::query()->with('category')->whereNull('archived_at')
            ->withCount(['copies as available_count' => fn ($query) => $query->available()]);
        $popularBooks = (clone $discovery)->has('loanItems')->withCount('loanItems')
            ->orderByDesc('loan_items_count')->orderBy('title')->orderBy('id')->limit(4)->get();
        $latestBooks = (clone $discovery)->latest('id')->limit(4)->get();
        $favorites = $request->user()->favoriteBooks()->whereNull('books.archived_at');
        $favoriteCount = (clone $favorites)->count();
        $favoriteBooks = (clone $favorites)->with('category')
            ->withCount(['copies as available_count' => fn ($query) => $query->available()])
            ->orderByPivot('created_at', 'desc')->orderByDesc('books.id')->limit(4)->get();

        // Status favorit hanya diambil untuk buku yang ditampilkan di dashboard.
        $displayedBooks = $popularBooks->concat($latestBooks)->concat($favoriteBooks);
        $favoriteIds = $request->user()->favoriteBooks()
            ->whereIn('books.id', $displayedBooks->pluck('id')->unique())
            ->pluck('books.id')->all();
        foreach ($displayedBooks as $book) {
            $book->is_favorite = in_array($book->id, $favoriteIds, true);
        }

        return view('dashboard', compact(
            'serviceSummary', 'activeLoans', 'recentReturns', 'activeCount', 'lateCount', 'dueTodayCount',
            'maxActiveLoans', 'remainingQuota', 'loanDays', 'hasMemberProfile', 'today',
            'studentProfile', 'estimatedFineTotal', 'popularBooks', 'latestBooks', 'favoriteBooks', 'favoriteCount'
        ));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Kamu berhasil keluar.');
    }
}
