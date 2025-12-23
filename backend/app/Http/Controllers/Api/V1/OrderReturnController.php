<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderReturnController extends Controller
{
    public function __construct(private OrderStatusService $statusService)
    {
    }

    /**
     * Liste les retours de l'utilisateur connecté ou tous les retours (admin).
     * 
     * @param Request $request La requête HTTP
     * @return \Illuminate\Http\JsonResponse Liste des retours
     */
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $query = OrderReturn::with(['order', 'user']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $returns = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($returns);
    }

    /**
     * Affiche un retour spécifique.
     * 
     * @param Request $request La requête HTTP
     * @param OrderReturn $orderReturn Le retour
     * @return \Illuminate\Http\JsonResponse Détails du retour
     */
    public function show(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->role !== 'admin' && $orderReturn->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $orderReturn->load(['order', 'user']);

        return response()->json(['data' => $orderReturn]);
    }

    /**
     * Crée une demande de retour pour une commande.
     * 
     * @param Request $request La requête HTTP
     * @param Order $order La commande à retourner
     * @return \Illuminate\Http\JsonResponse Le retour créé
     */
    public function store(Request $request, Order $order)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('view', $order);

        // Vérifier que la commande est éligible au retour
        if (!in_array($order->status, ['delivered', 'returned'])) {
            return response()->json([
                'error' => 'Order must be delivered to request a return',
            ], 422);
        }

        // Vérifier qu'il n'y a pas déjà un retour en cours
        $existingReturn = OrderReturn::where('order_id', $order->id)
            ->whereIn('status', ['requested', 'approved', 'received'])
            ->first();

        if ($existingReturn) {
            return response()->json([
                'error' => 'A return request already exists for this order',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:defective,wrong_item,not_as_described,changed_mind,other'],
            'description' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $orderReturn = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'requested',
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'items' => $data['items'] ?? null,
        ]);

        // Mettre à jour le statut de la commande si nécessaire
        if ($order->status === 'delivered') {
            $this->statusService->transition(
                $order,
                'returned',
                $user->id,
                'Return requested by customer'
            );
        }

        return response()->json(['data' => $orderReturn->load(['order', 'user'])], 201);
    }

    /**
     * Approuve une demande de retour (admin uniquement).
     * 
     * @param Request $request La requête HTTP
     * @param OrderReturn $orderReturn Le retour
     * @return \Illuminate\Http\JsonResponse Le retour mis à jour
     */
    public function approve(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        if ($orderReturn->status !== 'requested') {
            return response()->json([
                'error' => 'Only requested returns can be approved',
            ], 422);
        }

        $data = $request->validate([
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $orderReturn->update([
            'status' => 'approved',
            'approved_at' => now(),
            'refund_amount' => $data['refund_amount'] ?? $orderReturn->order->grand_total,
        ]);

        return response()->json(['data' => $orderReturn->fresh()]);
    }

    /**
     * Rejette une demande de retour (admin uniquement).
     * 
     * @param Request $request La requête HTTP
     * @param OrderReturn $orderReturn Le retour
     * @return \Illuminate\Http\JsonResponse Le retour mis à jour
     */
    public function reject(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        if ($orderReturn->status !== 'requested') {
            return response()->json([
                'error' => 'Only requested returns can be rejected',
            ], 422);
        }

        $orderReturn->update([
            'status' => 'rejected',
        ]);

        return response()->json(['data' => $orderReturn->fresh()]);
    }

    /**
     * Marque un retour comme reçu (admin uniquement).
     * 
     * @param Request $request La requête HTTP
     * @param OrderReturn $orderReturn Le retour
     * @return \Illuminate\Http\JsonResponse Le retour mis à jour
     */
    public function markReceived(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        if ($orderReturn->status !== 'approved') {
            return response()->json([
                'error' => 'Only approved returns can be marked as received',
            ], 422);
        }

        $orderReturn->update([
            'status' => 'received',
            'received_at' => now(),
        ]);

        return response()->json(['data' => $orderReturn->fresh()]);
    }

    /**
     * Marque un retour comme remboursé (admin uniquement).
     * 
     * @param Request $request La requête HTTP
     * @param OrderReturn $orderReturn Le retour
     * @return \Illuminate\Http\JsonResponse Le retour mis à jour
     */
    public function markRefunded(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        if ($orderReturn->status !== 'received') {
            return response()->json([
                'error' => 'Only received returns can be marked as refunded',
            ], 422);
        }

        $orderReturn->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        return response()->json(['data' => $orderReturn->fresh()]);
    }

    /**
     * Ajoute les informations de tracking du retour (client).
     * 
     * @param Request $request La requête HTTP
     * @param OrderReturn $orderReturn Le retour
     * @return \Illuminate\Http\JsonResponse Le retour mis à jour
     */
    public function addTracking(Request $request, OrderReturn $orderReturn)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->role !== 'admin' && $orderReturn->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($orderReturn->status !== 'approved') {
            return response()->json([
                'error' => 'Can only add tracking to approved returns',
            ], 422);
        }

        $data = $request->validate([
            'return_tracking_number' => ['required', 'string', 'max:255'],
            'return_tracking_carrier' => ['nullable', 'string', 'max:255'],
        ]);

        $orderReturn->update($data);

        return response()->json(['data' => $orderReturn->fresh()]);
    }
}
