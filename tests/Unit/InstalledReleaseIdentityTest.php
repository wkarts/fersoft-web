<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\InstalledReleaseIdentity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InstalledReleaseIdentityTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/installed-release-'.bin2hex(random_bytes(8));
        mkdir($this->root, 0700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
        parent::tearDown();
    }

    public function testLightweightStableTagMustPointAtHead(): void
    {
        $head = $this->initGit();
        $this->git(['tag', 'v5.0.235']);
        $identity = (new InstalledReleaseIdentity($this->root))->resolve();

        self::assertSame('5.0.235', $identity['version']);
        self::assertSame($head, $identity['git_commit_sha']);
        self::assertSame('stable', $identity['release_channel']);
        self::assertSame('v5.0.235', $identity['tag']);
        self::assertSame('git-tag', $identity['source']);
        self::assertNull($identity['base_stable_tag']);
    }

    public function testNewestUnrelatedTagIsNeverInstalledVersion(): void
    {
        $first = $this->initGit();
        $this->git(['tag', 'v999.0.0']);
        $this->commit('second');
        self::assertNotSame($first, trim($this->git(['rev-parse', 'HEAD'])));
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testOlderExactTagIsFoundBeyondHundredsOfUnrelatedTags(): void
    {
        $old = $this->initGit();
        $commands = '';
        for ($index = 0; $index < 300; $index++) {
            $commands .= 'update refs/tags/v999.0.'.$index.' '.$old."\n";
        }
        $process = new Process(['git', 'update-ref', '--stdin'], $this->root);
        $process->setInput($commands);
        $process->setTimeout(3);
        $process->mustRun();
        $this->commit('current');
        $this->git(['tag', 'v5.0.235']);

        self::assertSame('5.0.235', (new InstalledReleaseIdentity($this->root))->resolve()['version']);
    }

    public function testAnnotationCarriesContinuousBetaIdentity(): void
    {
        $head = $this->initGit();
        $this->annotate('ui-continuous', $this->metadata($head));
        $identity = (new InstalledReleaseIdentity($this->root))->resolve();

        self::assertSame('5.0.236-beta.51', $identity['version']);
        self::assertSame('v5.0.235', $identity['base_stable_tag']);
        self::assertSame($head, $identity['git_commit_sha']);
        self::assertSame('beta-continuous', $identity['release_channel']);
        self::assertSame('51', $identity['build_number']);
        self::assertSame('ui-continuous', $identity['tag']);
        self::assertSame('git-tag', $identity['source']);
    }

    public function testSemanticTagCannotBeRelabeledByConflictingAnnotation(): void
    {
        $head = $this->initGit();
        $this->annotate('v5.0.235', $this->metadata($head));

        $identity = (new InstalledReleaseIdentity($this->root))->resolve();
        self::assertSame('5.0.235', $identity['version']);
        self::assertSame('stable', $identity['release_channel']);
        self::assertNull($identity['base_stable_tag']);
    }

    public function testPrivateTargetIdentityWinsOverLocalTagsAndBuildInfo(): void
    {
        $head = $this->initGit();
        $this->annotate('ui-continuous', $this->metadata($head));
        $this->git(['update-ref', 'refs/laravel-updater/targets/ui-continuous', 'refs/tags/ui-continuous']);
        $this->git(['tag', '-d', 'ui-continuous']);
        $this->git(['tag', 'v5.0.235']);
        $differentBuild = $this->metadata($head);
        $differentBuild['version'] = 'v5.0.236-beta.50';
        $differentBuild['build_number'] = '50';
        $this->writeMetadata($differentBuild);

        self::assertSame('5.0.236-beta.51', (new InstalledReleaseIdentity($this->root))->resolve()['version']);
    }

    public function testPrivateLightweightSemanticTagIsSupported(): void
    {
        $head = $this->initGit();
        $this->git(['update-ref', 'refs/laravel-updater/targets/v5.0.235', $head]);
        $this->git(['tag', 'v5.0.236-beta.1']);

        $identity = (new InstalledReleaseIdentity($this->root))->resolve();
        self::assertSame('5.0.235', $identity['version']);
        self::assertSame('v5.0.235', $identity['tag']);
    }

    public function testLightweightContinuousTagDoesNotInventVersion(): void
    {
        $this->initGit();
        $this->git(['tag', 'ui-continuous']);
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testAnnotationShaMustAgreeWithTargetCommit(): void
    {
        $this->initGit();
        $this->annotate('ui-continuous', $this->metadata(str_repeat('a', 40)));
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testAnnotationTargetMustBeHeadEvenIfMetadataClaimsHead(): void
    {
        $old = $this->initGit();
        $head = $this->commit('second');
        $this->annotate('ui-continuous', $this->metadata($head), $old);
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testAnnotationRequiresKnownSchema(): void
    {
        $head = $this->initGit();
        $metadata = $this->metadata($head);
        unset($metadata['schema_version']);
        $this->annotate('ui-continuous', $metadata);
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testContinuousAnnotationRequiresMatchingBaseAndBuild(): void
    {
        $head = $this->initGit();
        foreach ([
            ['base_stable_tag' => null],
            ['base_stable_tag' => 'v5.0.234'],
            ['build_number' => '50'],
        ] as $invalid) {
            $this->annotate('ui-continuous', array_replace($this->metadata($head), $invalid));
            self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
        }
    }

    public function testHumanAnnotatedSemanticTagStillIdentifiesStableRelease(): void
    {
        $this->initGit();
        $this->git(['tag', '-a', 'v5.0.235', '-m', 'Stable release']);
        self::assertSame('5.0.235', (new InstalledReleaseIdentity($this->root))->resolve()['version']);
    }

    public function testBuildInfoSupportsZipInstallationWithoutGit(): void
    {
        $head = str_repeat('b', 40);
        $metadata = $this->metadata($head);
        unset($metadata['schema_version']);
        $this->writeMetadata($metadata);
        $identity = (new InstalledReleaseIdentity($this->root))->resolve();

        self::assertSame('5.0.236-beta.51', $identity['version']);
        self::assertSame($head, $identity['git_commit_sha']);
        self::assertSame('build-info', $identity['source']);
        self::assertSame('ui-continuous', $identity['tag']);
    }

    public function testStableReleaseBuildInfoSupportsZipWithoutGit(): void
    {
        $sha = str_repeat('b', 40);
        $this->writeMetadata($this->stableMetadata($sha), 'release-build-info.json');
        $identity = (new InstalledReleaseIdentity($this->root))->resolve();

        self::assertSame('5.0.237', $identity['version']);
        self::assertSame('v5.0.237', $identity['tag']);
        self::assertSame('stable', $identity['release_channel']);
        self::assertSame($sha, $identity['git_commit_sha']);
        self::assertSame('build-info', $identity['source']);
    }

    public function testStablePublisherMetadataIsAcceptedByThePhpReaderWithoutGit(): void
    {
        $head = $this->initGit();
        $this->git(['branch', '-M', 'main']);
        $producer = dirname(__DIR__, 2).'/scripts/release/identity.py';
        $process = new Process([
            'python3', $producer, 'write', '--repository', $this->root,
            '--version', 'v5.0.237', '--commit', $head,
        ], $this->root);
        $process->setTimeout(10);
        $process->mustRun();
        self::assertFileExists($this->root.'/release-build-info.json');
        $this->removeDirectory($this->root.'/.git');

        $identity = (new InstalledReleaseIdentity($this->root))->resolve();

        self::assertSame('5.0.237', $identity['version']);
        self::assertSame('v5.0.237', $identity['tag']);
        self::assertSame($head, $identity['git_commit_sha']);
        self::assertSame('stable', $identity['release_channel']);
        self::assertSame('build-info', $identity['source']);
    }

    public function testStableReleaseBuildInfoPrecedesLeftoverUiMetadata(): void
    {
        $sha = str_repeat('b', 40);
        $this->writeMetadata($this->metadata($sha));
        $this->writeMetadata($this->stableMetadata($sha), 'release-build-info.json');

        self::assertSame('5.0.237', (new InstalledReleaseIdentity($this->root))->resolve()['version']);
    }

    public function testStableManifestTagMustMatchVersion(): void
    {
        foreach ([
            ['tag' => 'v5.0.236'],
            ['tag' => 'ui-continuous'],
            ['tag' => null],
            ['schema_version' => 2],
            ['release_channel' => 'prerelease'],
        ] as $invalid) {
            $this->writeMetadata(array_replace($this->stableMetadata(str_repeat('b', 40)), $invalid), 'release-build-info.json');
            self::assertNull((new InstalledReleaseIdentity($this->root))->resolve(), json_encode($invalid));
        }
    }

    public function testPublicStableManifestMustMatchGitHead(): void
    {
        $head = $this->initGit();
        $this->writeMetadata($this->stableMetadata(str_repeat('a', 40)), 'public/release-build-info.json');
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());

        $this->writeMetadata($this->stableMetadata($head), 'public/release-build-info.json');
        self::assertSame($head, (new InstalledReleaseIdentity($this->root))->resolve()['git_commit_sha']);
    }

    public function testStableMetadataCannotClaimADifferentTagInUiCompatibilityFile(): void
    {
        $metadata = $this->stableMetadata(str_repeat('b', 40));
        $metadata['tag'] = 'v5.0.236';
        $this->writeMetadata($metadata);

        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testPublicBuildInfoIsSupportedAndBoundToGitHead(): void
    {
        $head = $this->initGit();
        $this->writeMetadata($this->metadata($head), 'public/ui-build-info.json');
        self::assertSame($head, (new InstalledReleaseIdentity($this->root))->resolve()['git_commit_sha']);
    }

    public function testIntegerBuildNumberIsNormalized(): void
    {
        $metadata = $this->metadata(str_repeat('b', 40));
        $metadata['build_number'] = 51;
        $this->writeMetadata($metadata);
        self::assertSame('51', (new InstalledReleaseIdentity($this->root))->resolve()['build_number']);
    }

    public function testGitWorktreeFileIsSupported(): void
    {
        $head = $this->initGit();
        $this->git(['tag', 'v5.0.235']);
        $worktree = $this->root.'/worktree';
        $this->git(['worktree', 'add', '--quiet', '--detach', $worktree, $head]);
        self::assertFileExists($worktree.'/.git');
        self::assertSame('5.0.235', (new InstalledReleaseIdentity($worktree))->resolve()['version']);
    }

    public function testStaleBuildInfoIsIgnoredAfterCheckout(): void
    {
        $first = $this->initGit();
        $this->writeMetadata($this->metadata($first));
        $service = new InstalledReleaseIdentity($this->root);
        self::assertSame('5.0.236-beta.51', $service->resolve()['version']);

        $this->commit('second');
        self::assertNull($service->resolve());

        $this->git(['checkout', '--detach', $first]);
        self::assertSame($first, $service->resolve()['git_commit_sha']);
    }

    public function testSameInstanceObservesChangedFileAndMovedTag(): void
    {
        $head = $this->initGit();
        $this->annotate('ui-continuous', $this->metadata($head));
        $service = new InstalledReleaseIdentity($this->root);
        self::assertSame('5.0.236-beta.51', $service->resolve()['version']);

        $next = $this->commit('second');
        self::assertNull($service->resolve());
        $metadata = $this->metadata($next);
        $metadata['version'] = 'v5.0.236-beta.52';
        $metadata['build_number'] = '52';
        $this->annotate('ui-continuous', $metadata);
        self::assertSame('5.0.236-beta.52', $service->resolve()['version']);

        $this->git(['tag', '-d', 'ui-continuous']);
        $this->writeMetadata($metadata);
        self::assertSame('52', $service->resolve()['build_number']);
        $metadata['build_number'] = '53';
        $metadata['version'] = 'v5.0.236-beta.53';
        $this->writeMetadata($metadata);
        self::assertSame('53', $service->resolve()['build_number']);
    }

    public function testStaleBuildInfoDoesNotMaskExactLocalTag(): void
    {
        $this->initGit();
        $this->git(['tag', 'v5.0.235']);
        $this->writeMetadata($this->metadata(str_repeat('a', 40)));
        self::assertSame('5.0.235', (new InstalledReleaseIdentity($this->root))->resolve()['version']);
    }

    public function testStableIdentityDoesNotGuessUnverifiedBase(): void
    {
        $this->initGit();
        $this->git(['tag', 'v5.0.236-rc.2+build.7']);
        $identity = (new InstalledReleaseIdentity($this->root))->resolve();
        self::assertSame('5.0.236-rc.2+build.7', $identity['version']);
        self::assertSame('prerelease', $identity['release_channel']);
        self::assertNull($identity['base_stable_tag']);
    }

    public function testMalformedOrOversizedMetadataIsRejected(): void
    {
        file_put_contents($this->root.'/ui-build-info.json', '{broken');
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
        file_put_contents($this->root.'/ui-build-info.json', str_repeat('x', 65537));
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testShortShaUnsafeVersionAndInconsistentChannelAreRejected(): void
    {
        foreach ([
            ['git_commit_sha' => 'b1d10e6'],
            ['version' => '5.0.236-beta.01'],
            ['version' => '5.0.236-beta.51;touch /tmp/invalid'],
            ['base_stable_tag' => 'v5.0.235-rc.1'],
            ['release_channel' => 'stable'],
            ['build_number' => ['51']],
            ['tag' => '../invalid'],
            ['schema_version' => 99],
            ['base_stable_tag' => null],
            ['base_stable_tag' => 'v5.0.234'],
            ['build_number' => '50'],
            ['build_number' => null],
            ['version' => 'v5.1.236-beta.51'],
            ['version' => 'v5.0.236-beta.51+012345678901234567890'],
        ] as $invalid) {
            $this->writeMetadata(array_replace($this->metadata(str_repeat('b', 40)), $invalid));
            self::assertNull((new InstalledReleaseIdentity($this->root))->resolve(), json_encode($invalid));
        }
    }

    public function testOversizedTagAnnotationIsIgnored(): void
    {
        $this->initGit();
        $this->git(['tag', '-a', 'ui-continuous', '-m', str_repeat('x', 65537)]);
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    public function testBrokenGitDirectoryCannotBypassMetadataHeadValidation(): void
    {
        mkdir($this->root.'/.git');
        $this->writeMetadata($this->metadata(str_repeat('b', 40)));
        self::assertNull((new InstalledReleaseIdentity($this->root))->resolve());
    }

    private function metadata(string $sha): array
    {
        return [
            'schema_version' => 1,
            'version' => 'v5.0.236-beta.51',
            'base_stable_tag' => 'v5.0.235',
            'git_commit_sha' => $sha,
            'release_channel' => 'beta-continuous',
            'build_number' => '51',
        ];
    }

    private function stableMetadata(string $sha): array
    {
        return [
            'schema_version' => 1,
            'version' => 'v5.0.237',
            'tag' => 'v5.0.237',
            'git_commit_sha' => $sha,
            'release_channel' => 'stable',
            'build_number' => '39017970356',
        ];
    }

    private function writeMetadata(array $metadata, string $relativePath = 'ui-build-info.json'): void
    {
        $path = $this->root.'/'.$relativePath;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, json_encode($metadata, JSON_THROW_ON_ERROR));
        clearstatcache(true, $path);
    }

    private function initGit(): string
    {
        $this->git(['init', '--quiet']);
        $this->git(['config', 'user.name', 'Release test']);
        $this->git(['config', 'user.email', 'release-test@example.invalid']);
        return $this->commit('initial');
    }

    private function commit(string $message): string
    {
        file_put_contents($this->root.'/tracked.txt', $message);
        $this->git(['add', 'tracked.txt']);
        $this->git(['commit', '--quiet', '-m', $message]);
        return trim($this->git(['rev-parse', 'HEAD']));
    }

    private function annotate(string $tag, array $metadata, ?string $target = null): void
    {
        $arguments = ['tag', '-f', '-a', $tag, '-m', json_encode($metadata, JSON_THROW_ON_ERROR)];
        if ($target !== null) {
            $arguments[] = $target;
        }
        $this->git($arguments);
    }

    private function git(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), $this->root);
        $process->setTimeout(3);
        $process->mustRun();
        return $process->getOutput();
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($directory);
    }
}
