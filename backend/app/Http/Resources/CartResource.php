<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'guest_token' => $this->guest_token,
            'currency' => $this->currency,
            'status' => $this->status,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'totals' => $this->totals,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
