<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class ContractEarlyCancelation extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'contract_id',
        'payment_id',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'end_date' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ContractPayment::class);
    }
}
