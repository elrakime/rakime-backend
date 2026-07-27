<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'sale_return_id'  => $this->sale_return_id,
            'sale_item_id'    => $this->sale_item_id,
            'quantity'        => $this->quantity,
            'reason'          => $this->reason,

            'sale_item' => $this->whenLoaded('saleItem', fn () => [
                'id'       => $this->saleItem->id,
                'quantity' => $this->saleItem->quantity,
                'price'    => $this->saleItem->price,
                'product'  => $this->saleItem->relationLoaded('product') ? [
                    'id'   => $this->saleItem->product->id,
                    'name' => $this->saleItem->product->name,
                ] : null,
                'stock' => $this->saleItem->relationLoaded('stock') ? [
                    'id'   => $this->saleItem->stock->id,
                    'name' => $this->saleItem->stock->name,
                ] : null,
            ]),
        ];
    }
}
