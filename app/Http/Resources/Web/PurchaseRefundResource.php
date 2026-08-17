<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'purchase_id'        => $this->purchase_id,
            'purchase_return_id' => $this->purchase_return_id,
            'amount'             => $this->amount,
            'status'             => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
            'created_by'         => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'         => new AvatarResource($this->whenLoaded('updater')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
