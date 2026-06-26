<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'balance'    => $this->balance,
            'owner_type' => $this->owner_type,
            'owner_id'   => $this->owner_id,
            'owner'      => $this->whenLoaded('owner'),
            'created_at' => $this->created_at,
        ];
    }
}
