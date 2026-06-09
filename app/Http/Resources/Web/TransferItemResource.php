<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\Web\StockResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'transfer_id' => $this->transfer_id,
            'stock_id'    => $this->stock_id,
            'quantity'    => $this->quantity,

            'stock'   => new StockResource($this->whenLoaded('stock')),
        ];
    }
}
