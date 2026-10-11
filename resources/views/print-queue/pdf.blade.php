<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Kartu siswa dan label buku</title>
<style>
@page { margin:12mm; }
body { font-family:'DejaVu Sans',sans-serif; margin:0; color:#17233e; font-size:8pt; }
.sheet { position:relative; width:186mm; height:260mm; page-break-after:always; }
.sheet:last-child { page-break-after:auto; }
.card, .label { position:absolute; border:0.2mm dashed #99a2b7; overflow:hidden; }
.card { width:85.2mm; height:53.6mm; background:#202b50; color:#fff; border:0.2mm solid #354573; border-radius:3mm; }
.card-orbit { position:absolute; right:-9mm; top:-12mm; width:45mm; height:45mm; border:6mm solid #304365; border-radius:50%; }
.card-caption { position:absolute; top:11mm; left:4mm; margin:0; color:#c5d3ee; font-size:5pt; letter-spacing:.5pt; }
.card-status { position:absolute; right:4mm; top:4mm; color:#c4f0e3; background:#304e65; border-radius:2mm; padding:1mm 2mm; font-size:5pt; }
.barcode-paper { position:absolute; top:32mm; left:3mm; width:79mm; height:15mm; background:white; border-radius:1mm; }
.card.has-login .barcode-paper { width:52mm; }
.label { width:89.6mm; height:41.6mm; text-align:center; }
.card-brand, .school, .name, .meta, .barcode, .code, .note, .title, .inventory, .shelf { position:absolute; margin:0; overflow:hidden; }
.card-brand { top:3mm; left:4mm; width:77mm; height:5mm; font-size:10pt; font-weight:bold; color:#fff; }
.school { top:8mm; left:4mm; width:77mm; height:3mm; font-size:6pt; color:#d2d9ef; }
.name { top:15mm; left:4mm; width:77mm; height:8mm; font-size:10pt; font-weight:bold; line-height:1.2; }
.meta { top:23mm; left:4mm; width:77mm; height:8mm; font-size:6.5pt; line-height:1.4; }
.barcode { top:33mm; left:5mm; width:75mm; height:10mm; }
.code { top:44mm; left:4mm; width:77mm; height:4mm; font-family:'DejaVu Sans Mono',monospace; font-size:7pt; text-align:center; color:#000; }
.note { top:49mm; left:4mm; width:77mm; height:3mm; font-size:5.5pt; color:#d2d9ef; }
.login-qr { position:absolute; left:58mm; top:25mm; width:23mm; height:23mm; }
.login-qr-caption { position:absolute; left:56mm; top:48mm; width:27mm; text-align:center; font-size:5pt; }
.card.has-login .meta { width:49mm; }
.card.has-login .barcode { left:4mm; width:50mm; }
.card.has-login .code { left:4mm; width:50mm; font-size:6pt; }
.card.has-login .note { width:50mm; font-size:5pt; }
.label .school { color:#52627c; top:2mm; left:3mm; width:84mm; font-size:5.5pt; }
.title { top:6mm; left:3mm; width:84mm; height:7mm; font-size:8pt; font-weight:bold; line-height:1.2; }
.label .barcode { top:14mm; left:8mm; width:74mm; height:11mm; }
.label .code { top:25.5mm; left:3mm; width:84mm; }
.inventory { top:30mm; left:3mm; width:84mm; height:5.5mm; font-size:6pt; line-height:1.2; }
.shelf { top:37mm; left:3mm; width:84mm; height:3mm; font-size:5.5pt; }
</style></head><body>
@foreach($members->chunk(8) as $sheet)
<div class="sheet">
@foreach($sheet->values() as $index=>$member)
@php($hasLogin = $member->login_qr_hash && $member->user->is_active)
<div class="card {{ $hasLogin ? 'has-login' : '' }}" style="left:{{ ($index % 2)*96 }}mm;top:{{ intdiv($index,2)*59 }}mm">
    <div class="card-orbit"></div><div class="barcode-paper"></div>
    <div class="card-brand">Ruang Pustaka</div>
    <div class="card-status">{{ $member->user->is_active ? 'Anggota aktif' : 'Nonaktif' }}</div>
    <div class="card-caption">KARTU ANGGOTA PERPUSTAKAAN</div><div class="school">{{ config('library.school_name') }}</div>
    <div class="name" @if(mb_strlen($member->user->name)>45) style="font-size:7.5pt" @endif>{{ $member->user->name }}</div>
    <div class="meta">{{ $member->schoolClass?->name ?? 'Kelas belum diisi' }} · {{ $member->user->is_active ? 'Aktif' : 'NONAKTIF' }}<br>{{ $member->member_code }}</div>
    <img class="barcode" alt="Barcode siswa" src="data:image/svg+xml;base64,{{ base64_encode(\App\Support\LibraryBarcode::svg(\App\Support\LibraryBarcode::member($member))) }}">
    <div class="code">{{ \App\Support\LibraryBarcode::member($member) }}</div><div class="note">Tunjukkan kartu kepada petugas perpustakaan.</div>
    @if($hasLogin)
        <img class="login-qr" alt="QR login" src="data:image/svg+xml;base64,{{ base64_encode(\App\Support\StudentLoginQr::svg($member)) }}">
        <div class="login-qr-caption">QR LOGIN · RAHASIA</div>
    @endif
</div>
@endforeach
</div>
@endforeach
@foreach($copies->chunk(10) as $sheet)
<div class="sheet">
@foreach($sheet->values() as $index=>$copy)
<div class="label" style="left:{{ ($index % 2)*96 }}mm;top:{{ intdiv($index,2)*47 }}mm">
    <div class="school">RUANG PUSTAKA · {{ config('library.school_name') }}</div><div class="title">{{ $copy->book->title }}</div>
    <img class="barcode" alt="Barcode eksemplar" src="data:image/svg+xml;base64,{{ base64_encode(\App\Support\LibraryBarcode::svg(\App\Support\LibraryBarcode::copy($copy))) }}">
    <div class="code">{{ \App\Support\LibraryBarcode::copy($copy) }}</div><div class="inventory">{{ $copy->inventory_code }}</div>
    <div class="shelf">{{ $copy->shelf_location ?? 'Rak belum diisi' }}{{ $copy->archived_at || $copy->book->archived_at ? ' · NONAKTIF' : '' }}</div>
</div>
@endforeach
</div>
@endforeach
</body></html>
