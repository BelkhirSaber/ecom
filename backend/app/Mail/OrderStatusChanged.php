<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Crée une nouvelle instance de notification de changement de statut.
     * 
     * @param Order $order La commande dont le statut a changé
     * @param string $oldStatus L'ancien statut
     * @param string $newStatus Le nouveau statut
     */
    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    /**
     * Définit l'enveloppe du message (sujet, destinataire).
     * 
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order #{$this->order->id} Status Updated",
        );
    }

    /**
     * Définit le contenu du message email.
     * 
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-changed',
            with: [
                'order' => $this->order,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
            ],
        );
    }

    /**
     * Retourne les pièces jointes du message.
     * 
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
