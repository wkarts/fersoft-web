<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovimentoRealtime implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public array $payload;
    public int $empresaId;
    public ?int $filialId;

    public function __construct(string $type, array $payload, int $empresaId, ?int $filialId = null)
    {
        $this->type = $type;
        $this->payload = $payload;
        $this->empresaId = $empresaId;
        $this->filialId = $filialId;
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('empresa.' . $this->empresaId . '.monitor')];

        if (!empty($this->filialId)) {
            $channels[] = new PrivateChannel('empresa.' . $this->empresaId . '.filial.' . $this->filialId . '.monitor');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'movimento.realtime';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
