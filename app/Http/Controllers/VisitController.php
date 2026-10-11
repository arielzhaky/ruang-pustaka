<?php

namespace App\Http\Controllers;

use App\Models\LibraryVisit;
use App\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'], 'period' => ['nullable', 'in:day,month,year'], 'q' => ['nullable', 'string', 'max:100']]);
        $date = CarbonImmutable::parse($data['date'] ?? today()->toDateString())->locale('id');
        $period = $data['period'] ?? 'day';
        $totals = self::totals($date);
        $weekStart = $date->startOfWeek(1);
        $daily = LibraryVisit::whereBetween('visit_date', [$weekStart->toDateString(), $weekStart->addDays(6)->toDateString()])->selectRaw('visit_date, COUNT(*) as total')->groupBy('visit_date')->pluck('total', 'visit_date');
        $week = collect(range(0, 6))->map(fn ($day) => ['date' => $weekStart->addDays($day), 'total' => (int) ($daily[$weekStart->addDays($day)->toDateString()] ?? 0)]);
        // Aggregate by date in SQL so this stays compatible with MySQL and SQLite.
        $yearDays = LibraryVisit::whereBetween('visit_date', [$date->startOfYear()->toDateString(), $date->endOfYear()->toDateString()])->selectRaw('visit_date, COUNT(*) as total')->groupBy('visit_date')->get();
        $months = collect(range(1, 12))->map(fn ($month) => ['date' => $date->startOfYear()->addMonths($month - 1), 'total' => (int) $yearDays->filter(fn ($row) => $row->visit_date->month === $month)->sum('total')]);
        [$start, $end] = match ($period) {
            'month' => [$date->startOfMonth(), $date->endOfMonth()],
            'year' => [$date->startOfYear(), $date->endOfYear()],
            default => [$date, $date],
        };
        $visits = LibraryVisit::whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])->orderByDesc('visited_at')->orderByDesc('id')->paginate(25)->withQueryString();
        $members = Member::with(['user', 'schoolClass'])->whereHas('user', fn ($q) => $q->where('role', 'anggota')->where('is_active', true))
            ->when($data['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('member_code', 'like', '%'.$search.'%')->orWhere('nis', 'like', '%'.$search.'%')->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->orderBy('member_code')->limit(100)->get();

        return view('visits.index', compact('date', 'period', 'totals', 'week', 'months', 'visits', 'members', 'start', 'end'));
    }

    public static function totals(CarbonImmutable $date): array
    {
        $rows = LibraryVisit::whereBetween('visit_date', [$date->startOfYear()->toDateString(), $date->endOfYear()->toDateString()])
            ->selectRaw('COUNT(*) as yearly, SUM(CASE WHEN visit_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as monthly, SUM(CASE WHEN visit_date = ? THEN 1 ELSE 0 END) as daily', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString(), $date->toDateString()])->first();

        return ['day' => (int) $rows->daily, 'month' => (int) $rows->monthly, 'year' => (int) $rows->yearly];
    }

    public function store(Request $request)
    {
        $data = $request->validate(['member_id' => ['required', 'integer', 'exists:members,id']]);
        $visit = DB::transaction(function () use ($data, $request) {
            $member = Member::with(['user', 'schoolClass'])->lockForUpdate()->findOrFail($data['member_id']);
            if ($member->user?->role !== 'anggota' || ! $member->user->is_active) {
                throw ValidationException::withMessages(['member_id' => 'Pilih siswa dengan akun aktif.']);
            }
            $now = now();

            return LibraryVisit::firstOrCreate(['member_id' => $member->id, 'visit_date' => $now->toDateString()], [
                'created_by' => $request->user()->id, 'student_name' => $member->user->name,
                'member_code' => $member->member_code, 'class_name' => $member->schoolClass?->name, 'visited_at' => $now,
            ]);
        });

        return redirect()->route('visits.index')->with('success', $visit->wasRecentlyCreated ? 'Kunjungan '.$visit->student_name.' hari ini berhasil dicatat.' : $visit->student_name.' sudah tercatat hari ini. Jumlah pengunjung tidak ditambah lagi.');
    }

    public function destroy(LibraryVisit $visit)
    {
        $visit->delete();

        return back()->with('success', 'Catatan kunjungan yang keliru telah dihapus.');
    }
}
