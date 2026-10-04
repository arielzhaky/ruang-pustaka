@php($isFavorite = (bool) $favoriteBook->is_favorite)
<form action="{{ route($isFavorite ? 'favorites.destroy' : 'favorites.store', $favoriteBook) }}" method="POST" class="favorite-form">
    @csrf
    @if ($isFavorite) @method('DELETE') @endif
    <input type="hidden" name="return_to" value="{{ $returnTo ?? 'dashboard' }}">
    @if (in_array($returnTo ?? 'dashboard', ['catalog', 'detail'], true))
        @foreach (request()->only(['q', 'category_id', 'page', 'copies_page']) as $key => $value)
            @if (is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
        @endforeach
    @endif
    <button type="submit" class="favorite-button {{ $isFavorite ? 'is-saved' : '' }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" aria-label="{{ $isFavorite ? 'Hapus ' : 'Simpan ' }}{{ $favoriteBook->title }} {{ $isFavorite ? 'dari' : 'ke' }} favorit">
        <x-icon name="heart" :size="17"/><span>{{ $isFavorite ? 'Tersimpan' : 'Favorit' }}</span>
    </button>
</form>
