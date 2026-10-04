<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class LoanCharges
{
    public static function lateDays(object $item, ?CarbonInterface $asOf = null): int
    {
        $due = CarbonImmutable::parse($item->due_date)->startOfDay();
        $end = CarbonImmutable::parse($item->returned_at ?? $asOf ?? now())->startOfDay();

        return max(0, (int) $due->diffInDays($end));
    }

    public static function lateFine(object $item, ?CarbonInterface $asOf = null): int
    {
        if ($item->returned_at !== null) {
            // Jangan menerapkan denda baru ke pengembalian lama.
            return (int) ($item->fine_amount ?? 0);
        }

        return self::lateDays($item, $asOf) * (int) $item->fine_daily_rate;
    }

    public static function total(object $item, ?CarbonInterface $asOf = null): int
    {
        return self::lateFine($item, $asOf) + (int) ($item->replacement_amount ?? 0);
    }
}
