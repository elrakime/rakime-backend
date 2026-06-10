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
            'paid_at'        => $this->paid_at,
            'note'           => $this->note,
            'created_at'     => $this->created_at,

            'receivedBy' => new UserResource($this->whenLoaded('receivedBy')),
        ];
    }
}
