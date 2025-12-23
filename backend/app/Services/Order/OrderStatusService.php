<?php

namespace App\Services\Order;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    /**
     * Définit les transitions de statut autorisées pour les commandes.
     * Chaque statut peut transitionner vers un ensemble limité de statuts suivants.
     * 
     * @var array<string, array<string>>
     */
    protected array $allowedTransitions = [
        'pending' => ['pending_cod', 'processing', 'cancelled'],
        'pending_cod' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
        'paid' => ['processing', 'cancelled'],
    ];

    /**
     * Vérifie si une transition de statut est autorisée.
     * 
     * @param string $fromStatus Statut actuel
     * @param string $toStatus Statut cible
     * @return bool True si la transition est autorisée
     */
    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return false;
        }

        $allowed = $this->allowedTransitions[$fromStatus] ?? [];
        return in_array($toStatus, $allowed, true);
    }

    /**
     * Retourne la liste des statuts autorisés depuis un statut donné.
     * 
     * @param string $fromStatus Statut actuel
     * @return array Liste des statuts cibles possibles
     */
    public function getAllowedTransitions(string $fromStatus): array
    {
        return $this->allowedTransitions[$fromStatus] ?? [];
    }

    /**
     * Effectue une transition de statut pour une commande.
     * Valide la transition, met à jour le statut et enregistre un log.
     * 
     * @param Order $order La commande à mettre à jour
     * @param string $newStatus Le nouveau statut
     * @param int|null $userId ID de l'utilisateur effectuant la transition
     * @param string|null $reason Raison de la transition (optionnel)
     * @return Order La commande mise à jour
     * @throws ValidationException Si la transition n'est pas autorisée
     */
    public function transition(Order $order, string $newStatus, ?int $userId = null, ?string $reason = null): Order
    {
        $oldStatus = $order->status;

        if (!$this->canTransition($oldStatus, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition from '{$oldStatus}' to '{$newStatus}'."],
            ]);
        }

        $order->forceFill(['status' => $newStatus])->save();

        Log::channel('catalogue')->info('order.status_changed', [
            'order_id' => $order->id,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);

        OrderStatusChanged::dispatch($order, $oldStatus, $newStatus);

        return $order;
    }

    /**
     * Retourne tous les statuts de commande disponibles.
     * 
     * @return array Liste de tous les statuts possibles
     */
    public function getAllStatuses(): array
    {
        return array_keys($this->allowedTransitions);
    }
}
