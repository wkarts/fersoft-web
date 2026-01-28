<?php

namespace App\Http\Controllers\Updates;

use App\Http\Controllers\Controller;
use App\Models\Updates\UpdateLog;
use App\Models\Updates\UpdateVersion;
use App\Services\Updates\UpdateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateApiController extends Controller
{
    public function __construct(protected UpdateManager $updateManager)
    {
    }

    public function current(): JsonResponse
    {
        $current = $this->updateManager->currentVersion();

        return response()->json([
            'version' => $current?->version,
            'status' => $current?->status,
            'applied_at' => $current?->applied_at,
            'mode' => $current?->mode ?? config('updates.mode', 'stable'),
            'provider' => $current?->provider ?? config('updates.default_provider'),
            'observations' => $current?->observations,
            'metadata' => $current?->metadata,
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $current = $request->query('current_version');
        $provider = $request->query('provider');

        if (! $current) {
            $current = $this->updateManager->currentVersion()?->version;
        }

        $versions = $this->updateManager->fetchAvailableVersions($current, $provider);

        return response()->json([
            'available' => $versions->map(fn ($version) => [
                'version' => $version->identifier,
                'description' => $version->description,
                'released_at' => $version->releasedAt?->toIso8601String(),
                'metadata' => $version->metadata,
            ]),
            'current_version' => $current,
            'mode' => config('updates.mode', 'stable'),
            'branch' => config('updates.mode') === 'dev'
                ? config('updates.dev_branch', 'develop')
                : config('updates.providers.github.branch', 'main'),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string'],
            'provider' => ['nullable', 'string'],
            'downgrade' => ['sometimes', 'boolean'],
            'composer_action' => ['nullable', 'string', 'in:none,install,update,dump-autoload'],
            'observations' => ['nullable', 'string'],
            'restore_dependencies' => ['nullable', 'boolean'],
            'mode' => ['nullable', 'in:stable,dev'],
        ]);

        $metadata = $request->input('metadata', []);
        $mode = $data['mode'] ?? ($data['downgrade'] ? null : config('updates.mode', 'stable'));
        if (! $mode) {
            $mode = config('updates.mode', 'stable');
        }
        if (! is_array($metadata)) {
            $metadata = [];
        }
        $metadata['requested_via'] = 'api';
        $metadata['requested_at'] = now()->toIso8601String();

        $version = $this->updateManager->scheduleUpdate(
            $data['version'],
            $data['provider'] ?? null,
            (bool) ($data['downgrade'] ?? false),
            [
                'composer_action' => $data['composer_action'] ?? null,
                'observations' => $data['observations'] ?? null,
                'restore_dependencies' => $request->boolean('restore_dependencies'),
                'mode' => $mode,
                'metadata' => $metadata,
            ]
        );

        return response()->json([
            'message' => 'Update scheduled successfully.',
            'version' => $version->version,
            'status' => $version->status,
            'mode' => $version->mode,
        ], Response::HTTP_ACCEPTED);
    }

    public function history(): JsonResponse
    {
        $history = UpdateVersion::query()->latest('created_at')->with('logs')->paginate(15);

        return response()->json($history);
    }

    public function log(UpdateVersion $version, UpdateLog $log): JsonResponse
    {
        abort_unless($log->update_version_id === $version->id, 404);

        return response()->json($log);
    }
}
