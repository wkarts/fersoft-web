<?php

namespace Tests\Feature;

use App\Models\MtrResiduo;
use Database\Seeders\MtrResiduosSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Testes isolados em SQLite :memory: ou no MySQL fersoft_test.
 * Nenhuma tabela preexistente é recriada ou apagada.
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
        $this->assertSame(0, DB::table('mtr_residuos')->whereNotNull('empresa_id')->count());
        $this->assertSame(0, DB::table('mtr_residuos')->whereNotNull('filial_id')->count());
        $this->assertSame(0, DB::table('mtr_residuos')->whereNotNull('usuario_id')->count());
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

    public function test_corrige_descricao_antiga_e_mantem_campo_de_empresa_nulo(): void
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
        $this->assertNull($after->empresa_id);
        $this->assertNull($after->filial_id);
        $this->assertNull($after->usuario_id);
        $this->assertSame('Resíduos de ferro ou aço', $after->res_descricao);
        $this->assertSame(895, $this->importedCount());
    }

    public function test_model_forca_vinculos_nulos_mesmo_com_atribuicao_direta(): void
    {
        $registro = new MtrResiduo();
        $registro->res_codigo = 2147483000;
        $registro->res_codigo_ibama = 'TESTE-GLOBAL';
        $registro->res_descricao = 'Referência global do teste';
        $registro->empresa_id = 99;
        $registro->filial_id = 99;
        $registro->usuario_id = 99;
        $registro->save();

        $salvo = DB::table('mtr_residuos')
            ->where('res_codigo', 2147483000)
            ->first();

        $this->assertNotNull($salvo);
        $this->assertNull($salvo->empresa_id);
        $this->assertNull($salvo->filial_id);
        $this->assertNull($salvo->usuario_id);
    }

    public function test_catalogo_global_sem_filtros_e_sem_injecao_de_empresa_filial_usuario(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/MtrDeparaController.php');
        $databaseSeeder = file_get_contents($root . '/database/seeders/DatabaseSeeder.php');
        $migration = file_get_contents(
            $root . '/database/migrations/2026_10_14_020000_seed_global_mtr_residuos_catalog.php'
        );

        // A relação De-Para continua sendo por empresa, mas a leitura do
        // catálogo é incondicional e compartilhada por todos os tenants.
        $this->assertStringContainsString("'residuos' => MtrResiduo::query()", $controller);
        $begin = strpos($controller, "'residuos' => MtrResiduo::query()");
        $catalogQuery = substr($controller, $begin, 140);
        $this->assertStringContainsString("->orderBy('res_codigo_ibama')", $catalogQuery);
        $this->assertStringNotContainsString("where('empresa_id'", $catalogQuery);
        $this->assertStringNotContainsString("whereNull('empresa_id'", $catalogQuery);
        $this->assertStringNotContainsString("filial_id", $catalogQuery);

        // O model não pode herdar injeção de IDs do TenantInjectable.
        $model = new MtrResiduo();
        foreach (['empresa_id', 'filial_id', 'usuario_id'] as $field) {
            $this->assertNotContains($field, $model->getFillable());
        }

        $seeder = file_get_contents($root . '/database/seeders/MtrResiduosSeeder.php');
        $this->assertStringContainsString("'empresa_id' => null", $seeder);
        $this->assertStringContainsString("'filial_id' => null", $seeder);
        $this->assertStringContainsString("'usuario_id' => null", $seeder);
        $this->assertStringContainsString('MtrResiduosSeeder::class', $databaseSeeder);
        $this->assertStringContainsString('app(MtrResiduosSeeder::class)->run()', $migration);
        $this->assertStringContainsString("NULL DEFAULT NULL", $migration);
    }
}