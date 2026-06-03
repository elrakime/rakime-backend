<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'branch'     => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'wilaya'     => $this->whenLoaded('wilaya', fn () => [
                'id'   => $this->wilaya->id,
                'name' => $this->wilaya->name,
            ]),
            'name'       => $this->name,
            'phone'      => $this->phone,
            'birthdate'  => $this->birthdate?->toDateString(),
            'address'    => $this->address,
            'occupation' => $this->occupation,
            'employer'   => $this->employer,
            'salary'     => $this->salary,
            'nin'        => $this->nin,
            'ccp_number' => $this->ccp_number,
            'ccp_key'    => $this->ccp_key,
            'eccp'       => $this->eccp,
            'created_at' => $this->created_at,
        ];
    }
}
