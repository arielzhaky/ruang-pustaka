<?php

return [
    'school_name' => 'SMPN 26 TANGERANG',
    // Termasuk pinjaman yang terlambat dan belum dikembalikan.
    'max_active_loans' => max(1, (int) env('LIBRARY_MAX_ACTIVE_LOANS', 3)),
    'fine_daily_rate' => max(0, (int) env('LIBRARY_FINE_DAILY_RATE', 2000)),
    'max_reservations' => 3,
    'reservation_hold_days' => 2,
    'renewal_days' => 7,
    'reminder_days' => 2,
    'loan_days' => max(1, (int) env('LIBRARY_LOAN_DAYS', 7)),
];
