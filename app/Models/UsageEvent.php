<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event', 'ip_address', 'user_agent', 'row_count', 'col_count', 'filename_hash'])]
class UsageEvent extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_count' => 'integer',
            'col_count' => 'integer',
        ];
    }

    /**
     * Get usage totals grouped by event type.
     *
     * @return array<string, int>
     */
    public static function totals(): array
    {
        return static::selectRaw('event, count(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();
    }
}
