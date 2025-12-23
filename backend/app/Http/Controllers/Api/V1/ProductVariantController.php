<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Product $product)
    {
        $query = $product->variants()->newQuery();

        if ($request->boolean('only_active')) {
            $query->where('is_active', true);
        }

        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->input('stock_status'));
        }

        if ($request->boolean('has_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->input('price_max'));
        }

        $attributes = $request->input('attributes');
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if (! is_string($key) || $key === '' || ! preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    continue;
                }

                $column = "attributes->{$key}";
                if (is_array($value)) {
                    $values = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
                    if (count($values) > 0) {
                        $query->whereIn($column, $values);
                    }
                } elseif ($value !== null && $value !== '') {
                    $query->where($column, $value);
                }
            }
        }

        if ($search = $request->input('q')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $sortField = $request->input('sort', 'name');
        $sortDir = $request->input('direction', 'asc');
        if (! in_array($sortField, ['created_at', 'name', 'price', 'stock_quantity'], true)) {
            $sortField = 'name';
        }
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $query->orderBy($sortField, $sortDir);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $variants = $query->paginate($perPage);

        return ProductVariantResource::collection($variants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductVariantRequest $request, Product $product)
    {
        $data = $this->prepareData($request->validated(), $product);
        $stockPayload = $this->extractStockPayload($data);

        $variant = $product->variants()->create($data);

        $this->syncStockIfNeeded($variant, $stockPayload, 'initial_stock', $request, 'Stock initial synchronised during variant creation.');

        Log::channel('catalogue')->info('product_variant.created', [
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'user_id' => optional($request->user())->id,
        ]);

        return (new ProductVariantResource($variant))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product, ProductVariant $variant)
    {
        $this->assertVariantBelongsToProduct($product, $variant);

        return new ProductVariantResource($variant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant)
    {
        $this->assertVariantBelongsToProduct($product, $variant);

        $data = $this->prepareData($request->validated(), $product, $variant);
        $stockPayload = $this->extractStockPayload($data);

        $variant->update($data);

        $this->syncStockIfNeeded($variant, $stockPayload, 'manual_adjustment', $request, 'Stock synchronised during variant update.');

        Log::channel('catalogue')->info('product_variant.updated', [
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'user_id' => optional($request->user())->id,
        ]);

        return new ProductVariantResource($variant->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, ProductVariant $variant)
    {
        $this->assertVariantBelongsToProduct($product, $variant);

        $variantId = $variant->id;
        $variantSku = $variant->sku;

        $variant->delete();

        Log::channel('catalogue')->info('product_variant.deleted', [
            'variant_id' => $variantId,
            'product_id' => $product->id,
            'sku' => $variantSku,
            'user_id' => optional(request()->user())->id,
        ]);

        return response()->noContent();
    }

    protected function prepareData(array $data, Product $product, ?ProductVariant $variant = null): array
    {
        $data['product_id'] = $product->id;

        if (! array_key_exists('currency', $data) || empty($data['currency'])) {
            $data['currency'] = $variant?->currency ?? 'USD';
        }

        if (! array_key_exists('is_active', $data) && ! $variant) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function extractStockPayload(array &$data): array
    {
        $payload = [
            'quantity' => $data['stock_quantity'] ?? null,
            'status' => $data['stock_status'] ?? null,
        ];

        unset($data['stock_quantity'], $data['stock_status']);

        return $payload;
    }

    protected function syncStockIfNeeded(ProductVariant $variant, array $payload, string $reason, ?Request $request = null, ?string $description = null): void
    {
        $quantity = $payload['quantity'];
        $status = $payload['status'];

        if ($quantity === null && $status === null) {
            return;
        }

        $newQuantity = $quantity !== null ? (int) $quantity : (int) $variant->stock_quantity;

        try {
            $this->inventory->syncStock(
                $variant,
                $newQuantity,
                $reason,
                [
                    'requested_quantity' => $quantity,
                    'requested_status' => $status,
                    'controller' => static::class,
                    'product_id' => $variant->product_id,
                ],
                optional($request?->user())->id,
                $status,
                $description
            );
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'stock_quantity' => [$e->getMessage()],
            ]);
        }
    }

    protected function assertVariantBelongsToProduct(Product $product, ProductVariant $variant): void
    {
        if ($variant->product_id !== $product->id) {
            abort(Response::HTTP_NOT_FOUND, 'Variant not found for this product.');
        }
    }
}
