<?php

namespace Tests\Unit;

use App\Http\Controllers\PagamentoEletronicoController;
use App\Models\{PagamentoCobranca, PagamentoConexao, Usuario};
use App\Services\Pagamentos\{Cobrancas, Estabelecimento, ImportarExtrato};
use Argws\ElectronicPayments\Asaas\Connection;
use Argws\ElectronicPayments\PaymentMethod;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Facade};
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\{PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class PagamentosEletronicosTest extends TestCase
{
    private MockHandler $http;
    private Cobrancas $service;
    private PagamentoConexao $c;
    private Estabelecimento $site;

    protected function setUp(): void
    {
        $app = new Application(dirname(__DIR__, 2));
        Facade::setFacadeApplication($app);
        $app->instance('config', new Repository([
            'app' => ['key' => 'base64:'.base64_encode(random_bytes(32)), 'cipher' => 'AES-256-CBC'],
            'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:']]],
        ]));
        $app->instance('request', Request::create('https://erp.test'));
        $app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);
        $app->register(\Illuminate\View\ViewServiceProvider::class);
        $app->register(\Illuminate\Session\SessionServiceProvider::class);
        $app['config']->set('view.paths', []);
        $app['config']->set('session.driver', 'array');
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.null', ['driver' => 'monolog', 'handler' => \Monolog\Handler\NullHandler::class]);
        if (!class_exists('Log')) {
            class_alias(\Illuminate\Support\Facades\Log::class, 'Log');
        }
        $app->register(DatabaseServiceProvider::class);
        $app->register(EncryptionServiceProvider::class);
        $app->boot();
        foreach (glob($app->databasePath('migrations/2026_11_26_15000[0-7]_*.php')) as $migration) {
            (require $migration)->up();
        }
        DB::statement('CREATE TABLE config_notas (empresa_id INTEGER, ambiente INTEGER)');
        DB::table('config_notas')->insert(['empresa_id' => 1, 'ambiente' => 1]);
        DB::statement('CREATE TABLE conta_recebers (id INTEGER PRIMARY KEY, empresa_id INTEGER, filial_id INTEGER, cliente_id INTEGER, valor_integral NUMERIC, valor_recebido NUMERIC DEFAULT 0, status INTEGER DEFAULT 0, data_vencimento TEXT, tipo_pagamento TEXT, data_recebimento TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE boletos (id INTEGER PRIMARY KEY, conta_id INTEGER)');
        DB::statement('CREATE TABLE clientes (id INTEGER PRIMARY KEY, empresa_id INTEGER, razao_social TEXT, cpf_cnpj TEXT, email TEXT)');
        DB::table('conta_recebers')->insert(['id' => 3, 'empresa_id' => 1, 'cliente_id' => 5, 'valor_integral' => 10, 'data_vencimento' => '2026-11-27']);
        DB::table('clientes')->insert(['id' => 5, 'empresa_id' => 1, 'razao_social' => 'Pagador de teste', 'cpf_cnpj' => '00000000000']);
        $usuario = new Usuario;
        $usuario->id = 7;
        $this->site = new Estabelecimento(1, 0, $usuario);
        \App\Models\PagamentoProvedor::create(['empresa_id' => 1, 'local' => 0, 'provedor' => 'asaas']);
        $this->c = PagamentoConexao::create(['empresa_id' => 1, 'local' => 0, 'provedor' => 'asaas', 'ambiente' => 'production',
            'carteira' => 'teste', 'webhook_id' => (string) Str::uuid(), 'credencial' => 'credencial-ficticia',
            'segredo' => str_repeat('s', 32), 'selecionada' => true]);
        $this->http = new MockHandler;
        $this->service = new class($this->http) extends Cobrancas {
            public function __construct(private MockHandler $http) {}
            public function connection(PagamentoConexao $c): Connection
            {
                return new Connection($c->account(), fn () => Estabelecimento::fiscal($c->empresa_id, $c->local),
                    fn () => $c->credencial, $this->http->__invoke(...));
            }
        };
    }

    public function test_cache_antigo_oculta_pagamentos_em_telas_nativas_mesmo_com_conexao_ativa(): void
    {
        config(['pagamentos_eletronicos.provedores' => null,
            'view.paths' => [resource_path('views')], 'view.compiled' => sys_get_temp_dir()]);
        self::assertFalse(Estabelecimento::integracaoInstalada());
        self::assertNull(Estabelecimento::disponivel());
        foreach ([0, 8] as $local) {
            self::assertSame('', trim(view('pagamentos.parametros.estabelecimento', ['localPagamento' => $local])->render()));
        }
        self::assertSame('', trim(view('pagamentos.pdv')->render()));
    }

    public function test_rotas_da_integracao_aguardam_a_ultima_migration(): void
    {
        config(['pagamentos_eletronicos' => require config_path('pagamentos_eletronicos.php')]);
        $middleware = new \App\Http\Middleware\VerificaInstalacaoPagamentos;
        self::assertTrue($middleware->handle(request(), fn () => true));

        (require database_path('migrations/2026_11_26_150007_parametrizar_formas_de_pagamento.php'))->down();
        $resposta = $middleware->handle(request(), fn () => self::fail('Integração liberada durante a atualização.'));
        self::assertSame(503, $resposta->getStatusCode());
    }

    public function test_erp_carrega_uma_unica_sdk_asaas_interna_ao_pacote_de_pagamentos(): void
    {
        $vendor = dirname(__DIR__, 2).'/vendor';
        $sdk = 'argws/asaas-sdk-php';
        $esperado = $vendor.'/argws/electronic-payments/vendor/'.$sdk.'/src/AsaasSdk.php';

        self::assertDirectoryDoesNotExist($vendor.'/'.$sdk);
        self::assertFileExists($esperado);
        self::assertSame(realpath($esperado), (new \ReflectionClass(\Asaas\Sdk\AsaasSdk::class))->getFileName());
    }

    private function cobranca(?int $titulo = 3): PagamentoCobranca
    {
        return PagamentoCobranca::create(['conexao_id' => $this->c->id, 'referencia' => (string) Str::uuid(),
            'origem' => $titulo ? 'titulo-3' : 'pdv-7-teste', 'vigente' => true, 'usuario_id' => 7, 'cliente_id' => 5,
            'conta_receber_id' => $titulo, 'centavos' => 1000, 'externo' => 'pay_teste', 'metodo' => 'pix']);
    }

    private function resposta(array $dados): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($dados, JSON_THROW_ON_ERROR));
    }

    private function pagamento(PagamentoCobranca $p): array
    {
        return ['id' => 'pay_teste', 'value' => 10, 'externalReference' => $p->referencia,
            'status' => 'RECEIVED', 'paymentDate' => '2026-11-24'];
    }

    public function test_webhook_repetido_baixa_uma_vez_com_data_do_asaas(): void
    {
        $p = $this->cobranca();
        $remote = $this->pagamento($p);
        $this->http->append($this->resposta($remote), $this->resposta(['data' => [$remote], 'hasMore' => false]));
        $api = $this->service->provider($this->c);
        $event = $api->webhook(json_encode(['id' => 'evt_1', 'event' => 'PAYMENT_RECEIVED', 'payment' => $remote]), str_repeat('s', 32), $this->c->segredo);
        self::assertTrue($this->service->evento($p, $event));
        self::assertTrue($this->service->evento($p, $event));
        self::assertSame(1, DB::table('pagamento_eventos')->count());
        self::assertSame('2026-11-24', DB::table('conta_recebers')->value('data_recebimento'));
        self::assertSame('credit_available', $p->fresh()->conciliacao);
    }

    public function test_token_invalido_e_recusado_antes_da_consulta(): void
    {
        $request = Request::create('/', 'POST', [], [], [], ['HTTP_ASAAS_ACCESS_TOKEN' => 'incorreto'], '{}');
        self::assertSame(403, (new PagamentoEletronicoController($this->service))->webhook($request, $this->c->webhook_id)->getStatusCode());
        self::assertSame(0, DB::table('pagamento_eventos')->count());
    }

    public function test_mudanca_do_ambiente_fiscal_bloqueia_credencial_de_producao(): void
    {
        DB::table('config_notas')->update(['ambiente' => 2]);
        self::assertNotSame($this->c->credencial, DB::table('pagamento_conexoes')->value('credencial'));
        $this->expectException(\DomainException::class);
        $this->service->sincronizar($this->cobranca());
    }

    public function test_sandbox_nao_baixa_titulo_nem_importa_extrato(): void
    {
        $this->c->update(['ambiente' => 'sandbox']);
        $p = $this->cobranca();
        $this->http->append($this->resposta($this->pagamento($p)));
        $this->service->sincronizar($p);
        self::assertSame('simulation', $p->fresh()->conciliacao);
        self::assertEquals(0, DB::table('conta_recebers')->value('status'));
        $this->expectException(HttpException::class);
        (new ImportarExtrato($this->service))->executar($this->site, $this->c, '2026-11-01', '2026-11-10');
    }

    public function test_divergencia_de_valor_segue_para_conferencia(): void
    {
        $p = $this->cobranca();
        $this->http->append($this->resposta(array_replace($this->pagamento($p), ['value' => 11])));
        $this->service->sincronizar($p);
        self::assertSame('review', $p->fresh()->conciliacao);
        self::assertEquals(0, DB::table('conta_recebers')->value('status'));
    }

    public function test_reenviar_titulo_reservado_reutiliza_a_mesma_cobranca(): void
    {
        $p = $this->cobranca();
        $existente = $this->service->emitir($this->site, PaymentMethod::Pix, 3, $this->c->webhook_id);
        self::assertSame($p->referencia, $existente->referencia);
        self::assertSame(1, PagamentoCobranca::count());
    }

    public function test_titulo_de_outra_filial_nao_e_visivel_na_matriz(): void
    {
        DB::table('conta_recebers')->update(['filial_id' => 8]);
        self::assertFalse($this->service->titulo($this->c, 3)->exists());
    }

    public function test_emissao_do_pix_usa_referencia_do_erp_e_retorna_id_externo(): void
    {
        $this->http->append($this->resposta(['data' => []]), $this->resposta(['id' => 'cus_teste']),
            function ($request) {
                $payload = json_decode((string) $request->getBody(), true);
                self::assertSame('PIX', $payload['billingType']);
                self::assertSame('cus_teste', $payload['customer']);
                self::assertEquals(10, $payload['value']);
                return $this->resposta($payload + ['id' => 'pay_novo', 'status' => 'PENDING']);
            });
        $p = $this->service->emitir($this->site, PaymentMethod::Pix, 3, $this->c->webhook_id);
        self::assertSame('pay_novo', $p->fresh()->externo);
        self::assertEquals(0, DB::table('conta_recebers')->value('status'));
    }

    public function test_reimportar_extrato_preserva_a_mesma_tarifa_pendente(): void
    {
        // A importação usa a mesma tabela da conciliação OFX.
        DB::statement('CREATE TABLE conta_empresas (id INTEGER PRIMARY KEY, empresa_id INTEGER, filial_id INTEGER, status INTEGER)');
        DB::statement('CREATE TABLE bank_statement_transactions (id INTEGER PRIMARY KEY, empresa_id INTEGER, filial_id INTEGER, usuario_id INTEGER, conta_bancaria_id INTEGER, numero_documento TEXT, import_hash TEXT UNIQUE, data_transacao TEXT, valor NUMERIC, tipo TEXT, status TEXT, descricao TEXT, created_at TEXT, updated_at TEXT)');
        DB::table('conta_empresas')->insert(['id' => 9, 'empresa_id' => 1, 'status' => 1]);
        $linha = ['id' => 'tr_1', 'date' => '2026-11-20', 'value' => -1.25, 'type' => 'PAYMENT_FEE'];
        $this->http->append($this->resposta(['data' => [$linha], 'hasMore' => false]), $this->resposta(['data' => [$linha], 'hasMore' => false]));
        $this->c->update(['conta_bancaria_id' => 9]);
        $extrato = new ImportarExtrato($this->service);
        self::assertSame(1, $extrato->executar($this->site, $this->c, '2026-11-01', '2026-11-25'));
        self::assertSame(0, $extrato->executar($this->site, $this->c, '2026-11-01', '2026-11-25'));
        self::assertSame('debit', DB::table('bank_statement_transactions')->value('tipo'));
    }

    public function test_pdv_confere_carrinho_e_impede_reutilizar_pagamento(): void
    {
        $itens = [['id' => 2, 'quantidade' => 1, 'valor' => 10]];
        $p = $this->cobranca(null);
        $p->update(['carrinho_hash' => $this->service->assinaturaCarrinho($itens)]);
        $this->http->append($this->resposta($this->pagamento($p)));
        $venda = ['pagamento_eletronico' => $p->referencia, 'cliente' => 5, 'tipo_pagamento' => '17', 'itens' => $itens];
        $reserva = $this->service->reservarPdv($this->site, $venda, 10);
        self::assertSame($p->id, $reserva->id);
        $reserva->update(['venda_caixa_id' => 99]);
        $this->expectException(HttpException::class);
        $this->service->reservarPdv($this->site, $venda, 10);
    }

    public function test_pdv_misto_confirma_somente_a_parcela_eletronica(): void
    {
        $itens = [['id' => 2, 'quantidade' => 1, 'valor' => 100]];
        $p = $this->cobranca(null);
        $p->update(['centavos' => 7000, 'parcela_pdv' => 1, 'carrinho_hash' => $this->service->assinaturaCarrinho($itens)]);
        $this->http->append($this->resposta(array_replace($this->pagamento($p), ['value' => 70])));
        $venda = ['pagamento_eletronico' => $p->referencia, 'cliente' => 5, 'itens' => $itens,
            'pag_multi' => [['tipo' => '01', 'valor' => '30,00'], ['tipo' => '17', 'valor' => '70,00']]];
        self::assertSame($p->id, $this->service->reservarPdv($this->site, $venda, 100)->id);
        $venda['pag_multi'][0]['valor'] = '29,00';
        $this->expectException(HttpException::class);
        $this->service->reservarPdv($this->site, $venda, 100);
    }

    public function test_pdv_recusa_venda_ainda_pendente(): void
    {
        $p = $this->cobranca(null);
        $this->http->append($this->resposta(array_replace($this->pagamento($p), ['status' => 'PENDING'])));
        $this->expectException(HttpException::class);
        $this->service->reservarPdv($this->site, ['pagamento_eletronico' => $p->referencia, 'cliente' => 5,
            'tipo_pagamento' => '17'], 10.0);
    }

    public function test_configuracao_usa_a_filial_do_cadastro_e_respeita_a_empresa_e_as_permissoes(): void
    {
        DB::statement('CREATE TABLE usuarios (id INTEGER PRIMARY KEY, empresa_id INTEGER, adm INTEGER, locais TEXT, permissao TEXT, local_padrao INTEGER)');
        DB::statement('CREATE TABLE filials (id INTEGER PRIMARY KEY, empresa_id INTEGER, status INTEGER, ambiente INTEGER)');
        DB::table('usuarios')->insert(['id' => 7, 'empresa_id' => 1, 'adm' => 1, 'local_padrao' => -1,
            'locais' => '[-1,8,9]', 'permissao' => '["/configNF","/filial"]']);
        DB::table('filials')->insert([['id' => 8, 'empresa_id' => 1, 'status' => 1, 'ambiente' => 2],
            ['id' => 9, 'empresa_id' => 2, 'status' => 1, 'ambiente' => 1]]);
        session(['user_logged' => ['empresa' => 1, 'id' => 7, 'super' => false]]);
        self::assertSame(0, Estabelecimento::paraParametros(0)->local);
        $filial = Estabelecimento::paraParametros(8);
        self::assertSame(8, $filial->local);
        self::assertNull(Estabelecimento::paraParametros(9));
        DB::table('usuarios')->update(['permissao' => '["/configNF"]']);
        self::assertNull(Estabelecimento::paraParametros(8));

        $cadastro = $filial->provedores()->create(['empresa_id' => 1, 'local' => 8, 'provedor' => 'asaas']);
        $parametros = new \App\Services\Pagamentos\ParametrosAsaas($this->service);
        try {
            $parametros->salvar($filial, $cadastro, Request::create('/', 'PUT', ['ambiente' => 'production',
                'credencial' => 'chave-de-teste', 'segredo' => str_repeat('s', 32), 'ativar' => 1]));
            self::fail('Produção aceita para uma filial em homologação.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
        }
        self::assertCount(0, $filial->conexoes()->get());
        self::assertTrue($this->c->fresh()->selecionada);
    }

    public function test_provedor_tem_um_cadastro_e_credenciais_independentes_por_ambiente(): void
    {
        $dados = ['empresa_id' => 1, 'local' => 0, 'provedor' => 'asaas'];
        $cadastro = $this->site->provedores()->firstOrCreate($dados);
        self::assertCount(1, $this->site->provedores()->get());
        $parametros = new \App\Services\Pagamentos\ParametrosAsaas($this->service);
        $this->http->append($this->resposta(['data' => [['id' => 'carteira-sandbox']], 'hasMore' => false]));
        $parametros->salvar($this->site, $cadastro, Request::create('/', 'PUT', ['ambiente' => 'sandbox',
            'credencial' => 'credencial-sandbox', 'segredo' => str_repeat('h', 32), 'ativar' => 1]));
        $sandbox = $this->site->ativa();
        self::assertSame('sandbox', $sandbox->ambiente);
        self::assertSame('credencial-sandbox', $sandbox->credencial);
        self::assertNotSame('credencial-sandbox', $sandbox->getRawOriginal('credencial'));
        self::assertNull($this->c->fresh()->selecionada);
        $parametros->salvar($this->site, $cadastro, Request::create('/', 'PUT', ['ambiente' => 'production', 'ativar' => 1]));
        self::assertSame($this->c->id, $this->site->ativa()->id);
        self::assertNull($sandbox->fresh()->selecionada);
        self::assertSame($this->c->webhook_id, $this->site->ativa()->webhook_id);
    }

    public function test_evento_de_uma_conexao_nao_autentica_com_token_de_outra(): void
    {
        $outra = $this->c->replicate(['webhook_id', 'carteira', 'selecionada']);
        $outra->ambiente = 'sandbox';
        $outra->webhook_id = (string) Str::uuid();
        $outra->carteira = 'sandbox';
        $outra->segredo = str_repeat('t', 32);
        $outra->save();
        $token = str_repeat('s', 32);
        $request = Request::create('/', 'POST', [], [], [], ['HTTP_ASAAS_ACCESS_TOKEN' => $token], '{"event":"TRANSFER_DONE"}');
        $controller = new PagamentoEletronicoController($this->service);
        self::assertSame(403, $controller->webhook($request, $outra->webhook_id)->getStatusCode());
        self::assertSame(204, $controller->webhook($request, $this->c->webhook_id)->getStatusCode());
        self::assertNotSame($token, $this->c->getRawOriginal('segredo'));
        self::assertSame(hash('sha256', $token), $this->c->webhook_hash);
        self::assertArrayNotHasKey('webhook_hash', $this->c->toArray());
    }

    public function test_permissoes_de_extrato_saldo_csv_e_importacao_sao_individuais(): void
    {
        $acesso = \App\Services\Pagamentos\AcessoFinanceiro::class;
        self::assertSame([], $acesso::doUsuario($this->site));
        DB::table('pagamento_acessos')->insert(['provedor_id' => $this->site->provedores()->value('id'),
            'usuario_id' => $this->site->usuario->id, 'recursos' => '["statement","exportar"]']);
        $direitos = $acesso::doUsuario($this->site);
        self::assertContains('statement', $direitos);
        self::assertNotContains('importar', $direitos);
        self::assertFalse($acesso::permiteCsv('statement', $direitos));
        self::assertTrue($acesso::permiteCsv('statement', [...$direitos, 'balance']));
        self::assertSame([], $acesso::doUsuario(new Estabelecimento(1, 4, $this->site->usuario)));
        self::assertSame([], $acesso::doUsuario(new Estabelecimento(2, 0, $this->site->usuario)));
        $this->site->usuario->id = 99;
        self::assertSame([], $acesso::doUsuario($this->site));
    }

    public function test_forma_desligada_e_limite_de_parcelas_sao_validados_no_servidor(): void
    {
        $this->c->update(['metodos' => ['credit_card'], 'max_parcelas_cartao' => 3]);
        foreach ([[PaymentMethod::Pix, 1, 403], [PaymentMethod::CreditCard, 4, 422]] as [$metodo, $quantidade, $esperado]) {
            try {
                $this->service->emitir($this->site, $metodo, 3, $this->c->webhook_id, numeroParcelas: $quantidade);
                self::fail('A emissão deveria ser recusada.');
            } catch (HttpException $e) {
                self::assertSame($esperado, $e->getStatusCode());
            }
        }
        self::assertSame(0, PagamentoCobranca::count());
    }

    public function test_cartao_parcelado_confere_grupo_e_baixa_apenas_apos_confirmacao_do_total(): void
    {
        $p = $this->cobranca();
        $p->update(['metodo' => 'credit_card', 'numero_parcelas' => 2, 'parcelamento_externo' => 'inst_1']);
        $linha = $this->pagamento($p);
        $linha['value'] = 5;
        $linha += ['installment' => 'inst_1', 'billingType' => 'CREDIT_CARD'];
        $segunda = array_merge($linha, ['id' => 'pay_2', 'status' => 'PENDING']);
        $this->http->append($this->resposta($linha), $this->resposta(['data' => [$linha, $segunda], 'hasMore' => false]));
        $this->service->sincronizar($p, 'pay_2');
        self::assertSame(0, DB::table('conta_recebers')->value('status'));
        $segunda['status'] = 'CONFIRMED';
        $this->http->append($this->resposta($linha), $this->resposta(['data' => [$linha, $segunda], 'hasMore' => false]));
        $this->service->sincronizar($p, 'pay_2');
        self::assertSame(1, DB::table('conta_recebers')->value('status'));
        self::assertSame(10, DB::table('conta_recebers')->value('valor_recebido'));
    }

    public function test_migracao_reagrupa_credenciais_existentes_preservando_webhook_e_chave(): void
    {
        $conexoes = require database_path('migrations/2026_11_26_150004_vincular_conexoes_a_provedores.php');
        $provedores = require database_path('migrations/2026_11_26_150003_create_pagamento_provedores_table.php');
        (require database_path('migrations/2026_11_26_150006_create_pagamento_acessos_table.php'))->down();
        $hashes = require database_path('migrations/2026_11_26_150005_add_webhook_hash_to_pagamento_conexoes.php');
        $hashes->down();
        $conexoes->down();
        $provedores->down();
        $antiga = (array) DB::table('pagamento_conexoes')->first();
        $sandbox = $antiga;
        unset($sandbox['id']);
        $sandbox['webhook_id'] = (string) Str::uuid();
        $sandbox['ambiente'] = 'sandbox';
        $sandbox['selecionada'] = null;
        DB::table('pagamento_conexoes')->insert($sandbox);
        $provedores->up();
        $conexoes->up();
        $hashes->up();
        self::assertSame(2, $this->site->conexoes()->whereNull('webhook_hash')->count());
        self::assertSame(1, $this->site->provedores()->count());
        self::assertSame(2, $this->site->conexoes()->count());
        self::assertSame($antiga['credencial'], $this->site->ativa()->getRawOriginal('credencial'));
        self::assertSame($antiga['webhook_id'], $this->site->ativa()->webhook_id);
    }
}
