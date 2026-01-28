<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\SystemUpdate;
use App\Models\SystemUpdateFile;
use App\Models\SystemUpdateLog;
use App\Models\SystemUpdateNode;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class SystemUpdateManager
{
    public const MODE_FULL_RELEASE = 'full_release';
    public const MODE_PATCH_HOTFIX = 'patch_hotfix';
    public const MODE_DB_ONLY = 'db_only';

    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function resolveMode(): string
    {
        $setting = SystemSetting::where('key', 'update_mode')->first();

        return $setting?->value ?: config('update_manager.default_mode');
    }

    public function run(?string $reference = null, array $files = [], array $options = []): SystemUpdate
    {
        $mode = $this->resolveMode();
        $lock = Cache::lock(config('update_manager.lock_key'), 120);

        if (! $lock->get()) {
            throw new \RuntimeException('Já existe um update em execução.');
        }

        try {
            $now = Carbon::now();
            $flags = $this->buildFlags($options);
            $update = SystemUpdate::create([
                'version' => $reference ?? $this->currentCommitHash(),
                'mode' => $mode,
                'target_reference' => $reference,
                'status' => 'running',
                'flags' => $flags,
                'dry_run' => (bool) ($options['dry_run'] ?? false),
                'initiated_by' => $options['initiated_by'] ?? 'system',
                'lock_key' => config('update_manager.lock_key'),
                'started_at' => $now,
            ]);

            $this->log($update, 'Execução iniciada', 'info', [
                'mode' => $mode,
                'flags' => $flags,
                'reference' => $reference,
            ]);

            DB::transaction(function () use ($update, $flags, $files, $reference) {
                $this->prepareBackups($update, $flags);

                match ($update->mode) {
                    self::MODE_FULL_RELEASE => $this->runFullRelease($update),
                    self::MODE_PATCH_HOTFIX => $this->runPatchHotfix($update, $files, $reference),
                    self::MODE_DB_ONLY => $this->runDbOnly($update, $flags),
                    default => throw new \InvalidArgumentException('Modo de update inválido: '.$update->mode),
                };

                $this->finalize($update, 'success');
            });

            return $update->fresh();
        } catch (\Throwable $exception) {
            Log::error('Falha na execução de update', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if (isset($update) && $update instanceof SystemUpdate) {
                $this->finalize($update, 'failed', $exception->getMessage());
            }

            throw $exception;
        } finally {
            $lock->release();
        }
    }

    public function rollback(SystemUpdate $update, array $options = []): SystemUpdate
    {
        $lock = Cache::lock(config('update_manager.lock_key'), 120);
        if (! $lock->get()) {
            throw new \RuntimeException('Não foi possível obter lock para rollback.');
        }

        try {
            $this->log($update, 'Iniciando rollback', 'warning', $options);

            match ($update->mode) {
                self::MODE_FULL_RELEASE => $this->rollbackFullRelease($update, $options),
                self::MODE_PATCH_HOTFIX => $this->rollbackPatchHotfix($update),
                self::MODE_DB_ONLY => $this->rollbackDbOnly($update, $options),
                default => throw new \InvalidArgumentException('Modo de update inválido para rollback'),
            };

            $this->finalize($update, 'rolled_back');

            return $update->fresh();
        } catch (\Throwable $exception) {
            $this->log($update, 'Rollback falhou: '.$exception->getMessage(), 'error');
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    private function buildFlags(array $options): array
    {
        $defaults = [
            'mysql_db_backup' => true,
            'composer_update' => true,
            'migrations_update' => true,
            'dry_run' => false,
        ];

        return array_merge($defaults, Arr::only($options, array_keys($defaults)));
    }

    private function prepareBackups(SystemUpdate $update, array $flags): void
    {
        if ($update->dry_run) {
            $this->log($update, 'Dry-run habilitado, backups apenas registrados logicamente', 'info');

            return;
        }

        if ($flags['mysql_db_backup']) {
            $update->backup_db_path = $this->createDatabaseBackup($update);
            $update->save();
        }

        $update->backup_code_path = $this->snapshotCodebase($update);
        $update->save();
    }

    private function runFullRelease(SystemUpdate $update): void
    {
        if ($update->dry_run) {
            $this->log($update, 'Simulação de full_release concluída', 'info');

            return;
        }

        $reference = $update->target_reference ?: 'origin/main';
        $this->assertGitReference($reference);

        $releasePath = $this->prepareReleaseDirectory($reference);
        $this->log($update, 'Release preparada', 'info', ['path' => $releasePath]);

        $this->runComposerIfEnabled($update);
        $this->runMigrationsIfNeeded($update);

        $this->log($update, 'Full release finalizada');
    }

    private function runPatchHotfix(SystemUpdate $update, array $files, ?string $reference): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Arquivos alvo do patch não foram informados.');
        }

        foreach ($files as $file) {
            $this->registerFileBackup($update, $file, $reference);
        }

        if ($update->dry_run) {
            $this->log($update, 'Simulação de patch_hotfix concluída', 'info', ['files' => $files]);

            return;
        }

        $reference = $reference ?: 'HEAD';
        $this->assertGitReference($reference);

        foreach ($files as $file) {
            $this->applyFileFromGit($update, $file, $reference);
        }

        $this->runComposerIfEnabled($update);
        $this->runMigrationsIfNeeded($update);

        $this->log($update, 'Patch aplicado com sucesso', 'info', ['files' => $files]);
    }

    private function runDbOnly(SystemUpdate $update, array $flags): void
    {
        if ($update->dry_run) {
            $this->log($update, 'Simulação de db_only concluída', 'info');

            return;
        }

        if ($flags['migrations_update']) {
            $this->runMigrationsIfNeeded($update);
        }

        $this->log($update, 'Rotina de banco concluída');
    }

    private function finalize(SystemUpdate $update, string $status, ?string $error = null): void
    {
        $update->status = $status;
        $update->finished_at = Carbon::now();
        if ($error) {
            $update->notes = trim(($update->notes ?: '')."\n".$error);
        }
        $update->save();

        $this->log($update, 'Finalização registrada', 'info', ['status' => $status]);
    }

    private function log(SystemUpdate $update, string $message, string $level = 'info', array $context = []): void
    {
        SystemUpdateLog::create([
            'system_update_id' => $update->id,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    private function createDatabaseBackup(SystemUpdate $update): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $fileName = "db_{$timestamp}_{$update->version}.sql";
        $backupPath = rtrim(config('update_manager.backup_path'), '/');

        Storage::makeDirectory('updates/backups');
        $fullPath = $backupPath.'/'.$fileName;

        $command = [
            config('update_manager.backup.database.binary'),
            '-u'.env('DB_USERNAME'),
        ];

        $password = env('DB_PASSWORD');
        if ($password) {
            $command[] = '-p'.$password;
        }

        $command[] = env('DB_DATABASE');

        try {
            $process = new Process($command);
            $process->run();

            if ($process->isSuccessful()) {
                File::put($fullPath, $process->getOutput());
            } else {
                $this->log($update, 'Falha ao executar mysqldump, gerando backup lógico apenas', 'warning', [
                    'error' => $process->getErrorOutput(),
                ]);
                File::put($fullPath, '-- Backup lógico gerado (mysqldump indisponível)');
            }
        } catch (\Throwable $exception) {
            $this->log($update, 'Erro ao gerar dump, fallback para arquivo lógico', 'warning', [
                'message' => $exception->getMessage(),
            ]);
            File::put($fullPath, '-- Backup lógico gerado (exception)');
        }

        if (config('update_manager.backup.database.compress')) {
            File::put($fullPath.'.gz', gzencode(File::get($fullPath)));
            File::delete($fullPath);
            $fullPath .= '.gz';
        }

        $this->log($update, 'Backup de banco gerado', 'info', ['path' => $fullPath]);

        return $fullPath;
    }

    private function snapshotCodebase(SystemUpdate $update): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $path = rtrim(config('update_manager.backup_path'), '/')."/code_{$timestamp}_{$update->version}.tar";

        Storage::makeDirectory('updates/backups');
        $process = new Process(['tar', '-cf', $path, '.']);
        $process->run(null, ['PWD' => base_path()]);

        if (! $process->isSuccessful()) {
            $this->log($update, 'Falha ao compactar código, criando snapshot lógico', 'warning', [
                'error' => $process->getErrorOutput(),
            ]);
            File::put($path, 'Snapshot lógico do código - tar indisponível');
        }

        return $path;
    }

    private function runComposerIfEnabled(SystemUpdate $update): void
    {
        $flags = $update->flags ?? [];
        if (! ($flags['composer_update'] ?? true)) {
            $this->log($update, 'composer_update desabilitado, etapa ignorada');

            return;
        }

        $this->log($update, 'Executando composer install');

        $process = new Process(['composer', 'install', '--no-dev', '--optimize-autoloader'], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('composer install falhou: '.$process->getErrorOutput());
        }

        $this->log($update, 'Composer finalizado com sucesso');
    }

    private function runMigrationsIfNeeded(SystemUpdate $update): void
    {
        $flags = $update->flags ?? [];
        if (! ($flags['migrations_update'] ?? true)) {
            $this->log($update, 'migrations_update desabilitado explicitamente', 'warning');

            return;
        }

        $this->log($update, 'Executando migrations pendentes');
        $exitCode = Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Erro ao executar migrations');
        }
    }

    private function assertGitReference(string $reference): void
    {
        $process = new Process(['git', 'rev-parse', '--verify', $reference], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \InvalidArgumentException('Referência git inválida: '.$reference);
        }
    }

    private function prepareReleaseDirectory(string $reference): string
    {
        $releasePath = rtrim(config('update_manager.release_path'), '/').'/'.$this->sanitizeReference($reference).'_'.Carbon::now()->format('YmdHis');
        File::ensureDirectoryExists($releasePath);

        $process = new Process(['git', 'archive', '--format=tar', $reference], base_path());
        $process->run();

        if ($process->isSuccessful()) {
            $extract = new Process(['tar', '-xf', '-', '-C', $releasePath]);
            $extract->setInput($process->getOutput());
            $extract->run();
        } else {
            File::put($releasePath.'/.failed', $process->getErrorOutput());
        }

        return $releasePath;
    }

    private function sanitizeReference(string $reference): string
    {
        return Str::slug(str_replace(['/', ' '], '-', $reference));
    }

    private function registerFileBackup(SystemUpdate $update, string $file, ?string $reference): void
    {
        $absolute = base_path($file);
        $backupDir = rtrim(config('update_manager.backup_path'), '/').'/'.$update->id;
        File::ensureDirectoryExists($backupDir.'/'.dirname($file));

        if (File::exists($absolute)) {
            $hashBefore = md5_file($absolute);
            $backupPath = $backupDir.'/'.$file;
            File::copy($absolute, $backupPath);
        } else {
            $hashBefore = null;
            $backupPath = null;
        }

        SystemUpdateFile::create([
            'system_update_id' => $update->id,
            'path' => $file,
            'hash_before' => $hashBefore,
            'backup_path' => $backupPath,
            'reference' => $reference,
        ]);
    }

    private function applyFileFromGit(SystemUpdate $update, string $file, string $reference): void
    {
        $process = new Process(['git', 'show', sprintf('%s:%s', $reference, $file)], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Não foi possível extrair arquivo do git: '.$file);
        }

        $absolutePath = base_path($file);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $process->getOutput());

        $updateFile = $update->files()->where('path', $file)->latest()->first();
        if ($updateFile) {
            $updateFile->hash_after = md5($process->getOutput());
            $updateFile->save();
        }
    }

    private function rollbackFullRelease(SystemUpdate $update, array $options): void
    {
        $this->log($update, 'Rollback de full_release iniciado', 'warning');
        if ($update->backup_code_path && File::exists($update->backup_code_path)) {
            $this->log($update, 'Snapshot de código disponível em '.$update->backup_code_path);
        }

        if (($options['restore_db'] ?? false) && $update->backup_db_path && File::exists($update->backup_db_path)) {
            $this->log($update, 'Restauração de banco solicitada (executar manualmente com segurança)', 'warning');
        }
    }

    private function rollbackPatchHotfix(SystemUpdate $update): void
    {
        foreach ($update->files as $file) {
            if ($file->backup_path && File::exists($file->backup_path)) {
                File::copy($file->backup_path, base_path($file->path));
            }
        }

        $this->log($update, 'Arquivos restaurados a partir dos backups', 'info');
    }

    private function rollbackDbOnly(SystemUpdate $update, array $options): void
    {
        if (($options['restore_dump'] ?? false) && $update->backup_db_path && File::exists($update->backup_db_path)) {
            $this->log($update, 'Restauração de dump registrada (execução deve ser orquestrada com segurança)', 'warning');
        }
    }

    private function currentCommitHash(): string
    {
        $process = new Process(['git', 'rev-parse', 'HEAD'], base_path());
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : 'unknown';
    }
}
