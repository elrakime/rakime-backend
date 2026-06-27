<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{

    protected $fillable = [
        'purchase_id',
        'reference',
        'note',
        'returned_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeApproved($query): void
    {
        $query->whereNotNull('approved_at');
    }

    public function scopePending($query): void
    {
        $query->whereNull('approved_at');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';
        });

        static::created(function (self $model) {
            $model->updateQuietly(['reference' => 'RET-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }
}
