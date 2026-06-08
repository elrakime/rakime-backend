<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'purchase_id' => $this->purchase_id,
            'reference'   => $this->reference,
            'returned_at' => $this->returned_at,
            'created_at'  => $this->created_at,

            'purchase' => $this->whenLoaded('purchase', fn () => [
                'id'      => $this->purchase->id,
                'reference' => $this->purchase->reference,
            ]),
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
