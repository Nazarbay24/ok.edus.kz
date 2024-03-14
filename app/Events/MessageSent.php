<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $message;
    public $user;
    public $channelId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $message, $channelId)
    {
        $this->user = $user;
        $this->message = $message;
        $this->channelId = $channelId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('chat.'.$this->channelId);
    }

    public function broadcastAs()
    {
        return 'new_message';
    }

    public function broadcastWith() {
        return [
            "message_id" => $this->message->id,
            "is_my" => false,
            "text" => $this->message->content,
            "username" => $this->user->surname.' '.$this->user->name,
            "is_read" => $this->message->read_status == 1 ? true : false,
            "date" => $this->message->created_at
        ];
    }
}
