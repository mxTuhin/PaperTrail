<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    /**
     * Show usage totals and the recent event log.
     *
     * Full aggregation is implemented in Step 06.
     */
    public function index(): View
    {
        return view('admin.index');
    }
}
