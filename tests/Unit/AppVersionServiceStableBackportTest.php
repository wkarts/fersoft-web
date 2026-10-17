<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AppVersion;
use App\Services\AppVersionService;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/** Stable regressions kept separate from the existing develop identity tests. */
final class AppVersionServiceStableBackportTest extends TestCase
{
    private string $directory;
    private Application $application;
    private Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/app-version-stable-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/storage/app/releases/5.0.236', 0775, true);
        $this->application = new Application($this->directory);
        $this->application->useStoragePath($this->directory.'/storage');
        $this->application->instance('config', new Repository([
            'app' => ['timezone' => 'UTC'],
            'filesystems' => ['disks' => ['local' => ['driver' => 'local', 'root' => $this->directory.'/storage/app']]],
        ]));
        $this->application->instance('files', new Filesystem());
        $this->application->singleton('filesystem', fn ($app) => new FilesystemManager($app));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->application);
        $this->database = new Capsule($this->application);
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();
        $this->application->instance('db', $this->database->getDatabaseManager());
        $this->application->bind('db.schema', fn () => $this->database->schema());
        foreach ([
            '2026_03_10_120100_create_app_versions_table.php',
            '2026_03_10_130200_add_cumulative_release_columns_to_app_versions_table.php',
        ] as $migration) {
            (require dirname(__DIR__, 2).'/database/migrations/'.$migration)->up();
        }
        AppVersion::clearBootedModels();
        AppVersion::unsetEventDispatcher();
        $this->git(['init', '--initial-branch=main']);
        $this->git(['config', 'user.name', 'Release Test']);
        $this->git(['config', 'user.email', 'release@example.test']);
        file_put_contents($this->directory.'/code.txt', "stable 236\n");
        $this->git(['add', 'code.txt']);
        $this->git(['commit', '-m', 'stable 236']);
    }

    protected function tearDown(): void
    {
        AppVersion::unsetConnectionResolver();
        AppVersion::clearBootedModels();
        $this->database->getDatabaseManager()->disconnect();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Application::setInstance(null);
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function testPrivateStableTargetCreatesCurrentVersionWhenHistoryIsEmpty(): void
    {
        $head = $this->nextCommit();
        $this->git(['update-ref', 'refs/laravel-updater/targets/v5.0.237', $head]);
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.236"}');
        self::assertSame(0, AppVersion::query()->count());

        $service = new AppVersionService();
        $current = $service->currentOrFallback();
        self::assertTrue($current->exists);
        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertSame('stable', $current->release_channel);
        self::assertNotNull($current->installed_at);
        self::assertSame('v5.0.237', $current->metadata['installed_release']['tag']);
        self::assertSame($current->id, $service->current()->id);
        self::assertSame(1, $service->history()->count());
        self::assertSame(1, AppVersion::query()->where('is_current', true)->count());
    }

    public function testPrivateStableTargetCorrectsOldLabelAlreadyHoldingNewSha(): void
    {
        $head = $this->nextCommit();
        $old = $this->seedStable('5.0.236', $head);
        $this->git(['update-ref', 'refs/laravel-updater/targets/v5.0.237', $head]);
        self::assertSame('', $this->git(['tag', '--list']));
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.236"}');

        $service = new AppVersionService();
        self::assertSame('5.0.237', $service->resolveInstalledVersion());
        self::assertSame($head, $service->resolveInstalledRevision());
        $current = $service->currentOrFallback();
        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertSame('stable', $current->release_channel);
        self::assertSame('v5.0.237', $current->metadata['installed_release']['tag']);
        self::assertNull($current->metadata['installed_release']['base_stable_tag']);
        self::assertNull($current->metadata['installed_release']['build_number']);

        // Reconcile the label without pretending to repair unverifiable history.
        $old->refresh();
        self::assertFalse($old->is_current);
        self::assertSame($head, $old->commit_hash);
        self::assertSame('<p>Historical notes</p>', $old->release_notes_html);
        self::assertSame('2026-10-16 12:00:00', $old->installed_at->format('Y-m-d H:i:s'));
        self::assertSame(['retained' => true], $old->metadata);

        // Compare persisted rows, including database defaults absent after insert.
        $attributes = $current->fresh()->getAttributes();
        self::assertSame($attributes, $service->currentOrFallback()->fresh()->getAttributes());
        self::assertSame(2, AppVersion::query()->count());
        self::assertSame(1, AppVersion::query()->where('is_current', true)->count());
    }

    public function testExactStableTagsPreserveHistoryDuringUpgradeAndRollback(): void
    {
        $previous = $this->git(['rev-parse', 'HEAD']);
        $this->git(['tag', 'v5.0.236']);
        $old = $this->seedStable('5.0.236', $previous);
        $head = $this->nextCommit();
        $this->git(['tag', 'v5.0.237']);
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.236"}');
        $service = new AppVersionService();

        $current = $service->currentOrFallback();
        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        $old->refresh();
        self::assertSame($previous, $old->commit_hash);
        self::assertSame('<p>Historical notes</p>', $old->release_notes_html);
        self::assertSame('2026-10-16 12:00:00', $old->installed_at->format('Y-m-d H:i:s'));

        // The same service must notice the checkout instead of retaining cached identity.
        $this->git(['checkout', '--detach', $previous]);
        $rolledBack = $service->currentOrFallback();
        self::assertSame('5.0.236', $rolledBack->version);
        self::assertSame($previous, $rolledBack->commit_hash);
        self::assertSame('<p>Historical notes</p>', $rolledBack->release_notes_html);
        self::assertSame('Stable history', $rolledBack->title);
        self::assertTrue($rolledBack->metadata['retained']);
        self::assertSame($head, AppVersion::query()->where('version', '5.0.237')->value('commit_hash'));
        self::assertFalse(AppVersion::query()->where('version', '5.0.237')->first()->is_current);
        self::assertSame(2, AppVersion::query()->count());
        self::assertSame(1, AppVersion::query()->where('is_current', true)->count());
    }

    private function seedStable(string $version, string $revision): AppVersion
    {
        return AppVersion::query()->create([
            'version' => $version, 'version_major' => 5, 'version_minor' => 0, 'version_patch' => 236,
            'title' => 'Stable history', 'is_current' => true, 'commit_hash' => $revision,
            'release_channel' => 'stable', 'release_notes_html' => '<p>Historical notes</p>',
            'installed_at' => '2026-10-16 12:00:00', 'metadata' => ['retained' => true],
        ]);
    }

    private function nextCommit(): string
    {
        file_put_contents($this->directory.'/code.txt', "stable 237\n");
        $this->git(['add', 'code.txt']);
        $this->git(['commit', '-m', 'stable 237']);
        return $this->git(['rev-parse', 'HEAD']);
    }

    private function git(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), $this->directory);
        $process->mustRun();
        return trim($process->getOutput());
    }
}
