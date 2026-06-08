<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'stock_id'         => $this->stock_id,
            'source_id'        => $this->source_id,
            'source_type'      => $this->source_type,
            'purchase_price'   => $this->purchase_price,
            'initial_quantity' => $this->initial_quantity,
            'current_quantity' => $this->current_quantity,
            'purchased_at'     => $this->purchased_at,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
