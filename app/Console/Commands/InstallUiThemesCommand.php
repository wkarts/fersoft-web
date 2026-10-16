<?php

namespace App\Console\Commands;

use App\Services\UiThemeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Instala somente o esquema do catálogo visual, sem tocar migrations de outros módulos. */
class InstallUiThemesCommand extends Command
{
    protected $signature = 'ui-themes:install
        {--apply : Executa somente as migrations pendentes do catálogo visual}
        {--repair-recorded : Com --apply, reexecuta o up idempotente quando o registro existe mas a tabela/coluna falta}';

    protected $description = 'Diagnostica e, se solicitado, instala as migrations aditivas dos temas da interface';

    private const MIGRATIONS = [
        '2026_10_14_230100_create_ui_theme_presets_table' => 'ui_theme_presets',
        '2026_10_14_230101_create_ui_theme_company_settings_table' => 'ui_theme_company_settings',
        '2026_10_14_230102_create_ui_theme_user_preferences_table' => 'ui_theme_user_preferences',
        '2026_10_15_060000_add_menu_mode_to_ui_theme_user_preferences_table' => ['ui_theme_user_preferences', ['menu_mode']],
    ];

    public function handle(): int
    {
        if ($this->option('repair-recorded') && !$this->option('apply')) {
            $this->error('--repair-recorded requer --apply.');
            return self::FAILURE;
        }

        try {
            $migrationTable = config('database.migrations', 'migrations');
            $registered = Schema::hasTable($migrationTable)
                ? DB::table($migrationTable)->whereIn('migration', array_keys(self::MIGRATIONS))
                    ->pluck('migration')->all()
                : [];

            foreach (self::MIGRATIONS as $migration => $target) {
                [$table, $columns] = $this->targetColumns($migration, $target);
                $path = database_path('migrations/' . $migration . '.php');
                if (!is_file($path)) {
                    $this->error("Arquivo ausente: {$path}");
                    return self::FAILURE;
                }

                $hasRecord = in_array($migration, $registered, true);
                $hasSchema = $this->hasColumns($table, $columns);
                $missingIndexes = Schema::hasTable($table) ? $this->missingUniqueIndexes($table) : [];
                $this->line(sprintf('%s | registro: %s | esquema: %s',
                    $migration, $hasRecord ? 'aplicado' : 'pendente', $hasSchema ? 'presente' : 'ausente'
                ));

                if ($missingIndexes) {
                    $this->error("Índice único ausente em {$table}: " . implode('; ', $missingIndexes)
                        . '. Não será criado automaticamente em tabela pré-existente.');
                    return self::FAILURE;
                }

                if (!$this->option('apply') || $hasRecord && $hasSchema) {
                    continue;
                }

                if ($hasRecord) {
                    if (!$this->option('repair-recorded')) {
                        $this->error('Migração registrada sem esquema físico. Avalie a divergência e use --apply --repair-recorded.');
                        return self::FAILURE;
                    }

                    // Os quatro up() são aditivos; um esquema parcial incompatível
                    // lança erro e interrompe a instalação sem apagar nenhum dado.
                    (require $path)->up();
                    $this->info("Esquema recuperado: {$migration}");
                } else {
                    $exit = Artisan::call('migrate', [
                        '--path' => [$path],
                        '--realpath' => true,
                        '--force' => true,
                    ]);
                    $this->line(trim(Artisan::output()));
                    if ($exit !== self::SUCCESS) {
                        $this->error("Falha na migração {$migration}.");
                        return self::FAILURE;
                    }
                }

                if (!$this->hasColumns($table, $columns)) {
                    $this->error("A migração {$migration} terminou sem completar o esquema de {$table}.");
                    return self::FAILURE;
                }
            }

            $missing = [];
            foreach (self::MIGRATIONS as $migration => $target) {
                [$table, $columns] = $this->targetColumns($migration, $target);
                $present = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
                foreach (array_diff($columns, $present) as $column) {
                    $missing[] = "{$table}.{$column}";
                }
                if ($present) {
                    foreach ($this->missingUniqueIndexes($table) as $index) {
                        $missing[] = "{$table} índice único ({$index})";
                    }
                }
            }

            if ($missing) {
                $this->error('Esquema incompleto: ' . implode(', ', $missing));
                return self::FAILURE;
            }

            $this->info($this->option('apply')
                ? 'Catálogo visual instalado; nenhuma tabela legada foi alterada.'
                : 'Esquema básico presente. Diagnóstico somente leitura; nada foi alterado.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Falha ao consultar/aplicar migrations do catálogo: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function hasColumns(string $table, array $columns): bool
    {
        return Schema::hasTable($table)
            && !array_diff($columns, Schema::getColumnListing($table));
    }

    private function targetColumns(string $migration, $target): array
    {
        if (is_array($target)) {
            return $target;
        }

        $columns = UiThemeService::REQUIRED_COLUMNS[$target];
        if ($migration === '2026_10_14_230102_create_ui_theme_user_preferences_table') {
            $columns = array_values(array_diff($columns, ['menu_mode']));
        }

        return [$target, $columns];
    }

    private function missingUniqueIndexes(string $table): array
    {
        $missing = [];
        foreach (UiThemeService::REQUIRED_UNIQUE_INDEXES[$table] as $columns) {
            if (!Schema::hasIndex($table, $columns, 'unique')) {
                $missing[] = implode(', ', $columns);
            }
        }

        return $missing;
    }
}
