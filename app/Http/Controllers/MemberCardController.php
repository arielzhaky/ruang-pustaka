<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberCardController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->role === 'anggota', 403);

        return response()->view('members.card', [
            'studentProfile' => $request->user()->member()->with('schoolClass')->first(),
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }
}
