<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class FinancialRecord extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'client_id',
        'contract_id',
        'revenues',
        'expenses',
        'income',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'revenues' => 'array',
            'expenses' => 'array',
            'income'   => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
