<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\HandlesLocalizedInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use HandlesLocalizedInput;
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $with = ['category'];
        if ($request->boolean('with_variants')) {
            $with[] = 'variants';
        }

        $query = Product::query()->with($with);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
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

        if ($request->boolean('only_active')) {
            $query->where('is_active', true);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc');
        if (! in_array($sortField, ['created_at', 'name', 'price', 'stock_quantity'], true)) {
            $sortField = 'created_at';
        }
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query->orderBy($sortField, $sortDir);

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $this->prepareData($request->validated());
        $data = $this->applyLocalizedInput($request, $data, [
            'name',
            'short_description',
            'description',
            'meta_title',
            'meta_description',
        ]);
        $stockPayload = $this->extractStockPayload($data);

        $product = Product::create($data);

        $this->syncStockIfNeeded($product, $stockPayload, 'initial_stock', $request, 'Stock initial synchronised during product creation.');

        Log::channel('catalogue')->info('product.created', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'user_id' => optional($request->user())->id,
        ]);

        return (new ProductResource($product->load('category', 'variants')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return new ProductResource($product->load('category', 'variants'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $this->prepareData($request->validated(), $product);
        $data = $this->applyLocalizedInput($request, $data, [
            'name',
            'short_description',
            'description',
            'meta_title',
            'meta_description',
        ], $product);
        $stockPayload = $this->extractStockPayload($data);

        $product->update($data);

        $this->syncStockIfNeeded($product, $stockPayload, 'manual_adjustment', $request, 'Stock synchronised during product update.');

        Log::channel('catalogue')->info('product.updated', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'user_id' => optional($request->user())->id,
        ]);

        return new ProductResource($product->fresh()->load('category', 'variants'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $productId = $product->id;
        $productSku = $product->sku;

        $product->delete();

        Log::channel('catalogue')->info('product.deleted', [
            'product_id' => $productId,
            'sku' => $productSku,
            'user_id' => optional(request()->user())->id,
        ]);

        return response()->noContent();
    }

    protected function prepareData(array $data, ?Product $product = null): array
    {
        if (! array_key_exists('currency', $data) || empty($data['currency'])) {
            $data['currency'] = 'USD';
        }

        if (array_key_exists('slug', $data)) {
            if (! empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $product?->id);
            } elseif (! $product) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            } else {
                unset($data['slug']);
            }
        } elseif (! $product) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        if (! array_key_exists('is_active', $data) && ! $product) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'product';
        }

        $slug = $base;
        $counter = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
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

    protected function syncStockIfNeeded(Product $product, array $payload, string $reason, ?Request $request = null, ?string $description = null): void
    {
        $quantity = $payload['quantity'];
        $status = $payload['status'];

        if ($quantity === null && $status === null) {
            return;
        }

        $newQuantity = $quantity !== null ? (int) $quantity : (int) $product->stock_quantity;

        try {
            $this->inventory->syncStock(
                $product,
                $newQuantity,
                $reason,
                [
                    'requested_quantity' => $quantity,
                    'requested_status' => $status,
                    'controller' => static::class,
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
}
