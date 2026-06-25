<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'image'      => $this->getFirstMediaUrl('image') ?: null,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'is_active'  => $this->is_active,
            'roles'      => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')),
            'branches'   => $this->whenLoaded('branches', fn () => $this->branches->map(fn ($b) => [
                'id'   => $b->id,
                'name' => $b->name,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
