<?php

namespace App\Http\Resources\Web;

use App\Models\Expiration;
use App\Models\InventoryTransfer;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Restock;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'stock_id'      => $this->stock_id,
            'inventory_id'  => $this->inventory_id,
            'product_id'    => $this->product_id,
            'source_id'     => $this->source_id,
            'source_type'   => $this->source_type,
            'movement_type' => $this->movement_type,
            'old_quantity'  => $this->old_quantity,
            'new_quantity'  => $this->new_quantity,
            'quantity'      => $this->quantity,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'created_by'    => new AvatarResource($this->whenLoaded('creator')),
            'updated_by'    => new AvatarResource($this->whenLoaded('updater')),

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
            'source' => $this->whenLoaded('source', function () {
                $source = $this->source;

                return [
                    'id'        => $source->id,
                    'type'      => class_basename($source),
                    'reference' => $source->reference ?? null,
                    ...match (true) {
                        $source instanceof Sale              => [
                            'client' => $this->whenLoadedNested($source, 'client', fn () => [
                                'id'   => $source->client->id,
                                'name' => $source->client->name,
                            ]),
                        ],
                        $source instanceof Purchase          => [
                            'supplier' => $this->whenLoadedNested($source, 'supplier', fn () => [
                                'id'   => $source->supplier->id,
                                'name' => $source->supplier->name,
                            ]),
                        ],
                        $source instanceof PurchaseReturn    => [
                            'purchase' => $this->whenLoadedNested($source, 'purchase', fn () => [
                                'id'       => $source->purchase->id,
                                'reference'=> $source->purchase->reference,
                                'supplier' => $source->purchase->relationLoaded('supplier') && $source->purchase->supplier ? [
                                    'id'   => $source->purchase->supplier->id,
                                    'name' => $source->purchase->supplier->name,
                                ] : null,
                            ]),
                        ],
                        $source instanceof InventoryTransfer => [
                            'from_inventory' => $this->whenLoadedNested($source, 'fromInventory', fn () => [
                                'id'   => $source->fromInventory->id,
                                'name' => $source->fromInventory->name,
                            ]),
                            'to_inventory'   => $this->whenLoadedNested($source, 'toInventory', fn () => [
                                'id'   => $source->toInventory->id,
                                'name' => $source->toInventory->name,
                            ]),
                        ],
                        default => [],
                    },
                ];
            }),
        ];
    }

    /**
     * Check if a nested relation is loaded on the source model and return the value.
     */
    private function whenLoadedNested($source, string $relation, callable $callback): mixed
    {
        if ($source->relationLoaded($relation) && $source->{$relation}) {
            return $callback();
        }

        return null;
    }
}
