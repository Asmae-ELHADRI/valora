<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ServiceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $serviceRequest;
    protected $customTitle;
    protected $customMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct($serviceRequest, $title = null, $message = null)
    {
        $this->serviceRequest = $serviceRequest;
        $this->customTitle = $title;
        $this->customMessage = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mission',
            'title' => $this->customTitle ?? 'Nouvelle demande',
            'service_request_id' => $this->serviceRequest->id,
            'offer_title' => $this->serviceRequest->serviceOffer->title,
            'desc' => $this->customMessage ?? 'Vous avez reçu une nouvelle mise à jour pour votre mission.',
            'time' => now()->diffForHumans(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
