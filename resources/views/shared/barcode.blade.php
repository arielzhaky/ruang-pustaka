<div class="library-barcode" role="img" aria-label="Barcode {{ $barcodeCode }}">
    <div class="barcode-graphic">{!! \App\Support\LibraryBarcode::svg($barcodeCode) !!}</div>
    <span class="barcode-value">{{ $barcodeCode }}</span>
</div>
@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/barcodes.css') }}?v={{ filemtime(public_path('css/barcodes.css')) }}">@endpush
@endonce
