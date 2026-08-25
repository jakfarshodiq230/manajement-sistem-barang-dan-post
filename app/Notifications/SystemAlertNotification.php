<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SystemAlertNotification extends Notification
{
    use Queueable;

    public string $title;
    public string $message;
    public ?string $url;
    public string $type;
    public string $icon;
    public ?string $eventCode;
    public ?int $branchId;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'primary',
        string $icon = 'ri-notification-3-line',
        ?string $eventCode = null,
        ?int $branchId = null
    ) {
        $this->title     = $title;
        $this->message   = $message;
        $this->url       = $url;
        $this->type      = $type;
        $this->icon      = $icon;
        $this->eventCode = $eventCode;
        $this->branchId  = $branchId;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'      => $this->title,
            'message'    => $this->message,
            'url'        => $this->url,
            'type'       => $this->type,
            'icon'       => $this->icon,
            'event_code' => $this->eventCode,
            'branch_id'  => $this->branchId,
        ];
    }
}
