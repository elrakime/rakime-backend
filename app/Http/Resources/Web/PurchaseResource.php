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
            'items'            => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'         => $item->id,
                'product'    => $item->relationLoaded('product') ? [
                    'id'   => $item->product->id,
                    'name' => $item->product->name,
                ] : null,
                'quantity'   => $item->quantity,
                'price'      => $item->price,
                'subtotal'   => $item->quantity * $item->price,
            ])),
            'payments'         => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id'             => $p->id,
                'amount'         => $p->amount,
                'payment_method' => $p->payment_method->value,
                'paid_at'        => $p->paid_at,
            ])),
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
