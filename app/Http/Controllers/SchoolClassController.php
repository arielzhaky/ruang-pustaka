<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Member;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return view('school_classes.index', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', 'unique:school_classes,name'],
            'grade_level' => ['required', 'in:7,8,9'],
        ]);

        $schoolClass = new SchoolClass();
        $schoolClass->name = $data['name'];
        $schoolClass->grade_level = $data['grade_level'];
        $schoolClass->is_active = true;
        $schoolClass->save();

        return redirect()->route('school-classes.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('school_classes', 'name')->ignore($schoolClass),
            ],
            'grade_level' => ['required', 'in:7,8,9'],
            'is_active' => ['required', 'boolean'],
        ]);

        $schoolClass->name = $data['name'];
        $schoolClass->grade_level = $data['grade_level'];
        $schoolClass->is_active = (bool) $data['is_active'];
        $schoolClass->save();

        return redirect()->route('school-classes.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }
        public function destroy(SchoolClass $schoolClass)
    {
    $hasStudents = Member::where('school_class_id', $schoolClass->id)
        ->exists();

    if ($hasStudents) {
        return redirect()->route('school-classes.index')
            ->with('error', 'Kelas tidak bisa dihapus karena masih digunakan siswa.');
    }

    $schoolClass->delete();

    return redirect()->route('school-classes.index')
        ->with('success', 'Kelas berhasil dihapus.');
    }

}