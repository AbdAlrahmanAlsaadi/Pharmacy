<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateStatuseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    public $data;

    public function __construct($message, $data = [])
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('pharmacists-channel');
    }

    public function broadcastAs()
    {
        return 'admin.notification';
    }}
