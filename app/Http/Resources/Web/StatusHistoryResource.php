<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'from_status' => $this->from_status,
            'to_status'   => $this->to_status,
            'changed_at'  => $this->changed_at,
            'changed_by'  => new AvatarResource($this->whenLoaded('user')),
        ];
    }
}
