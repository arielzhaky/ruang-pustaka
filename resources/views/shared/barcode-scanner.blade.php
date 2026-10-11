@php($isLoginScanner = ($scanMode ?? '') === 'login')
<section class="barcode-scanner mb-4" data-barcode-scanner data-max-copies="{{ config('library.max_active_loans') }}" data-mode="{{ $scanMode ?? 'lookup' }}" @if($isLoginScanner) data-login-url="{{ route('login.qr') }}" @else data-lookup-url="{{ route('barcodes.lookup') }}" @endif aria-labelledby="scan-heading-{{ $scannerId }}">
    <div class="scanner-heading"><div><p class="page-eyebrow mb-1">SCAN BARCODE / QR</p><h2 id="scan-heading-{{ $scannerId }}" class="h5 mb-1">{{ $scanTitle ?? 'Cari siswa atau eksemplar' }}</h2></div><x-icon name="barcode" :size="26"/></div>
    <p class="small text-secondary">{{ $isLoginScanner ? 'Arahkan kamera ke QR login pada kartu siswa atau pilih gambar kartu. Kamu akan langsung masuk tanpa kata sandi.' : 'Pindai barcode garis atau QR berisi kode anggota/eksemplar. Gunakan kamera, pilih gambar kartu/label, atau scan USB pada kolom kode.' }}</p>
    <form action="{{ $isLoginScanner ? route('login.qr') : route('barcodes.lookup') }}" method="{{ $isLoginScanner ? 'POST' : 'GET' }}" data-scan-form class="scanner-controls">
        @if($isLoginScanner) @csrf @endif
        <div class="scanner-code-field"><label for="scan-code-{{ $scannerId }}" class="form-label">{{ $isLoginScanner ? 'Pemindai USB / kode QR login' : 'Kode barcode' }}</label><input id="scan-code-{{ $scannerId }}" name="{{ $isLoginScanner ? 'student_qr' : 'code' }}" type="{{ $isLoginScanner ? 'password' : 'text' }}" class="form-control" maxlength="100" required autocomplete="off" spellcheck="false" placeholder="{{ $isLoginScanner ? 'Scan QR lalu tekan Enter' : 'RPS00000001 / RPC00000001' }}" data-scan-code></div>
        @if($isLoginScanner || ($scanMode ?? 'lookup') === 'visit')<input type="hidden" name="kind" value="member" data-scan-kind>
        @elseif(($scanMode ?? 'lookup') === 'return')<input type="hidden" name="kind" value="copy" data-scan-kind>
        @else<div><label for="scan-kind-{{ $scannerId }}" class="form-label">Jenis data</label><select id="scan-kind-{{ $scannerId }}" name="kind" class="form-select" data-scan-kind><option value="any">Otomatis</option><option value="member">Siswa</option><option value="copy">Eksemplar</option></select></div>@endif
        <button type="submit" class="btn btn-primary" data-scan-submit>{{ $isLoginScanner ? 'Masuk' : 'Cari / Scan' }}</button>
    </form>
    <div class="scanner-actions mt-3">
        <button type="button" class="btn btn-outline-primary" data-scan-camera>Gunakan kamera</button>
        <button type="button" class="btn btn-outline-primary" data-scan-stop hidden>Hentikan kamera</button>
        <button type="button" class="btn btn-outline-primary" data-scan-image>Pilih gambar</button>
        <input id="scan-file-{{ $scannerId }}" type="file" accept="image/*" hidden data-scan-file>
        <button type="button" class="btn btn-outline-secondary" data-scan-cancel hidden>Batalkan pembacaan</button>
    </div>
    <div id="scan-reader-{{ $scannerId }}" class="scanner-reader mt-3" data-scan-reader hidden></div>
    <div class="scanner-camera-tools mt-3" data-camera-tools hidden>
        <label hidden>Kamera<select class="form-select" data-camera-select aria-label="Pilih kamera"></select></label>
        <label hidden>Zoom <output data-zoom-label>1×</output><input type="range" data-camera-zoom aria-label="Zoom kamera"></label>
        <button type="button" class="btn btn-outline-primary" data-camera-torch aria-pressed="false" hidden>Nyalakan lampu</button>
        <p class="small text-secondary mb-0">Seluruh gambar dipindai. Fokus, zoom, dan lampu menyesuaikan dukungan kamera.</p>
    </div>
    <figure class="scanner-image-preview mt-3" data-scan-preview hidden><img alt="Gambar kartu atau label yang dipilih"><figcaption class="small text-secondary mt-2">Gambar diproses di perangkatmu. Jika ada beberapa kode, pilih salah satu hasil di bawah.</figcaption></figure>
    <div class="mt-3" data-scan-choices hidden></div>
    <p class="scanner-message mt-3 mb-0" data-scan-status role="status" aria-live="polite"></p>
    <div class="scanner-result mt-3" data-scan-result hidden></div>
    <noscript><p class="alert alert-info mt-3">Pemindai memerlukan JavaScript. Gunakan pencarian dan pilihan siswa/eksemplar yang tersedia di halaman peminjaman.</p></noscript>
</section>
@once
@php($scannerVersion = max(filemtime(public_path('css/barcodes.css')), filemtime(public_path('js/barcode-reader.js')), filemtime(public_path('js/barcode-scanner.js')), filemtime(public_path('js/barcode-worker.js'))))
@push('styles')<link rel="stylesheet" href="{{ asset('css/barcodes.css') }}?v={{ $scannerVersion }}">@endpush
@push('scripts')
<script src="{{ asset('js/barcode-reader.js') }}?v={{ $scannerVersion }}" defer></script>
<script src="{{ asset('js/barcode-scanner.js') }}?v={{ $scannerVersion }}" defer></script>
@endpush
@endonce
