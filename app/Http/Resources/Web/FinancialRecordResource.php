<?php

declare(strict_types=1);

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'client_id'   => $this->client_id,
            'contract_id' => $this->contract_id,
            'revenues'    => $this->revenues,
            'expenses'    => $this->expenses,
            'income'      => $this->income,
            'note'        => $this->note,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'client'      => new ClientResource($this->whenLoaded('client')),
            'contract'    => new ContractResource($this->whenLoaded('contract')),
            'created_by'  => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'  => new AvatarResource($this->whenLoaded('updater')),
        ];
    }
}
