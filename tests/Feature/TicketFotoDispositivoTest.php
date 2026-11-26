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
            '*create_pesagem_ticket_imagens_table', '*add_snapshot_storage_and_multi_destinations_to_config_notas',
            '*add_pesagem_permitir_foto_dispositivo_to_config_notas'] as $nome) {
            (require glob(database_path('migrations/'.$nome.'.php'))[0])->up();
        }
        Schema::table('tickets_pesagem', function (Blueprint $table) {
            $table->string('peso_origem')->nullable();
            $table->integer('producao_lote_id')->nullable();
        });
        Storage::fake(PesagemTicketImagem::DISCO_DISPOSITIVO);
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
        $foto = $ticket->fotosDispositivo()->firstOrFail();
        $this->assertSame(7, $foto->metadata_json['usuario_id']);
        $this->assertSame('Lacre', $foto->metadata_json['legenda']);
        Storage::disk('fotos_ticket')->assertExists($foto->arquivo_path);
        $this->assertSame(200, $this->controller->save($pedido)->status());
        $this->assertSame(1, TicketPesagem::count());
        $this->assertSame(1, $ticket->fotosDispositivo()->count());

        $edicao = $this->pedido();
        $edicao->merge(['edicao_confirmada' => true, 'token' => $ticket->token]);
        $this->assertSame(200, $this->controller->update($edicao, $ticket->id)->status());
        $this->assertSame(200, $this->controller->update($edicao, $ticket->id)->status());
        $this->assertSame(2, $ticket->fotosDispositivo()->count());

        PesagemTicketImagem::create(['empresa_id' => 1, 'ticket_pesagem_id' => $ticket->id, 'camera_uuid' => 'legada']);
        $this->assertSame(1, $ticket->imagens()->count());
        $servico = app(PesagemTicketImagemService::class);
        $servico->persistirDoTicket($ticket, [['image_url' => 'https://camera.example/imagem.jpg']]);
        $this->assertSame(1, $ticket->imagens()->count());
        $this->assertSame('https://camera.example/imagem.jpg', $ticket->imagens()->first()->arquivo_url);
        $this->assertSame(2, $ticket->fotosDispositivo()->count());
        $galeria = $this->controller->fotosDaPesagem(1)->getData(true);
        $this->assertSame('Lacre', $galeria[$ticket->id][0]['legenda']);
        $this->assertSame(200, $this->controller->fotoDispositivo($ticket->id, $foto->id)->getStatusCode());
        session(['user_logged.local_padrao' => 9]);
        $this->assertEmpty($this->controller->fotosDaPesagem(1)->getData(true));
        $servico->excluirDoTicket($ticket);
        Storage::disk('fotos_ticket')->assertMissing($foto->arquivo_path);
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
        $this->assertEmpty(Storage::disk('fotos_ticket')->allFiles());
    }
}
