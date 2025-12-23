<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'cart_id' => $this->cart_id,
            'status' => $this->status,
            'currency' => $this->currency,
            'totals' => [
                'currency' => $this->currency,
                'subtotal' => $this->subtotal,
                'discount_total' => $this->discount_total,
                'shipping_total' => $this->shipping_total,
                'tax_total' => $this->tax_total,
                'grand_total' => $this->grand_total,
            ],
            'shipping_address_id' => $this->shipping_address_id,
            'billing_address_id' => $this->billing_address_id,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'placed_at' => $this->placed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
