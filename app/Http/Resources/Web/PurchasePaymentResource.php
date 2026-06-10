<?php

namespace App\Http\Resources\Web;

use App\Enums\PurchasePaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'purchase_id'    => $this->purchase_id,
            'amount'         => $this->amount,
            'payment_method' => [
                'value' => $this->payment_method,
                'name'  => PurchasePaymentMethod::from($this->payment_method)->get_name(),
                'color' => PurchasePaymentMethod::from($this->payment_method)->get_color(),
            ],
            'paid_at'        => $this->paid_at,
            'created_at'     => $this->created_at,
        ];
    }
}
