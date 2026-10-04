@php($charge = \App\Support\LoanCharges::total($chargeItem, $generatedAt ?? $today ?? null))
<span class="text-nowrap">Rp{{ number_format($charge, 0, ',', '.') }}</span>
@if (! $chargeItem->returned_at)
    <small class="d-block text-secondary">Perkiraan denda</small>
@elseif ($chargeItem->return_condition === 'hilang')
    <small class="d-block text-secondary">Penggantian buku</small>
@elseif ($chargeItem->fine_amount === null)
    <small class="d-block text-secondary">Denda belum dicatat</small>
@endif
