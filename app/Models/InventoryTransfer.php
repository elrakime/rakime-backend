<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;
use App\Enums\InventoryTransferStatus;

class InventoryTransfer extends Model
{
    use LogsActivity;
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'from_inventory_id',
        'to_inventory_id',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => InventoryTransferStatus::class,
        ];
    }

    public function scopeByUserBranches(Builder $query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->where(function (Builder $q) use ($branchIds) {
                $q->whereHas('fromInventory', fn (Builder $sub) => $sub->whereIn('branch_id', $branchIds))
                  ->orWhereHas('toInventory', fn (Builder $sub) => $sub->whereIn('branch_id', $branchIds));
            });
        }
    }

    public function branchIds(): array
    {
        return array_filter([
            $this->fromInventory?->branch_id,
            $this->toInventory?->branch_id,
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function fromInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'from_inventory_id');
    }

    public function toInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'to_inventory_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    public function restocks(): MorphMany
    {
        return $this->morphMany(Restock::class, 'fulfilled_with');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', InventoryTransferStatus::RECEIVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', InventoryTransferStatus::PENDING);
    }
}
