<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'purchase_id' => $this->purchase_id,
            'reference'   => $this->reference,
            'note'        => $this->note,
            'returned_at' => $this->returned_at,
            'created_at'  => $this->created_at,

            'purchase' => $this->whenLoaded('purchase', fn () => [
                'id'      => $this->purchase->id,
                'reference' => $this->purchase->reference,
            ]),
            'items' => ReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
