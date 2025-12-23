<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderTrackingController extends Controller
{
    public function __construct(private OrderStatusService $statusService)
    {
    }

    /**
     * Ajoute ou met à jour les informations de tracking d'une commande.
     * Réservé aux administrateurs.
     * 
     * @param Request $request La requête HTTP
     * @param Order $order La commande à mettre à jour
     * @return OrderResource La commande mise à jour
     */
    public function update(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('updateStatus', $order);

        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:255'],
            'tracking_carrier' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
        ]);

        // Si la commande n'est pas encore expédiée, la passer en "shipped"
        if ($order->status === 'processing') {
            $this->statusService->transition(
                $order,
                'shipped',
                $user->id,
                'Tracking information added'
            );
        }

        $order->update([
            'tracking_number' => $data['tracking_number'],
            'tracking_carrier' => $data['tracking_carrier'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'shipped_at' => $order->shipped_at ?? now(),
        ]);

        return new OrderResource($order->fresh());
    }

    /**
     * Récupère les informations de tracking d'une commande.
     * Accessible au propriétaire et aux admins.
     * 
     * @param Request $request La requête HTTP
     * @param Order $order La commande
     * @return \Illuminate\Http\JsonResponse Informations de tracking
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('view', $order);

        return response()->json([
            'data' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'tracking_carrier' => $order->tracking_carrier,
                'tracking_url' => $order->tracking_url,
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'status' => $order->status,
            ],
        ]);
    }

    /**
     * Marque une commande comme livrée.
     * Réservé aux administrateurs.
     * 
     * @param Request $request La requête HTTP
     * @param Order $order La commande
     * @return OrderResource La commande mise à jour
     */
    public function markDelivered(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('updateStatus', $order);

        if ($order->status !== 'shipped') {
            return response()->json([
                'error' => 'Order must be in shipped status to mark as delivered',
            ], 422);
        }

        $this->statusService->transition(
            $order,
            'delivered',
            $user->id,
            'Package delivered'
        );

        $order->update([
            'delivered_at' => now(),
        ]);

        return new OrderResource($order->fresh());
    }
}
