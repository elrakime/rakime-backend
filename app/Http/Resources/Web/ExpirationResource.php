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
            'inventory_id' => $this->inventory_id,
            'reference'    => $this->reference,
            'note'         => $this->note,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'created_by'   => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'   => new AvatarResource($this->whenLoaded('updater')),

            'user'      => new UserResource($this->whenLoaded('user')),
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
            'items'     => ExpirationItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
