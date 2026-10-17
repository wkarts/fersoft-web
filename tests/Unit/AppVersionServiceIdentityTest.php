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

final class AppVersionServiceIdentityTest extends TestCase
{
    private string $directory;
    private Application $application;
    private Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/app-version-service-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/storage/app/releases/5.0.230', 0775, true);
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
        file_put_contents($this->directory.'/code.txt', "old\n");
        $this->git(['add', 'code.txt']);
        $this->git(['commit', '-m', 'old release']);
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

    public function testBetaIdentityReplacesStaleLabelWithoutRewritingHistoricalCommit(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.230', $old);
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.230"}');
        $head = $this->nextCommit();
        $this->annotateBeta($head);

        $service = new AppVersionService();
        self::assertSame('5.0.236-beta.51', $service->resolveInstalledVersion());
        self::assertSame($head, $service->resolveInstalledRevision());
        $current = $service->currentOrFallback();
        self::assertSame('5.0.236-beta.51', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertSame('v5.0.235', $current->metadata['installed_release']['base_stable_tag']);
        self::assertSame('ui-continuous', $current->metadata['installed_release']['tag']);
        self::assertSame($old, AppVersion::query()->where('version', '5.0.230')->value('commit_hash'));
        self::assertFalse(AppVersion::query()->where('version', '5.0.230')->first()->is_current);

        $installedAt = $current->installed_at;
        self::assertTrue($service->currentOrFallback()->installed_at->equalTo($installedAt));
        self::assertSame(2, AppVersion::query()->count());
    }

    public function testArtifactSyncCannotAttachTheNewHeadToAnUnverifiedOldVersion(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.230', $old);
        $this->nextCommit();

        $service = new AppVersionService();
        $service->syncFromArtifacts('5.0.230');

        self::assertSame('N/A', $service->currentOrFallback()->version);
        self::assertSame('unverified', $service->currentOrFallback()->metadata['identity_status']);
        self::assertSame($old, AppVersion::query()->where('version', '5.0.230')->value('commit_hash'));
    }

    public function testRollbackReconcilesTheExactStableTagAndPreservesBetaHistory(): void
    {
        $stable = $this->git(['rev-parse', 'HEAD']);
        $this->git(['tag', 'v5.0.235']);
        $beta = $this->nextCommit();
        $this->annotateBeta($beta);
        $service = new AppVersionService();
        $service->currentOrFallback();

        $this->git(['checkout', '--detach', $stable]);
        $current = $service->currentOrFallback();

        self::assertSame('5.0.235', $current->version);
        self::assertSame($stable, $current->commit_hash);
        self::assertSame($beta, AppVersion::query()->where('version', '5.0.236-beta.51')->value('commit_hash'));
        self::assertSame(1, AppVersion::query()->where('is_current', true)->count());
    }

    public function testArtifactSyncRegistersVerifiedBetaEvenWithoutReleaseNoteFiles(): void
    {
        $head = $this->nextCommit();
        $this->annotateBeta($head);
        $service = new AppVersionService();
        $service->syncFromArtifacts();

        self::assertSame('5.0.236-beta.51', $service->current()->version);
        self::assertSame($head, $service->current()->commit_hash);
        self::assertSame('v5.0.235', $service->current()->metadata['installed_release']['base_stable_tag']);
    }

    public function testExternalCheckoutDoesNotKeepClaimingThePreviouslyVerifiedBetaIsInstalled(): void
    {
        $beta = $this->nextCommit();
        $this->annotateBeta($beta);
        $service = new AppVersionService();
        $service->currentOrFallback();
        file_put_contents($this->directory.'/other.txt', 'external checkout');
        $this->git(['add', 'other.txt']);
        $this->git(['commit', '-m', 'external revision']);

        self::assertSame('N/A', $service->currentOrFallback()->version);
        self::assertSame($beta, AppVersion::query()->where('version', '5.0.236-beta.51')->value('commit_hash'));
    }

    public function testSemanticVersionsAcceptPrereleasesAndRejectInvalidPathsAndIdentifiers(): void
    {
        $service = new AppVersionService();
        foreach (['v5.0.235', '5.0.236-beta.51', '5.0.236-rc.1', '5.0.236+build.51'] as $version) {
            self::assertTrue($service->isSemanticVersion($version), $version);
        }
        foreach (['../5.0.235', '5.0.236-beta.01', '5.0.236-', "5.0.235\nother", '05.0.235'] as $version) {
            self::assertFalse($service->isSemanticVersion($version), $version);
        }
    }

    public function testLegacyInstallationWithoutGitKeepsDeclaredVersionAndHistoricalCommitSeparate(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.230', $old);
        (new Filesystem())->deleteDirectory($this->directory.'/.git');
        mkdir($this->directory.'/storage/app/releases/5.0.235', 0775, true);
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.235"}');

        $service = new AppVersionService();
        $current = $service->currentOrFallback();

        self::assertSame('5.0.235', $current->version);
        self::assertNull($current->commit_hash);
        self::assertSame($old, AppVersion::query()->where('version', '5.0.230')->value('commit_hash'));
    }

    private function seedCurrent(string $version, string $revision): void
    {
        AppVersion::query()->create([
            'version' => $version, 'version_major' => 5, 'version_minor' => 0, 'version_patch' => 230,
            'is_current' => true, 'commit_hash' => $revision, 'installed_at' => now(),
        ]);
    }

    private function nextCommit(): string
    {
        file_put_contents($this->directory.'/code.txt', "new\n");
        $this->git(['add', 'code.txt']);
        $this->git(['commit', '-m', 'beta code']);
        return $this->git(['rev-parse', 'HEAD']);
    }

    private function annotateBeta(string $head): void
    {
        $identity = [
            'schema_version' => 1, 'version' => 'v5.0.236-beta.51', 'base_stable_tag' => 'v5.0.235',
            'git_commit_sha' => $head, 'release_channel' => 'beta-continuous', 'build_number' => 51,
        ];
        $this->git(['tag', '-a', 'ui-continuous', '-m', json_encode($identity, JSON_THROW_ON_ERROR)]);
        $this->git(['update-ref', 'refs/laravel-updater/targets/ui-continuous', 'refs/tags/ui-continuous']);
        $this->git(['tag', '-d', 'ui-continuous']);
    }

    private function git(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), $this->directory);
        $process->mustRun();
        return trim($process->getOutput());
    }
}
