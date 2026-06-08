<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{

    protected $fillable = [
        'installment_id',
        'amount',
        'received_by',
        'paid_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'integer',
            'paid_at' => 'datetime',
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
