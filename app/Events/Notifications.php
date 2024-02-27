<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Notifications implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     private $userId;
     private $eventName;
     public $message;

    public function __construct($userId, $eventName, $message)
    {
        $this->userId = $userId;
        $this->eventName = $eventName;
        $this->message = $message;
    }
    public function broadcastOn()
    {
        return new PrivateChannel('notifications.'.$this->userId);
    }
    public function broadcastAs(){
        return $this->eventName;
    }
}
