<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'subscription_id'   => $this->subscription_id,
            'installment_id'    => $this->installment_id,
            'amount'            => $this->amount,
            'status'            => $this->status ? [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ] : null,
            'due_date'          => $this->due_date,
            'last_attempted_at' => $this->last_attempted_at,
            'tax_amount'        => $this->tax_amount,
            'metadata'          => $this->metadata,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'created_by'        => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'        => new AvatarResource($this->whenLoaded('updater')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
