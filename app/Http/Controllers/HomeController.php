<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keuzedeel;

class HomeController extends Controller
{
    public function home()
    {
        $parents = Keuzedeel::whereNull('parent_id')
            ->orderBy('volgorde')
            ->get();

        return view('home', compact('parents'));
    }

    public function info(Keuzedeel $keuzedeel)
    {
        $delen = $keuzedeel->delen()->orderBy('volgorde')->get();
        return view('info', compact('keuzedeel', 'delen'));
    }
}
