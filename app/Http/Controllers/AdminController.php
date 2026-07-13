<?php

namespace App\Http\Controllers;

use App\Models\UsageEvent;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    /**
     * Display usage totals and the recent event log.
     */
    public function index(): View
    {
        $totals = UsageEvent::totals();
        $recent = UsageEvent::latest()->limit(50)->get();

        return view('admin.index', compact('totals', 'recent'));
    }
}
