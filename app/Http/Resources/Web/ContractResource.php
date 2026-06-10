<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'client_id'      => $this->client_id,
            'account_id'     => $this->account_id,
            'branch_id'      => $this->branch_id,
            'reference'      => $this->reference,
            'status'         => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'max_amount'     => $this->max_amount,
            'advance_amount' => $this->advance_amount,
            'months_count'   => $this->months_count,
            'total_amount'   => $this->total_amount,
            'monthly_amount' => $this->monthly_amount,
            'note'           => $this->note,
            'confirmed_at'   => $this->confirmed_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,

            'user'         => new UserResource($this->whenLoaded('user')),
            'client'       => new ClientResource($this->whenLoaded('client')),
            'account'      => new AccountResource($this->whenLoaded('account')),
            'branch'       => new BranchResource($this->whenLoaded('branch')),
            'items'        => ContractItemResource::collection($this->whenLoaded('items')),
            'installments' => InstallmentResource::collection($this->whenLoaded('installments')),
        ];
    }
}
