<nav class="workspace-nav" aria-label="Menu perpustakaan">
    <a href="{{ route('home') }}" class="workspace-nav-link"><x-icon name="book"/><span>Beranda</span></a>
    <p class="nav-caption">PERPUSTAKAAN</p>
    @foreach ($navigation as $item)
        <a href="{{ route($item['route']) }}" class="workspace-nav-link {{ request()->routeIs(...$item['patterns']) ? 'is-active' : '' }}"
           @if (request()->routeIs(...$item['patterns'])) aria-current="page" @endif>
            <x-icon :name="$item['icon']"/><span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
<div class="workspace-account">
    <a href="{{ route('account.edit') }}" class="workspace-nav-link {{ request()->routeIs('account.*') ? 'is-active' : '' }}"><x-icon name="users"/><span>Pengaturan Akun</span></a>
    <a href="{{ route('borrowing-rules') }}" class="workspace-nav-link {{ request()->routeIs('borrowing-rules') ? 'is-active' : '' }}"
       @if (request()->routeIs('borrowing-rules')) aria-current="page" @endif><x-icon name="help"/><span>Panduan Peminjaman</span></a>
    <form action="{{ route('logout') }}" method="POST">@csrf
        <button class="workspace-nav-link" type="submit"><x-icon name="logout"/><span>Keluar</span></button>
    </form>
    <div class="workspace-user">
        <span class="user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <div><strong>{{ auth()->user()->name }}</strong><small>{{ $isStaff ? 'Petugas / Admin' : 'Siswa' }}</small></div>
    </div>
</div>
