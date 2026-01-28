<?php

namespace App\Services\Updates\Drivers;

use App\Services\Updates\Contracts\UpdateDriver;
use App\Services\Updates\DTOs\AvailableVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GitHubUpdateDriver implements UpdateDriver
{
    protected ?string $token = null;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $organization = null;
    protected ?string $repository = null;
    protected ?string $branch = null;
    protected array $options = [];
    protected ?string $lastDownloadedPath = null;

    public function configure(array $credentials = [], array $options = []): void
    {
        $this->token        = Arr::get($credentials, 'token');
        $this->username     = Arr::get($credentials, 'username');
        $this->password     = Arr::get($credentials, 'password');
        $this->organization = Arr::get($credentials, 'organization');
        $this->repository   = Arr::get($credentials, 'repository');
        $this->branch       = Arr::get($credentials, 'branch', 'main');

        $this->options = array_merge([
            'mode'       => Arr::get($options, 'mode', Config::get('updates.mode', 'stable')),
            'dev_branch' => Arr::get($options, 'dev_branch', Config::get('updates.dev_branch', 'develop')),
        ], $options);

        if (! $this->organization && $this->repository && str_contains($this->repository, '/')) {
            [$org, $repo]       = explode('/', $this->repository, 2);
            $this->organization = $org;
            $this->repository   = $repo;
        }

        if (! $this->organization || ! $this->repository) {
            throw new RuntimeException('GitHub driver requires organization and repository (ex.: UPDATE_GITHUB_ORGANIZATION=Org e UPDATE_GITHUB_REPOSITORY=repo).');
        }
    }

    /**
     * @return Collection<int, AvailableVersion>
     */
    public function fetchAvailableVersions(?string $currentVersion = null): Collection
    {
        $mode = $this->options['mode'] ?? 'stable';

        if ($mode === 'dev') {
            return $this->fetchDevVersions($currentVersion);
        }

        $response = $this->makeRequest("repos/{$this->organization}/{$this->repository}/releases");

        $releases = collect($response->json() ?? [])
            ->map(fn (array $release) => new AvailableVersion(
                identifier: $release['tag_name'] ?? $release['name'] ?? Str::random(8),
                description: $release['name'] ?? null,
                releasedAt: isset($release['published_at']) ? Carbon::parse($release['published_at']) : null,
                metadata: $release,
            ));

        if ($releases->isEmpty()) {
            $tagsResponse = $this->makeRequest("repos/{$this->organization}/{$this->repository}/tags");
            $tags = $tagsResponse->json() ?? [];

            $releases = collect($tags)->map(fn (array $tag) => new AvailableVersion(
                identifier: $tag['name'],
                description: Arr::get($tag, 'commit.sha'),
                releasedAt: null,
                metadata: $tag,
            ));
        }

        $releases = $releases->sortByDesc(fn (AvailableVersion $version) => $version->identifier)->values();

        if ($currentVersion) {
            $releases = $releases->filter(function (AvailableVersion $version) use ($currentVersion) {
                if (! $this->isSemanticVersion($version->identifier) || ! $this->isSemanticVersion($currentVersion)) {
                    return true;
                }

                return version_compare($version->identifier, $currentVersion, '>');
            })->values();
        }

        return $releases;
    }

    public function download(string $version): string
    {
        $mode           = $this->options['mode'] ?? 'stable';
        $targetMetadata = Arr::get($this->options, 'target_version_metadata', []);
        $sha            = Arr::get($targetMetadata, 'sha');

        $identifier = $mode === 'dev' && $sha ? $sha : $version;

        $endpoint = "repos/{$this->organization}/{$this->repository}/zipball/{$identifier}";
        $response = $this->makeRequest($endpoint, 'GET');

        if (! $response->successful()) {
            throw new RuntimeException("Failed to download release {$version} from GitHub: " . $response->body());
        }

        $disk   = Storage::disk(Config::get('updates.storage_disk', 'local'));
        $suffix = $mode === 'dev' && $sha ? $sha : $version;
        $path   = 'updates/' . Str::slug($this->repository . '-' . $suffix) . '.zip';

        $disk->put($path, $response->body());

        $this->lastDownloadedPath = $path;

        return $path;
    }

    public function cleanup(string $version): void
    {
        $disk = Storage::disk(Config::get('updates.storage_disk', 'local'));
        $path = $this->lastDownloadedPath ?? 'updates/' . Str::slug($this->repository . '-' . $version) . '.zip';

        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        $this->lastDownloadedPath = null;
    }

    /* =======================================================================
     | Internal helpers
     * ======================================================================= */

    protected function makeRequest(string $endpoint, string $method = 'GET', array $payload = [])
    {
        $baseUrl = 'https://api.github.com/';
        $http = Http::withHeaders([
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => Config::get('app.name', 'Laravel-App') . ' UpdateManager',
        ]);

        if ($this->token) {
            $http = $http->withToken($this->token);
        } elseif ($this->username && $this->password) {
            $http = $http->withBasicAuth($this->username, $this->password);
        }

        $url = Str::finish($baseUrl, '/') . ltrim($endpoint, '/');

        $options = $method === 'GET' ? ['query' => $payload] : ['json' => $payload];
        $response = $http->send($method, $url, $options);

        if (! $response->successful()) {
            Log::warning('GitHub API request failed', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            throw new RuntimeException('GitHub API request failed: ' . $response->body());
        }

        return $response;
    }

    protected function fetchDevVersions(?string $currentVersion = null): Collection
    {
        $branch = $this->options['dev_branch'] ?? $this->branch ?? 'develop';

        $response = $this->makeRequest(
            "repos/{$this->organization}/{$this->repository}/commits",
            'GET',
            ['sha' => $branch, 'per_page' => 30]
        );

        $commits = collect($response->json() ?? []);

        $versions = $commits->map(function (array $commit) use ($branch) {
            $sha = Arr::get($commit, 'sha');
            $shortSha = $sha ? substr($sha, 0, 7) : Str::random(7);
            $date = Arr::get($commit, 'commit.author.date');
            $releasedAt = $date ? Carbon::parse($date) : null;
            $timestamp = $releasedAt ? $releasedAt->format('YmdHis') : now()->format('YmdHis');

            $identifier = sprintf(
                'dev-%s-%s-%s',
                Str::slug($branch, '-'),
                $timestamp,
                $shortSha
            );

            return new AvailableVersion(
                identifier: $identifier,
                description: Arr::get($commit, 'commit.message'),
                releasedAt: $releasedAt,
                metadata: [
                    'sha'       => $sha,
                    'branch'    => $branch,
                    'commit'    => $commit,
                    'timestamp' => $timestamp,
                ],
            );
        });

        if ($currentVersion) {
            $currentTimestamp = $this->extractDevTimestamp($currentVersion);

            if ($currentTimestamp) {
                $versions = $versions->filter(fn (AvailableVersion $version) =>
                    $version->releasedAt && $version->releasedAt->greaterThan($currentTimestamp)
                )->values();
            }
        }

        return $versions;
    }

    protected function extractDevTimestamp(string $identifier): ?Carbon
    {
        if (preg_match('/-(\d{14})-[0-9a-f]{7}$/', $identifier, $matches)) {
            return Carbon::createFromFormat('YmdHis', $matches[1]);
        }

        return null;
    }

    protected function isSemanticVersion(string $value): bool
    {
        return (bool) preg_match('/^\d+(\.\d+){0,2}$/', $value);
    }
}
