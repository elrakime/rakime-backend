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
            'status'            => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'note'              => $this->note,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'created_by'        => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'        => new AvatarResource($this->whenLoaded('updater')),

            'from_inventory' => new InventoryResource($this->whenLoaded('fromInventory')),
            'to_inventory'   => new InventoryResource($this->whenLoaded('toInventory')),
            
            'items'          => InventoryTransferItemResource::collection($this->whenLoaded('items')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
