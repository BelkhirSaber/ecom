<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function decrement(Request $request)
    {
        $data = $request->validate([
            'stockable_type' => ['required', 'string'],
            'stockable_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $stockable = null;
        if ($data['stockable_type'] === 'product') {
            $stockable = Product::query()->findOrFail($data['stockable_id']);
        } elseif ($data['stockable_type'] === 'variant') {
            $stockable = ProductVariant::query()->findOrFail($data['stockable_id']);
        } elseif (class_exists($data['stockable_type'])) {
            $stockable = $data['stockable_type']::query()->findOrFail($data['stockable_id']);
        }

        if (! $stockable) {
            throw ValidationException::withMessages([
                'stockable_type' => ['Invalid stockable_type.'],
            ]);
        }

        try {
            $movement = $this->inventory->decrementStock(
                $stockable,
                (int) $data['quantity'],
                $data['reason'] ?? 'sale',
                $data['metadata'] ?? [],
                optional($request->user())->id,
                $data['description'] ?? null
            );
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'quantity' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'movement_id' => $movement->id,
            'balance_after' => $movement->balance_after,
        ]);
    }
}
