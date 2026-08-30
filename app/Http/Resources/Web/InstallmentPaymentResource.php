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
            'payment_id'     => $this->payment_id,
            'installment_id' => $this->installment_id,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,

            'payment'     => new ContractPaymentResource($this->whenLoaded('payment')),
            'installment' => new InstallmentResource($this->whenLoaded('installment')),
        ];
    }
}
