<?php

namespace App\Jobs\Updates;

use App\Models\Updates\UpdateVersion;
use App\Services\Updates\UpdateManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ApplyUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected UpdateVersion $version,
        protected ?string $provider = null,
        protected bool $downgrade = false
    ) {
        $this->onQueue('updates');
    }

    public function handle(UpdateManager $updateManager): void
    {
        $updateManager->applyVersion($this->version->fresh(), $this->provider, $this->downgrade);
    }

    public function failed(Throwable $exception): void
    {
        $version = $this->version->fresh();
        if (! $version) {
            return;
        }

        $version->status = 'failed';
        $version->save();

        $updateManager = app(UpdateManager::class);
        $updateManager->log($version, 'apply', 'failed', $exception->getMessage(), [
            'exception' => $exception->getTraceAsString(),
        ]);
    }
}
