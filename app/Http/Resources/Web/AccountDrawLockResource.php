<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountDrawLockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'account_id' => $this->account_id,
            'month'      => $this->month?->toDateString(),
            'account'    => new AccountResource($this->whenLoaded('account')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => new AvatarResource($this->whenLoaded('creator')),
            'updated_by' => new AvatarResource($this->whenLoaded('updater')),
        ];
    }
}
