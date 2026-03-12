<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use App\Models\EmpresaLogada;
use App\Models\ItemCompra;
use App\Models\Produto;
use App\Models\Usuario;
use App\Models\VideoAjuda;
use App\Models\UsuarioAcesso;
use App\Models\DeliveryConfig;
use App\Models\Empresa;
use App\Models\PlanoEmpresa;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\Estoque;
use App\Models\IfoodConfig;
use App\Models\ConfigCatraca;
use App\Models\ConfigSystem;
use App\Models\PedidoEcommerce;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use App\Helpers\Menu;
use Illuminate\Pagination\Paginator;
use \DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use App\Helpers\EncryptionHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Utils\WhatsAppUtil;
use App\Services\AppVersionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

        /**
         * Register WhatsAppUtil services.
         */
        //$this->app->singleton(WhatsAppUtil::class, function ($app) {
        //    return new WhatsAppUtil();
        //});

        $this->app->singleton(\App\Utils\WhatsAppUtil::class, function($app) {
            // o $app->make injeta o EvoApiService corretamente
            return new \App\Utils\WhatsAppUtil(
                $app->make(\App\Services\EvoApiService::class)
            );
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        EncryptionHelper::initialize();

        /*
        $encryptionKey = env('ENCRYPTION_KEY');
        try {
            // Depuração: exibe o valor da chave antes e depois da decodificação
            Log::info('Chave ENCRYPTION_KEY inicial:', ['chave_base64' => $encryptionKey]);

            // Decodifica a chave
            $decodedKey = base64_decode($encryptionKey, true);

            // Depuração: Verifica o tamanho da chave decodificada
            if (!$decodedKey) {
                throw new \Exception('Falha na decodificação da chave ENCRYPTION_KEY.');
            }

            $keyLength = strlen($decodedKey);
            Log::info('Tamanho da chave decodificada:', ['tamanho' => $keyLength]);

            if ($keyLength !== 32) {
                throw new \Exception('A chave ENCRYPTION_KEY deve ser uma string base64 válida com 32 bytes.');
            }

            // Inicializa o EncryptionHelper com a chave válida
            EncryptionHelper::initialize($encryptionKey);
            Log::info('EncryptionHelper inicializado com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao inicializar EncryptionHelper: ' . $e->getMessage());
            dd('Erro ao inicializar EncryptionHelper:', $e->getMessage());
        }
        */

        // Forçar leitura do .env para debug
        //dd(config('app.env'), config('database.connections.mysql.database'));

        view()->composer('*',function($view){

            $menu = new Menu();

            // print_r($menu->getMenu());

            // die();

            $tema = null;
            $tema_menu = null;
            $tipo_menu = 'lateral';
            $video_url = $this->getVideoUrl();

            // $video_url = "";

            $ultimoAcesso = null;
            $value = session('user_logged');
            $empresa_id = null;
            $upgrade = false;
            $casasDecimais = 2;
            $casasDecimaisQtd = 2;
            $armazenamento = 0;
            $totalParaArmazenar = 0;
            $percentualArmazenamento = 0;
            $logo = "";
            $alerta_sonoro = "";
            if($value){
                $empresa_id = $value['empresa'];
                $usuario = Usuario::find($value['id']);
                if($usuario == null){
                    return redirect('/login/logoff');
                }
                $tema = $usuario->tema;
                $tema_menu = $usuario->tema_menu;
                if($usuario->tipo_menu != ''){
                    $tipo_menu = $usuario->tipo_menu;
                }
                $empresa = Empresa::find($empresa_id);

                if($empresa->configNota){
                    $alerta_sonoro = $empresa->configNota->alerta_sonoro;
                    $casasDecimais = $empresa->configNota->casas_decimais;
                    $casasDecimaisQtd = $empresa->configNota->casas_decimais_qtd;

                    if($empresa->configNota->logo != ""){
                        $logo = $empresa->configNota->logo;
                    }
                }

                if($value['super']){
                    $contrato = 1;
                }else{
                    $upgrade = $this->getUpgrade($empresa);

                    if($empresa->contrato){
                        $contrato = $empresa->contrato->status;

                    }else{
                        $contrato = 1;
                    }

                    //verifica plano do tipo armazenamento
                    if($empresa->planoEmpresa->plano->armazenamento > 0){
                        $armazenamento = number_format($this->totalArmazenamento($empresa)/1000, 2);
                        $totalParaArmazenar = $empresa->planoEmpresa->plano->armazenamento;

                        $percentualArmazenamento = 100-(($totalParaArmazenar-$armazenamento)/$totalParaArmazenar*100);

                    }
                }

            }else{
                $contrato = 1;
            }

            $alertas = [];
            $semValidade = $this->verificaItensSemValidade($empresa_id);
            if($semValidade) {
                array_push($alertas,
                    [
                        'msg' => 'Existe itens em estoque sem cadastro de data de validade!',
                        'titulo' => 'Alerta validade',
                        'link' => '/compras/produtosSemValidade'
                    ]
                );
            }

            $alertaValidade = $this->verificaValidadeProdutos($empresa_id);
            if($alertaValidade) {
                array_push($alertas,
                    [
                        'msg' => 'Existe Produtos com validade próxima!',
                        'titulo' => 'Validade próxima',
                        'link' => '/compras/validadeAlerta'
                    ]
                );
            }

            $somaContas = $this->verificaContasPagar($empresa_id);
            if($somaContas > 0) {
                $dataHoje = date('d/m/Y', strtotime("-". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));
                $dataFutura = date('d/m/Y', strtotime("+". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));
                array_push($alertas,
                    [
                        'msg' => 'Contas a pagar R$'.number_format($somaContas, 2),
                        'titulo' => 'Alerta contas',
                        'link' => '/contasPagar/filtro?fornecedor=&data_inicial='.$dataHoje.'&data_final='.$dataFutura.'&status=todos'
                    ]
                );
            }


            $somaContas = $this->verificaContasReceber($empresa_id);
            if($somaContas > 0) {
                $dataHoje = date('d/m/Y', strtotime("-". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));
                $dataFutura = date('d/m/Y', strtotime("+". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));
                array_push($alertas,
                    [
                        'msg' => 'Contas a receber R$'.number_format($somaContas, 2),
                        'titulo' => 'Receber',
                        'link' => '/contasReceber/filtro?cliente=&data_inicial='.$dataHoje.'&data_final='.$dataFutura.'&status=todos'
                    ]
                );
            }

            if (\Schema::hasTable('produtos')){

                // $produtos = Produto::
                // where('empresa_id', $empresa_id)
                // ->get();

                // $contDesfalque = 0;
                // foreach($produtos as $p){
                //     if($p->estoque_minimo > 0){
                //         $estoque = Estoque::
                //         where('produto_id', $p->id)
                //         ->where('empresa_id', $empresa_id)
                //         ->first();
                //         $temp = null;
                //         if($estoque == null){
                //             $contDesfalque++;
                //         }else{
                //             $contDesfalque++;
                //         }
                //     }
                // }

                // if($contDesfalque > 0){
                //     array_push($alertas,
                //         [
                //             'msg' => 'Produtos com estoque minimo: ' . $contDesfalque,
                //             'titulo' => 'Alerta estoque',
                //             'link' => '/relatorios/filtroEstoqueMinimo'
                //         ]
                //     );
                // }

            }

            $countPedidos = $this->verificaPedidosEcommerce($empresa_id);

            if($countPedidos > 0) {

                array_push($alertas,
                    [
                        'msg' => 'Pedidos novos: ' . $countPedidos,
                        'titulo' => 'Pedido Ecommerce',
                        'link' => '/pedidosEcommerce/filtro?cliente=&data_inicial=&data_final=&estado=0'
                    ]
                );
            }

            $rotaAtiva = $this->rotaAtiva();

            $ultimoAcesso = null;

            if($value){
                $usuario = Usuario::find($value['id']);

                if($usuario){
                    $ultimoAcesso = $usuario->ultimoAcesso();
                }
            }

            $uri = $_SERVER['REQUEST_URI'];
            $uri = explode("/", $uri);
            $uri = "/".$uri[1];

            $deliveryConfig = DeliveryConfig::where('empresa_id', $empresa_id)->first();
            if($deliveryConfig != null){
                if(!$deliveryConfig->notificacao_novo_pedido){
                    $deliveryConfig = null;
                }
            }

            $ifoodConfig = null;
            try{
                $ifoodConfig = IfoodConfig::where('empresa_id', $empresa_id)->first();
            }catch(\Exception $e){}

            $config = null;
            try{
                $config = ConfigSystem::first();
            }catch(\Exception $e){}

            $cor = '';
            if($config != null){
                $cor = $config->cor;
            }

            if($tema == null){
                $tema = 1;
            }
            if($tema_menu == null){
                $tema_menu = 1;
            }

            $appVersionCurrent = null;
            $appVersionHistory = collect();
            $appVersionHistoryGrouped = collect();

            if (Schema::hasTable('app_versions')) {
                $appVersionService = app(AppVersionService::class);
                $installedVersion = $appVersionService->resolveInstalledVersion();
                $installedRevision = $appVersionService->resolveInstalledRevision();

                $currentDbVersion = $appVersionService->current();

                $needsSync = $currentDbVersion === null
                    || $currentDbVersion->version !== $installedVersion
                    || (
                        !empty($installedRevision)
                        && empty(trim((string) optional($currentDbVersion)->commit_hash))
                    );

                if ($needsSync) {
                    $appVersionService->syncFromManifest();
                    $appVersionService->syncFromArtifacts($installedVersion);
                }

                $appVersionCurrent = $appVersionService->currentOrFallback();

                if ($appVersionCurrent && empty(trim((string) $appVersionCurrent->commit_hash)) && !empty($installedRevision)) {
                    $appVersionCurrent->commit_hash = $installedRevision;
                }

                $appVersionHistory = $appVersionService->history();
                $appVersionHistoryGrouped = $appVersionService->groupedHistoryByMajor();
            }

            $configCatraca = null;

            try{
                $configCatraca = ConfigCatraca::where('empresa_id', $empresa_id)
                    ->where('usuario_id', $value['id'])
                    ->first();

            }catch(\Exception $e){
            }

            $view->with('alertas', $alertas);
            $view->with('rotaAtiva', $rotaAtiva);
            $view->with('menu', $menu->preparaMenu());
            $view->with('tema', $tema);
            $view->with('configCatraca', $configCatraca);
            $view->with('deliveryConfig', $deliveryConfig != null);
            $view->with('ifoodConfig', $ifoodConfig != null);
            $view->with('tema_menu', $tema_menu);
            $view->with('logo', $logo);
            $view->with('alerta_sonoro', $alerta_sonoro);
            $view->with('tipoMenu', $tipo_menu);
            $view->with('armazenamento', $armazenamento);
            $view->with('totalParaArmazenar', $totalParaArmazenar);
            $view->with('percentualArmazenamento', $percentualArmazenamento);
            $view->with('video_url', $video_url);
            $view->with('ultimoAcesso', $ultimoAcesso);
            $view->with('upgrade', $upgrade);
            $view->with('uri', $uri);
            $view->with('cor', $cor);
            $view->with('casasDecimais', $casasDecimais);
            $view->with('casasDecimaisQtd', $casasDecimaisQtd);
            $view->with('contrato', $contrato);
            $view->with('appVersionCurrent', $appVersionCurrent);
            $view->with('appVersionHistory', $appVersionHistory);
            $view->with('appVersionHistoryGrouped', $appVersionHistoryGrouped);
        });

    }

// private function registerScheduleCommands(){
//     // file_put_contents("aaaa.txt", "e ai bro");

//     $this->app->booted(function () {
//         $schedule = $this->app->make(Schedule::class);
//         $schedule->call(function () {
//             file_put_contents("aaaa.txt", "e ai bro");
//             $this->getUsersLogin();
//         })->everyMinute();
//     });

// }

    private function getUsersLogin(){
        $empresas = Empresa::
        orderBy('id', 'desc')
            ->where('status', 1)
            ->get();

        $total = 0;
        $minutos = env("MINUTOS_ONLINE");

        foreach($empresas as $e){
            $ult = $e->ultimoLogin2($e->id);
            if($ult != null){
                $strValidade = strtotime($ult->updated_at);
                $strHoje = strtotime(date('Y-m-d H:i:s'));
                $dif = $strHoje - $strValidade;
                $dif = $dif/60;

                if((int) $dif <= $minutos && $e->usuarios[0]->login != env("USERMASTER")){
                    $total++;
                }
            }
        }

        EmpresaLogada::create(['total' => $total]);
    }

    private function getUpgrade($empresa){
        $empresa = Empresa::find($empresa->id);

        $expiracao = $empresa->planoEmpresa->expiracao;

        $alertaDias = env('ALERTA_PAGAMENTO_DIAS');

        $strValidade = strtotime($expiracao);
        $strHoje = strtotime(date('Y-m-d'));
        $dif = $strValidade - $strHoje;
        $dif = $dif/24/60/60;

        if($dif <= $alertaDias) return true;
        return false;
    }

    private function getVideoUrl(){
        if (url()->full()){
            $url = url()->full();
            try{

                $video = VideoAjuda::where('url_sistema', $url)->first();
                if($video == null) return "";
                return $video->url_video;
            }catch(\Exception $e){
                return "";
            }
        }
    }

    private function rotaAtiva(){
        if (isset($_SERVER['REQUEST_URI'])){
            $uri = $_SERVER['REQUEST_URI'];
            $uri = explode("/", $uri);
            $uri = $uri[1];

            $rotaSuper = [
                'empresas', 'planos', 'ibpt', 'contrato', 'financeiro', 'cidades', 'representantes',
                'online', 'etiquetas', 'relatorioSuper', 'ticketsSuper', 'cidadeDelivery',
                'categoriaMasterDelivery', 'produtosDestaque', 'planosPendentes', 'pesquisa', 'alertas',
                'errosLog', 'config', 'appUpdate'
            ];

            $rotaDeCadastros = [
                'categorias', 'produtos', 'clientes', 'fornecedores', 'transportadoras', 'categoriasServico', 'servicos',
                'categoriasConta', 'veiculos', 'usuarios', 'marcas', 'contaBancaria', 'acessores', 'gruposCliente',
                'listaDePrecos', 'formasPagamento', 'taxas-pagamento'
            ];

            $rotaDeEntradas = [
                'compraFiscal', 'compraManual', 'compras', 'cotacao', 'dfe'
            ];

            $rotaDeGestaoPessoal = [
                'funcionarios', 'eventosFuncionario', 'funcionarioEventos', 'apuracaoMensal'
            ];

            $rotaDeEstoque = [
                'estoque', 'inventario', 'transferencia'
            ];

            $rotaFinanceiro = [
                'contasPagar', 'contasReceber', 'fluxoCaixa', 'graficos', 'contas-empresa', 'plano-contas'
            ];

            $rotaConfig = [
                'configNF', 'escritorio', 'naturezaOperacao', 'tributos', 'enviarXml', 'tickets', 'configEmail',
                'filial'
            ];

            $rotaPedidos = [
                'pedidos', 'deliveryComplemento', 'telasPedido', 'controleCozinha', 'mesas'
            ];

            $rotaVenda = [
                'caixa', 'vendas', 'frenteCaixa', 'orcamentoVenda', 'ordemServico', 'vendasEmCredito', 'devolucao',
                'agendamentos', 'trocas', 'nferemessa'
            ];

            $rotaBalcao = [
                'vendas-balcao'
            ];

            $rotaCTe = [
                'cte', 'categoriaDespesa'
            ];

            $rotaCTeOs = [
                'cteos'
            ];

            $rotaMDFe = [
                'mdfe'
            ];

            $rotaEvento = [
                'eventos'
            ];

            $rotaLocacao = [
                'locacao'
            ];

            $rotaRelatorio = [
                'relatorios',
                'dre'
            ];

            $rotaNfse = [
                'nfse',
                'nfse-config'
            ];

            $rotaEcommerce = [
                'categoriaEcommerce', 'produtoEcommerce', 'configEcommerce',
                'carrosselEcommerce', 'pedidosEcommerce', 'autorPost', 'categoriaPosts',
                'postBlog', 'contatoEcommerce', 'clienteEcommerce', 'informativoEcommerce',
                'cuponsEcommerce'
            ];

            $rotaNuvemShop = [
                'nuvemshop'
            ];

            $rotaIfood = [
                'ifood'
            ];

            $rotaDelivery = [
                'deliveryCategoria', 'configDelivery', 'deliveryProduto', 'deliveryComplemento',
                'funcionamentoDelivery', 'push', 'tamanhosPizza', 'clientesDelivery', 'categoriaDeLoja',
                'pedidosDelivery', 'bairrosDeliveryLoja', 'codigoDesconto', 'carrosselDelivery',
                'motoboys', 'pedidosMesa', 'mesas'
            ];

            if(in_array($uri, $rotaSuper)) return 'SUPER';
            if(in_array($uri, $rotaDeCadastros)) return 'Cadastros';
            if(in_array($uri, $rotaDeEntradas)) return 'Entradas';
            if(in_array($uri, $rotaDeGestaoPessoal)) return 'Gestão Pessoal';
            if(in_array($uri, $rotaDeEstoque)) return 'Estoque';
            if(in_array($uri, $rotaFinanceiro)) return 'Financeiro';
            if(in_array($uri, $rotaConfig)) return 'Configurações';
            if(in_array($uri, $rotaVenda)) return 'Vendas';
            if(in_array($uri, $rotaCTe)) return 'CTe';
            if(in_array($uri, $rotaBalcao)) return 'Vendas Balcão';
            if(in_array($uri, $rotaCTeOs)) return 'CTe Os';
            if(in_array($uri, $rotaMDFe)) return 'MDFe';
            if(in_array($uri, $rotaEvento)) return 'Eventos';
            if(in_array($uri, $rotaRelatorio)) return 'Relatórios';
            if(in_array($uri, $rotaLocacao)) return 'Locação';
            if(in_array($uri, $rotaPedidos)) return 'Pedidos';
            if(in_array($uri, $rotaEcommerce)) return 'Ecommerce';
            if(in_array($uri, $rotaNfse)) return 'Emissão de NFSe';
            if(in_array($uri, $rotaNuvemShop)) return 'Nuvem Shop';
            if(in_array($uri, $rotaIfood)) return 'iFood';
            if(in_array($uri, $rotaDelivery)) return 'Delivery';

        }else{
            return "";
        }
    }

    private function verificaItensSemValidade($empresa_id){
        if (\Schema::hasTable('produtos')){
            $produtos = Produto::select('id')
                ->where('alerta_vencimento', '>', 0)
                ->where('empresa_id', $empresa_id)
                ->get();

            $itensCompra = ItemCompra::
            select('item_compras.*')
                ->where('validade', NULL)
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->where('compras.empresa_id', $empresa_id)
                ->limit(100)->get();

            foreach($itensCompra as $i){
                foreach($produtos as $p){
                    if($p->id == $i->produto_id){
                        return true;
                    }
                }
            }
            return false;
        }
    }

    private function verificaValidadeProdutos($empresa_id){
        if (\Schema::hasTable('item_compras')){

            $dataHoje = date('Y-m-d', strtotime("-30 days",strtotime(date('Y-m-d'))));
            $dataFutura = date('Y-m-d', strtotime("+30 days",strtotime(date('Y-m-d'))));

            $itens = ItemCompra::
            select('item_compras.*')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->whereBetween('validade', [$dataHoje, $dataFutura])
                ->where('compras.empresa_id', $empresa_id)
                ->limit(300)->get();


            foreach($itens as $i){
                $strValidade = strtotime($i->validade);
                $strHoje = strtotime(date('Y-m-d'));
                $dif = $strValidade - $strHoje;
                $dif = $dif/24/60/60;
                if($dif <= $i->produto->alerta_vencimento) return true;
            }

            return false;
        }
    }

    private function verificaContasPagar($empresa_id){

        if (\Schema::hasTable('conta_pagars')){
            $dataHoje = date('Y-m-d', strtotime("-". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));
            $dataFutura = date('Y-m-d', strtotime("+". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));

            $somaContas = ContaPagar::
            selectRaw('sum(valor_integral) as valor')
                ->whereBetween('data_vencimento', [$dataHoje, $dataFutura])
                ->where('status', 0)
                ->where('empresa_id', $empresa_id)
                ->first();

            return $somaContas->valor ?? 0;
        }
    }

    private function verificaContasReceber($empresa_id){
        if (\Schema::hasTable('conta_recebers')){
            $dataHoje = date('Y-m-d', strtotime("-". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));
            $dataFutura = date('Y-m-d', strtotime("+". env('ALERTA_CONTAS_DIAS') ." days",strtotime(date('Y-m-d'))));

            $somaContas = ContaReceber::
            selectRaw('sum(valor_integral) as valor')
                ->whereBetween('data_vencimento', [$dataHoje, $dataFutura])
                ->where('status', 0)
                ->where('empresa_id', $empresa_id)
                ->first();

            return $somaContas->valor ?? 0;
        }
    }

    private function verificaPedidosEcommerce($empresa_id){
        if (\Schema::hasTable('pedido_ecommerces')){

            $pedidos = PedidoEcommerce::
            where('status_preparacao', 0)
                ->where('valor_total', '>', 0)
                ->where('empresa_id', $empresa_id)
                ->get();

            return sizeof($pedidos);
        }
        return 0;
    }

    private function totalArmazenamento($empresa){
        $armazenamento = $empresa->planoEmpresa->plano->armazenamento;
        $tabelasArmazenamento = tabelasArmazenamento();

        $soma = 0;
        foreach($tabelasArmazenamento as $key => $t){
            try{
                $res = DB::table($key)
                    ->select(DB::raw('count(*) as linhas'))
                    ->where('empresa_id', $empresa->id)
                    ->first();

                $soma += $res->linhas * $t;
            }catch(\Exception $e){

            }
        }

        return $soma;
    }


}
