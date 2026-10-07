<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\Role;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewCosts = $this->canViewCosts($request);

        return [
            'id'             => $this->id,
            'parent_contract_id' => $this->parent_contract_id,
            'extended_at'    => $this->extended_at,
            'client_id'      => $this->client_id,
            'account_id'     => $this->account_id,
            'branch_id'      => $this->branch_id,
            'reference'      => $this->reference,
            'status'         => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'payment_status' => $this->payment_status,
            'max_amount'     => $this->max_amount,
            'advance_amount' => $this->advance_amount,
            'months_count'   => $this->months_count,
            'total_amount'   => $this->total_amount,
            'net_amount'     => $this->net_amount,
            'monthly_amount' => $this->monthly_amount,
            'purchase_cost'  => $this->when($canViewCosts, $this->purchase_cost),
            'net_profit'     => $this->when($canViewCosts, $this->net_profit),
            'start_date'      => $this->start_date,
            'end_date'        => $this->end_date,
            'cancel_date'     => $this->cancel_date,
            'note'           => $this->note,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'created_by'     => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'     => new AvatarResource($this->whenLoaded('updater')),

            'user'         => new UserResource($this->whenLoaded('user')),
            'client'       => new ClientResource($this->whenLoaded('client')),
            'account'      => new AccountResource($this->whenLoaded('account')),
            'branch'       => new BranchResource($this->whenLoaded('branch')),
            'items'         => ContractItemResource::collection($this->whenLoaded('items')),
            'installments'  => InstallmentResource::collection($this->whenLoaded('installments')),
            'subscriptions' => SubscriptionResource::collection($this->whenLoaded('subscriptions')),
            'financial_records' => FinancialRecordResource::collection($this->whenLoaded('financialRecords')),

            'parent_contract' => new self($this->whenLoaded('parentContract')),
            'extension'       => new self($this->whenLoaded('extension')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }

    /**
     * Purchase cost and net profit are only visible to admins and managers.
     */
    private function canViewCosts(Request $request): bool
    {
        $user = $request->user();

        return $user && $user->hasAnyRole([Role::ADMIN->value, Role::MANAGER->value]);
    }
}
