<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'installment_id' => $this->installment_id,
            'amount'         => $this->amount,
            'received_by'    => $this->received_by,
            'note'           => $this->note,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'created_by'     => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'     => new AvatarResource($this->whenLoaded('updater')),

            'receivedBy' => new UserResource($this->whenLoaded('receivedBy')),
        ];
    }
}
