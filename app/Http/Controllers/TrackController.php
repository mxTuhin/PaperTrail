<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackController extends Controller
{
    /**
     * Record a usage event (metadata only — never file contents).
     *
     * Full validation & persistence is implemented in Step 06.
     */
    public function store(Request $request): Response
    {
        return response()->noContent();
    }
}
