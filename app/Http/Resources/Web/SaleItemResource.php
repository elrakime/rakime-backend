<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'sale_id'    => $this->sale_id,
            'product_id' => $this->product_id,
            'stock_id'   => $this->stock_id,
            'quantity'   => $this->quantity,
            'price'      => $this->price,

            'product' => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
            'stock' => new StockResource($this->whenLoaded('stock')),
        ];
    }
}
