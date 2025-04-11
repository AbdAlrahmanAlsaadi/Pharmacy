<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $data;

    public function __construct($message, $data = [])
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('pharmacists-channel');
    }

    public function broadcastAs()
    {
        return 'admin.notification';
    }
}
