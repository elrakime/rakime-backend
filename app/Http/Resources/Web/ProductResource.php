<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->whenLoaded('type', fn () => [
                'id'   => $this->type->id,
                'name' => $this->type->name,
            ]),
            'color'        => $this->whenLoaded('color', fn () => [
                'id'   => $this->color->id,
                'name' => $this->color->name,
                'code' => $this->color->code,
            ]),
            'brand'        => $this->whenLoaded('brand', fn () => [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'name'         => $this->name,
            'barcode'      => $this->barcode,
            'image'        => $this->getFirstMediaUrl('image') ?: ($this->image ?: null),
            'min_quantity' => $this->min_quantity,
            'created_at'   => $this->created_at,
        ];
    }
}
