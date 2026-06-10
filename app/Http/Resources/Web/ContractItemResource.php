<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'contract_id'                => $this->contract_id,
            'product_id'                 => $this->product_id,
            'stock_id'                   => $this->stock_id,
            'batch_id'                   => $this->batch_id,
            'price_id'                   => $this->price_id,
            'quantity'                   => $this->quantity,
            'unit_price'                 => $this->unit_price,
            'unit_price_snapshot'        => $this->unit_price_snapshot,
            'installment_price_snapshot' => $this->installment_price_snapshot,

            'product' => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
            'stock' => new StockResource($this->whenLoaded('stock')),
            'batch' => new BatchResource($this->whenLoaded('batch')),
            'price' => new PriceResource($this->whenLoaded('price')),
        ];
    }
}
