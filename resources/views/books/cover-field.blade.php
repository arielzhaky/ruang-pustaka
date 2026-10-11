<div class="mb-4">
    <label for="cover" class="form-label">Upload cover manual (JPG, PNG, WebP; opsional)</label>
    @if ($coverBook?->cover_path)
        <div class="mb-2">@include('books.cover', ['large' => false])</div>
        <div class="form-check mb-2">
            <input type="checkbox" name="remove_cover" id="remove_cover" value="1" class="form-check-input">
            <label for="remove_cover" class="form-check-label">Hapus cover saat ini</label>
        </div>
    @endif
    <input type="file" name="cover" id="cover" class="form-control" accept="image/jpeg,image/png,image/webp" aria-describedby="cover-help">
    <div id="cover-help" class="form-text">JPG, PNG, atau WebP. Maksimal 2 MB dan 3000 × 3000 piksel. Unggahan baru menggantikan cover lama.</div>
</div>
