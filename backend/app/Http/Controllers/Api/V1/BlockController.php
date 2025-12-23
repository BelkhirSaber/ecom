<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\HandlesLocalizedInput;
use App\Http\Controllers\Controller;
use App\Models\Block;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    use HandlesLocalizedInput;

    /**
     * Liste tous les blocks actifs (public).
     */
    public function index()
    {
        $blocks = Block::where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json(['data' => $blocks]);
    }

    /**
     * Liste tous les blocks (admin).
     */
    public function indexAdmin(Request $request)
    {
        $blocks = Block::orderBy('order')->paginate(15);

        return response()->json($blocks);
    }

    /**
     * Affiche un block par sa clé (public).
     */
    public function show(string $key)
    {
        $block = Block::where('key', $key)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json(['data' => $block]);
    }

    /**
     * Crée un nouveau block (admin).
     */
    public function store(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:blocks,key'],
            'type' => ['required', 'in:slider,banner,featured_products,text,html'],
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable', 'array'],
            'title_translations.*' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'content_translations' => ['nullable', 'array'],
            'order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data = $this->applyLocalizedInput($request, $data, [
            'title',
            'content',
        ]);

        $block = Block::create($data);

        return response()->json(['data' => $block], 201);
    }

    /**
     * Met à jour un block (admin).
     */
    public function update(Request $request, Block $block)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'key' => ['sometimes', 'string', 'max:255', 'unique:blocks,key,' . $block->id],
            'type' => ['sometimes', 'in:slider,banner,featured_products,text,html'],
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable', 'array'],
            'title_translations.*' => ['nullable', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'content_translations' => ['nullable', 'array'],
            'order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data = $this->applyLocalizedInput($request, $data, [
            'title',
            'content',
        ], $block);

        $block->update($data);

        return response()->json(['data' => $block->fresh()]);
    }

    /**
     * Supprime un block (admin).
     */
    public function destroy(Request $request, Block $block)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $block->delete();

        return response()->json(['message' => 'Block deleted successfully']);
    }
}
