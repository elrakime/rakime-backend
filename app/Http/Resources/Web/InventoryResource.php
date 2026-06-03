<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'branch'     => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
