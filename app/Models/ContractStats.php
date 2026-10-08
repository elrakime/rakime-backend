<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractStats extends Model
{
    protected $table = 'contract_stats';

    protected $fillable = [
        'month',
        'branch_id',
        'account_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'data'  => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
