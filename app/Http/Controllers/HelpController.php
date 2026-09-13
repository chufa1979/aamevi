<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;

/** Videos instructivos: cada rol ve solo los suyos, listados en `config/help_videos.php`. */
class HelpController extends Controller
{
    public function index(Request $request): View
    {
        return view('ayuda', [
            'videos' => config('help_videos.'.$request->user()->role->value, []),
        ]);
    }
}
