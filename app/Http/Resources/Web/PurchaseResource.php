<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference'        => $this->reference,
            'status'           => [
                'value' => $this->status->value,
                'name'  => $this->status->get_name(),
                'color' => $this->status->get_color(),
            ],
            'supplier'         => new SupplierResource($this->whenLoaded('supplier')),
            'branch'           => new BranchResource($this->whenLoaded('branch')),
            'inventory'        => new InventoryResource($this->whenLoaded('inventory')),
            'items'            => PurchaseItemResource::collection($this->whenLoaded('items')),
            'payments'         => PurchasePaymentResource::collection($this->whenLoaded('payments')),
            'total_amount'     => $this->total_amount,
            'paid_amount'      => $this->paid_amount,
            'remaining_amount' => $this->total_amount - $this->paid_amount,
            'note'             => $this->note,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'created_by'       => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'       => new AvatarResource($this->whenLoaded('updater')),

            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
        ];
    }
}
