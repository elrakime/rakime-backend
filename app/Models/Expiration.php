<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;
use App\Enums\ExpirationStatus;

class Expiration extends Model
{
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'branch_id',
        'inventory_id',
        'reference',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExpirationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpirationItem::class, 'expiration_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';

            if ($model->branch_id === null && $model->inventory_id !== null) {
                $model->branch_id = Inventory::whereKey($model->inventory_id)->value('branch_id');
            }
        });

        static::created(function (self $model) {
            $model->updateQuietly(['reference' => 'EXP-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ExpirationStatus::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', ExpirationStatus::PENDING);
    }
}
