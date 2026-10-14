<?php

namespace Tests\Feature;

use Database\Seeders\MtrResiduosSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Testes em SQLite :memory: isolado. Nunca utiliza um banco de produção.
 */
class MtrResiduosSeederTest extends TestCase
{
    private bool $createdFixtureTable = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Alguns testes anteriores já migraram a base fersoft_test. Nunca
        // recriar ou excluir uma tabela preexistente durante a suíte.
        if (!Schema::hasTable('mtr_residuos')) {
            Schema::create('mtr_residuos', function (Blueprint $table): void {
                $table->id();
                $table->integer('res_codigo')->unique();
                $table->string('res_codigo_ibama', 50);
                $table->text('res_descricao');
                $table->text('grr_descricao')->nullable();
                $table->string('grr_representacao', 20)->nullable();
                $table->unsignedInteger('empresa_id')->nullable();
                $table->unsignedInteger('filial_id')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->timestamps();
            });

            $this->createdFixtureTable = true;
        }

        // Até quando a tabela já existe, nenhuma modificação do teste
        // permanece no banco após a execução.
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();

        if ($this->createdFixtureTable) {
            Schema::drop('mtr_residuos');
        }

        parent::tearDown();
    }

    /** @return array<int, int> */
    private function catalogCodes(): array
    {
        $lines = file(database_path('seeders/data/MTR_RESIDUOS.ndjson'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return array_map(
            static fn (string $line): int => (int) json_decode($line, true, 512, JSON_THROW_ON_ERROR)['res_codigo'],
            $lines ?: []
        );
    }

    private function importedCount(): int
    {
        return DB::table('mtr_residuos')
            ->whereIn('res_codigo', $this->catalogCodes())
            ->count();
    }

    public function test_insere_catalogo_sem_importar_pk_e_preserva_codigos_ibama(): void
    {
        $this->seed(MtrResiduosSeeder::class);

        $this->assertSame(895, $this->importedCount());
        $this->assertSame('010101', DB::table('mtr_residuos')->where('res_codigo', 735)->value('res_codigo_ibama'));
        $this->assertSame('010304(*)', DB::table('mtr_residuos')->where('res_codigo', 740)->value('res_codigo_ibama'));
        $this->assertNull(DB::table('mtr_residuos')->where('res_codigo', 645)->value('empresa_id'));
        $this->assertCount(895, array_unique($this->catalogCodes()));
    }

    public function test_segunda_execucao_nao_duplica_nem_altera_timestamps_ou_pk(): void
    {
        $this->seed(MtrResiduosSeeder::class);
        $before = DB::table('mtr_residuos')->where('res_codigo', 735)->first();
        DB::table('mtr_residuos')->where('res_codigo', 735)->update([
            'updated_at' => '2020-01-01 00:00:00',
        ]);

        $this->seed(MtrResiduosSeeder::class);

        $after = DB::table('mtr_residuos')->where('res_codigo', 735)->first();
        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->created_at, $after->created_at);
        $this->assertSame('2020-01-01 00:00:00', $after->updated_at);
        $this->assertSame(895, $this->importedCount());
    }

    public function test_corrige_descricao_antiga_sem_alterar_dados_de_tenant(): void
    {
        $this->seed(MtrResiduosSeeder::class);
        $before = DB::table('mtr_residuos')->where('res_codigo', 645)->first();

        // Altera somente a descrição: não introduz FKs fictícias em fersoft_test.
        DB::table('mtr_residuos')->where('res_codigo', 645)->update([
            'res_descricao' => 'Descrição antiga',
        ]);

        $this->seed(MtrResiduosSeeder::class);
        $after = DB::table('mtr_residuos')->where('res_codigo', 645)->first();

        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->empresa_id, $after->empresa_id);
        $this->assertSame($before->filial_id, $after->filial_id);
        $this->assertSame($before->usuario_id, $after->usuario_id);
        $this->assertSame('Resíduos de ferro ou aço', $after->res_descricao);
        $this->assertSame(895, $this->importedCount());
    }

    public function test_catalogo_global_e_visivel_no_depara_sem_remover_escopo_do_tenant(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/MtrDeparaController.php');
        $databaseSeeder = file_get_contents($root . '/database/seeders/DatabaseSeeder.php');
        $migration = file_get_contents(
            $root . '/database/migrations/2026_10_14_020000_seed_global_mtr_residuos_catalog.php'
        );

        $this->assertStringContainsString("->whereNull('empresa_id')", $controller);
        $this->assertStringContainsString("->orWhere('empresa_id', \$this->empresa_id)", $controller);
        $this->assertStringContainsString('MtrResiduosSeeder::class', $databaseSeeder);
        $this->assertStringContainsString('app(MtrResiduosSeeder::class)->run()', $migration);
    }
}