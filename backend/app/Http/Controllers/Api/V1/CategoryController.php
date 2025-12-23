<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->boolean('only_active')) {
            $query->where('is_active', true);
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        } elseif ($request->boolean('only_roots')) {
            $query->whereNull('parent_id');
        }

        $query->orderBy('position')->orderBy('name');

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $categories = $query->paginate($perPage);

        return CategoryResource::collection(
            $categories->load('parent')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $data = $this->prepareData($request->validated());

        $category = Category::create($data);

        Log::channel('catalogue')->info('category.created', [
            'category_id' => $category->id,
            'name' => $category->name,
            'user_id' => optional($request->user())->id,
        ]);

        return (new CategoryResource($category->load('parent', 'children')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return new CategoryResource(
            $category->load('parent', 'children')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $this->prepareData($request->validated(), $category);

        $category->update($data);

        Log::channel('catalogue')->info('category.updated', [
            'category_id' => $category->id,
            'name' => $category->name,
            'user_id' => optional($request->user())->id,
        ]);

        return new CategoryResource(
            $category->fresh()->load('parent', 'children')
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        Log::channel('catalogue')->info('category.deleted', [
            'category_id' => $category->id,
            'user_id' => optional(request()->user())->id,
        ]);

        return response()->noContent();
    }

    protected function prepareData(array $data, ?Category $category = null): array
    {
        if (array_key_exists('parent_id', $data)) {
            $data['parent_id'] = $data['parent_id'] ?: null;
        }

        if (array_key_exists('slug', $data)) {
            if (! empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $category?->id);
            } elseif (! $category) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            } else {
                unset($data['slug']);
            }
        } elseif (! $category) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        return $data;
    }

    protected function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'category';
        }

        $slug = $base;
        $counter = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
