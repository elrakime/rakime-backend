<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class ExpirationItem extends Model
{
    use HasUserstamps;

    protected $table = 'expiration_items';

    protected $fillable = [
        'expiration_id',
        'stock_id',
        'quantity',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function expiration(): BelongsTo
    {
        return $this->belongsTo(Expiration::class, 'expiration_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
