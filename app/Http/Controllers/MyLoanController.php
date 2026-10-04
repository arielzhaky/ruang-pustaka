<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MyLoanController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()->role === 'anggota', 403);

        $loans = DB::table('loan_items as li')
            ->join('loans as l', 'l.id', '=', 'li.loan_id')
            ->join('members as m', 'm.id', '=', 'l.member_id')
            ->join('book_copies as bc', 'bc.id', '=', 'li.book_copy_id')
            ->join('books as b', 'b.id', '=', 'bc.book_id')
            ->where('m.user_id', auth()->id())
            ->select(
                'l.loan_code',
                'l.borrowed_at',
                'li.id as item_id',
                'li.due_date',
                'li.returned_at',
                'li.return_condition', 'li.fine_daily_rate', 'li.fine_amount', 'li.replacement_amount',
                'b.title',
                'bc.inventory_code'
            )
            ->orderByDesc('l.borrowed_at')
            ->paginate(10);

        $renewals = \App\Models\LoanRenewal::whereIn('loan_item_id', $loans->pluck('item_id'))->get()->keyBy('loan_item_id');

        return view('my-loans.index', compact('loans', 'renewals'));
    }
}
