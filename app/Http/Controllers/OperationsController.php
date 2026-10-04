<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LoanItem;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function overdue(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'class_id' => ['nullable', 'integer', 'exists:school_classes,id'], 'page' => ['nullable', 'integer', 'min:1']]);
        $items = LoanItem::with(['loan.member.user', 'loan.member.schoolClass', 'bookCopy.book'])->whereNull('returned_at')->whereDate('due_date', '<', today())
            ->when($data['q'] ?? null, fn ($q, $search) => $q->whereHas('loan.member', fn ($m) => $m->where(fn ($m) => $m->where('member_code', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")))))
            ->when($data['class_id'] ?? null, fn ($q, $id) => $q->whereHas('loan.member', fn ($m) => $m->where('school_class_id', $id)))
            ->orderBy('due_date')->orderBy('id')->paginate(15)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();

        return view('operations.overdue', compact('items', 'classes'));
    }

    public function activity(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $logs = ActivityLog::when($data['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('actor_name', 'like', "%{$search}%")->orWhere('action', 'like', "%{$search}%")))->latest('id')->paginate(25)->withQueryString();

        return view('operations.activity', compact('logs'));
    }

    public function reminders(Request $request)
    {
        abort_unless($request->user()->role === 'anggota', 403);
        $items = LoanItem::with(['bookCopy.book', 'loan'])->whereHas('loan.member', fn ($q) => $q->where('user_id', $request->user()->id))
            ->whereNull('returned_at')->whereDate('due_date', '<=', today()->addDays(config('library.reminder_days')))->orderBy('due_date')->get();

        return view('operations.reminders', compact('items'));
    }
}
