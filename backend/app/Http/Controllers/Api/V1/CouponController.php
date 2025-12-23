<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private CouponService $couponService)
    {
    }

    /**
     * Liste tous les coupons (admin uniquement).
     */
    public function index(Request $request)
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->paginate(15);

        return response()->json($coupons);
    }

    /**
     * Crée un nouveau coupon (admin uniquement).
     */
    public function store(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_categories' => ['nullable', 'array'],
        ]);

        $data['code'] = strtoupper($data['code']);

        $coupon = Coupon::create($data);

        return response()->json(['data' => $coupon], 201);
    }

    /**
     * Affiche un coupon (admin uniquement).
     */
    public function show(Request $request, Coupon $coupon)
    {
        return response()->json(['data' => $coupon]);
    }

    /**
     * Met à jour un coupon (admin uniquement).
     */
    public function update(Request $request, Coupon $coupon)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:coupons,code,' . $coupon->id],
            'type' => ['sometimes', 'in:fixed,percentage'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_categories' => ['nullable', 'array'],
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);

        return response()->json(['data' => $coupon->fresh()]);
    }

    /**
     * Supprime un coupon (admin uniquement).
     */
    public function destroy(Request $request, Coupon $coupon)
    {
        $user = $request->user('sanctum');
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted successfully']);
    }

    /**
     * Valide un code coupon pour le panier actuel.
     */
    public function validate(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'error' => 'Cart not found',
            ], 404);
        }

        try {
            $result = $this->couponService->validateAndApply($data['code'], $cart, $user->id);

            return response()->json([
                'valid' => true,
                'discount_amount' => $result['discount_amount'],
                'message' => $result['message'],
                'coupon' => [
                    'code' => $result['coupon']->code,
                    'type' => $result['coupon']->type,
                    'value' => $result['coupon']->value,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
