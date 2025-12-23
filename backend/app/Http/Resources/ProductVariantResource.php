<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'pricing' => [
                'price' => $this->price,
                'compare_price' => $this->compare_price,
                'cost_price' => $this->cost_price,
                'currency' => $this->currency,
            ],
            'stock' => [
                'quantity' => $this->stock_quantity,
                'status' => $this->stock_status,
            ],
            'is_active' => (bool) $this->is_active,
            'attributes' => $this->attributes,
            'dimensions' => [
                'weight' => $this->weight,
                'width' => $this->width,
                'height' => $this->height,
                'length' => $this->length,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
