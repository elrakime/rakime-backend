<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'wallet_id'      => $this->wallet_id,
            'movement_type'  => [
                'value' => $this->movement_type->value,
                'name'  => $this->movement_type->get_name(),
                'color' => $this->movement_type->get_color(),
            ],
            'amount'         => $this->amount,
            'source_type'   => $this->source_type,
            'source_id'     => $this->source_id,
            'note'           => $this->note,
            'performed_by'   => $this->performed_by,
            'performedBy'    => $this->whenLoaded('performedBy'),
            'source'         => $this->whenLoaded('source'),
            'created_at'     => $this->created_at,
        ];
    }
}
