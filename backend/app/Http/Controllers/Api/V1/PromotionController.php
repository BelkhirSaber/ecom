<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\HandlesLocalizedInput;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use HandlesLocalizedInput;
    /**
     * Liste toutes les promotions actives (public).
     */
    public function index()
    {
        $promotions = Promotion::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json(['data' => $promotions]);
    }

    /**
     * Liste toutes les promotions (admin).
     */
    public function indexAdmin(Request $request)
    {
        $promotions = Promotion::orderBy('priority', 'desc')->paginate(15);

        return response()->json($promotions);
    }

    /**
     * Affiche une promotion (admin).
     */
    public function show(Request $request, Promotion $promotion)
    {
        return response()->json(['data' => $promotion]);
    }

    /**
     * Crée une nouvelle promotion (admin).
     */
    public function store(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_translations' => ['nullable', 'array'],
            'name_translations.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.*' => ['nullable', 'string'],
            'type' => ['required', 'in:product,category,cart'],
            'discount_type' => ['required', 'in:fixed,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_categories' => ['nullable', 'array'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $data = $this->applyLocalizedInput($request, $data, [
            'name',
            'description',
        ]);

        $promotion = Promotion::create($data);

        return response()->json(['data' => $promotion], 201);
    }

    /**
     * Met à jour une promotion (admin).
     */
    public function update(Request $request, Promotion $promotion)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'name_translations' => ['nullable', 'array'],
            'name_translations.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.*' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:product,category,cart'],
            'discount_type' => ['sometimes', 'in:fixed,percentage'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_categories' => ['nullable', 'array'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $data = $this->applyLocalizedInput($request, $data, [
            'name',
            'description',
        ], $promotion);

        $promotion->update($data);

        return response()->json(['data' => $promotion->fresh()]);
    }

    /**
     * Supprime une promotion (admin).
     */
    public function destroy(Request $request, Promotion $promotion)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $promotion->delete();

        return response()->json(['message' => 'Promotion deleted successfully']);
    }
}
