<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoanReportController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('reports.loans', $this->reportData($request));
    }

    public function print(Request $request)
    {
        return view('reports.loans-print', $this->reportData($request, true));
    }

    public function download(Request $request)
    {
        $data = $this->reportData($request, true);

        return Pdf::loadView('reports.loans-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->download('laporan-peminjaman-'.now()->format('Y-m-d-His').'.pdf');
    }

    private function reportData(Request $request, bool $print = false): array
    {
        $endDateRules = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('start_date')) {
            $endDateRules[] = 'after_or_equal:start_date';
        }
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'active', 'late', 'returned', 'lost'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => $endDateRules,
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'status.in' => 'Status laporan tidak valid.',
            'start_date.date_format' => 'Tanggal awal tidak valid.',
            'end_date.date_format' => 'Tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            'page.integer' => 'Nomor halaman tidak valid.',
            'page.min' => 'Nomor halaman minimal 1.',
        ]);

        $status = $data['status'] ?? 'all';
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;
        $generatedAt = now();
        $today = $generatedAt->copy()->startOfDay();
        $todayString = $today->toDateString();
        $statusLabels = [
            'all' => 'Semua status',
            'active' => 'Dipinjam (belum terlambat)',
            'late' => 'Terlambat (belum kembali)',
            'returned' => 'Selesai (kembali / hilang)',
            'lost' => 'Buku hilang',
        ];
        $filters = array_filter([
            'status' => $status, 'start_date' => $startDate, 'end_date' => $endDate,
        ], fn ($value) => $value !== null && $value !== '');

        $query = DB::table('loan_items as li')
            ->join('loans as l', 'l.id', '=', 'li.loan_id')
            ->join('members as m', 'm.id', '=', 'l.member_id')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->join('book_copies as bc', 'bc.id', '=', 'li.book_copy_id')
            ->join('books as b', 'b.id', '=', 'bc.book_id');

        if ($startDate) {
            $query->where('l.borrowed_at', '>=', $startDate.' 00:00:00');
        }
        if ($endDate) {
            // Batas eksklusif hari berikutnya mencakup seluruh jam pada tanggal akhir.
            $query->where('l.borrowed_at', '<', Carbon::parse($endDate)->addDay()->startOfDay());
        }
        if ($status === 'active') {
            $query->whereNull('li.returned_at')->where('li.due_date', '>=', $todayString);
        } elseif ($status === 'late') {
            $query->whereNull('li.returned_at')->where('li.due_date', '<', $todayString);
        } elseif ($status === 'returned') {
            $query->whereNotNull('li.returned_at');
        }

        if ($status === 'lost') {
            $query->where('li.return_condition', 'hilang');
        }

        // Ringkasan memakai semua hasil sesuai filter, sebelum pagination.
        $totals = (clone $query)->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN li.returned_at IS NULL AND li.due_date >= ? THEN 1 ELSE 0 END) as active', [$todayString])
            ->selectRaw('SUM(CASE WHEN li.returned_at IS NULL AND li.due_date < ? THEN 1 ELSE 0 END) as late', [$todayString])
            ->selectRaw('SUM(CASE WHEN li.returned_at IS NOT NULL THEN 1 ELSE 0 END) as returned')
            ->first();
        $summary = array_map('intval', (array) $totals);

        $query->select(
            'li.return_condition', 'li.fine_daily_rate', 'li.fine_amount', 'li.replacement_amount',
            'li.id', 'li.due_date', 'li.returned_at', 'l.loan_code', 'l.borrowed_at',
            'u.name as student_name', 'b.title as book_title', 'bc.inventory_code'
        )->orderByDesc('l.borrowed_at')->orderByDesc('li.id');

        // Halaman cetak sengaja tidak menggunakan paginate agar semua hasil tercetak.
        $items = $print ? $query->get() : $query->paginate(10)->appends($filters);
        foreach ($items as $item) {
            $dueDate = Carbon::parse($item->due_date)->startOfDay();
            $end = $item->returned_at ? Carbon::parse($item->returned_at)->startOfDay() : $today;
            $item->late_days = $dueDate->lt($end) ? (int) $dueDate->diffInDays($end) : 0;
            $item->status_key = $item->returned_at ? 'returned' : ($item->late_days > 0 ? 'late' : 'active');
        }
        $rowStart = $print ? 1 : ($items->firstItem() ?? 1);
        $periodLabel = match (true) {
            $startDate !== null && $endDate !== null => Carbon::parse($startDate)->format('d/m/Y').' – '.Carbon::parse($endDate)->format('d/m/Y'),
            $startDate !== null => 'Mulai '.Carbon::parse($startDate)->format('d/m/Y'),
            $endDate !== null => 'Sampai '.Carbon::parse($endDate)->format('d/m/Y'),
            default => 'Semua tanggal',
        };

        return compact(
            'items', 'summary', 'rowStart', 'status', 'statusLabels', 'filters',
            'startDate', 'endDate', 'periodLabel', 'generatedAt'
        );
    }
}
