<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RestockStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Restock extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'branch_id',
        'reference',
        'status',
        'note',
        'fulfilled_at',
        'fulfilled_with_id',
        'fulfilled_with_type',
    ];

    protected function casts(): array
    {
        return [
            'status'       => RestockStatus::class,
            'created_at'   => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', RestockStatus::DRAFT);
    }

    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', RestockStatus::SUBMITTED);
    }

    public function scopeFulfilled(Builder $query): void
    {
        $query->where('status', RestockStatus::FULFILLED);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', RestockStatus::CANCELLED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestockItem::class);
    }

    public function fulfilledWith(): MorphTo
    {
        return $this->morphTo('fulfilled_with');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'moveable_id');
    }
}
