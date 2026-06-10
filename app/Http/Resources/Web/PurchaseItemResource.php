<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'purchase_id' => $this->purchase_id,
            'product_id'  => $this->product_id,
            'quantity'    => $this->quantity,
            'price'       => $this->price,
            'subtotal'    => $this->quantity * $this->price,

            'product' => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
        ];
    }
}
