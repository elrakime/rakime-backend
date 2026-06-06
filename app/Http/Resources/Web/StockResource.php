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
            'id'           => $this->id,
            'inventory_id' => $this->inventory_id,
            'product_id'   => $this->product_id,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
            'product'   => new ProductResource($this->whenLoaded('product')),
            'batches'   => \App\Http\Resources\Web\BatchResource::collection($this->whenLoaded('batches')),
            'prices'    => \App\Http\Resources\Web\PriceResource::collection($this->whenLoaded('prices')),
        ];
    }
}
