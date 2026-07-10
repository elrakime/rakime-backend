<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\StatusHistory;

trait HasStatusHistory
{
    public function initializeHasStatusHistory(): void
    {
        $this->with = array_unique(array_merge($this->with, ['statusHistories.user']));
    }
    protected static function bootHasStatusHistory(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                $model->statusHistories()->create([
                    'from_status' => $model->getOriginal('status'),
                    'to_status' => $model->status,
                    'changed_by' => auth()->id(),
                    'changed_at' => now(),
                ]);
            }
        });
    }

    public function statusHistories()
    {
        return $this->morphMany(StatusHistory::class, 'statusable');
    }
}
