<?php
// Isolated browser harness: renders the real scanner without database access.
$root = dirname(__DIR__, 2);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/scanner-test') return false;
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8099');
$mode = in_array($_GET['mode'] ?? '', ['loan', 'visit', 'return', 'login']) ? $_GET['mode'] : 'lookup';
echo Illuminate\Support\Facades\Blade::render(<<<'BLADE'
<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="test-token"><style>body{font-family:sans-serif;margin:20px}button,input,select{padding:8px}button{cursor:pointer}.mt-3{margin-top:16px}</style></head><body>
@include('shared.barcode-scanner', ['scannerId' => 'test', 'scanMode' => $mode])
@stack('styles')
<form id="loan-form"><select id="member_id"><option value="">Pilih siswa</option></select><div data-loan-copy-list></div><button data-loan-submit disabled>Simpan</button></form><select id="visit-member-id"><option value="">Pilih siswa</option></select>
@stack('scripts')
</body></html>
BLADE, ['mode' => $mode]);
