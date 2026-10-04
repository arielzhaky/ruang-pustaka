<?php

namespace App\Http\Controllers;

use App\Models\FinePayment;
use App\Models\LoanItem;
use App\Support\LoanCharges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', 'in:unverified,unpaid,paid'], 'page' => ['nullable', 'integer', 'min:1']]);
        $items = LoanItem::with(['loan.member.user', 'bookCopy.book', 'payment'])
            ->whereNotNull('returned_at')->where(fn ($q) => $q->where('fine_amount', '>', 0)->orWhere('replacement_amount', '>', 0));
        if ($request->user()->role !== 'petugas') {
            abort_unless($request->user()->role === 'anggota', 403);
            $items->whereHas('loan.member', fn ($q) => $q->where('user_id', $request->user()->id));
        }
        $unpaid = (int) (clone $items)->where('billing_status', 'unpaid')->selectRaw('COALESCE(SUM(COALESCE(fine_amount, 0) + COALESCE(replacement_amount, 0)), 0) as amount')->value('amount');
        $unverified = (clone $items)->where('billing_status', 'unverified')->count();
        $items = $items->when($filters['status'] ?? null, fn ($q, $status) => $q->where('billing_status', $status))->latest('id')->paginate(15)->withQueryString();

        return view('billing.index', compact('items', 'unpaid', 'unverified'));
    }

    public function confirm(Request $request, LoanItem $loanItem)
    {
        $request->validate(['confirmation' => ['required', 'accepted']]);
        DB::transaction(function () use ($loanItem) {
            $item = LoanItem::lockForUpdate()->findOrFail($loanItem->id);
            if (! $item->returned_at || $item->billing_status !== 'unverified' || LoanCharges::total($item) <= 0) {
                throw ValidationException::withMessages(['billing' => 'Tagihan ini tidak memerlukan konfirmasi.']);
            }
            $item->forceFill(['billing_status' => 'unpaid'])->save();
        }, 3);

        return back()->with('success', 'Status tagihan lama dikonfirmasi belum dibayar.');
    }

    public function pay(Request $request, LoanItem $loanItem)
    {
        $data = $request->validate(['method' => ['required', 'in:cash,transfer,historical'], 'notes' => ['nullable', 'string', 'max:500'], 'confirmation' => ['required', 'accepted']]);
        $payment = DB::transaction(function () use ($loanItem, $request, $data) {
            $item = LoanItem::lockForUpdate()->findOrFail($loanItem->id);
            if ($existing = $item->payment()->first()) {
                return $existing;
            }
            $legacy = $data['method'] === 'historical';
            if (! $item->returned_at || LoanCharges::total($item) <= 0 || $item->billing_status !== ($legacy ? 'unverified' : 'unpaid')) {
                throw ValidationException::withMessages(['billing' => 'Pembayaran hanya dapat dicatat untuk tagihan tetap dengan status yang sesuai.']);
            }
            $payment = FinePayment::create(['loan_item_id' => $item->id, 'receipt_code' => (string) Str::uuid(),
                'amount' => LoanCharges::total($item), 'method' => $data['method'], 'notes' => $data['notes'] ?? null,
                'received_by' => $request->user()->id, 'paid_at' => now()]);
            $item->forceFill(['billing_status' => 'paid'])->save();

            return $payment;
        }, 3);

        return redirect()->route('billing.receipt', $payment)->with('success', 'Pelunasan berhasil dicatat.');
    }

    public function receipt(Request $request, FinePayment $payment)
    {
        $payment->load(['loanItem.loan.member.user', 'loanItem.bookCopy.book', 'receivedBy']);
        abort_unless($request->user()->role === 'petugas' || $payment->loanItem->loan->member->user_id === $request->user()->id, 404);

        return view('billing.receipt', compact('payment'));
    }
}
