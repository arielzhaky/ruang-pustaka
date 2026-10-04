@props(['name' => 'book', 'size' => 18])
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('dashboard') <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> @break
    @case('users') <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v2"/> @break
    @case('category') <path d="m3 3 8 .1L21 13l-8 8L3 11Z"/><circle cx="7" cy="7" r="1"/> @break
    @case('class') <path d="M4 21V3h16v18M2 21h20M8 7h1m6 0h1M8 11h1m6 0h1M10 21v-6h4v6"/> @break
    @case('loan') <rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h6m-6 4h3"/> @break
    @case('report') <path d="M4 20V10m5 10V6m5 14v-8m5 8V3M2 21h20"/> @break
    @case('help') <circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4m0 3h.01"/> @break
    @case('logout') <path d="M9 4H4v16h5m-1-8h13m-4-4 4 4-4 4"/> @break
    @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
    @case('arrow') <path d="M5 12h14m-5-5 5 5-5 5"/> @break
    @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
    @case('heart') <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/> @break
    @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/> @break
    @case('card') <rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 16a3 3 0 0 1 6 0m3-6h4m-4 4h4"/> @break
    @case('print') <path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v7H6Zm11-2h1"/> @break
    @default <path d="M12 5c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V4c-4-1-7-1-10 1Zm0 0v15M5 8h3m8 0h3M5 12h3m8 0h3"/>
@endswitch
</svg>
