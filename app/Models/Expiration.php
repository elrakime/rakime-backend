<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUserstamps;

class Expiration extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'inventory_id',
        'reference',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
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
        });

        static::created(function (self $model) {
            $model->updateQuietly(['reference' => 'EXP-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }
}
