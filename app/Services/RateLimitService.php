<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class RateLimitService
{
    /**
     * Check if user has reached the rate limit.
     * 
     * @param \Illuminate\Database\Query\Builder  $query
     * @param int  $maxAttempts
     * @param int  $interval
     * @param string  $column
     * @return bool
     */
    public function rateLimitReached(Builder $query, int $maxAttempts, int $interval, string $column = 'created_at'): bool
    {
        if ($query->count() >= $maxAttempts) {
            $resets = $query->orderByDesc($column)->limit($maxAttempts)->get();
            $earliest = Carbon::parse($resets->last()->{$column});
            $hoursDiff = $earliest->diffInHours(now());
            
            if ($hoursDiff <= $interval) {
                return true;
            }
        }

        return false;
    }
}