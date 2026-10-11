<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'status' => ['nullable', 'in:active,inactive'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'q.string' => 'Kata pencarian harus berupa teks.',
            'q.max' => 'Kata pencarian maksimal 100 karakter.',
            'class_id.integer' => 'Kelas tidak valid.',
            'class_id.exists' => 'Kelas tidak ditemukan.',
            'status.in' => 'Status siswa tidak valid.',
            'page.integer' => 'Nomor halaman tidak valid.',
            'page.min' => 'Nomor halaman minimal 1.',
        ]);
        $filters['q'] = trim($filters['q'] ?? '');
        $studentQuery = Member::whereHas('user', fn ($query) => $query->where('role', 'anggota'));
        $memberStats = [
            'total' => (clone $studentQuery)->count(),
            'active' => (clone $studentQuery)->whereHas('user', fn ($query) => $query->where('is_active', true))->count(),
            'inactive' => (clone $studentQuery)->whereHas('user', fn ($query) => $query->where('is_active', false))->count(),
        ];
        $members = (clone $studentQuery)->with(['user', 'schoolClass'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $search = '%'.$filters['q'].'%';
                $query->where(fn ($query) => $query->where('nis', 'like', $search)
                    ->orWhere('member_code', 'like', $search)
                    ->orWhereHas('user', fn ($user) => $user->where(fn ($user) => $user
                        ->where('name', 'like', $search)->orWhere('email', 'like', $search))));
            })
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('school_class_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query
                ->whereHas('user', fn ($user) => $user->where('is_active', $status === 'active')))
            ->latest()->orderByDesc('id')
            ->paginate(10)->appends($filters);

        $classes = SchoolClass::where('is_active', true)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $filterClasses = SchoolClass::orderBy('grade_level')->orderBy('name')->get();

        return view('members.index', compact('members', 'classes', 'filterClasses', 'memberStats', 'filters'));
    }

    public function store(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'nis' => ['nullable', 'string', 'max:30', 'unique:members,nis'],
            'member_code' => [
                'required', 'string', 'max:30', 'unique:members,member_code',
            ],
            'school_class_id' => [
                'required',
                Rule::exists('school_classes', 'id')->where('is_active', 1),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'in:laki-laki,perempuan'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
        ], [
            'gender.required' => 'Pilih jenis kelamin siswa.',
            'gender.in' => 'Jenis kelamin harus laki-laki atau perempuan.',
            'age.required' => 'Usia siswa wajib diisi.',
            'age.integer' => 'Usia harus berupa angka bulat.',
            'age.min' => 'Usia minimal 1 tahun.',
            'age.max' => 'Usia maksimal 120 tahun.',
            'nis.unique' => 'NIS sudah digunakan siswa lain.',
        ]);

        DB::transaction(function () use ($data) {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->role = 'anggota';
            $user->is_active = true;
            $user->save();

            $member = new Member;
            $member->user_id = $user->id;
            $member->member_code = $data['member_code'];
            $member->nis = $data['nis'] ?? null;
            $member->gender = $data['gender'];
            $member->age = $data['age'];
            $member->school_class_id = $data['school_class_id'];
            $member->phone = $data['phone'] ?? null;
            $member->save();
        });

        return redirect()->route('members.index')
            ->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function edit(Member $member)
    {
        $member->load('user');

        abort_unless($member->user?->role === 'anggota', 404);

        $classes = SchoolClass::query()
            ->where(function ($query) use ($member) {
                $query->where('is_active', true)
                    ->orWhere('id', $member->school_class_id);
            })
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return view('members.edit', compact('member', 'classes'));
    }

    public function update(Request $request, Member $member)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $member->load('user');

        abort_unless($member->user?->role === 'anggota', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($member->user_id),
            ],
            'nis' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('members', 'nis')->ignore($member->id),
            ],
            'member_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('members', 'member_code')->ignore($member->id),
            ],
            'school_class_id' => [
                'required',
                Rule::exists('school_classes', 'id')
                    ->where(function ($query) use ($member) {
                        $query->where('is_active', 1)
                            ->orWhere('id', $member->school_class_id);
                    }),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', 'in:laki-laki,perempuan'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'gender.required' => 'Pilih jenis kelamin siswa.',
            'gender.in' => 'Jenis kelamin harus laki-laki atau perempuan.',
            'age.required' => 'Usia siswa wajib diisi.',
            'age.integer' => 'Usia harus berupa angka bulat.',
            'age.min' => 'Usia minimal 1 tahun.',
            'age.max' => 'Usia maksimal 120 tahun.',
            'nis.unique' => 'NIS sudah digunakan siswa lain.',
        ]);

        DB::transaction(function () use ($member, $data) {
            $user = $member->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->is_active = (bool) $data['is_active'];

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            $member->nis = $data['nis'] ?? null;
            $member->gender = $data['gender'];
            $member->age = $data['age'];
            $member->member_code = $data['member_code'];
            $member->school_class_id = $data['school_class_id'];
            $member->phone = $data['phone'] ?? null;
            $member->save();
        });

        return redirect()->route('members.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Member $member)
    {
        $member->load('user');

        abort_unless($member->user?->role === 'anggota', 404);

        $hasLoans = DB::table('loans')
            ->where('member_id', $member->id)
            ->exists();

        if ($hasLoans) {
            return redirect()->route('members.index')
                ->with(
                    'error',
                    'Siswa tidak bisa dihapus karena sudah memiliki riwayat peminjaman. Nonaktifkan akunnya melalui tombol Ubah.'
                );
        }

        DB::transaction(function () use ($member) {
            $user = $member->user;

            $member->delete();
            $user->delete();
        });

        return redirect()->route('members.index')
            ->with('success', 'Data siswa berhasil dihapus.');
    }
}
