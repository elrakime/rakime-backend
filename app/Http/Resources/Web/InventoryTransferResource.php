<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'from_inventory_id' => $this->from_inventory_id,
            'to_inventory_id'   => $this->to_inventory_id,
            'performed_by'      => $this->performed_by,
            'note'              => $this->note,
            'transferred_at'    => $this->transferred_at,
            'received_at'       => $this->received_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,

            'from_inventory' => new InventoryResource($this->whenLoaded('fromInventory')),
            'to_inventory'   => new InventoryResource($this->whenLoaded('toInventory')),
            'performer'      => new UserResource($this->whenLoaded('performedBy')),
            'items'          => InventoryTransferItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
