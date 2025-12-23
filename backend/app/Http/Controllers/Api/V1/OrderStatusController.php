<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderStatusController extends Controller
{
    public function __construct(private OrderStatusService $statusService)
    {
    }

    /**
     * Met à jour le statut d'une commande.
     * Vérifie les permissions et valide la transition de statut.
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
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updatedOrder = $this->statusService->transition(
            $order,
            $data['status'],
            $user->id,
            $data['reason'] ?? null
        );

        return new OrderResource($updatedOrder);
    }

    /**
     * Annule une commande.
     * Vérifie les permissions et effectue la transition vers le statut 'cancelled'.
     * 
     * @param Request $request La requête HTTP
     * @param Order $order La commande à annuler
     * @return OrderResource La commande annulée
     */
    public function cancel(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('cancel', $order);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updatedOrder = $this->statusService->transition(
            $order,
            'cancelled',
            $user->id,
            $data['reason'] ?? 'Cancelled by user'
        );

        return new OrderResource($updatedOrder);
    }

    /**
     * Retourne les transitions de statut autorisées pour une commande.
     * 
     * @param Request $request La requête HTTP
     * @param Order $order La commande à consulter
     * @return \Illuminate\Http\JsonResponse Liste des statuts autorisés
     */
    public function allowedTransitions(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('view', $order);

        $allowed = $this->statusService->getAllowedTransitions($order->status);

        return response()->json([
            'current_status' => $order->status,
            'allowed_transitions' => $allowed,
        ]);
    }
}
