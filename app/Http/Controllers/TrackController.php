<?php

namespace App\Http\Controllers;

use App\Models\UsageEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * Record a usage event from the client — metadata only, never file contents.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'in:upload,process,print'],
            'row_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'col_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'filename_hash' => ['nullable', 'string', 'max:64'],
        ]);

        UsageEvent::create([
            ...$validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(null, 204);
    }
}
