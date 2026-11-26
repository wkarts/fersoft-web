<?php

namespace Tests\Feature;

use App\Http\Controllers\TicketPesagemController;
use App\Models\PesagemTicketImagem;
use App\Models\TicketPesagem;
use App\Services\Pesagem\PesagemTicketImagemService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketFotoDispositivoTest extends TestCase
{
    private TicketPesagemController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['config_notas', 'veiculos', 'produtos', 'pesagens'] as $nome) {
            Schema::create($nome, function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('empresa_id')->default(1);
                $table->unsignedInteger('filial_id')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
            DB::table($nome)->insert(['id' => 1]);
        }
        Schema::table('pesagens', function (Blueprint $table) {
            $table->float('peso')->nullable();
            $table->float('peso_final')->nullable();
            $table->float('peso_liquido_bruto')->nullable();
        });
        foreach (['*create_tickets_pesagem_table', '*add_evidence_fields_to_tickets_pesagem_table',
            '*create_pesagem_ticket_imagens_table', '*add_imagens_persistidas_json_to_tickets_pesagem_table', '*add_snapshot_storage_and_multi_destinations_to_config_notas',
            '*add_pesagem_permitir_foto_dispositivo_to_config_notas'] as $nome) {
            (require glob(database_path('migrations/'.$nome.'.php'))[0])->up();
        }
        Schema::table('tickets_pesagem', function (Blueprint $table) {
            $table->string('peso_origem')->nullable();
            $table->integer('producao_lote_id')->nullable();
        });
        DB::table('config_notas')->update([
            'pesagem_snapshot_disk' => 'public',
            'pesagem_snapshot_base_path' => 'capturas-empresa',
        ]);
        Storage::fake('public');
        session(['user_logged' => ['empresa' => 1, 'id' => 7, 'super' => true, 'local_padrao' => -1]]);
        $this->controller = new class extends TicketPesagemController {
            public function __construct()
            {
                $this->empresa_id = 1;
                $this->usuario_id = 7;
                $this->filial_id = null;
            }
        };
    }

    private function pedido(): Request
    {
        return Request::create('/ticketsPesagem/save', 'POST', [
            'pesagem_id' => 1, 'veiculo_id' => 1, 'produto_id' => 1, 'peso' => 23,
            'tipo' => 'entrada', 'status' => 'em andamento', 'token' => Str::random(40),
            'quantidade_fotos' => 1, 'fotos_dispositivo' => [[
                'chave' => (string) Str::uuid(), 'capturado_em' => now()->toISOString(), 'legenda' => 'Lacre',
            ]],
        ], [], ['fotos_dispositivo' => [['arquivo' => UploadedFile::fake()->image('foto.jpg', 32, 32)]]]);
    }

    public function test_foto_opcional_respeita_empresa_e_estabelecimento(): void
    {
        $pedido = $this->pedido();
        $this->assertSame(403, $this->controller->save($pedido)->status());
        DB::table('config_notas')->update(['pesagem_permitir_foto_dispositivo' => true]);
        session(['user_logged.local_padrao' => 9]);
        $this->assertSame(403, $this->controller->save($pedido)->status());
        $this->assertSame(0, TicketPesagem::count());
    }

    public function test_foto_perdura_apos_recoleta_e_reenvio_e_eh_removida_com_ticket(): void
    {
        DB::table('config_notas')->update(['pesagem_permitir_foto_dispositivo' => true]);
        $pedido = $this->pedido();
        $resposta = $this->controller->save($pedido);
        $this->assertSame(200, $resposta->status(), $resposta->getContent());
        $ticket = TicketPesagem::firstOrFail();
        $foto = $ticket->imagens()->firstOrFail();
        $this->assertSame('dispositivo', $foto->metadata_json['origem']);
        $this->assertSame(7, $foto->metadata_json['usuario_id']);
        $this->assertSame('Lacre', $foto->metadata_json['legenda']);
        $this->assertNull($foto->adp_camera_id);
        $this->assertSame('public', $foto->storage_disk);
        $this->assertSame('capturas-empresa', $foto->storage_base_path);
        $this->assertSame('capturas-empresa/1/1/'.$ticket->id, dirname($foto->arquivo_path));
        Storage::disk('public')->assertExists($foto->arquivo_path);
        $this->assertSame($foto->arquivo_path, $ticket->imagens_persistidas_json[0]['arquivo_path']);
        $this->assertSame($foto->imagem_url, json_decode($ticket->camera_snapshots_json, true)[0]['arquivo_url']);
        $this->assertSame(200, $this->controller->save($pedido)->status());
        $this->assertSame(1, TicketPesagem::count());
        $this->assertSame(1, $ticket->imagens()->count());

        $edicao = $this->pedido();
        $adp = UploadedFile::fake()->image('balanca.jpg', 32, 32);
        $snapshot = json_encode([['image_data_url' => 'data:image/jpeg;base64,'.base64_encode($adp->get())]]);
        $edicao->merge(['edicao_confirmada' => true, 'token' => $ticket->token, 'camera_snapshots_json' => $snapshot]);
        $this->assertSame(200, $this->controller->update($edicao, $ticket->id)->status());
        $this->assertSame(200, $this->controller->update($edicao, $ticket->id)->status());
        $ticket->refresh();
        $this->assertSame(3, $ticket->imagens()->count());
        $this->assertCount(3, $ticket->imagens_persistidas_json);
        $evidencias = json_decode($ticket->balanca_evidence_json, true)['cameras'];
        $this->assertCount(1, $evidencias);
        $arquivoAdp = $evidencias[0]['arquivo_path'];
        $this->assertSame(dirname($foto->arquivo_path), dirname($arquivoAdp));
        Storage::disk('public')->assertExists($arquivoAdp);

        $recoleta = Request::create('/ticketsPesagem/update/'.$ticket->id, 'POST', $edicao->except(['fotos_dispositivo', 'quantidade_fotos']));
        $this->assertSame(200, $this->controller->update($recoleta, $ticket->id)->status());
        $ticket->refresh();
        $this->assertCount(3, $ticket->imagens_persistidas_json);
        $this->assertContains($foto->arquivo_path, array_column($ticket->imagens_persistidas_json, 'arquivo_path'));
        Storage::disk('public')->assertMissing($arquivoAdp);
        Storage::disk('public')->assertExists($foto->arquivo_path);
        $this->assertStringNotContainsString('base64', $ticket->camera_snapshots_json);
        $this->assertStringNotContainsString('base64,', json_encode($foto->metadata_json));
        app(PesagemTicketImagemService::class)->excluirDoTicket($ticket);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_destino_local_padrao_usa_diretorio_publico_original(): void
    {
        $publico = storage_path('framework/testing/publico-'.Str::uuid());
        $this->app->usePublicPath($publico);
        DB::table('config_notas')->update([
            'pesagem_permitir_foto_dispositivo' => true,
            'pesagem_snapshot_disk' => 'public_path',
            'pesagem_snapshot_base_path' => 'pesagem_ticket_imagens',
        ]);
        try {
            $this->assertSame(200, $this->controller->save($this->pedido())->status());
            $ticket = TicketPesagem::firstOrFail();
            $foto = $ticket->imagens()->firstOrFail();
            $this->assertSame('pesagem_ticket_imagens/1/1/'.$ticket->id, dirname($foto->arquivo_path));
            $this->assertFileExists(public_path($foto->arquivo_path));
            $this->assertSame(asset($foto->arquivo_path), $foto->imagem_url);
            $detalhe = $this->controller->edit($ticket->id)->getData(true);
            $this->assertSame($foto->arquivo_path, $detalhe['imagens_persistidas_json'][0]['arquivo_path']);
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($publico);
        }
    }

    public function test_foto_manual_nao_supre_evidencia_obrigatoria_da_balanca(): void
    {
        Schema::table('config_notas', function (Blueprint $table) {
            $table->boolean('bloquear_pesagem_manual_balanca')->default(true);
            $table->boolean('pesagem_exigir_imagem_quando_balanca_tem_camera')->default(true);
        });
        Schema::create('balanca_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('empresa_id');
            $table->boolean('ativo');
            $table->json('adp_camera_uuids');
            $table->softDeletes();
        });
        DB::table('balanca_configs')->insert(['empresa_id' => 1, 'ativo' => true, 'adp_camera_uuids' => '["balanca-1"]']);
        DB::table('config_notas')->update(['pesagem_permitir_foto_dispositivo' => true]);
        $pedido = $this->pedido();
        $pedido->merge([
            'balanca_config_id' => 1, 'peso_origem' => 'balanca',
            'balanca_evidence_json' => '{"balanca":{"id":1},"peso":{"valor":23}}',
        ]);
        $resposta = $this->controller->save($pedido);
        $this->assertSame(422, $resposta->status());
        $this->assertStringContainsString('possui câmera vinculada', $resposta->getData()->error);
        $this->assertSame(0, TicketPesagem::count());
    }

    public function test_falha_gravando_registro_reverte_arquivos_e_ticket(): void
    {
        DB::table('config_notas')->update(['pesagem_permitir_foto_dispositivo' => true]);
        PesagemTicketImagem::creating(function () {
            throw new \RuntimeException('Falha de gravação simulada.');
        });
        $resposta = $this->controller->save($this->pedido());
        $this->assertSame(500, $resposta->status());
        $this->assertSame(0, TicketPesagem::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }
}
