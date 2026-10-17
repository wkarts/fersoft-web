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

    public function testStableTagReplacesPreviousStableLabelDespiteStaleComposerArtifactsAndDatabase(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.236', $old);
        AppVersion::query()->where('version', '5.0.236')->update([
            'title' => 'Previous stable release',
            'release_notes_current_html' => '<p>Previous stable notes.</p>',
        ]);
        $this->git(['tag', 'v5.0.236']);
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.236"}');
        mkdir($this->directory.'/storage/app/releases/5.0.236', 0775, true);
        file_put_contents($this->directory.'/storage/app/releases/manifest.json', json_encode([
            'current_version' => '5.0.236',
            'releases' => [['version' => '5.0.236', 'commit_hash' => $old]],
        ], JSON_THROW_ON_ERROR));
        $head = $this->nextCommit();
        // Reproduce the exact private tag ref fetched by the Laravel Updater.
        $this->git(['update-ref', 'refs/laravel-updater/targets/v5.0.237', $head]);

        $service = new AppVersionService();
        self::assertSame('5.0.237', $service->resolveInstalledVersion());
        self::assertSame($head, $service->resolveInstalledRevision());
        $current = $service->currentOrFallback();
        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertSame('stable', $current->release_channel);
        self::assertSame('v5.0.237', $current->metadata['installed_release']['tag']);
        self::assertSame(237, $current->version_patch);
        $previous = AppVersion::query()->where('version', '5.0.236')->firstOrFail();
        self::assertFalse($previous->is_current);
        self::assertSame($old, $previous->commit_hash);
        self::assertSame('<p>Previous stable notes.</p>', $previous->release_notes_current_html);
        $installedAt = $current->installed_at;
        self::assertTrue($service->currentOrFallback()->installed_at->equalTo($installedAt));
        self::assertSame(3, AppVersion::query()->count());
        self::assertSame(1, AppVersion::query()->where('is_current', true)->count());
    }

    public function testNewVerifiedIdentityImportsExistingReleaseHistoryAndNotesOnlyOnce(): void
    {
        $head = $this->git(['rev-parse', 'HEAD']);
        $this->git(['update-ref', 'refs/laravel-updater/targets/v5.0.237', $head]);
        file_put_contents($this->directory.'/storage/app/releases/manifest.json', json_encode([
            'current_version' => '5.0.236',
            'releases' => [[
                'version' => '5.0.236', 'title' => 'Previous stable release',
                'current_html' => '<p>Previous stable notes.</p>',
                'installed_at' => '2026-01-02 03:04:05', 'commit_hash' => str_repeat('a', 40),
            ]],
        ], JSON_THROW_ON_ERROR));
        mkdir($this->directory.'/storage/app/releases/5.0.237', 0775, true);
        file_put_contents($this->directory.'/storage/app/releases/5.0.237/current.html', '<p>Installed release notes.</p>');
        self::assertSame(0, AppVersion::query()->count());

        $service = new class extends AppVersionService {
            public int $manifestImports = 0;
            public int $artifactImports = 0;

            public function syncFromManifest(?string $manifestPath = null): int
            {
                $this->manifestImports++;
                return parent::syncFromManifest($manifestPath);
            }

            public function syncFromArtifacts(?string $installedVersion = null): int
            {
                $this->artifactImports++;
                return parent::syncFromArtifacts($installedVersion);
            }
        };
        $current = $service->currentOrFallback();

        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertSame('<p>Installed release notes.</p>', $current->release_notes_current_html);
        $previous = AppVersion::query()->where('version', '5.0.236')->firstOrFail();
        self::assertSame('<p>Previous stable notes.</p>', $previous->release_notes_current_html);
        self::assertSame('2026-01-02 03:04:05', $previous->installed_at->format('Y-m-d H:i:s'));
        self::assertSame(str_repeat('a', 40), $previous->commit_hash);
        self::assertFalse($previous->is_current);
        self::assertSame(1, $service->manifestImports);
        self::assertSame(1, $service->artifactImports);
        $installedAt = $current->installed_at;
        // Simulate the JSON key ordering returned by a native MySQL JSON column.
        $metadata = $current->metadata;
        $metadata['installed_release'] = array_reverse($metadata['installed_release'], true);
        $current->metadata = $metadata;
        $current->updated_at = '2026-02-03 04:05:06';
        $current->save();
        $again = $service->currentOrFallback();
        self::assertSame('<p>Installed release notes.</p>', $again->release_notes_current_html);
        self::assertTrue($again->installed_at->equalTo($installedAt));
        self::assertSame('2026-02-03 04:05:06', $again->updated_at->format('Y-m-d H:i:s'));
        self::assertSame(1, $service->manifestImports);
        self::assertSame(1, $service->artifactImports);
    }

    public function testExactStableTagCorrectsTheLabelEvenWhenOldRecordAlreadyCarriesTheNewHash(): void
    {
        $head = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.236', $head);
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.236"}');
        $this->git(['tag', 'v5.0.237']);

        $current = (new AppVersionService())->currentOrFallback();

        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertFalse(AppVersion::query()->where('version', '5.0.236')->firstOrFail()->is_current);
        // Do not invent a replacement hash for historical data already inconsistent.
        self::assertSame($head, AppVersion::query()->where('version', '5.0.236')->value('commit_hash'));
    }

    public function testStableZipIdentityReconcilesTheDatabaseWithoutGit(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.236', $old);
        $head = $this->nextCommit();
        (new Filesystem())->deleteDirectory($this->directory.'/.git');
        file_put_contents($this->directory.'/composer.json', '{"version":"5.0.236"}');
        file_put_contents($this->directory.'/release-build-info.json', json_encode([
            'schema_version' => 1, 'version' => 'v5.0.237', 'tag' => 'v5.0.237',
            'git_commit_sha' => $head, 'release_channel' => 'stable', 'build_number' => '39017970356',
        ], JSON_THROW_ON_ERROR));

        $service = new AppVersionService();
        $current = $service->currentOrFallback();

        self::assertSame('5.0.237', $current->version);
        self::assertSame($head, $current->commit_hash);
        self::assertSame('build-info', $current->metadata['installed_release']['source']);
        self::assertSame('39017970356', $current->build_number);
        self::assertSame($old, AppVersion::query()->where('version', '5.0.236')->value('commit_hash'));
    }

    public function testArtifactImportPreservesRecordedNotesHashesAndInstallationDatesAfterReconciliation(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.236', $old);
        $head = $this->nextCommit();
        $this->git(['tag', 'v5.0.237']);
        $service = new AppVersionService();
        $service->currentOrFallback();
        foreach (['5.0.236', '5.0.237'] as $version) {
            AppVersion::query()->where('version', $version)->update([
                'title' => 'Recorded '.$version,
                'installed_at' => '2026-01-02 03:04:05',
                'release_notes_current_html' => '<p>Recorded notes '.$version.'</p>',
                'release_notes_current_pdf_path' => 'releases/'.$version.'/recorded.pdf',
            ]);
            mkdir($this->directory.'/storage/app/releases/'.$version, 0775, true);
            file_put_contents($this->directory.'/storage/app/releases/'.$version.'/current.html', '<p>Different imported notes.</p>');
        }

        // Even an old explicit argument must not replace a verified checkout identity.
        $service->syncFromArtifacts('5.0.236');

        foreach (['5.0.236' => $old, '5.0.237' => $head] as $version => $sha) {
            $record = AppVersion::query()->where('version', $version)->firstOrFail();
            self::assertSame('Recorded '.$version, $record->title);
            self::assertSame('2026-01-02 03:04:05', $record->installed_at->format('Y-m-d H:i:s'));
            self::assertSame('<p>Recorded notes '.$version.'</p>', $record->release_notes_current_html);
            self::assertSame('releases/'.$version.'/recorded.pdf', $record->release_notes_current_pdf_path);
            self::assertSame($sha, $record->commit_hash);
        }
        self::assertSame('5.0.237', $service->current()->version);
        self::assertSame(1, AppVersion::query()->where('is_current', true)->count());
    }

    public function testManifestImportOnlyFillsMissingHistoryAndCannotSelectAnOldInstalledVersion(): void
    {
        $old = $this->git(['rev-parse', 'HEAD']);
        $this->seedCurrent('5.0.236', $old);
        $head = $this->nextCommit();
        $this->git(['tag', 'v5.0.237']);
        $service = new AppVersionService();
        $service->currentOrFallback();
        AppVersion::query()->where('version', '5.0.236')->update([
            'title' => 'Recorded previous release',
            'installed_at' => '2026-01-02 03:04:05',
            'release_notes_current_html' => '<p>Historical notes.</p>',
        ]);
        AppVersion::query()->where('version', '5.0.237')->update([
            'title' => 'Recorded installed release',
            'installed_at' => '2026-02-03 04:05:06',
            'release_notes_current_html' => '<p>Installed release notes.</p>',
        ]);
        file_put_contents($this->directory.'/storage/app/releases/manifest.json', json_encode([
            'current_version' => '5.0.236',
            'releases' => [
                [
                    'version' => '5.0.236', 'title' => 'Different imported title',
                    'current_html' => '<p>Different imported notes.</p>', 'commit_hash' => null,
                    'installed_at' => '2026-10-17 00:00:00', 'author' => 'Imported historical author',
                ],
                [
                    'version' => '5.0.237', 'title' => null, 'current_html' => null,
                    'commit_hash' => null, 'installed_at' => '2026-10-17 00:00:00', 'metadata' => [],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $service->syncFromManifest();

        $previous = AppVersion::query()->where('version', '5.0.236')->firstOrFail();
        self::assertSame('Recorded previous release', $previous->title);
        self::assertSame('<p>Historical notes.</p>', $previous->release_notes_current_html);
        self::assertSame('2026-01-02 03:04:05', $previous->installed_at->format('Y-m-d H:i:s'));
        self::assertSame($old, $previous->commit_hash);
        self::assertSame('Imported historical author', $previous->author);
        self::assertFalse($previous->is_current);
        $current = $service->current();
        self::assertSame('5.0.237', $current->version);
        self::assertSame('Recorded installed release', $current->title);
        self::assertSame('<p>Installed release notes.</p>', $current->release_notes_current_html);
        self::assertSame('2026-02-03 04:05:06', $current->installed_at->format('Y-m-d H:i:s'));
        self::assertSame($head, $current->commit_hash);
        self::assertSame('v5.0.237', $current->metadata['installed_release']['tag']);
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
        [$major, $minor, $patch] = array_map('intval', explode('.', $version));
        AppVersion::query()->create([
            'version' => $version, 'version_major' => $major, 'version_minor' => $minor, 'version_patch' => $patch,
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
