<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('stockable_type');
        $id = $request->integer('stockable_id');

        $query = \App\Models\StockMovement::query();

        if ($type && $id) {
            $modelClass = null;
            if ($type === 'product') {
                $modelClass = Product::class;
            } elseif ($type === 'variant') {
                $modelClass = ProductVariant::class;
            } elseif (class_exists($type)) {
                $modelClass = $type;
            }

            if ($modelClass) {
                $query->where('stockable_type', $modelClass)->where('stockable_id', $id);
            }
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $movements = $query->orderByDesc('id')->paginate($perPage);

        return StockMovementResource::collection($movements);
    }
}
