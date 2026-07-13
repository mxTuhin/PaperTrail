<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class AppController extends Controller
{
    public function index(): View
    {
        return view('app.index');
    }

    public function print(): View
    {
        return view('app.print');
    }
}
