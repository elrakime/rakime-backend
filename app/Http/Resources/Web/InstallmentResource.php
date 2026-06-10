<?php

namespace App\Http\Resources\Web;

use App\Enums\InstallmentPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'contract_id'    => $this->contract_id,
            'month_number'   => $this->month_number,
            'amount'         => $this->amount,
            'status'         => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'payment_method' => $this->payment_method ? [
                'value' => $this->payment_method,
                'name'  => InstallmentPaymentMethod::from($this->payment_method)->get_name(),
                'color' => InstallmentPaymentMethod::from($this->payment_method)->get_color(),
            ] : null,
            'due_date'       => $this->due_date,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,

            'cashPayment' => new InstallmentPaymentResource($this->whenLoaded('cashPayment')),
            'draws'       => DrawResource::collection($this->whenLoaded('draws')),
        ];
    }
}
