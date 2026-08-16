<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class StockOpnameCreated extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $url;

    public function __construct($title, $message, $url = '/audit/stock-opname')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'color' => 'primary',
            'icon' => 'ri-file-list-3-line',
        ];
    }
}
