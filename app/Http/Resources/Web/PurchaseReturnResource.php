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
            'status'      => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'note'        => $this->note,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'created_by'  => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'  => new AvatarResource($this->whenLoaded('updater')),

            'purchase' => new PurchaseResource($this->whenLoaded('purchase')),
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
