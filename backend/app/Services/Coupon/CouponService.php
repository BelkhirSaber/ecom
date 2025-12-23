<?php

namespace App\Services\Coupon;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponService
{
    /**
     * Valide et applique un coupon à un panier.
     * 
     * @param string $code Code du coupon
     * @param Cart $cart Panier
     * @param int $userId ID de l'utilisateur
     * @return array Résultat de la validation et montant de la réduction
     * @throws \Exception Si le coupon n'est pas valide
     */
    public function validateAndApply(string $code, Cart $cart, int $userId): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            throw new \Exception('Coupon not found');
        }

        if (!$coupon->isValid()) {
            throw new \Exception('Coupon is not valid or has expired');
        }

        if (!$coupon->canBeUsedByUser($userId)) {
            throw new \Exception('You have reached the usage limit for this coupon');
        }

        // Vérifier le montant minimum de commande
        if ($coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
            throw new \Exception("Minimum order amount of {$coupon->min_order_amount} required");
        }

        // Vérifier les produits applicables
        if ($coupon->applicable_products && !empty($coupon->applicable_products)) {
            $cartProductIds = $cart->items->pluck('purchasable_id')->toArray();
            $hasApplicableProduct = !empty(array_intersect($cartProductIds, $coupon->applicable_products));
            
            if (!$hasApplicableProduct) {
                throw new \Exception('This coupon is not applicable to items in your cart');
            }
        }

        // Vérifier les catégories applicables
        if ($coupon->applicable_categories && !empty($coupon->applicable_categories)) {
            $cartCategoryIds = $cart->items->map(function ($item) {
                return $item->purchasable?->category_id;
            })->filter()->unique()->toArray();
            
            $hasApplicableCategory = !empty(array_intersect($cartCategoryIds, $coupon->applicable_categories));
            
            if (!$hasApplicableCategory) {
                throw new \Exception('This coupon is not applicable to items in your cart');
            }
        }

        // Calculer la réduction
        $discountAmount = $this->calculateDiscount($coupon, $cart);

        return [
            'coupon' => $coupon,
            'discount_amount' => $discountAmount,
            'message' => 'Coupon applied successfully',
        ];
    }

    /**
     * Calcule le montant de la réduction.
     * 
     * @param Coupon $coupon Le coupon
     * @param Cart $cart Le panier
     * @return float Montant de la réduction
     */
    public function calculateDiscount(Coupon $coupon, Cart $cart): float
    {
        $subtotal = (float) $cart->subtotal;

        if ($coupon->type === 'fixed') {
            $discount = (float) $coupon->value;
        } elseif ($coupon->type === 'percentage') {
            $discount = $subtotal * ((float) $coupon->value / 100);
        } else {
            $discount = 0;
        }

        // Appliquer le plafond de réduction si défini
        if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
            $discount = (float) $coupon->max_discount_amount;
        }

        // Ne pas dépasser le montant du panier
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return round($discount, 2);
    }

    /**
     * Enregistre l'utilisation d'un coupon.
     * 
     * @param Coupon $coupon Le coupon
     * @param int $userId ID de l'utilisateur
     * @param int|null $orderId ID de la commande (optionnel)
     * @return CouponUsage L'enregistrement d'utilisation
     */
    public function recordUsage(Coupon $coupon, int $userId, ?int $orderId = null): CouponUsage
    {
        $coupon->increment('usage_count');

        return CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $userId,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Vérifie si un code coupon existe et est valide.
     * 
     * @param string $code Code du coupon
     * @return array Informations sur la validité
     */
    public function checkCoupon(string $code): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => 'Coupon not found',
            ];
        }

        if (!$coupon->isValid()) {
            return [
                'valid' => false,
                'message' => 'Coupon is not valid or has expired',
                'coupon' => $coupon,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Coupon is valid',
            'coupon' => $coupon,
        ];
    }
}
