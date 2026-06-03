<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreasuryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'balance'    => $this->balance,
            'branch'     => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
