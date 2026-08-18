<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'contract_id'         => $this->contract_id,
            'reference'           => $this->reference,
            'subscription_number' => $this->subscription_number,
            'amount'              => $this->amount,
            'status'              => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
            'created_by'          => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'          => new AvatarResource($this->whenLoaded('updater')),

            'contract' => new ContractResource($this->whenLoaded('contract')),
            'draws'    => DrawResource::collection($this->whenLoaded('draws')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
