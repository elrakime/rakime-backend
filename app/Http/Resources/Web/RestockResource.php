<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'branch_id'    => $this->branch_id,
            'reference'    => $this->reference,
            'status'       => $this->status,
            'note'         => $this->note,
            'fulfilled_at' => $this->fulfilled_at,
            'created_at'   => $this->created_at,

            'user' => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'items' => RestockItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
