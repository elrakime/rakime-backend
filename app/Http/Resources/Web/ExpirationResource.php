<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpirationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'inventory_id' => $this->inventory_id,
            'reference'    => $this->reference,
            'note'         => $this->note,
            'reported_at'  => $this->reported_at,
            'approved_at'  => $this->approved_at,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            'user'      => new UserResource($this->whenLoaded('user')),
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
            'items'     => ExpirationItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
