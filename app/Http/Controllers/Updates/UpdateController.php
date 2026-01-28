<?php

namespace App\Http\Controllers\Updates;

use App\Http\Controllers\Controller;
use App\Models\Updates\UpdateLog;
use App\Models\Updates\UpdateVersion;
use App\Services\Updates\UpdateManager;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpdateController extends Controller
{
    public function __construct(
        protected UpdateManager $updateManager,
        protected ViewFactory $viewFactory
    ) {
        $this->middleware(function ($request, $next) {
            $session = session('user_logged');

            if (!$session) {
                return redirect('/login');
            }

            if (empty($session['super'])) {
                return redirect('/graficos');
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $currentVersion = $this->updateManager->currentVersion();
        $available      = $this->updateManager->fetchAvailableVersions($currentVersion?->version);
        $history        = UpdateVersion::query()->latest('created_at')->with('latestLog')->paginate(10);

        $defaultProvider = config('updates.default_provider');
        $providers = collect(config('updates.providers', []))
            ->filter(function ($settings, $key) use ($defaultProvider) {
                if ($key === $defaultProvider) {
                    return true;
                }

                return is_array($settings) && array_filter($settings);
            })
            ->keys()
            ->values()
            ->all();
        $composerActions = [
            'none' => 'Não executar composer',
            'install' => 'composer install',
            'update' => 'composer update',
            'dump-autoload' => 'composer dump-autoload',
        ];

        return $this->viewFactory->make('updates.dashboard', [
            'currentVersion' => $currentVersion,
            'availableVersions' => $available,
            'history' => $history,
            'allowDowngrade' => (bool) config('updates.allow_downgrade'),
            'providers' => $providers,
            'defaultProvider' => $defaultProvider,
            'mode' => config('updates.mode', 'stable'),
            'stableBranch' => config('updates.providers.github.branch', 'main'),
            'devBranch' => config('updates.dev_branch', 'develop'),
            'composerActions' => $composerActions,
            'defaultComposerAction' => config('updates.composer.default_action', 'none'),
            'title' => 'Atualizações do Sistema',
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string'],
            'provider' => ['nullable', 'string'],
            'composer_action' => ['nullable', 'string', 'in:none,install,update,dump-autoload'],
            'observations' => ['nullable', 'string'],
            'follow' => ['nullable', 'boolean'],
        ]);

        $provider = $data['provider'] ?? null;
        $currentVersion = $this->updateManager->currentVersion();
        $available = $this->updateManager->fetchAvailableVersions($currentVersion?->version, $provider);
        $selected = $available->firstWhere(fn ($item) => $item->identifier === $data['version']);
        if (! $selected) {
            return back()->withErrors(['version' => __('A versão selecionada não está disponível para atualização.')])->withInput();
        }

        $metadata = $selected->metadata ?? [];
        if ($selected?->releasedAt) {
            $metadata['released_at'] = $selected->releasedAt->toIso8601String();
        }
        $metadata['identifier'] = $data['version'];
        $metadata['requested_via'] = 'web';
        $metadata['requested_at'] = now()->toIso8601String();

        if (config('updates.mode') === 'dev' && empty($metadata['sha'])) {
            return back()->withErrors(['version' => __('Não foi possível localizar o commit selecionado para atualização.')])->withInput();
        }

        $version = $this->updateManager->scheduleUpdate(
            $data['version'],
            $provider,
            false,
            [
                'composer_action' => $data['composer_action'] ?? null,
                'observations' => $data['observations'] ?? null,
                'mode' => config('updates.mode', 'stable'),
                'metadata' => $metadata,
            ]
        );

        if ($request->boolean('follow')) {
            return redirect()->route('updates.running', $version)->with('status', __('Update scheduled successfully.'));
        }

        return redirect()->route('updates.index')->with('status', __('Update scheduled successfully.'));
    }

    public function downgrade(Request $request, UpdateVersion $version): RedirectResponse
    {
        abort_unless(config('updates.allow_downgrade'), 403);

        $data = $request->validate([
            'provider' => ['nullable', 'string'],
            'composer_action' => ['nullable', 'string', 'in:none,install,update,dump-autoload'],
            'observations' => ['nullable', 'string'],
            'restore_dependencies' => ['nullable', 'boolean'],
            'follow' => ['nullable', 'boolean'],
        ]);

        $metadata = $version->metadata ?? [];
        $metadata['identifier'] = $version->version;
        $metadata['requested_via'] = 'web';
        $metadata['requested_at'] = now()->toIso8601String();
        if (config('updates.mode') === 'dev' && empty($metadata['sha'])) {
            return back()->withErrors(['version' => __('Não há commit registrado para esta versão. Não é possível realizar o downgrade.')])->withInput();
        }

        $scheduled = $this->updateManager->scheduleUpdate(
            $version->version,
            $data['provider'] ?? null,
            true,
            [
                'composer_action' => $data['composer_action'] ?? null,
                'observations' => $data['observations'] ?? null,
                'restore_dependencies' => $request->boolean('restore_dependencies'),
                'mode' => $version->mode ?? config('updates.mode', 'stable'),
                'metadata' => $metadata,
            ]
        );

        if ($request->boolean('follow')) {
            return redirect()->route('updates.running', $scheduled)->with('status', __('Downgrade scheduled successfully.'));
        }

        return redirect()->route('updates.index')->with('status', __('Downgrade scheduled successfully.'));
    }

    public function showLog(UpdateVersion $version, UpdateLog $log): View
    {
        abort_unless($log->update_version_id === $version->id, 404);

        return $this->viewFactory->make('updates.log', [
            'version' => $version,
            'log' => $log,
            'migrations' => $version->migrations()->orderBy('created_at')->get(),
            'title' => 'Logs de Atualização',
        ]);
    }

    public function running(UpdateVersion $version): View
    {
        return $this->viewFactory->make('updates.running', [
            'version' => $version->load('logs'),
            'title' => 'Execução de atualização',
        ]);
    }

    public function logsStream(UpdateVersion $version, Request $request): \Illuminate\Http\JsonResponse
    {
        $lastId = (int) $request->query('last_id', 0);
        $logs = $version->logs()
            ->when($lastId > 0, fn ($query) => $query->where('id', '>', $lastId))
            ->orderBy('id')
            ->get();

        $version->refresh();

        return response()->json([
            'logs' => $logs->map(fn (UpdateLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'status' => $log->status,
                'message' => $log->message,
                'context' => $log->context,
                'created_at' => $log->created_at?->toIso8601String(),
            ]),
            'version_status' => $version->status,
            'completed' => in_array($version->status, ['completed', 'failed'], true),
        ]);
    }
}
