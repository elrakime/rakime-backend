<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUserstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDrawLock extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'account_id',
        'month',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
