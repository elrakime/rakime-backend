<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class InstallmentPayment extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'installment_id',
        'amount',
        'received_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'integer',
        ];
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
