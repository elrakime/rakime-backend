<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'branch_id'    => $this->branch_id,
            'client_id'    => $this->client_id,
            'reference'    => $this->reference,
            'total_amount' => $this->total_amount,
            'note'         => $this->note,
            'sold_at'      => $this->sold_at,
            'created_at'   => $this->created_at,

            'user'   => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'client' => $this->whenLoaded('client', fn () => [
                'id'        => $this->client->id,
                'firstname' => $this->client->firstname,
                'lastname'  => $this->client->lastname,
                'phone'     => $this->client->phone,
            ]),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
