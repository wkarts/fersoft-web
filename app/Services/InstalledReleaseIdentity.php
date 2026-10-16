<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Process\Process;

/** Resolves only release metadata belonging to the code actually installed. */
class InstalledReleaseIdentity
{
    private const TARGET_PREFIX = 'refs/laravel-updater/targets/';
    private const TAG_PREFIX = 'refs/tags/';
    private const MAX_METADATA_BYTES = 65536;
    private const MAX_REFS_BYTES = 262144;

    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = rtrim($root ?? (function_exists('base_path') ? base_path() : dirname(__DIR__, 2)), '/\\');
    }

    /**
     * @return array{version:string,base_stable_tag:?string,git_commit_sha:string,release_channel:string,build_number:?string,tag:?string,source:string}|null
     */
    public function resolve(): ?array
    {
        // Deliberately avoid caching: the same service may outlive a checkout/rollback.
        clearstatcache(true, $this->root.'/.git');
        $hasGit = is_dir($this->root.'/.git') || is_file($this->root.'/.git');
        $head = $hasGit ? $this->git(['rev-parse', '--verify', 'HEAD'], 128) : null;
        $head = $head !== null ? strtolower(trim($head)) : null;
        if ($hasGit && !$this->isFullSha($head)) {
            return null;
        }

        if ($head !== null) {
            $selected = $this->fromRefs(self::TARGET_PREFIX, $head);
            if ($selected !== null) {
                return $selected;
            }
        }

        foreach (['ui-build-info.json', 'public/ui-build-info.json'] as $relativePath) {
            $metadata = $this->readJsonFile($this->root.'/'.$relativePath);
            if ($metadata === null) {
                continue;
            }
            $identity = $this->fromMetadata($metadata, $head, 'build-info');
            if ($identity !== null) {
                return $identity;
            }
        }

        return $head !== null ? $this->fromRefs(self::TAG_PREFIX, $head) : null;
    }

    private function fromRefs(string $prefix, string $head): ?array
    {
        $output = $this->git([
            'for-each-ref', '--points-at='.$head, '--count=256', '--sort=-version:refname',
            '--format=%(refname)%09%(objecttype)%09%(objectname)%09%(*objecttype)%09%(*objectname)',
            $prefix,
        ], self::MAX_REFS_BYTES);
        if ($output === null) {
            return null;
        }

        foreach (explode("\n", rtrim($output, "\r\n")) as $line) {
            $parts = explode("\t", $line);
            if (count($parts) !== 5 || !str_starts_with($parts[0], $prefix)) {
                continue;
            }
            [$ref, $type, $object, $peeledType, $peeledObject] = $parts;
            $tag = substr($ref, strlen($prefix));
            if (!$this->isTagName($tag)) {
                continue;
            }
            $commit = $type === 'commit' ? $object : ($peeledType === 'commit' ? $peeledObject : null);
            if ($commit === null || strtolower($commit) !== $head) {
                continue;
            }

            if ($type === 'tag' && $this->isFullSha($object)) {
                $contents = $this->git(['cat-file', 'tag', $object], self::MAX_METADATA_BYTES);
                if ($contents !== null) {
                    $separator = strpos($contents, "\n\n");
                    $metadata = $separator !== false ? $this->decodeJson(substr($contents, $separator + 2)) : null;
                    if ($metadata !== null && ($metadata['schema_version'] ?? null) === 1) {
                        $identity = $this->fromMetadata($metadata, $head, 'git-tag', $tag);
                        $namedVersion = $this->semanticVersion($tag);
                        if ($identity !== null && ($namedVersion === null || $identity['version'] === $namedVersion)) {
                            return $identity;
                        }
                    }
                }
            }

            $version = $this->semanticVersion($tag);
            if ($version !== null) {
                return [
                    'version' => $version,
                    'base_stable_tag' => null,
                    'git_commit_sha' => $head,
                    'release_channel' => str_contains(explode('+', $version, 2)[0], '-') ? 'prerelease' : 'stable',
                    'build_number' => null,
                    'tag' => $tag,
                    'source' => 'git-tag',
                ];
            }
        }

        return null;
    }

    private function fromMetadata(array $metadata, ?string $head, string $source, ?string $tag = null): ?array
    {
        if (array_key_exists('schema_version', $metadata) && $metadata['schema_version'] !== 1) {
            return null;
        }
        $version = is_string($metadata['version'] ?? null) ? $this->semanticVersion($metadata['version']) : null;
        $sha = is_string($metadata['git_commit_sha'] ?? null) ? strtolower($metadata['git_commit_sha']) : null;
        if ($version === null || !$this->isFullSha($sha) || ($head !== null && $sha !== $head)) {
            return null;
        }

        $base = $metadata['base_stable_tag'] ?? null;
        if ($base !== null) {
            $baseVersion = is_string($base) ? $this->semanticVersion($base) : null;
            if ($baseVersion === null || str_contains($baseVersion, '-') || str_contains($baseVersion, '+')) {
                return null;
            }
            $base = 'v'.$baseVersion;
        }

        $prerelease = str_contains(explode('+', $version, 2)[0], '-');
        $channel = $metadata['release_channel'] ?? ($prerelease ? 'prerelease' : 'stable');
        if (!in_array($channel, ['beta-continuous', 'prerelease', 'stable'], true)
            || ($channel === 'stable' && $prerelease)
            || ($channel !== 'stable' && !$prerelease)
            || ($channel === 'beta-continuous' && !preg_match('/-beta\.[1-9][0-9]*(?:\+|$)/D', $version))) {
            return null;
        }

        $build = $metadata['build_number'] ?? null;
        if ($build !== null && (!is_int($build) && !is_string($build))) {
            return null;
        }
        $build = $build !== null ? (string) $build : null;
        if ($build !== null && !preg_match('/^[1-9][0-9]{0,19}$/D', $build)) {
            return null;
        }

        if ($channel === 'beta-continuous') {
            if ($base === null || $build === null) {
                return null;
            }
            [$major, $minor, $patch] = explode('.', substr($base, 1));
            $expectedPrefix = $major.'.'.$minor.'.'.$this->incrementDecimal($patch).'-beta.';
            if (preg_match('/^'.preg_quote($expectedPrefix, '/').'([1-9][0-9]*)(?:\+[^\s]+)?$/D', $version, $matches) !== 1
                || $matches[1] !== $build) {
                return null;
            }
        }

        if ($tag === null && isset($metadata['tag'])) {
            if (!is_string($metadata['tag']) || !$this->isTagName($metadata['tag'])) {
                return null;
            }
            $tag = $metadata['tag'];
        }
        if ($tag === null && $channel === 'beta-continuous') {
            $tag = 'ui-continuous';
        }

        return [
            'version' => $version,
            'base_stable_tag' => $base,
            'git_commit_sha' => $sha,
            'release_channel' => $channel,
            'build_number' => $build,
            'tag' => $tag,
            'source' => $source,
        ];
    }

    private function readJsonFile(string $path): ?array
    {
        clearstatcache(true, $path);
        if (!is_file($path) || is_link($path) || !is_readable($path)) {
            return null;
        }
        $size = filesize($path);
        if ($size === false || $size < 2 || $size > self::MAX_METADATA_BYTES) {
            return null;
        }
        $contents = file_get_contents($path, false, null, 0, self::MAX_METADATA_BYTES + 1);

        return is_string($contents) && strlen($contents) <= self::MAX_METADATA_BYTES ? $this->decodeJson($contents) : null;
    }

    private function decodeJson(string $json): ?array
    {
        try {
            $value = json_decode(trim($json), true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return null;
        }

        return is_array($value) && !array_is_list($value) ? $value : null;
    }

    private function semanticVersion(string $version): ?string
    {
        $version = preg_replace('/^[vV]/', '', trim($version));
        // The application's app_versions.version column has a 30-character limit.
        if (!is_string($version) || strlen($version) > 30) {
            return null;
        }
        $number = '(?:0|[1-9][0-9]*)';
        $pre = '(?:0|[1-9][0-9]*|[0-9]*[A-Za-z-][0-9A-Za-z-]*)';
        $pattern = '/^'.$number.'\.'.$number.'\.'.$number.'(?:-'.$pre.'(?:\.'.$pre.')*)?(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/D';

        return preg_match($pattern, $version) === 1 ? $version : null;
    }

    private function isFullSha(?string $sha): bool
    {
        return $sha !== null && preg_match('/^(?:[a-f0-9]{40}|[a-f0-9]{64})$/D', $sha) === 1;
    }

    private function incrementDecimal(string $number): string
    {
        for ($index = strlen($number) - 1; $index >= 0; $index--) {
            if ($number[$index] !== '9') {
                $number[$index] = (string) ((int) $number[$index] + 1);
                return $number;
            }
            $number[$index] = '0';
        }

        return '1'.$number;
    }

    private function isTagName(string $tag): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._+\/-]{0,254}$/D', $tag) === 1
            && !str_contains($tag, '..');
    }

    /** Execute Git locally without a shell, network requests or unbounded output. */
    private function git(array $arguments, int $limit): ?string
    {
        $process = new Process(array_merge(['git'], $arguments), $this->root, [
            'GIT_TERMINAL_PROMPT' => '0',
            'GIT_OPTIONAL_LOCKS' => '0',
            'GIT_NO_REPLACE_OBJECTS' => '1',
            'LC_ALL' => 'C',
        ]);
        $process->setTimeout(3);
        $output = '';
        $bytes = 0;
        try {
            $process->run(function (string $type, string $buffer) use (&$output, &$bytes, $limit): void {
                $bytes += strlen($buffer);
                if ($bytes > $limit) {
                    throw new \OverflowException('Git metadata exceeds the local read limit.');
                }
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });
        } catch (\Throwable $exception) {
            if ($process->isRunning()) {
                $process->stop(0);
            }
            return null;
        }

        return $process->isSuccessful() ? $output : null;
    }
}
