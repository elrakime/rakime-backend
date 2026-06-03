<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RestockOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class RestockOrder extends Model
{
    use LogsActivity;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'branch_id',
        'reference',
        'status',
        'note',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => RestockOrderStatus::class,
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
        $query->where('status', RestockOrderStatus::DRAFT);
    }

    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', RestockOrderStatus::SUBMITTED);
    }

    public function scopeFulfilled(Builder $query): void
    {
        $query->where('status', RestockOrderStatus::FULFILLED);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', RestockOrderStatus::CANCELLED);
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
        return $this->hasMany(RestockOrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'moveable_id');
    }
}
