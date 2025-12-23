<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryService;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImportExportController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function export(Request $request)
    {
        $query = Product::query();
        $this->applyProductFilters($query, $request);

        $filename = 'products_' . now()->format('Ymd_His') . '.csv';

        Log::channel('catalogue')->info('product.export.started', [
            'user_id' => optional($request->user())->id,
            'filters' => $request->query(),
        ]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            $headers = [
                'id',
                'category_id',
                'type',
                'sku',
                'name',
                'slug',
                'price',
                'compare_price',
                'cost_price',
                'currency',
                'stock_quantity',
                'stock_status',
                'is_active',
                'short_description',
                'description',
                'attributes',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'published_at',
                'created_at',
                'updated_at',
            ];

            fputcsv($out, $headers);

            $query->chunk(500, function ($products) use ($out) {
                foreach ($products as $product) {
                    fputcsv($out, [
                        $product->id,
                        $product->category_id,
                        $product->type,
                        $product->sku,
                        $product->name,
                        $product->slug,
                        $product->price,
                        $product->compare_price,
                        $product->cost_price,
                        $product->currency,
                        $product->stock_quantity,
                        $product->stock_status,
                        (int) ((bool) $product->is_active),
                        $product->short_description,
                        $product->description,
                        json_encode($product->getAttribute('attributes'), JSON_UNESCAPED_UNICODE),
                        $product->meta_title,
                        $product->meta_description,
                        $product->meta_keywords,
                        optional($product->published_at)->toISOString(),
                        optional($product->created_at)->toISOString(),
                        optional($product->updated_at)->toISOString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importVariants(Request $request, Product $product)
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
            'dry_run' => ['nullable'],
            'update_existing' => ['nullable'],
            'delimiter' => ['nullable', 'string', 'max:1'],
        ]);

        $dryRun = filter_var($validated['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $updateExisting = filter_var($validated['update_existing'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $delimiter = (string) ($validated['delimiter'] ?? ',');

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => ['Unable to read uploaded file.'],
            ]);
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header) || count($header) === 0) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => ['CSV header row is missing or invalid.'],
            ]);
        }

        $header = array_map(fn ($h) => trim((string) $h), $header);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        $required = ['sku', 'name', 'price'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => ["Missing required column: {$col}"],
                ]);
            }
        }

        $rowNumber = 1;
        $total = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if (! is_array($data) || (count($data) === 1 && ($data[0] === null || $data[0] === ''))) {
                continue;
            }

            $total++;

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $rowErrors = $this->validateVariantRow($row);
            if (count($rowErrors) > 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'messages' => $rowErrors,
                    'data' => $row,
                ];
                continue;
            }

            $sku = trim((string) $row['sku']);

            $variant = $product->variants()->where('sku', $sku)->first();
            if (! $variant) {
                $foreignVariant = ProductVariant::where('sku', $sku)->first();
                if ($foreignVariant) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'messages' => ['sku exists for a different product.'],
                        'data' => $row,
                    ];
                    continue;
                }
            }

            if ($variant && ! $updateExisting) {
                $skipped++;
                continue;
            }

            $payload = $this->normalizeVariantPayload($row, $product, $variant);
            $stockPayload = $payload['stock'];
            unset($payload['stock']);

            $shouldSyncStock = ($stockPayload['quantity'] !== null) || ($stockPayload['status'] !== null);

            if ($dryRun) {
                if ($variant) {
                    $updated++;
                } else {
                    $created++;
                }
                continue;
            }

            try {
                if ($variant) {
                    $variant->update($payload);

                    if ($shouldSyncStock) {
                        $newQuantity = $stockPayload['quantity'] !== null
                            ? (int) $stockPayload['quantity']
                            : (int) $variant->stock_quantity;

                        $this->inventory->syncStock(
                            $variant,
                            $newQuantity,
                            'import',
                            [
                                'source' => 'csv',
                                'row' => $rowNumber,
                                'product_id' => $product->id,
                            ],
                            optional($request->user())->id,
                            $stockPayload['status'],
                            'Stock synchronised during variant import.'
                        );
                    }

                    $updated++;
                } else {
                    $variant = $product->variants()->create($payload);

                    if ($shouldSyncStock) {
                        $newQuantity = $stockPayload['quantity'] !== null
                            ? (int) $stockPayload['quantity']
                            : (int) $variant->stock_quantity;

                        $this->inventory->syncStock(
                            $variant,
                            $newQuantity,
                            'import',
                            [
                                'source' => 'csv',
                                'row' => $rowNumber,
                                'product_id' => $product->id,
                            ],
                            optional($request->user())->id,
                            $stockPayload['status'],
                            'Stock synchronised during variant import.'
                        );
                    }

                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $rowNumber,
                    'messages' => [$e->getMessage()],
                    'data' => $row,
                ];
            }
        }

        fclose($handle);

        Log::channel('catalogue')->info('product_variant.import.completed', [
            'user_id' => optional($request->user())->id,
            'product_id' => $product->id,
            'dry_run' => $dryRun,
            'update_existing' => $updateExisting,
            'total_rows' => $total,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'status' => 'ok',
            'product_id' => $product->id,
            'dry_run' => $dryRun,
            'update_existing' => $updateExisting,
            'summary' => [
                'total_rows' => $total,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors_count' => count($errors),
            ],
            'errors' => $errors,
        ]);
    }

    public function exportVariants(Request $request, Product $product)
    {
        $query = $product->variants()->newQuery();
        $this->applyVariantFilters($query, $request);

        $filename = 'product_' . $product->id . '_variants_' . now()->format('Ymd_His') . '.csv';

        Log::channel('catalogue')->info('product_variant.export.started', [
            'user_id' => optional($request->user())->id,
            'product_id' => $product->id,
            'filters' => $request->query(),
        ]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            $headers = [
                'id',
                'product_id',
                'sku',
                'name',
                'price',
                'compare_price',
                'cost_price',
                'currency',
                'stock_quantity',
                'stock_status',
                'is_active',
                'attributes',
                'weight',
                'width',
                'height',
                'length',
                'created_at',
                'updated_at',
            ];

            fputcsv($out, $headers);

            $query->chunk(500, function ($variants) use ($out) {
                foreach ($variants as $variant) {
                    fputcsv($out, [
                        $variant->id,
                        $variant->product_id,
                        $variant->sku,
                        $variant->name,
                        $variant->price,
                        $variant->compare_price,
                        $variant->cost_price,
                        $variant->currency,
                        $variant->stock_quantity,
                        $variant->stock_status,
                        (int) ((bool) $variant->is_active),
                        json_encode($variant->getAttribute('attributes'), JSON_UNESCAPED_UNICODE),
                        $variant->weight,
                        $variant->width,
                        $variant->height,
                        $variant->length,
                        optional($variant->created_at)->toISOString(),
                        optional($variant->updated_at)->toISOString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
            'dry_run' => ['nullable'],
            'update_existing' => ['nullable'],
            'delimiter' => ['nullable', 'string', 'max:1'],
        ]);

        $dryRun = filter_var($validated['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $updateExisting = filter_var($validated['update_existing'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $delimiter = (string) ($validated['delimiter'] ?? ',');

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => ['Unable to read uploaded file.'],
            ]);
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header) || count($header) === 0) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => ['CSV header row is missing or invalid.'],
            ]);
        }

        $header = array_map(fn ($h) => trim((string) $h), $header);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        $required = ['sku', 'name', 'price'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => ["Missing required column: {$col}"],
                ]);
            }
        }

        $rowNumber = 1;
        $total = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if (! is_array($data) || (count($data) === 1 && ($data[0] === null || $data[0] === ''))) {
                continue;
            }

            $total++;

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? null;
            }

            $rowErrors = $this->validateRow($row);
            if (count($rowErrors) > 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'messages' => $rowErrors,
                    'data' => $row,
                ];
                continue;
            }

            $sku = trim((string) $row['sku']);

            $product = Product::where('sku', $sku)->first();
            if ($product && ! $updateExisting) {
                $skipped++;
                continue;
            }

            $payload = $this->normalizeProductPayload($row, $product);
            $stockPayload = $payload['stock'];
            unset($payload['stock']);

            $shouldSyncStock = ($stockPayload['quantity'] !== null) || ($stockPayload['status'] !== null);

            if ($dryRun) {
                if ($product) {
                    $updated++;
                } else {
                    $created++;
                }
                continue;
            }

            try {
                if ($product) {
                    $product->update($payload);

                    if ($shouldSyncStock) {
                        $newQuantity = $stockPayload['quantity'] !== null
                            ? (int) $stockPayload['quantity']
                            : (int) $product->stock_quantity;

                        $this->inventory->syncStock(
                            $product,
                            $newQuantity,
                            'import',
                            [
                                'source' => 'csv',
                                'row' => $rowNumber,
                            ],
                            optional($request->user())->id,
                            $stockPayload['status'],
                            'Stock synchronised during product import.'
                        );
                    }
                    $updated++;
                } else {
                    $product = Product::create($payload);

                    if ($shouldSyncStock) {
                        $newQuantity = $stockPayload['quantity'] !== null
                            ? (int) $stockPayload['quantity']
                            : (int) $product->stock_quantity;

                        $this->inventory->syncStock(
                            $product,
                            $newQuantity,
                            'import',
                            [
                                'source' => 'csv',
                                'row' => $rowNumber,
                            ],
                            optional($request->user())->id,
                            $stockPayload['status'],
                            'Stock synchronised during product import.'
                        );
                    }
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $rowNumber,
                    'messages' => [$e->getMessage()],
                    'data' => $row,
                ];
            }
        }

        fclose($handle);

        Log::channel('catalogue')->info('product.import.completed', [
            'user_id' => optional($request->user())->id,
            'dry_run' => $dryRun,
            'update_existing' => $updateExisting,
            'total_rows' => $total,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'status' => 'ok',
            'dry_run' => $dryRun,
            'update_existing' => $updateExisting,
            'summary' => [
                'total_rows' => $total,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors_count' => count($errors),
            ],
            'errors' => $errors,
        ]);
    }

    protected function validateRow(array $row): array
    {
        $errors = [];

        $sku = trim((string) ($row['sku'] ?? ''));
        if ($sku === '') {
            $errors[] = 'sku is required.';
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'name is required.';
        }

        $price = $row['price'] ?? null;
        if ($price === null || $price === '') {
            $errors[] = 'price is required.';
        } elseif (! is_numeric($price) || (float) $price < 0) {
            $errors[] = 'price must be a non-negative number.';
        }

        if (array_key_exists('stock_quantity', $row) && $row['stock_quantity'] !== null && $row['stock_quantity'] !== '') {
            if (! is_numeric($row['stock_quantity']) || (int) $row['stock_quantity'] < 0) {
                $errors[] = 'stock_quantity must be a non-negative integer.';
            }
        }

        if (array_key_exists('stock_status', $row) && $row['stock_status'] !== null && $row['stock_status'] !== '') {
            $allowed = ['in_stock', 'out_of_stock', 'preorder'];
            if (! in_array((string) $row['stock_status'], $allowed, true)) {
                $errors[] = 'stock_status must be one of: in_stock, out_of_stock, preorder.';
            }
        }

        if (array_key_exists('category_id', $row) && $row['category_id'] !== null && $row['category_id'] !== '') {
            $categoryId = (int) $row['category_id'];
            if ($categoryId > 0 && ! Category::whereKey($categoryId)->exists()) {
                $errors[] = 'category_id does not exist.';
            }
        }

        if (array_key_exists('attributes', $row) && $row['attributes'] !== null && $row['attributes'] !== '') {
            $decoded = json_decode((string) $row['attributes'], true);
            if (! is_array($decoded) && $row['attributes'] !== 'null') {
                $errors[] = 'attributes must be valid JSON object/array.';
            }
        }

        return $errors;
    }

    protected function validateVariantRow(array $row): array
    {
        $errors = [];

        $sku = trim((string) ($row['sku'] ?? ''));
        if ($sku === '') {
            $errors[] = 'sku is required.';
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'name is required.';
        }

        $price = $row['price'] ?? null;
        if ($price === null || $price === '') {
            $errors[] = 'price is required.';
        } elseif (! is_numeric($price) || (float) $price < 0) {
            $errors[] = 'price must be a non-negative number.';
        }

        if (array_key_exists('stock_quantity', $row) && $row['stock_quantity'] !== null && $row['stock_quantity'] !== '') {
            if (! is_numeric($row['stock_quantity']) || (int) $row['stock_quantity'] < 0) {
                $errors[] = 'stock_quantity must be a non-negative integer.';
            }
        }

        if (array_key_exists('stock_status', $row) && $row['stock_status'] !== null && $row['stock_status'] !== '') {
            $allowed = ['in_stock', 'out_of_stock', 'preorder'];
            if (! in_array((string) $row['stock_status'], $allowed, true)) {
                $errors[] = 'stock_status must be one of: in_stock, out_of_stock, preorder.';
            }
        }

        if (array_key_exists('attributes', $row) && $row['attributes'] !== null && $row['attributes'] !== '') {
            $decoded = json_decode((string) $row['attributes'], true);
            if (! is_array($decoded) && $row['attributes'] !== 'null') {
                $errors[] = 'attributes must be valid JSON object/array.';
            }
        }

        return $errors;
    }

    protected function normalizeVariantPayload(array $row, Product $product, ?ProductVariant $existingVariant = null): array
    {
        $payload = [
            'product_id' => $product->id,
            'sku' => trim((string) $row['sku']),
            'name' => trim((string) $row['name']),
            'price' => (float) $row['price'],
            'compare_price' => array_key_exists('compare_price', $row)
                ? ($row['compare_price'] !== '' ? (float) $row['compare_price'] : null)
                : $existingVariant?->compare_price,
            'cost_price' => array_key_exists('cost_price', $row)
                ? ($row['cost_price'] !== '' ? (float) $row['cost_price'] : null)
                : $existingVariant?->cost_price,
            'currency' => array_key_exists('currency', $row)
                ? ($row['currency'] !== '' ? (string) $row['currency'] : ($existingVariant?->currency ?? 'USD'))
                : ($existingVariant?->currency ?? 'USD'),
            'is_active' => array_key_exists('is_active', $row)
                ? ($row['is_active'] !== '' ? (bool) filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : (bool) ($existingVariant?->is_active ?? true))
                : (bool) ($existingVariant?->is_active ?? true),
            'attributes' => array_key_exists('attributes', $row)
                ? $this->parseAttributes($row['attributes'] ?? null)
                : $existingVariant?->attributes,
            'weight' => array_key_exists('weight', $row)
                ? ($row['weight'] !== '' ? (float) $row['weight'] : null)
                : $existingVariant?->weight,
            'width' => array_key_exists('width', $row)
                ? ($row['width'] !== '' ? (float) $row['width'] : null)
                : $existingVariant?->width,
            'height' => array_key_exists('height', $row)
                ? ($row['height'] !== '' ? (float) $row['height'] : null)
                : $existingVariant?->height,
            'length' => array_key_exists('length', $row)
                ? ($row['length'] !== '' ? (float) $row['length'] : null)
                : $existingVariant?->length,
        ];

        $quantity = array_key_exists('stock_quantity', $row) && $row['stock_quantity'] !== ''
            ? (int) $row['stock_quantity']
            : null;
        $status = array_key_exists('stock_status', $row) && $row['stock_status'] !== '' ? (string) $row['stock_status'] : null;

        $payload['stock'] = [
            'quantity' => $quantity,
            'status' => $status,
        ];

        return $payload;
    }

    protected function normalizeProductPayload(array $row, ?Product $existingProduct = null): array
    {
        $name = trim((string) $row['name']);
        $ignoreId = $existingProduct?->id;

        $slug = $existingProduct?->slug;
        if (! $existingProduct || array_key_exists('slug', $row)) {
            $slugInput = array_key_exists('slug', $row) ? trim((string) $row['slug']) : '';
            $slugValue = $slugInput !== '' ? $slugInput : $name;
            $slug = $this->generateUniqueSlug($slugValue, $ignoreId);
        }

        $payload = [
            'category_id' => array_key_exists('category_id', $row)
                ? ($row['category_id'] !== '' ? (int) $row['category_id'] : null)
                : $existingProduct?->category_id,
            'type' => array_key_exists('type', $row)
                ? ($row['type'] !== '' ? (string) $row['type'] : ($existingProduct?->type ?? 'simple'))
                : ($existingProduct?->type ?? 'simple'),
            'sku' => trim((string) $row['sku']),
            'name' => $name,
            'slug' => $slug,
            'short_description' => array_key_exists('short_description', $row) ? ($row['short_description'] !== '' ? $row['short_description'] : null) : $existingProduct?->short_description,
            'description' => array_key_exists('description', $row) ? ($row['description'] !== '' ? $row['description'] : null) : $existingProduct?->description,
            'price' => (float) $row['price'],
            'compare_price' => array_key_exists('compare_price', $row)
                ? ($row['compare_price'] !== '' ? (float) $row['compare_price'] : null)
                : $existingProduct?->compare_price,
            'cost_price' => array_key_exists('cost_price', $row)
                ? ($row['cost_price'] !== '' ? (float) $row['cost_price'] : null)
                : $existingProduct?->cost_price,
            'currency' => array_key_exists('currency', $row)
                ? ($row['currency'] !== '' ? (string) $row['currency'] : ($existingProduct?->currency ?? 'USD'))
                : ($existingProduct?->currency ?? 'USD'),
            'is_active' => array_key_exists('is_active', $row)
                ? ($row['is_active'] !== '' ? (bool) filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : (bool) ($existingProduct?->is_active ?? true))
                : (bool) ($existingProduct?->is_active ?? true),
            'attributes' => array_key_exists('attributes', $row)
                ? $this->parseAttributes($row['attributes'] ?? null)
                : $existingProduct?->attributes,
            'meta_title' => array_key_exists('meta_title', $row) ? ($row['meta_title'] !== '' ? $row['meta_title'] : null) : $existingProduct?->meta_title,
            'meta_description' => array_key_exists('meta_description', $row) ? ($row['meta_description'] !== '' ? $row['meta_description'] : null) : $existingProduct?->meta_description,
            'meta_keywords' => array_key_exists('meta_keywords', $row) ? ($row['meta_keywords'] !== '' ? $row['meta_keywords'] : null) : $existingProduct?->meta_keywords,
            'published_at' => array_key_exists('published_at', $row)
                ? ($row['published_at'] !== '' ? $row['published_at'] : null)
                : $existingProduct?->published_at,
        ];

        $quantity = array_key_exists('stock_quantity', $row) && $row['stock_quantity'] !== ''
            ? (int) $row['stock_quantity']
            : null;
        $status = array_key_exists('stock_status', $row) && $row['stock_status'] !== '' ? (string) $row['stock_status'] : null;

        $payload['stock'] = [
            'quantity' => $quantity,
            'status' => $status,
        ];

        return $payload;
    }

    protected function parseAttributes($raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === 'null') {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
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

    protected function applyProductFilters($query, Request $request): void
    {
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
    }

    protected function applyVariantFilters($query, Request $request): void
    {
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
    }
}
