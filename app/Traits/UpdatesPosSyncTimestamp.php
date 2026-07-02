<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait UpdatesPosSyncTimestamp
{
    /**
     * Boot the trait to hook into model events.
     */
    protected static function bootUpdatesPosSyncTimestamp()
    {
        static::saved(function ($model) {
            self::updatePosTimestamp();
        });

        static::deleted(function ($model) {
            self::updatePosTimestamp();
        });
    }

    /**
     * Update the global timestamp.
     */
    protected static function updatePosTimestamp()
    {
        Cache::put('pos_last_update', time());
    }
}
