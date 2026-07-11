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
            'status'       => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'note'         => $this->note,
            'fulfilled_with' => $this->whenLoaded('fulfilledWith', fn () => [
                'id'   => $this->fulfilledWith->id,
                'type' => class_basename($this->fulfilledWith),
                'reference' => $this->fulfilledWith->reference ?? null,
            ]),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'created_by'   => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'   => new AvatarResource($this->whenLoaded('updater')),

            'user' => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'items' => RestockItemResource::collection($this->whenLoaded('items')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
