<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUserstamps;

class ContractPayment extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'contract_id',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function installments(): BelongsToMany
    {
        return $this->belongsToMany(Installment::class, 'installment_payments')
            ->withTimestamps();
    }

    public function earlyCancelations(): HasMany
    {
        return $this->hasMany(ContractEarlyCancelation::class);
    }
}
