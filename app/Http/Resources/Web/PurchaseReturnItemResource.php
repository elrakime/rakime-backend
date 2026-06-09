<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'purchase_return_id' => $this->purchase_return_id,
            'purchase_item_id'   => $this->purchase_item_id,
            'quantity'           => $this->quantity,
            'reason'             => $this->reason,

            'purchase_item' => $this->whenLoaded('purchaseItem', fn () => [
                'id'       => $this->purchaseItem->id,
                'quantity' => $this->purchaseItem->quantity,
                'price'    => $this->purchaseItem->price,
                'product'  => $this->purchaseItem->relationLoaded('product') ? [
                    'id'   => $this->purchaseItem->product->id,
                    'name' => $this->purchaseItem->product->name,
                ] : null,
            ]),
        ];
    }
}
