<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelAdd implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $message;
    public $user;
    public $toUserId;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $message, $toUserId)
    {
        $this->user = $user;
        $this->message = $message;
        $this->toUserId = $toUserId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('channels.'.$this->toUserId);
    }

    public function broadcastAs()
    {
        return 'new_channel';
    }

    public function broadcastWith() {
        return [
            'channel_id' => $this->toUserId.'_'.$this->user->id,
            'username' => $this->user->surname.' '.$this->user->name,
            'sender' => $this->user->name,
            'message' => $this->message->content,
            'date' => $this->message->created_at,
            'unread_count' => 1,
        ];
    }
}
