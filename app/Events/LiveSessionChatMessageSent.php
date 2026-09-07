<?php

namespace App\Events;

use App\Models\LiveSessionChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $liveSessionId;
    public int $userId;
    public string $userName;
    public string $message;
    public string $timestamp;

    public function __construct(LiveSessionChatMessage $chatMessage)
    {
        $this->liveSessionId = $chatMessage->live_session_id;
        $this->userId = $chatMessage->user_id;
        $this->userName = $chatMessage->user_name;
        $this->message = $chatMessage->message;
        $this->timestamp = $chatMessage->created_at->format('H:i');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("live-session.{$this->liveSessionId}.chat");
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }
}
