<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference'        => $this->reference,
            'status'           => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'supplier'         => $this->whenLoaded('supplier', fn () => [
                'id'   => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'items'            => PurchaseItemResource::collection($this->whenLoaded('items')),
            'payments'         => PurchasePaymentResource::collection($this->whenLoaded('payments')),
            'total_amount'     => $this->total_amount,
            'paid_amount'      => $this->paid_amount,
            'remaining_amount' => $this->total_amount - $this->paid_amount,
            'note'             => $this->note,
            'purchased_at'     => $this->purchased_at,
            'received_at'      => $this->received_at,
            'created_at'       => $this->created_at,
        ];
    }
}
