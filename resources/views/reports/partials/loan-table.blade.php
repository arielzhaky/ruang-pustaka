<table class="table align-middle report-table">
    <thead>
        <tr>
            <th scope="col">No.</th>
            <th scope="col">Kode peminjaman</th>
            <th scope="col">Siswa</th>
            <th scope="col">Buku / eksemplar</th>
            <th scope="col">Tanggal pinjam</th>
            <th scope="col">Batas kembali</th>
            <th scope="col">Tanggal selesai</th>
            <th scope="col">Status</th>
            <th scope="col">Keterlambatan</th>
            <th scope="col">Denda / penggantian</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $item)
            <tr>
                <td>{{ $rowStart + $loop->index }}</td>
                <td class="text-break">{{ $item->loan_code }}</td>
                <td>{{ $item->student_name }}</td>
                <td>{{ $item->book_title }}<br><small class="text-secondary">{{ $item->inventory_code }}</small></td>
                <td>{{ \Carbon\Carbon::parse($item->borrowed_at)->format('d/m/Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->due_date)->format('d/m/Y') }}</td>
                <td>{{ $item->returned_at ? \Carbon\Carbon::parse($item->returned_at)->format('d/m/Y') : 'Belum kembali' }}</td>
                <td>
                    @if ($item->return_condition === 'hilang')
                        <span class="badge text-bg-danger">Hilang</span>
                    @elseif ($item->status_key === 'returned')
                        <span class="badge text-bg-success">Dikembalikan</span>
                    @elseif ($item->status_key === 'late')
                        <span class="badge text-bg-danger">Terlambat</span>
                    @else
                        <span class="badge text-bg-primary">Dipinjam</span>
                    @endif
                </td>
                <td>{{ $item->late_days }} hari</td>
                <td>@include('shared.loan-charge', ['chargeItem' => $item])</td>
            </tr>
        @empty
            <tr><td colspan="10" class="text-center text-secondary py-4">Tidak ada peminjaman untuk pilihan ini.</td></tr>
        @endforelse
    </tbody>
</table>
