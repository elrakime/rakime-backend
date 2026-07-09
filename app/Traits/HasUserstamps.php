<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasUserstamps
{
    public function initializeHasUserstamps(): void
    {
        $this->with = array_unique(array_merge($this->with, ['creator', 'updater']));
    }

    public static function bootHasUserstamps(): void
    {
        static::creating(function ($model) {
            $userId = Auth::id();

            if ($userId) {
                $model->created_by = $userId;
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = Auth::id();

            if ($userId) {
                $model->updated_by = $userId;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
