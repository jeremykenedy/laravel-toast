<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ToastBroadcast implements ShouldBroadcast
{
    public bool $afterCommit = true;

    public function __construct(private string $channel, private array $toast)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'toast';
    }

    public function broadcastWith(): array
    {
        return ['toast' => $this->toast];
    }
}
