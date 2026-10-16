<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificaEmpresa;
use App\Services\UiThemeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

/** SQLite em memória: nunca aponta para o banco de homologação/produção. */
class UiThemeInstallationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array', 'ui_theme.active' => true]);
        DB::purge('sqlite');
        Schema::clearResolvedInstance('db.schema');
    }

    public function test_personal_studio_displays_factory_themes_before_installation(): void
    {
        View::shouldReceive('make')->once()
            ->with('themes.refined.studio.index', Mockery::on(function (array $data) {
                return $data['catalogReady'] === false
                    && isset($data['availableThemes']['fer-light'])
                    && count($data['customThemes']) === 0;
            }), [])
            ->andReturn('Temas de fábrica disponíveis');

        $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => 11, 'empresa' => 1, 'super' => 0]])
            ->get('/ui/temas')
            ->assertOk()
            ->assertSee('Temas de fábrica disponíveis');
        $this->assertFalse(Schema::hasTable('ui_theme_presets'));
    }

    public function test_company_theme_administration_rejects_ordinary_user(): void
    {
        $this->withoutMiddleware(VerificaEmpresa::class)
            ->withSession(['user_logged' => ['id' => 11, 'empresa' => 1, 'super' => 0]])
            ->get('/config/temas')
            ->assertForbidden();
    }

    public function test_diagnosis_is_read_only_and_apply_installs_only_theme_tables(): void
    {
        $this->artisan('ui-themes:install')->assertExitCode(1);
        $this->assertFalse(Schema::hasTable('migrations'));
        $this->assertFalse(Schema::hasTable('ui_theme_presets'));

        $this->artisan('ui-themes:install', ['--apply' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasTable('ui_theme_presets'));
        $this->assertTrue(Schema::hasTable('ui_theme_company_settings'));
        $this->assertTrue(Schema::hasColumn('ui_theme_user_preferences', 'menu_mode'));
        $this->assertSame(4, DB::table('migrations')->where('migration', 'like', '%ui_theme%')->count());

        // A inspeção completa é distinta do atalho de renderização normal.
        $this->assertTrue(app(UiThemeService::class)->catalogStatus()['ready']);
    }

    public function test_registered_migration_without_physical_table_requires_explicit_repair(): void
    {
        $this->artisan('ui-themes:install', ['--apply' => true])->assertExitCode(0);
        Schema::drop('ui_theme_presets');

        $this->artisan('ui-themes:install', ['--apply' => true])->assertExitCode(1);
        $this->assertFalse(Schema::hasTable('ui_theme_presets'));
        $this->artisan('ui-themes:install', ['--apply' => true, '--repair-recorded' => true])
            ->assertExitCode(0);
        $this->assertTrue(Schema::hasTable('ui_theme_presets'));
        $this->assertSame(1, DB::table('migrations')
            ->where('migration', '2026_10_14_230100_create_ui_theme_presets_table')->count());
    }

    public function test_partial_existing_table_is_diagnosed_instead_of_claimed_installed(): void
    {
        Schema::create('ui_theme_presets', function (Blueprint $table) {
            $table->increments('id');
        });

        $status = app(UiThemeService::class)->catalogStatus();
        $this->assertFalse($status['ready']);
        $this->assertContains('slug', $status['tables']['ui_theme_presets']['missing_columns']);
        $this->artisan('ui-themes:install')->assertExitCode(1);
        $this->assertFalse(Schema::hasTable('migrations'));
    }

    public function test_three_tables_without_menu_mode_still_require_the_fourth_migration(): void
    {
        foreach ([
            '2026_10_14_230100_create_ui_theme_presets_table.php',
            '2026_10_14_230101_create_ui_theme_company_settings_table.php',
            '2026_10_14_230102_create_ui_theme_user_preferences_table.php',
        ] as $migration) {
            (require database_path('migrations/' . $migration))->up();
        }

        $status = app(UiThemeService::class)->catalogStatus();
        $this->assertFalse($status['ready']);
        $this->assertContains('menu_mode', $status['tables']['ui_theme_user_preferences']['missing_columns']);
        $this->artisan('ui-themes:install')->assertExitCode(1);
        $this->artisan('ui-themes:install', ['--apply' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasColumn('ui_theme_user_preferences', 'menu_mode'));
    }

    public function test_existing_table_without_unique_index_is_not_silently_repaired(): void
    {
        Schema::create('ui_theme_company_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('default_theme_slug', 80)->nullable();
            $table->json('allowed_theme_slugs')->nullable();
            $table->timestamps();
        });

        $status = app(UiThemeService::class)->catalogStatus();
        $this->assertFalse($status['ready']);
        $this->assertSame([['company_id']],
            $status['tables']['ui_theme_company_settings']['missing_unique_indexes']);
        $this->artisan('ui-themes:install', ['--apply' => true])->assertExitCode(1);
        $this->assertFalse(Schema::hasIndex('ui_theme_company_settings', ['company_id'], 'unique'));
    }

    public function test_database_failure_is_not_reported_as_successful_installation(): void
    {
        config(['database.connections.sqlite.database' => '/this-directory-does-not-exist/ui-theme.sqlite']);
        DB::purge('sqlite');

        try {
            $this->artisan('ui-themes:install', ['--apply' => true])->assertExitCode(1);
        } finally {
            config(['database.connections.sqlite.database' => ':memory:']);
            DB::purge('sqlite');
        }
    }
}
