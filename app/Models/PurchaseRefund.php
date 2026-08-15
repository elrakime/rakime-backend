<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseRefundStatus;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRefund extends Model
{
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'purchase_id',
        'purchase_return_id',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PurchaseRefundStatus::class,
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }
}
