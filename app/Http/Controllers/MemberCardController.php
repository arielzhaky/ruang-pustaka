<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberCardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->role === 'anggota', 403);

        return view('members.card', [
            'studentProfile' => $request->user()->member()->with('schoolClass')->first(),
        ]);
    }
}
