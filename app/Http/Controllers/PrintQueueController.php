<?php

namespace App\Http\Controllers;

use App\Models\BookCopy;
use App\Models\Member;
use App\Models\PrintQueueItem;
use App\Models\User;
use App\Support\StudentLoginQr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrintQueueController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['type' => ['nullable', 'in:members,copies'], 'q' => ['nullable', 'string', 'max:100']]);
        $type = $data['type'] ?? 'members';
        $search = $data['q'] ?? '';
        $queue = PrintQueueItem::with(['member.user', 'member.schoolClass', 'bookCopy.book'])->where('user_id', $request->user()->id)->orderBy('id')->get();
        $candidates = $type === 'members'
            ? Member::with(['user', 'schoolClass'])->whereHas('user', fn ($q) => $q->where('role', 'anggota'))->when($search, fn ($q) => $q->where(fn ($q) => $q->where('member_code', 'like', '%'.$search.'%')->orWhere('nis', 'like', '%'.$search.'%')->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))->orderBy('member_code')
            : BookCopy::with('book')->whereNull('archived_at')->whereHas('book', fn ($q) => $q->whereNull('archived_at'))->when($search, fn ($q) => $q->where(fn ($q) => $q->where('inventory_code', 'like', '%'.$search.'%')->orWhereHas('book', fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('author', 'like', '%'.$search.'%'))))->orderBy('book_id')->orderBy('inventory_code');
        $candidates = $candidates->paginate(25)->withQueryString();

        return view('print-queue.index', compact('queue', 'candidates', 'type', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['type' => ['required', 'in:members,copies'], 'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['required', 'integer', 'distinct', 'min:1']]);
        $column = $data['type'] === 'members' ? 'member_id' : 'book_copy_id';
        $query = $data['type'] === 'members' ? Member::whereHas('user', fn ($q) => $q->where('role', 'anggota')) : BookCopy::whereNull('archived_at')->whereHas('book', fn ($q) => $q->whereNull('archived_at'));
        if ($query->whereIn('id', $data['ids'])->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'Ada siswa/eksemplar yang tidak tersedia. Muat ulang pilihan.']);
        }
        DB::transaction(function () use ($request, $data, $column) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $queue = PrintQueueItem::where('user_id', $request->user()->id);
            $existing = (clone $queue)->whereIn($column, $data['ids'])->count();
            if ($queue->count() + count($data['ids']) - $existing > 200) {
                throw ValidationException::withMessages(['ids' => 'Antrean maksimal 200 item. Cetak dan hapus sebagian antrean dahulu.']);
            }
            foreach ($data['ids'] as $id) {
                PrintQueueItem::firstOrCreate(['user_id' => $request->user()->id, $column => $id]);
            }
        });

        return redirect()->route('print-queue.index', ['type' => $data['type']])->with('success', 'Pilihan masuk antrean cetak. Item yang sama tidak ditambahkan dua kali.');
    }

    private function selected(Request $request)
    {
        $data = $request->validate(['queue_ids' => ['required', 'array', 'min:1', 'max:200'], 'queue_ids.*' => ['required', 'integer', 'distinct', 'min:1']]);
        $items = PrintQueueItem::with(['member.user', 'member.schoolClass', 'bookCopy.book'])->where('user_id', $request->user()->id)->whereIn('id', $data['queue_ids'])->orderBy('id')->get();
        abort_unless($items->count() === count($data['queue_ids']), 404);

        return $items;
    }

    public function destroy(Request $request)
    {
        $items = $this->selected($request);
        PrintQueueItem::where('user_id', $request->user()->id)->whereIn('id', $items->modelKeys())->delete();

        return back()->with('success', 'Item terpilih dihapus dari antrean cetak.');
    }

    public function download(Request $request)
    {
        $items = $this->selected($request);
        $members = $items->whereNotNull('member_id')->pluck('member');
        $copies = $items->whereNotNull('book_copy_id')->pluck('bookCopy');
        abort_if($members->contains(fn ($member) => $member->user?->role !== 'anggota'), 422, 'Ada akun yang bukan lagi siswa. Hapus dari antrean lalu coba kembali.');
        $members = $members->map(fn ($member) => $member->user->is_active ? StudentLoginQr::issue($member) : $member);

        return Pdf::loadView('print-queue.pdf', compact('members', 'copies'))
            ->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->download('kartu-dan-label-'.now()->format('Y-m-d-His').'.pdf')
            ->header('Cache-Control', 'no-store, private');
    }
}
