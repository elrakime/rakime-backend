<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'from_wallet_id' => $this->from_wallet_id,
            'to_wallet_id'   => $this->to_wallet_id,
            'amount'         => $this->amount,
            'note'           => $this->note,
            'fromWallet'     => $this->whenLoaded('fromWallet'),
            'toWallet'       => $this->whenLoaded('toWallet'),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'created_by'     => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'     => new AvatarResource($this->whenLoaded('updater')),
        ];
    }
}
