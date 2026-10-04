<div class="library-cover {{ ($large ?? false) ? 'library-cover-large' : '' }} cover-palette-{{ $coverBook->id % 4 }}">
    @if ($coverBook->cover_path)
        <img src="{{ asset('storage/'.$coverBook->cover_path) }}" alt="Cover {{ $coverBook->title }}"
             width="{{ ($large ?? false) ? 220 : 76 }}" height="{{ ($large ?? false) ? 310 : 108 }}" loading="lazy">
    @else
        <div class="library-cover-placeholder" role="img" aria-label="Sampul pengganti untuk {{ $coverBook->title }}">
            <x-icon name="book" :size="24"/>
            <span>{{ $coverBook->title }}</span>
            <small>{{ $coverBook->author }}</small>
        </div>
    @endif
</div>
