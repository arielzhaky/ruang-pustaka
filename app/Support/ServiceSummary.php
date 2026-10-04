<?php

namespace App\Support;

use App\Models\BookReservation;
use App\Models\LoanItem;
use App\Models\LoanRenewal;
use App\Models\User;

class ServiceSummary
{
    public static function for(User $user): array
    {
        $items = LoanItem::query();
        $reservations = BookReservation::active();
        $renewals = LoanRenewal::where('status', 'pending');
        if ($user->role !== 'petugas') {
            $items->whereHas('loan.member', fn ($q) => $q->where('user_id', $user->id));
            $reservations->whereHas('member', fn ($q) => $q->where('user_id', $user->id));
            $renewals->whereHas('loanItem.loan.member', fn ($q) => $q->where('user_id', $user->id));
        }

        return [
            'reservations' => $reservations->count(),
            'renewals' => $renewals->count(),
            'unpaid' => (int) (clone $items)->whereNotNull('returned_at')->where('billing_status', 'unpaid')->selectRaw('COALESCE(SUM(COALESCE(fine_amount, 0) + COALESCE(replacement_amount, 0)), 0) as amount')->value('amount'),
            'reminders' => (clone $items)->whereNull('returned_at')->whereDate('due_date', '<=', today()->addDays(config('library.reminder_days')))->count(),
        ];
    }
}
