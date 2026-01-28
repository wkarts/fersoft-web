<?php

namespace App\Services\Updates;

use App\Jobs\Updates\ApplyUpdateJob;
use App\Models\Updates\UpdateLog;
use App\Models\Updates\UpdateSourceConfig;
use App\Models\Updates\UpdateVersion;
use App\Models\Updates\UpdateVersionMigration;
use App\Services\Updates\Contracts\UpdateDriver;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class UpdateManager
{
    /**
     * Migrations usadas pela infraestrutura do módulo de updates e que nunca devem ser revertidas.
     */
    protected const INFRASTRUCTURE_MIGRATIONS = [
        '2025_10_19_000000_create_update_versions_table',
        '2025_10_19_000010_create_update_logs_table',
        '2025_10_19_000020_create_update_source_configs_table',
        '2025_10_19_000030_create_update_version_migrations_table',
        '2025_11_15_000040_alter_update_versions_add_tracking_columns',
    ];

    public function __construct(
        protected DatabaseManager $databaseManager,
        protected FilesystemFactory $filesystemFactory
    ) {
    }

    /**
     * Resolve o driver configurado para o provider escolhido.
     */
    public function getDriver(?string $provider = null): UpdateDriver
    {
        $provider = $provider ?? Config::get('updates.default_provider');

        return match ($provider) {
            'github'     => App::make(Drivers\GitHubUpdateDriver::class),
            'svn'        => App::make(Drivers\SvnUpdateDriver::class),
            'filesystem' => App::make(Drivers\FilesystemUpdateDriver::class),
            default      => throw new RuntimeException("Update provider [{$provider}] is not supported."),
        };
    }

    /**
     * Configura o driver com credenciais e opções adicionais.
     */
    public function configureDriver(
        UpdateDriver $driver,
        ?UpdateSourceConfig $config = null,
        array $overrides = []
    ): void {
        $provider = $config?->provider ?? Config::get('updates.default_provider');

        $baseCredentials = Config::get("updates.providers.{$provider}", []) ?? [];
        $baseOptions = [
            'mode'       => Config::get('updates.mode', 'stable'),
            'dev_branch' => Config::get('updates.dev_branch', 'develop'),
        ];

        $dbCredentials = $config?->credentials ?? [];
        $dbOptions = $config?->options ?? [];

        if (! is_array($dbCredentials)) {
            $dbCredentials = [];
        }

        if (! is_array($dbOptions)) {
            $dbOptions = [];
        }

        $credentials = array_merge($baseCredentials, $dbCredentials);
        $options = array_merge($baseOptions, $dbOptions, $overrides);

        $driver->configure($credentials, $options);
    }

    /**
     * Recupera a lista de versões disponíveis a partir do provider.
     */
    public function fetchAvailableVersions(?string $currentVersion = null, ?string $provider = null): Collection
    {
        $provider    = $provider ?? Config::get('updates.default_provider');
        $driver      = $this->getDriver($provider);
        $config      = $this->getActiveConfig($provider);
        $currentMeta = $currentVersion
            ? UpdateVersion::query()->where('version', $currentVersion)->value('metadata') ?? []
            : [];

        $overrides = [
            'mode'                     => Config::get('updates.mode', 'stable'),
            'dev_branch'               => Config::get('updates.dev_branch', 'develop'),
            'current_version'          => $currentVersion,
            'current_version_metadata' => $currentMeta,
        ];

        if ($provider === 'github') {
            $overrides['branch'] = $overrides['mode'] === 'stable'
                ? Config::get('updates.providers.github.branch', 'main')
                : Config::get('updates.dev_branch', 'develop');
        }

        $this->configureDriver($driver, $config, $overrides);

        return $driver->fetchAvailableVersions($currentVersion);
    }

    /**
     * Garante que existe uma versão atual registrada, inicializando a partir do .env se necessário.
     */
    public function currentVersion(): ?UpdateVersion
    {
        $current = UpdateVersion::query()
            ->where('status', 'completed')
            ->orderByDesc('applied_at')
            ->first();

        if ($current) {
            return $current;
        }

        $envKey    = Config::get('updates.env_version_key', 'VERSION');
        $envVers   = env($envKey) ?? env('APP_VERSION');
        $mode      = Config::get('updates.mode', 'stable');
        $provider  = Config::get('updates.default_provider');

        if (! $envVers) {
            return null;
        }

        $version = UpdateVersion::firstOrCreate(
            ['version' => $envVers],
            [
                'status'          => 'completed',
                'mode'            => $mode,
                'provider'        => $provider,
                'composer_action' => 'none',
                'is_downgrade'    => false,
                'applied_at'      => now(),
                'metadata'        => [
                    'source' => 'env-bootstrap',
                ],
            ]
        );

        $metadata        = $version->metadata ?? [];
        $archiveBasePath = Config::get('updates.archive.path', storage_path('app/updates/archives'));
        if (! is_dir($archiveBasePath)) {
            File::makeDirectory($archiveBasePath, 0755, true, true);
        }

        if (! Arr::get($metadata, 'archive.path')) {
            $timestamp  = now()->format('YmdHis');
            $slug       = Str::slug($version->version);
            $snapshot   = $this->createApplicationSnapshot($archiveBasePath, $timestamp, $slug, 'bootstrap');
            $relative   = $snapshot
                ? Str::after($snapshot, storage_path('app') . DIRECTORY_SEPARATOR)
                : null;

            $metadata['archive'] = [
                'disk'   => Config::get('updates.storage_disk', 'local'),
                'path'   => $relative,
                'kept'   => true,
                'source' => 'bootstrap',
            ];

            $version->metadata = $metadata;
            $version->save();
        }

        Config::set('app.version', $version->version);

        return $version;
    }

    /**
     * Agenda uma atualização (upgrade ou downgrade) colocando o job na fila.
     */
    public function scheduleUpdate(
        string $version,
        ?string $provider = null,
        bool $downgrade = false,
        array $options = []
    ): UpdateVersion {
        $allowDowngrade = Config::get('updates.allow_downgrade');
        if ($downgrade && ! $allowDowngrade) {
            throw new RuntimeException('Downgrade is not allowed by configuration.');
        }

        $provider        = $provider ?? Config::get('updates.default_provider');
        $mode            = $options['mode'] ?? Config::get('updates.mode', 'stable');
        $composerConfig  = Config::get('updates.composer');
        $composerEnabled = Arr::get($composerConfig, 'enabled', true);
        $composerAction  = $composerEnabled
            ? ($options['composer_action'] ?? Arr::get($composerConfig, 'default_action', 'none'))
            : 'none';

        $record = UpdateVersion::updateOrCreate(
            ['version' => $version],
            []
        );

        $record->fill([
            'status'          => 'scheduled',
            'provider'        => $provider,
            'mode'            => $mode,
            'composer_action' => $composerAction,
            'is_downgrade'    => $downgrade,
        ]);

        if (array_key_exists('observations', $options)) {
            $record->observations = $options['observations'];
        }

        $metadata                     = $record->metadata ?? [];
        $metadata                     = array_merge($metadata, Arr::get($options, 'metadata', []));
        $metadata['composer_action']  = $composerAction;
        if ($downgrade) {
            $metadata['restore_dependencies'] = (bool) Arr::get($options, 'restore_dependencies', false);
        }
        $record->metadata = $metadata;
        $record->save();

        $this->log($record, 'schedule', 'queued', 'Update scheduled.', [
            'provider'        => $provider,
            'mode'            => $mode,
            'downgrade'       => $downgrade,
            'composer_action' => $composerAction,
            'observations'    => $record->observations,
        ]);

        ApplyUpdateJob::dispatch($record, $provider, $downgrade);

        return $record;
    }

    /**
     * Executa de fato o upgrade/downgrade.
     */
    public function applyVersion(UpdateVersion $version, ?string $provider = null, bool $downgrade = false): void
    {
        $provider           = $provider ?? $version->provider ?? Config::get('updates.default_provider');
        $mode               = $version->mode ?? Config::get('updates.mode', 'stable');
        $keepDownloads      = (bool) Config::get('updates.archive.keep_downloads', true);
        $connection         = $this->resolveUpdateConnection();
        $disk               = $this->getDisk();
        $metadata           = $version->metadata ?? [];
        $archiveMeta        = Arr::get($metadata, 'archive', []);
        $relativeArchive    = null;
        $useLocalArchive    = false;

        $version->provider     = $provider;
        $version->mode         = $mode;
        $version->is_downgrade = $downgrade;
        $version->status       = 'preparing';
        $version->save();

        $driver       = $this->getDriver($provider);
        $activeConfig = $this->getActiveConfig($provider);
        $overrides    = [
            'mode'                     => $mode,
            'dev_branch'               => Config::get('updates.dev_branch', 'develop'),
            'current_version'          => $version->version,
            'current_version_metadata' => $metadata,
            'target_version_metadata'  => $metadata,
        ];

        if ($provider === 'github') {
            $overrides['branch'] = $mode === 'stable'
                ? Config::get('updates.providers.github.branch', 'main')
                : Config::get('updates.dev_branch', 'develop');
        }

        $this->configureDriver($driver, $activeConfig, $overrides);

        try {
            $backupSnapshot = $this->performBackup($version, $downgrade);
            if ($backupSnapshot) {
                $metadata['backups'] = array_values(array_filter(array_merge(
                    Arr::get($metadata, 'backups', []),
                    [$backupSnapshot]
                )));
            }

            $version->metadata = $metadata;
            $version->save();

            $version->status = 'downloading';
            $version->save();

            if ($downgrade && ! empty($archiveMeta['path'])) {
                $candidatePath = $archiveMeta['path'];
                $absolute      = $this->resolveArchiveAbsolutePath($disk, $candidatePath);

                if ($absolute && file_exists($absolute)) {
                    $relativeArchive             = $candidatePath;
                    $useLocalArchive             = true;
                    $archiveMeta['disk']         = $archiveMeta['disk'] ?? Config::get('updates.storage_disk', 'local');
                    $archiveMeta['source']       = 'local-cache';
                    $archiveMeta['last_used_at'] = now()->toIso8601String();
                    $metadata['archive']         = $archiveMeta;
                    $version->metadata           = $metadata;
                    $version->save();

                    $this->log($version, 'download', 'cached', "Using cached archive for version {$version->version}.", [
                        'path' => $candidatePath,
                    ]);
                }
            }

            if (! $useLocalArchive) {
                $relativeArchive = $driver->download($version->version);
                $this->log($version, 'download', 'success', "Version {$version->version} downloaded.", [
                    'path' => $relativeArchive,
                ]);

                $metadata['archive'] = [
                    'disk'         => Config::get('updates.storage_disk', 'local'),
                    'path'         => $relativeArchive,
                    'kept'         => $keepDownloads,
                    'source'       => $downgrade ? 'remote-downgrade' : 'remote-upgrade',
                    'last_used_at' => now()->toIso8601String(),
                ];

                $version->metadata = $metadata;
                $version->save();
            }

            if (! $relativeArchive) {
                throw new RuntimeException('Package archive path not resolved.');
            }

            $absoluteArchive = $this->resolveArchiveAbsolutePath($disk, $relativeArchive);
            if (! $absoluteArchive || ! file_exists($absoluteArchive)) {
                throw new RuntimeException('Downloaded archive not found.');
            }

            $version->status = 'installing';
            $version->save();

            $this->log($version, 'install', 'running', 'Starting installation tasks.');

            $this->syncApplicationFilesFromArchive($version, $relativeArchive);
            $this->runMigrations($version, $connection, $downgrade);

            if ($downgrade) {
                $this->restoreDependenciesIfRequested($version);
            }

            $this->runComposer($version);

            $version->status     = 'completed';
            $version->applied_at = now();
            $version->save();

            $this->syncEnvVersion($version->version);

            $this->log(
                $version,
                $downgrade ? 'downgrade' : 'apply',
                'success',
                $downgrade
                    ? "Version {$version->version} rolled back successfully."
                    : "Version {$version->version} applied successfully."
            );
        } catch (Throwable $exception) {
            $version->status = 'failed';
            $version->save();

            $this->log($version, 'apply', 'failed', $exception->getMessage(), [
                'exception' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        } finally {
            if (! $keepDownloads && ! $useLocalArchive) {
                $driver->cleanup($version->version);
            }
        }
    }

    /**
     * Realiza o backup antes de aplicar a atualização.
     */
    protected function performBackup(UpdateVersion $version, bool $downgrade): ?array
    {
        $backupConfig = Config::get('updates.backup', []);
        if (! Arr::get($backupConfig, 'enabled', true)) {
            $this->log($version, 'backup', 'skipped', 'Backup disabled by configuration.');
            return null;
        }

        $driver = Arr::get($backupConfig, 'driver', 'database');
        if ($driver === 'none') {
            $this->log($version, 'backup', 'skipped', 'Backup driver set to none.');
            return null;
        }

        $skipDatabaseBackup = (bool) Arr::get($backupConfig, 'skip_database_backup', false);

        $basePath = Arr::get($backupConfig, 'path');

        if (empty($basePath) || !is_string($basePath)) {
            $basePath = storage_path('app/updates/backups');
        }

        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        if ($basePath === '') {
            $basePath = storage_path('app/updates/backups');
        }

        if (! is_dir($basePath)) {
            File::makeDirectory($basePath, 0755, true, true);
        }

        $timestamp = now()->format('YmdHis');
        $slug      = Str::slug($version->version);

        $snapshot = [
            'created_at'           => now()->toIso8601String(),
            'driver'               => $driver,
            'database_dump'        => null,
            'vendor_archive'       => null,
            'composer_files'       => [],
            'app_archive'          => null,
            'app_archive_relative' => null,
            'path'                 => $basePath,
            'downgrade'            => $downgrade,
        ];

        try {
            if (in_array($driver, ['database', 'full'], true)) {
                if ($skipDatabaseBackup) {
                    $this->log($version, 'backup', 'skipped', 'Database backup skipped by configuration (skip_database_backup=true).');
                } else {
                    $snapshot['database_dump'] = $this->createDatabaseDump($basePath, $timestamp, $slug);
                }
            }

            if ($driver === 'full') {
                $snapshot['vendor_archive'] = $this->archiveVendorDirectory($basePath, $timestamp, $slug);
                $snapshot['composer_files'] = $this->backupComposerFiles($basePath, $timestamp, $slug);
            }

            $appArchive                      = $this->createApplicationSnapshot($basePath, $timestamp, $slug, 'app');
            $snapshot['app_archive']         = $appArchive;
            $relativeAppArchive              = $appArchive
                ? Str::after($appArchive, storage_path('app') . DIRECTORY_SEPARATOR)
                : null;
            $snapshot['app_archive_relative'] = $relativeAppArchive;

            $this->log($version, 'backup', 'success', 'Backup completed successfully.', $snapshot);
        } catch (Throwable $exception) {
            $this->log($version, 'backup', 'failed', 'Backup failed before update.', [
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Backup step failed: ' . $exception->getMessage(), previous: $exception);
        }

        return $snapshot;
    }

    /**
     * Cria o dump do banco de dados.
     *
     * Agora com fallback robusto: se o mysqldump falhar com erros de TCP/IP/host,
     * usa um exportador 100% em PHP via PDO.
     */
    protected function createDatabaseDump(string $basePath, string $timestamp, string $slug): ?string
    {
        $connection = $this->databaseManager->connection();
        $driver     = $connection->getDriverName();
        $config     = $connection->getConfig();
        $fileName   = "{$timestamp}-{$slug}-database.sql";
        $dumpPath   = $basePath . DIRECTORY_SEPARATOR . $fileName;

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                // ================================
                // Leitura/normalização de config
                // ================================
                $database = $this->normalizeEnvString(
                    env('DB_DATABASE', $config['database'] ?? null)
                );

                if (! $database) {
                    throw new RuntimeException('Database name not configured for MySQL backup.');
                }

                $host = $this->normalizeEnvString(
                    env('DB_HOST', $config['host'] ?? null)
                );

                if (empty($host)) {
                    $host = '127.0.0.1';
                }

                // Se no env vier "localhost", normalizamos para 127.0.0.1 na tentativa principal
                $primaryHost   = $host === 'localhost' ? '127.0.0.1' : $host;
                $fallbackHost  = $this->normalizeEnvString(env('DB_HOST_FALLBACK', 'localhost'));
                $username      = $this->normalizeEnvString(
                    env('DB_USERNAME', $config['username'] ?? 'root')
                );
                $password      = $this->normalizeEnvString(
                    env('DB_PASSWORD', $config['password'] ?? '')
                );
                $port          = (string) env('DB_PORT', $config['port'] ?? 3306);
                $mysqldump     = $this->getBackupBinary('mysqldump', 'mysqldump');

                $attemptsErrors = [];

                // 1ª tentativa: host normalizado (geralmente 127.0.0.1) com --protocol=tcp
                $command = [
                    $mysqldump,
                    '--host=' . $primaryHost,
                    '--port=' . $port,
                    '--user=' . $username,
                    '--result-file=' . $dumpPath,
                    '--skip-lock-tables',
                    '--protocol=tcp',
                ];

                if (! empty($config['unix_socket'])) {
                    $command[] = '--socket=' . $config['unix_socket'];
                }

                if (! empty($config['charset'])) {
                    $command[] = '--default-character-set=' . $config['charset'];
                }

                if ($password !== '') {
                    $command[] = '-p' . $password;
                }

                $command[] = $database;

                $process = new Process($command, null, null);
                $process->setTimeout(300);
                $process->run();

                if ($process->isSuccessful()) {
                    return $dumpPath;
                }

                $primaryErrorOutput = $process->getErrorOutput() ?: $process->getOutput();
                $attemptsErrors[]   = "host={$primaryHost}: " . $primaryErrorOutput;

                // Se não for erro típico de socket/host, falha direto
                if (! $this->isTcpSocketError($primaryErrorOutput)) {
                    throw new RuntimeException('mysqldump failed: ' . $primaryErrorOutput);
                }

                // 2ª tentativa: host fallback (localhost ou outro), sem forçar TCP
                Log::warning('[update] mysqldump TCP/IP error, trying fallback host without --protocol=tcp', [
                    'primary_host'  => $primaryHost,
                    'fallback_host' => $fallbackHost,
                    'error'         => $primaryErrorOutput,
                ]);

                $fallbackCommand = $this->buildMysqlDumpCommand(
                    $mysqldump,
                    $fallbackHost,
                    $port,
                    $username,
                    $database,
                    $dumpPath,
                    [
                        'password'     => $password,
                        'unix_socket'  => $config['unix_socket'] ?? null,
                        'charset'      => $config['charset'] ?? null,
                    ],
                    false // sem --protocol=tcp
                );

                $fallbackProcess = new Process($fallbackCommand, null, null);
                $fallbackProcess->setTimeout(300);
                $fallbackProcess->run();

                if ($fallbackProcess->isSuccessful()) {
                    return $dumpPath;
                }

                $fallbackErrorOutput = $fallbackProcess->getErrorOutput() ?: $fallbackProcess->getOutput();
                $attemptsErrors[]    = "host={$fallbackHost}: " . $fallbackErrorOutput;

                // ==========================================
                // Fallback final: dump 100% PHP via PDO
                // ==========================================
                try {
                    Log::warning('[update] mysqldump TCP/IP/host errors, using PHP PDO fallback dump.', [
                        'primary_error'  => $primaryErrorOutput,
                        'fallback_error' => $fallbackErrorOutput,
                    ]);

                    $this->createDatabaseDumpViaPdo($connection, $dumpPath, $database);

                    Log::info('[update] PHP PDO fallback dump completed successfully.', [
                        'dump_path' => $dumpPath,
                    ]);

                    return $dumpPath;
                } catch (Throwable $phpDumpException) {
                    $allErrors = implode(' | ', $attemptsErrors);

                    throw new RuntimeException(
                        'mysqldump failed after TCP/IP socket/host errors and PHP fallback also failed. '
                        . 'Attempts: ' . $allErrors
                        . ' | php_fallback_error: ' . $phpDumpException->getMessage(),
                        previous: $phpDumpException
                    );
                }

            case 'pgsql':
                $pgDump = $this->getBackupBinary('pg_dump', 'pg_dump');
                $host   = $this->normalizeEnvString($config['host'] ?? '127.0.0.1');
                $port   = (string) ($config['port'] ?? 5432);
                $user   = $this->normalizeEnvString($config['username'] ?? 'postgres');
                $db     = $this->normalizeEnvString($config['database'] ?? null);

                if (! $db) {
                    throw new RuntimeException('Database name not configured for PostgreSQL backup.');
                }

                $command = [
                    $pgDump,
                    '-h', $host,
                    '-p', $port,
                    '-U', $user,
                    '-f', $dumpPath,
                    $db,
                ];

                $env = [];
                $password = $this->normalizeEnvString($config['password'] ?? '');
                if ($password !== '') {
                    $env['PGPASSWORD'] = $password;
                }

                $process = new Process($command, null, $env ?: null);
                $process->setTimeout(300);
                $process->run();
                if (! $process->isSuccessful()) {
                    throw new RuntimeException('pg_dump failed: ' . $process->getErrorOutput());
                }
                break;

            case 'sqlite':
                $databasePath = $this->normalizeEnvString($config['database'] ?? null);
                if ($databasePath && file_exists($databasePath)) {
                    File::copy($databasePath, $dumpPath);
                } else {
                    throw new RuntimeException('SQLite database file not found for backup.');
                }
                break;

            default:
                throw new RuntimeException("Database driver [{$driver}] is not supported for backups.");
        }

        return $dumpPath;
    }

    /**
     * Fallback 100% PHP para backup de MySQL/MariaDB via PDO.
     *
     * Gera um arquivo .sql com:
     *  - SET FOREIGN_KEY_CHECKS=0/1
     *  - DROP TABLE IF EXISTS
     *  - SHOW CREATE TABLE
     *  - INSERTs para todos os registros de cada tabela
     */
    protected function createDatabaseDumpViaPdo(
        ConnectionInterface $connection,
        string $dumpPath,
        ?string $database = null
    ): void {
        $driver = $connection->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('PHP PDO fallback dump is only supported for MySQL/MariaDB.');
        }

        $config = $connection->getConfig();

        $dbName = $this->normalizeEnvString(
            $database ?? ($config['database'] ?? null)
        );

        if (! $dbName) {
            throw new RuntimeException('Database name not configured for PHP PDO fallback dump.');
        }

        $pdo = $connection->getPdo();

        $handle = @fopen($dumpPath, 'w');
        if (! $handle) {
            throw new RuntimeException("Unable to open dump file for writing: {$dumpPath}");
        }

        try {
            $header = sprintf(
                "-- Backup gerado via PHP PDO fallback\n-- Banco: %s\n-- Data: %s\n\nSET FOREIGN_KEY_CHECKS=0;\n\n",
                $dbName,
                now()->toDateTimeString()
            );
            fwrite($handle, $header);

            // Lista de tabelas
            $tables = $connection->select('SHOW TABLES');
            if (empty($tables)) {
                fwrite($handle, "-- Nenhuma tabela encontrada no banco {$dbName}.\n\n");
            }

            foreach ($tables as $tableRow) {
                $rowArray   = (array) $tableRow;
                $tableName  = array_values($rowArray)[0] ?? null;

                if (! $tableName) {
                    continue;
                }

                // Estrutura da tabela
                $createRow = (array) $connection->selectOne("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createRow['Create Table'] ?? $createRow['Create Table'] ?? null;
                if (! $createSql) {
                    // fallback genérico se o índice variar
                    $values = array_values($createRow);
                    $createSql = $values[1] ?? null;
                }

                if (! $createSql) {
                    continue;
                }

                fwrite($handle, sprintf("-- ----------------------------\n-- Estrutura da tabela `%s`\n-- ----------------------------\n", $tableName));
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                // Dados da tabela
                $rows = $connection->table($tableName)->get();
                if ($rows->isEmpty()) {
                    continue;
                }

                fwrite($handle, sprintf("-- Dados da tabela `%s`\n", $tableName));

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $columns  = array_keys($rowArray);

                    $colsSql = implode(', ', array_map(
                        fn ($col) => '`' . str_replace('`', '``', $col) . '`',
                        $columns
                    ));

                    $valuesSql = [];
                    foreach ($rowArray as $value) {
                        if (is_null($value)) {
                            $valuesSql[] = 'NULL';
                        } elseif (is_bool($value)) {
                            $valuesSql[] = $value ? '1' : '0';
                        } elseif (is_numeric($value)) {
                            $valuesSql[] = (string) $value;
                        } else {
                            $valuesSql[] = $pdo->quote((string) $value);
                        }
                    }

                    $insertSql = sprintf(
                        "INSERT INTO `%s` (%s) VALUES (%s);\n",
                        $tableName,
                        $colsSql,
                        implode(', ', $valuesSql)
                    );

                    fwrite($handle, $insertSql);
                }

                fwrite($handle, "\n\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    /**
     * Monta os argumentos do comando mysqldump.
     *
     * (mantido para compatibilidade)
     */
    protected function buildMysqlDumpCommand(
        string $binary,
        string $host,
        string $port,
        string $username,
        string $database,
        string $dumpPath,
        array $config,
        bool $forceTcp = false
    ): array {
        $host     = $this->normalizeEnvString($host);
        $username = $this->normalizeEnvString($username);
        $database = $this->normalizeEnvString($database);

        $command = [
            $binary,
            '--result-file=' . $dumpPath,
            '--skip-lock-tables',
        ];

        // Só adiciona --host se tiver valor
        if ($host !== null && $host !== '') {
            $command[] = '--host=' . $host;
        }

        $command[] = '--port=' . $port;
        $command[] = '--user=' . $username;

        if ($forceTcp) {
            $command[] = '--protocol=tcp';
        }

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket=' . $config['unix_socket'];
        }

        if (! empty($config['charset'])) {
            $command[] = '--default-character-set=' . $config['charset'];
        }

        $password = $this->normalizeEnvString($config['password'] ?? '');
        if ($password !== '') {
            $command[] = '-p' . $password;
        }

        $command[] = $database;

        return $command;
    }

    /**
     * Recupera o caminho configurado para binários de backup.
     */
    protected function getBackupBinary(string $key, string $default): string
    {
        $backupConfig = Config::get('updates.backup', []);
        $binaries     = Arr::get($backupConfig, 'binaries', []);
        $binary       = Arr::get($binaries, $key, $default);

        return is_string($binary) && $binary !== '' ? $binary : $default;
    }

    /**
     * Compacta a pasta vendor.
     */
    protected function archiveVendorDirectory(string $basePath, string $timestamp, string $slug): ?string
    {
        $vendorPath = base_path('vendor');
        if (! is_dir($vendorPath)) {
            return null;
        }

        $archivePath = $basePath . DIRECTORY_SEPARATOR . "{$timestamp}-{$slug}-vendor.zip";
        $zip         = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create vendor archive for backup.');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($vendorPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $relativePath = 'vendor/' . Str::after($file->getPathname(), $vendorPath . DIRECTORY_SEPARATOR);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }

        $zip->close();

        return $archivePath;
    }

    /**
     * Copia os arquivos composer.json e composer.lock para o diretório de backup.
     */
    protected function backupComposerFiles(string $basePath, string $timestamp, string $slug): array
    {
        $files   = ['composer.json', 'composer.lock'];
        $backups = [];

        foreach ($files as $file) {
            $fullPath = base_path($file);
            if (! file_exists($fullPath)) {
                continue;
            }

            $target = $basePath . DIRECTORY_SEPARATOR . "{$timestamp}-{$slug}-{$file}";
            File::copy($fullPath, $target);
            $backups[$file] = $target;
        }

        return $backups;
    }

    /**
     * Restaura vendor/composer caso solicitado em um downgrade.
     */
    protected function restoreDependenciesIfRequested(UpdateVersion $version): void
    {
        $metadata = $version->metadata ?? [];
        $restore  = (bool) Arr::get($metadata, 'restore_dependencies', false);
        if (! $restore) {
            $this->log($version, 'dependencies', 'skipped', 'Dependency restore not requested.');
            return;
        }

        $backups = collect(Arr::get($metadata, 'backups', []));
        if ($backups->isEmpty()) {
            $this->log($version, 'dependencies', 'failed', 'No backups available to restore dependencies.');
            throw new RuntimeException('No backup available to restore dependencies.');
        }

        $targetBackup = $backups
            ->filter(fn ($snapshot) => empty($snapshot['downgrade']))
            ->last() ?? $backups->last();

        $this->log($version, 'dependencies', 'running', 'Restoring dependencies from backup.', $targetBackup);

        $archive = Arr::get($targetBackup, 'vendor_archive');
        if ($archive && file_exists($archive)) {
            $this->extractVendorArchive($archive);
        }

        foreach (Arr::get($targetBackup, 'composer_files', []) as $file => $path) {
            if ($path && file_exists($path)) {
                File::copy($path, base_path($file));
            }
        }

        $this->log($version, 'dependencies', 'success', 'Dependencies restored from backup.');
    }

    protected function extractVendorArchive(string $archivePath): void
    {
        $vendorPath = base_path('vendor');
        if (is_dir($vendorPath)) {
            File::deleteDirectory($vendorPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Unable to open vendor archive for restoration.');
        }

        $zip->extractTo(base_path());
        $zip->close();
    }

    /**
     * Executa a ação de composer selecionada.
     */
    protected function runComposer(UpdateVersion $version): void
    {
        $composerConfig = Config::get('updates.composer');
        if (! Arr::get($composerConfig, 'enabled', true)) {
            $this->log($version, 'composer', 'skipped', 'Composer actions are disabled.');
            return;
        }

        $action = $version->composer_action ?? Arr::get($composerConfig, 'default_action', 'none');
        if ($action === 'none' || $action === null) {
            $this->log($version, 'composer', 'skipped', 'No composer action requested.');
            return;
        }

        $command = match ($action) {
            'install'       => ['composer', 'install', '--no-interaction', '--prefer-dist'],
            'update'        => ['composer', 'update', '--no-interaction'],
            'dump-autoload' => ['composer', 'dump-autoload'],
            default         => null,
        };

        if (! $command) {
            $this->log($version, 'composer', 'skipped', 'Unsupported composer action requested.', ['action' => $action]);
            return;
        }

        $this->log($version, 'composer', 'running', 'Executing composer ' . $action . '.');

        $process = new Process($command, base_path());
        $process->setTimeout((int) Arr::get($composerConfig, 'timeout', 600));
        $process->run();

        $context = [
            'action'       => $action,
            'output'       => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
            'exit_code'    => $process->getExitCode(),
        ];

        if (! $process->isSuccessful()) {
            $this->log($version, 'composer', 'failed', 'Composer ' . $action . ' failed.', $context);
            throw new RuntimeException('Composer action failed: ' . $process->getErrorOutput());
        }

        $this->log($version, 'composer', 'success', 'Composer ' . $action . ' completed successfully.', $context);
    }

    /**
     * Executa migrations para cima ou para baixo.
     */
    protected function runMigrations(UpdateVersion $version, ConnectionInterface $connection, bool $downgrade = false): void
    {
        if ($downgrade) {
            $this->rollbackMigrations($version, $connection);
            return;
        }

        $this->log($version, 'migrations', 'running', 'Running pending migrations.');

        $exitCode = Artisan::call('migrate', [
            '--database' => $connection->getName(),
            '--force'    => true,
        ]);

        if ($exitCode !== 0) {
            $this->log($version, 'migrations', 'failed', 'Migration command failed.', ['exit_code' => $exitCode]);
            throw new RuntimeException('Migration command failed.');
        }

        $batchMigrations = array_values(array_diff(
            $this->getRecentMigrations($connection),
            self::INFRASTRUCTURE_MIGRATIONS
        ));

        foreach ($batchMigrations as $migration) {
            UpdateVersionMigration::updateOrCreate(
                [
                    'update_version_id' => $version->id,
                    'migration'         => $migration,
                ],
                [
                    'direction' => 'up',
                ]
            );
        }

        $this->log($version, 'migrations', 'success', 'Migrations executed successfully.', [
            'migrations' => $batchMigrations,
        ]);
    }

    protected function rollbackMigrations(UpdateVersion $version, ConnectionInterface $connection): void
    {
        $migrations = $version->migrations()
            ->where('direction', 'up')
            ->whereNotIn('migration', self::INFRASTRUCTURE_MIGRATIONS)
            ->orderByDesc('id')
            ->get();

        if ($migrations->isEmpty()) {
            $this->log($version, 'migrations', 'skipped', 'No migrations registered for rollback.');
            return;
        }

        $list = $migrations->pluck('migration')->all();
        $this->log($version, 'migrations', 'preparing', 'Preparing rollback for migrations.', [
            'migrations' => $list,
        ]);

        foreach ($migrations as $migration) {
            $path    = $this->resolveMigrationPath($migration->migration);
            $exitCode = Artisan::call('migrate:rollback', [
                '--database' => $connection->getName(),
                '--force'    => true,
                '--path'     => $path,
            ]);

            if ($exitCode !== 0) {
                $this->log($version, 'migrations', 'failed', "Rollback failed for migration {$migration->migration}.", [
                    'exit_code' => $exitCode,
                ]);
                throw new RuntimeException("Rollback failed for migration {$migration->migration}.");
            }

            $migration->direction = 'down';
            $migration->save();

            $this->log($version, 'migrations', 'rolled_back', 'Migration rolled back successfully.', [
                'migration' => $migration->migration,
            ]);
        }

        $this->log($version, 'migrations', 'success', 'Rollback completed.', [
            'migrations' => $list,
        ]);
    }

    protected function resolveMigrationPath(string $migration): string
    {
        $file = Str::finish($migration, '.php');
        $path = database_path('migrations/' . $file);

        if (file_exists($path)) {
            return 'database/migrations/' . $file;
        }

        return 'database/migrations';
    }

    protected function getRecentMigrations(ConnectionInterface $connection): array
    {
        $batch = $connection->table('migrations')->max('batch');
        if (! $batch) {
            return [];
        }

        return $connection->table('migrations')
            ->where('batch', $batch)
            ->orderBy('id')
            ->pluck('migration')
            ->toArray();
    }

    /**
     * Sincroniza os arquivos da aplicação a partir do pacote baixado.
     */
    protected function syncApplicationFilesFromArchive(UpdateVersion $version, string $relativeArchivePath): void
    {
        $disk     = $this->getDisk();
        $absolute = $this->resolveArchiveAbsolutePath($disk, $relativeArchivePath);

        if (! $absolute || ! file_exists($absolute)) {
            throw new RuntimeException("Update archive [{$relativeArchivePath}] not found for extraction.");
        }

        $workdir = storage_path('app/updates/workdir/' . Str::slug($version->version) . '-' . uniqid());
        File::makeDirectory($workdir, 0755, true, true);

        $include = Config::get('updates.sync.include', [
            'app',
            'bootstrap',
            'config',
            'database',
            'public',
            'resources',
            'routes',
        ]);

        $exclude = Config::get('updates.sync.exclude', [
            'storage',
            'vendor',
            'public/storage',
            'node_modules',
            '.env',
            '.git',
            '.github',
            'tests',
        ]);

        try {
            $zip = new ZipArchive();
            if ($zip->open($absolute) !== true) {
                throw new RuntimeException('Unable to open update archive for extraction.');
            }

            $zip->extractTo($workdir);
            $zip->close();

            $entries = array_values(array_filter(scandir($workdir) ?: [], fn ($item) => ! in_array($item, ['.', '..'], true)));
            $root    = $workdir;
            if (count($entries) === 1 && is_dir($workdir . DIRECTORY_SEPARATOR . $entries[0])) {
                $root = $workdir . DIRECTORY_SEPARATOR . $entries[0];
            }

            foreach ($include as $path) {
                $source = $root . DIRECTORY_SEPARATOR . $path;
                $target = base_path($path);

                if (! file_exists($source)) {
                    continue;
                }

                if (is_file($source)) {
                    $directory = dirname($target);
                    if (! is_dir($directory)) {
                        File::makeDirectory($directory, 0755, true, true);
                    }
                    File::copy($source, $target);
                    continue;
                }

                $this->copyDirectoryFiltered($source, $target, $exclude);
            }
        } finally {
            File::deleteDirectory($workdir);
        }

        $this->log($version, 'files', 'success', 'Application files synchronized from archive.', [
            'archive' => $relativeArchivePath,
            'include' => $include,
            'exclude' => $exclude,
        ]);
    }

    protected function copyDirectoryFiltered(string $source, string $destination, array $excludePaths = []): void
    {
        if (! is_dir($source)) {
            return;
        }

        if (! is_dir($destination)) {
            File::makeDirectory($destination, 0755, true, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = Str::after($item->getPathname(), $source . DIRECTORY_SEPARATOR);

            foreach ($excludePaths as $excluded) {
                $excluded = trim($excluded, '/');
                if ($relativePath === $excluded || Str::startsWith($relativePath, $excluded . '/')) {
                    continue 2;
                }
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    File::makeDirectory($targetPath, 0755, true, true);
                }
            } else {
                $directory = dirname($targetPath);
                if (! is_dir($directory)) {
                    File::makeDirectory($directory, 0755, true, true);
                }
                File::copy($item->getPathname(), $targetPath);
            }
        }
    }

    protected function resolveArchiveAbsolutePath(Filesystem $disk, string $path): ?string
    {
        if (method_exists($disk, 'path') && ! Str::startsWith($path, DIRECTORY_SEPARATOR)) {
            return $disk->path($path);
        }

        if (Str::startsWith($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return storage_path('app/' . ltrim($path, '/'));
    }

    protected function createApplicationSnapshot(
        string $basePath,
        string $timestamp,
        string $slug,
        string $label = 'app'
    ): ?string {
        $archiveName = "{$timestamp}-{$slug}-{$label}.zip";
        $archivePath = $basePath . DIRECTORY_SEPARATOR . $archiveName;

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create application snapshot archive.');
        }

        $include = Config::get('updates.sync.include', [
            'app',
            'bootstrap',
            'config',
            'database',
            'public',
            'resources',
            'routes',
        ]);

        $exclude = Config::get('updates.sync.exclude', [
            'storage',
            'vendor',
            'public/storage',
            'node_modules',
            '.env',
            '.git',
            '.github',
            'tests',
        ]);

        foreach ($include as $path) {
            $root = base_path($path);
            if (! file_exists($root)) {
                continue;
            }

            if (is_file($root)) {
                $zip->addFile($root, $path);
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $relative = $path . '/' . Str::after($item->getPathname(), $root . DIRECTORY_SEPARATOR);

                foreach ($exclude as $excluded) {
                    $excluded = trim($excluded, '/');
                    if ($relative === $excluded || Str::startsWith($relative, $excluded . '/')) {
                        continue 2;
                    }
                }

                if ($item->isDir()) {
                    $zip->addEmptyDir($relative);
                } else {
                    $zip->addFile($item->getPathname(), $relative);
                }
            }
        }

        $zip->close();

        return $archivePath;
    }

    protected function resolveUpdateConnection(): ConnectionInterface
    {
        return $this->databaseManager->connection();
    }

    protected function getDisk(): Filesystem
    {
        $disk = Config::get('updates.storage_disk');

        return $this->filesystemFactory->disk($disk);
    }

    /**
     * Log + persistência em UpdateLog (com saneamento de contexto para JSON).
     */
    public function log(UpdateVersion $version, string $action, string $status, string $message, array $context = []): UpdateLog
    {
        // Garante que tudo em $context é JSON-safe (UTF-8 bem formado)
        $sanitizedContext = $this->sanitizeContextForJson($context);

        Log::info("[update] {$action} {$status} :: {$message}", $sanitizedContext + ['version' => $version->version]);

        return $version->logs()->create([
            'action'  => $action,
            'status'  => $status,
            'message' => $message,
            'context' => $sanitizedContext,
        ]);
    }

    protected function syncEnvVersion(string $version): void
    {
        $envKey  = Config::get('updates.env_version_key', 'VERSION');
        $envPath = base_path('.env');

        if (! File::exists($envPath) || ! is_writable($envPath)) {
            Config::set('app.version', $version);
            return;
        }

        $content = File::get($envPath);
        $pattern = '/^' . preg_quote($envKey, '/') . '=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $envKey . '=' . $version, $content);
        } else {
            $content .= (str_ends_with($content, PHP_EOL) ? '' : PHP_EOL)
                . $envKey . '=' . $version . PHP_EOL;
        }

        File::put($envPath, $content);
        Config::set('app.version', $version);
    }

    protected function getActiveConfig(?string $provider = null): ?UpdateSourceConfig
    {
        $provider = $provider ?? Config::get('updates.default_provider');

        return UpdateSourceConfig::query()
            ->where('provider', $provider)
            ->where('active', true)
            ->first();
    }

    /**
     * ==========================
     * Helpers de saneamento JSON
     * ==========================
     */

    /**
     * Normaliza strings vindas do .env/config (remove aspas e espaços).
     */
    protected function normalizeEnvString(?string $value): ?string
    {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value, " \t\n\r\0\x0B\"'");
    }

    /**
     * Detecta se a saída de erro do mysqldump é relacionada a TCP/IP/host.
     */
    protected function isTcpSocketError(?string $output): bool
    {
        if (! $output) {
            return false;
        }

        return str_contains($output, '2004')
            || str_contains($output, 'TCP/IP socket')
            || str_contains($output, '2005')
            || str_contains($output, 'Unknown MySQL server host');
    }

    /**
     * Sanitiza string para ser segura em JSON (evita "Malformed UTF-8 characters").
     */
    protected function sanitizeStringForJson(string $value): string
    {
        // Se já estiver ok em UTF-8, tenta manter
        if (function_exists('mb_check_encoding') && ! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        // Remove bytes inválidos com iconv, se disponível
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        // Último recurso: se json_encode ainda quebrar, aplica utf8_encode
        if (json_encode($value) === false) {
            $value = utf8_encode($value);
        }

        return $value;
    }

    /**
     * Sanitiza um valor qualquer (string, array, Throwable, etc.) para JSON.
     */
    protected function sanitizeValueForJson(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->sanitizeStringForJson($value);
        }

        if (is_array($value)) {
            return $this->sanitizeContextForJson($value);
        }

        if ($value instanceof Throwable) {
            return [
                'class'   => get_class($value),
                'message' => $this->sanitizeStringForJson($value->getMessage()),
                'trace'   => $this->sanitizeStringForJson($value->getTraceAsString()),
            ];
        }

        // Para recursos/objetos estranhos, faz um cast "seguro"
        if (is_resource($value)) {
            return (string) $value;
        }

        if (is_object($value) && ! $value instanceof \JsonSerializable) {
            // Evita jogar o objeto "cru" no json
            return [
                'class' => get_class($value),
                'dump'  => $this->sanitizeStringForJson(print_r($value, true)),
            ];
        }

        return $value;
    }

    /**
     * Sanitiza recursivamente o array de contexto para ser seguro em JSON.
     */
    protected function sanitizeContextForJson(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $sanitized[$key] = $this->sanitizeValueForJson($value);
        }

        return $sanitized;
    }
}
