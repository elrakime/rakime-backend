<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'address'    => $this->address,
            'is_active'  => $this->is_active,
            'wilaya'     => $this->whenLoaded('wilaya', fn () => [
                'id'   => $this->wilaya->id,
                'name' => $this->wilaya->name,
            ]),
            'image'     => $this->getFirstMediaUrl('image') ?: null,
            'metadata'  => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => new AvatarResource($this->whenLoaded('creator')),
            'updated_by' => new AvatarResource($this->whenLoaded('updater')),
        ];
    }
}
