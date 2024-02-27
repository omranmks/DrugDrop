<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicNotifications implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $eventName;
    public $message;
    public $mixedData;

   public function __construct($eventName, $message, $data)
   {
       $this->eventName = $eventName;
       $this->message = $message;
       $this->mixedData = $data;
   }
   public function broadcastOn()
   {
       return new Channel('notifications');
   }
   public function broadcastAs(){
       return $this->eventName;
   }
}
