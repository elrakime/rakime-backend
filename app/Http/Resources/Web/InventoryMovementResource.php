<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'stock_id'      => $this->stock_id,
            'batch_id'      => $this->batch_id,
            'inventory_id'  => $this->inventory_id,
            'product_id'    => $this->product_id,
            'source_id'     => $this->source_id,
            'movement_type' => $this->movement_type,
            'quantity'      => $this->quantity,
            'created_at'    => $this->created_at,

            'product' => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
            'inventory' => $this->whenLoaded('inventory', fn () => [
                'id'   => $this->inventory->id,
                'name' => $this->inventory->name,
            ]),
            'stock' => $this->whenLoaded('stock', fn () => [
                'id' => $this->stock->id,
            ]),
            'batch' => $this->whenLoaded('batch', fn () => [
                'id'               => $this->batch->id,
                'current_quantity' => $this->batch->current_quantity,
            ]),
        ];
    }
}
