<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'inventory_id'      => $this->inventory_id,
            'product_id'        => $this->product_id,
            'source_id'         => $this->source_id,
            'source_type'       => $this->source_type,
            'initial_quantity'  => $this->initial_quantity,
            'current_quantity'  => $this->current_quantity,
            'purchase_price'    => $this->purchase_price,
            'selling_price'     => $this->selling_price,
            'installment_price' => $this->installment_price,
            'purchased_at'      => $this->purchased_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            
            'inventory'         => new InventoryResource($this->whenLoaded('inventory')),
            'product'           => new ProductResource($this->whenLoaded('product')),
            // Depending on source_type, you could potentially load a morph resource here
            'source'            => $this->whenLoaded('source'),
        ];
    }
}
