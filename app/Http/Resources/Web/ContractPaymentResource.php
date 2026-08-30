<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'contract_id' => $this->contract_id,
            'amount'      => $this->amount,
            'note'        => $this->note,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'created_by'  => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'  => new AvatarResource($this->whenLoaded('updater')),

            'contract'     => new ContractResource($this->whenLoaded('contract')),
            'installments' => InstallmentResource::collection($this->whenLoaded('installments')),
        ];
    }
}
