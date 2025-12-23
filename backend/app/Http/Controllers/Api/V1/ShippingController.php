<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(private ShippingService $shippingService)
    {
    }

    /**
     * Calcule les options de livraison disponibles pour une adresse.
     * 
     * @param Request $request La requête HTTP
     * @return \Illuminate\Http\JsonResponse Options de livraison disponibles
     */
    public function calculate(Request $request)
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'state' => ['nullable', 'string', 'max:100'],
            'cart_total' => ['required', 'numeric', 'min:0'],
            'cart_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $address = [
            'country_code' => $data['country_code'],
            'postal_code' => $data['postal_code'] ?? '',
            'state' => $data['state'] ?? '',
        ];

        $options = $this->shippingService->calculateShippingOptions(
            $address,
            (float) $data['cart_total'],
            (float) ($data['cart_weight'] ?? 0.0)
        );

        return response()->json([
            'data' => $options,
            'meta' => [
                'count' => count($options),
                'address' => $address,
            ],
        ]);
    }

    /**
     * Retourne toutes les méthodes de livraison disponibles.
     * 
     * @return \Illuminate\Http\JsonResponse Liste des méthodes
     */
    public function methods()
    {
        $methods = $this->shippingService->getAllMethods();

        return response()->json([
            'data' => $methods,
        ]);
    }

    /**
     * Calcule le coût pour une méthode spécifique.
     * 
     * @param Request $request La requête HTTP
     * @return \Illuminate\Http\JsonResponse Coût de livraison
     */
    public function calculateMethod(Request $request)
    {
        $data = $request->validate([
            'method_key' => ['required', 'string'],
            'zone_key' => ['required', 'string'],
            'cart_total' => ['required', 'numeric', 'min:0'],
            'cart_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cost = $this->shippingService->calculateShippingCost(
            $data['method_key'],
            $data['zone_key'],
            (float) $data['cart_total'],
            (float) ($data['cart_weight'] ?? 0.0)
        );

        if ($cost === null) {
            return response()->json([
                'error' => 'Shipping method or zone not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'method_key' => $data['method_key'],
                'zone_key' => $data['zone_key'],
                'cost' => $cost,
                'currency' => config('shipping.default_currency', 'EUR'),
            ],
        ]);
    }
}
