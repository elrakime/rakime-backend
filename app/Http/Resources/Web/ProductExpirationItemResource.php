<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductExpirationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'expiration_id' => $this->expiration_id,
            'product_id'    => $this->product_id,
            'stock_id'      => $this->stock_id,
            'batch_id'      => $this->batch_id,
            'quantity'      => $this->quantity,
            'reason'        => $this->reason,

            'product' => new ProductResource($this->whenLoaded('product')),
            'stock'   => new StockResource($this->whenLoaded('stock')),
            'batch'   => new BatchResource($this->whenLoaded('batch')),
        ];
    }
}
