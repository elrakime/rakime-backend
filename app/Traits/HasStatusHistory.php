<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\StatusHistory;

trait HasStatusHistory
{
    protected static function bootHasStatusHistory(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                $model->statusHistories()->create([
                    'from_status' => $model->getOriginal('status'),
                    'to_status'   => $model->status,
                    'changed_by'  => auth()->id(),
                ]);
            }
        });
    }

    public function statusHistories()
    {
        return $this->morphMany(StatusHistory::class, 'statusable');
    }
}
