<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'code'     => $this->code,
            'accounts' => $this->whenLoaded('accounts', fn () => $this->accounts->map(fn ($a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'ccp_number' => $a->ccp_number,
            ])),
            'managers'   => $this->whenLoaded('managers', fn () => $this->managers->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
