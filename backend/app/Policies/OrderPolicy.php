<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Détermine si l'utilisateur peut voir la commande.
     * Un utilisateur peut voir ses propres commandes ou toutes les commandes s'il est admin.
     * 
     * @param User $user L'utilisateur authentifié
     * @param Order $order La commande à consulter
     * @return bool True si l'utilisateur peut voir la commande
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $this->isAdmin($user);
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour le statut de la commande.
     * Seuls les administrateurs peuvent modifier les statuts de commande.
     * 
     * @param User $user L'utilisateur authentifié
     * @param Order $order La commande à modifier
     * @return bool True si l'utilisateur peut modifier le statut
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Détermine si l'utilisateur peut annuler la commande.
     * Un utilisateur peut annuler sa propre commande si elle est en statut 'pending' ou 'pending_cod'.
     * Les admins peuvent annuler n'importe quelle commande.
     * 
     * @param User $user L'utilisateur authentifié
     * @param Order $order La commande à annuler
     * @return bool True si l'utilisateur peut annuler la commande
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->id !== $order->user_id) {
            return false;
        }

        return in_array($order->status, ['pending', 'pending_cod'], true);
    }

    /**
     * Vérifie si l'utilisateur a le rôle administrateur.
     * 
     * @param User $user L'utilisateur à vérifier
     * @return bool True si l'utilisateur est admin
     */
    protected function isAdmin(User $user): bool
    {
        return isset($user->role) && $user->role === 'admin';
    }
}
