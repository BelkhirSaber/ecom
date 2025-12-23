<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\HandlesLocalizedInput;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    use HandlesLocalizedInput;

    /**
     * Liste toutes les pages publiées (public).
     */
    public function index(Request $request)
    {
        $pages = Page::where('is_published', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'title_translations',
                'slug',
                'content',
                'content_translations',
                'meta_description',
                'meta_description_translations',
            ]);

        return response()->json(['data' => $pages]);
    }

    /**
     * Liste toutes les pages (admin).
     */
    public function indexAdmin(Request $request)
    {
        $pages = Page::orderBy('order')->orderBy('title')->paginate(15);

        return response()->json($pages);
    }

    /**
     * Affiche une page par son slug (public).
     */
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json(['data' => $page]);
    }

    /**
     * Crée une nouvelle page (admin).
     */
    public function store(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_translations' => ['nullable', 'array'],
            'title_translations.*' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'content' => ['required', 'string'],
            'content_translations' => ['nullable', 'array'],
            'content_translations.*' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_description_translations' => ['nullable', 'array'],
            'meta_description_translations.*' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'array'],
            'is_published' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        $data = $this->applyLocalizedInput($request, $data, [
            'title',
            'content',
            'meta_description',
        ]);

        $page = Page::create($data);

        return response()->json(['data' => $page], 201);
    }

    /**
     * Met à jour une page (admin).
     */
    public function update(Request $request, Page $page)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'title_translations' => ['nullable', 'array'],
            'title_translations.*' => ['nullable', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:pages,slug,' . $page->id],
            'content' => ['sometimes', 'string'],
            'content_translations' => ['nullable', 'array'],
            'content_translations.*' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_description_translations' => ['nullable', 'array'],
            'meta_description_translations.*' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'array'],
            'is_published' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        $data = $this->applyLocalizedInput($request, $data, [
            'title',
            'content',
            'meta_description',
        ], $page);

        $page->update($data);

        return response()->json(['data' => $page->fresh()]);
    }

    /**
     * Supprime une page (admin).
     */
    public function destroy(Request $request, Page $page)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $page->delete();

        return response()->json(['message' => 'Page deleted successfully']);
    }
}
