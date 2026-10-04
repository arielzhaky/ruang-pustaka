<div class="mb-3">
    <label for="replacement_price" class="form-label">Harga buku / biaya penggantian (Rp)</label>
    <input type="number" class="form-control" id="replacement_price" name="replacement_price" min="1" max="100000000" step="1"
           value="{{ old('replacement_price', $priceBook?->replacement_price) }}" aria-describedby="price-help">
    <div class="form-text" id="price-help">Isi nominal rupiah tanpa titik. Wajib tersedia saat mencatat buku hilang; biaya penggantian tidak ditambah denda keterlambatan.</div>
</div>
