<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'transfer_id' => $this->transfer_id,
            'product_id'  => $this->product_id,
            'quantity'    => $this->quantity,

            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
