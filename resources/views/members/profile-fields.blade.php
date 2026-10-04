<div class="col-md-6">
    <label for="gender" class="form-label">Jenis kelamin</label>
    <select id="gender" name="gender" class="form-select" required>
        <option value="">Pilih jenis kelamin</option>
        <option value="laki-laki" @selected(old('gender', $student?->gender) === 'laki-laki')>Laki-laki</option>
        <option value="perempuan" @selected(old('gender', $student?->gender) === 'perempuan')>Perempuan</option>
    </select>
</div>
<div class="col-md-6">
    <label for="age" class="form-label">Usia (tahun)</label>
    <input id="age" name="age" type="number" min="1" max="120" step="1" required
           class="form-control" value="{{ old('age', $student?->age) }}" aria-describedby="age-help">
    <div id="age-help" class="form-text">Isi usia saat ini. Perbarui saat data siswa berubah.</div>
</div>
