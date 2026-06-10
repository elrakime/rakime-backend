<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'subscription_id' => $this->subscription_id,
            'installment_id'  => $this->installment_id,
            'month_number'    => $this->month_number,
            'amount'          => $this->amount,
            'status'          => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'scheduled_date'  => $this->scheduled_date,
            'processed_at'    => $this->processed_at,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
