<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpirationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'expiration_id' => $this->expiration_id,
            'stock_id'      => $this->stock_id,
            'quantity'      => $this->quantity,
            'reason'        => $this->reason,

            'stock'   => new StockResource($this->whenLoaded('stock')),
        ];
    }
}
