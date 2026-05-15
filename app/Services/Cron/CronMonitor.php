<?php

namespace App\Services\Cron;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CronMonitor
{
    private const KEY_LAST_RUN = 'fersoft:cron:last_run';
    private const KEY_LAST_SERVER_RUN = 'fersoft:cron:last_server_run';
    private const KEY_LAST_INTERNAL_RUN = 'fersoft:cron:last_internal_run';

    public function markSchedulerRun(string $source, array $context = []): array
    {
        $source = $this->normalizeSource($source);

        $payload = [
            'source' => $source,
            'ran_at' => now()->toDateTimeString(),
            'timestamp' => now()->timestamp,
            'hostname' => gethostname() ?: null,
            'sapi' => PHP_SAPI,
            'context' => $context,
        ];

        Cache::forever(self::KEY_LAST_RUN, $payload);

        if ($source === 'server') {
            Cache::forever(self::KEY_LAST_SERVER_RUN, $payload);
        }

        if ($source === 'internal_http') {
            Cache::forever(self::KEY_LAST_INTERNAL_RUN, $payload);
        }

        return $payload;
    }

    public function status(): array
    {
        $lastRun = Cache::get(self::KEY_LAST_RUN);
        $lastServerRun = Cache::get(self::KEY_LAST_SERVER_RUN);
        $lastInternalRun = Cache::get(self::KEY_LAST_INTERNAL_RUN);

        $serverActive = $this->isServerCronActive();
        $internalActive = $this->isInternalCronActive();

        return [
            'mode' => $this->httpMode(),
            'server_active' => $serverActive,
            'internal_active' => $internalActive,
            'effective_source' => $serverActive ? 'server' : ($internalActive ? 'internal_http' : null),
            'ttl_seconds' => $this->ttlSeconds(),
            'last_run' => $lastRun,
            'last_server_run' => $lastServerRun,
            'last_internal_run' => $lastInternalRun,
            'now' => now()->toDateTimeString(),
        ];
    }

    public function shouldRunInternalHttp(): bool
    {
        $mode = $this->httpMode();

        if ($mode === 'disabled') {
            return false;
        }

        if ($mode === 'always') {
            return true;
        }

        return ! $this->isServerCronActive();
    }

    public function denyReasonForInternalHttp(): string
    {
        $mode = $this->httpMode();

        if ($mode === 'disabled') {
            return 'Cron interno HTTP está desabilitado por CRON_HTTP_MODE=disabled.';
        }

        if ($mode === 'auto' && $this->isServerCronActive()) {
            return 'Cron do servidor detectado como ativo recentemente. Execução HTTP ignorada para evitar duplicidade.';
        }

        return '';
    }

    public function isServerCronActive(): bool
    {
        return $this->isRecent(Cache::get(self::KEY_LAST_SERVER_RUN));
    }

    public function isInternalCronActive(): bool
    {
        return $this->isRecent(Cache::get(self::KEY_LAST_INTERNAL_RUN));
    }

    public function httpMode(): string
    {
        $mode = strtolower((string) env('CRON_HTTP_MODE', 'auto'));

        if (! in_array($mode, ['auto', 'always', 'disabled'], true)) {
            return 'auto';
        }

        return $mode;
    }

    public function ttlSeconds(): int
    {
        $ttl = (int) env('CRON_SERVER_HEARTBEAT_TTL_SECONDS', 180);

        return max(60, $ttl);
    }

    private function isRecent($payload): bool
    {
        if (! is_array($payload) || empty($payload['timestamp'])) {
            return false;
        }

        return ((int) $payload['timestamp']) >= now()->subSeconds($this->ttlSeconds())->timestamp;
    }

    private function normalizeSource(string $source): string
    {
        $source = strtolower(trim($source));

        if (in_array($source, ['http', 'internal', 'internal_http', 'web'], true)) {
            return 'internal_http';
        }

        return 'server';
    }
}
