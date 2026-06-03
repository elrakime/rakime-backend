<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'ccp_number'          => $this->ccp_number,
            'ccp_key'             => $this->ccp_key,
            'draw_day'            => $this->draw_day,
            'min_withdraw_amount' => $this->min_withdraw_amount,
            'max_withdraw_count'  => $this->max_withdraw_count,
            'created_at'          => $this->created_at,
        ];
    }
}
