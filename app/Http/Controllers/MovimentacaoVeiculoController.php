<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoVeiculo;
use App\Models\Veiculo;
use App\Models\Funcionario;
use App\Models\TipoMovimentacao;
use App\Services\ConnectApi\ConnectApiMessageService;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\AbastecimentoMovimentacao;
use App\Models\DespesaMovimentacao;
use App\Helpers\StockMove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Models\Usuario;
use App\Support\AuditContext;


class MovimentacaoVeiculoController extends BaseController
{
    use \App\Traits\GeocodeTrait;

    protected $model = MovimentacaoVeiculo::class;
    protected $resource = 'movimentacoes_veiculos';
    protected $table = 'movimentacoes_veiculos';
    protected $formTitle = 'Movimentações de Veículos';
    protected $listView = 'movimentacoes_veiculos.list';
    protected $registerView = 'movimentacoes_veiculos.register';
    protected $redirectPage = '/movimentacaoVeiculo';

    public function __construct()
    {
        // Libera chamadas via Cron/Artisan (Console) e a tela externa do motorista sem exigir sessão web
        if (app()->runningInConsole() || request()->is('coleta-motorista*')) {
            return;
        }

        parent::__construct();
    }

    /* --- Métodos Obrigatórios do BaseController --- */
    public function rules(): array { return []; }
    public function messages(): array { return []; }


    /**
     * Aplica filtro por filial considerando a coluna 'locais' da tabela de usuários.
     * Centralizado no BaseController para proteger TODAS as telas do ERP.
     */
    protected function applyFilialFilter($query, string $column = 'filial_id')
    {
        $usuarioId = session('user_logged')['id'] ?? session('user_logged')['usuario_id'] ?? null;
        if (!$usuarioId) return $query;

        $usuarioObj = \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $usuarioId)->first();
        if (!$usuarioObj || empty($usuarioObj->locais)) {
            return $query;
        }

        $locaisPermitidos = json_decode($usuarioObj->locais, true);
        if (!is_array($locaisPermitidos)) {
            $locaisPermitidos = ['-1'];
        }

        $filtroFilialId = request('filial_id');

        // Acesso total (Admin / Matriz)
        if (in_array('-1', $locaisPermitidos, true) || in_array(-1, $locaisPermitidos, true)) {
            if ($filtroFilialId !== null && $filtroFilialId !== '') {
                if ($filtroFilialId == '0') {
                    $query->where(function($q) use ($column) {
                        $q->whereNull($column)->orWhere($column, 0);
                    }); // 0 = Matriz
                } else {
                    $query->where($column, $filtroFilialId);
                }
            }
            return $query;
        }

        // Restrição rigorosa às filiais permitidas
        $locaisValidos = array_filter($locaisPermitidos, function($val) {
            return $val !== null && $val !== '';
        });

        $query->where(function ($q) use ($locaisValidos, $column) {
            $q->whereIn($column, $locaisValidos);
            // Permite matriz se o ID 0 estiver na lista
            if (in_array('0', $locaisValidos, true) || in_array(0, $locaisValidos, true)) {
                $q->orWhereNull($column);
            }
        });

        // Se o utilizador tentou filtrar na tela, valida a permissão
        if ($filtroFilialId !== null && $filtroFilialId !== '') {
            if (in_array((string)$filtroFilialId, $locaisValidos, true) || in_array((int)$filtroFilialId, $locaisValidos, true)) {
                if ($filtroFilialId == '0') {
                    $query->where(function($q) use ($column) {
                        $q->whereNull($column)->orWhere($column, 0);
                    });
                } else {
                    $query->where($column, $filtroFilialId);
                }
            } else {
                $query->where($column, -1); // Bloqueia a consulta se tentou burlar
            }
        }

        return $query;
    }

    protected function getTenantRecords()
    {
        return $this->applyFilialFilter(parent::getTenantRecords())
            ->with(['veiculo', 'motorista', 'tipoMovimentacao', 'cliente', 'fornecedor', 'abastecimentos', 'destinos.cliente', 'destinos.fornecedor'])
            ->orderByDesc('data_hora_saida');
    }

    protected function headers(): array
    {
        return [
            'Veículo', 'Motorista', 'Saída', 'Chegada',
            'Status', 'KM Ini.', 'KM Fim',
            'Total Abastec.',
            'Destino', 'Tipo',
            'Cliente / Fornecedor',
            'Docs Fiscais'
        ];
    }

    protected function fields(): array
    {
        return [
            'veiculo.placa', 'motorista.nome', 'data_saida_formatada', 'data_chegada_formatada',
            'status_formatado', 'km_inicial_formatado', 'km_final_formatado',
            'valor_total_abastecimentos_formatado',
            'destino', 'tipoMovimentacao.nome', 'entidade_nome',
            'documentos_fiscais_formatado'
        ];
    }

    public function index(Request $request)
    {
        $query = $this->model::where('empresa_id', $this->empresa_id);

        // 1. Proteção de Segurança pelo BaseController
        $query = $this->applyFilialFilter($query);

        // 2. Busca as filiais permitidas para exibir as opções no Filtro
        $user_logged = session('user_logged');
        $usuarioObj = \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $user_logged['id'] ?? $user_logged['usuario_id'] ?? 0)->first();

        $locaisPermitidos = $usuarioObj && !empty($usuarioObj->locais) ? json_decode($usuarioObj->locais, true) : ['-1'];
        if (!is_array($locaisPermitidos)) {
            $locaisPermitidos = ['-1'];
        }

        $filiaisQuery = \Illuminate\Support\Facades\DB::table('filials');
        if (\Illuminate\Support\Facades\Schema::hasColumn('filials', 'empresa_id')) {
            $filiaisQuery->where('empresa_id', $this->empresa_id);
        }
        if (!in_array('-1', $locaisPermitidos) && !in_array(-1, $locaisPermitidos)) {
            $filiaisQuery->whereIn('id', $locaisPermitidos);
        }
        $listaFiliais = $filiaisQuery->get();

        // 3. Aplicação dos demais Filtros da tela
        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }
        if ($request->filled('motorista_id')) {
            $query->where('motorista_id', $request->motorista_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_hora_saida', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_hora_saida', '<=', $request->data_fim);
        }

        // 4. Executa a consulta ordenando pelos mais recentes no topo e limitando a 20 por página
        $records = $query->with([
            'veiculo',
            'motorista',
            'tipoMovimentacao',
            'cliente',
            'fornecedor',
            'destinos.cliente',
            'destinos.fornecedor'
        ])
            ->orderByDesc('id')
            ->paginate(20);

        $records->appends($request->all());
        $links = $records->links();

        $filters = [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os Veículos',
                'options' => Veiculo::where('empresa_id', $this->empresa_id)->get()->map(function($v) {
                    return ['value' => $v->id, 'label' => $v->placa];
                })->toArray()
            ],
            [
                'name' => 'motorista_id',
                'label' => 'Motorista',
                'type' => 'select',
                'placeholder' => 'Todos os Motoristas',
                'options' => \App\Models\Funcionario::where('empresa_id', $this->empresa_id)->get()->map(function($f) {
                    return ['value' => $f->id, 'label' => $f->nome];
                })->toArray()
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'placeholder' => 'Todos os Status',
                'options' => [
                    ['value' => 'agendado', 'label' => 'Agendado'],
                    ['value' => 'iniciado', 'label' => 'Iniciado'],
                    ['value' => 'finalizado', 'label' => 'Finalizado'],
                    ['value' => 'cancelado', 'label' => 'Cancelado']
                ]
            ],
            ['name' => 'data_inicio', 'label' => 'Data Início', 'type' => 'date'],
            ['name' => 'data_fim', 'label' => 'Data Fim', 'type' => 'date'],
        ];

        return view($this->listView, [
            'records' => $records,
            'links' => $links,
            'listaFiliais' => $listaFiliais,
            'podeReabrirAgenda' => $this->podeReabrirAgendamento(),
            'filters' => $filters,
            'title' => $this->formTitle,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'filterUrl' => url($this->redirectPage),
            'newItemUrl' => url("{$this->redirectPage}/new"),
            'newItemText' => 'Nova Movimentação',
            'editUrl' => url("{$this->redirectPage}/edit"),
            'deleteUrl' => url("{$this->redirectPage}/delete"),
        ]);
    }

    /**
     * Segue o mesmo modelo OTP usado pela reabertura de pesagens.
     * A exigência de permissão é explícita porque ValidaAcesso ignora AJAX.
     */
    private function podeReabrirAgendamento(): bool
    {
        $usuario = $this->usuario_id ? Usuario::find($this->usuario_id) : null;
        if (!$usuario || !$usuario->hasOtp()) {
            return false;
        }

        $sessao = session('user_logged', []);
        if (!empty($sessao['super']) || !empty($sessao['adm']) || !empty($sessao['is_admin'])) {
            return true;
        }

        $permissoes = json_decode((string) $usuario->permissao, true);
        return is_array($permissoes) && (
                in_array('/movimentacaoVeiculo', $permissoes, true)
                || in_array('/movimentacaoVeiculo/agenda', $permissoes, true)
            );
    }

    public function list(Request $request)
    {
        view()->share('podeReabrirAgenda', $this->podeReabrirAgendamento());
        return parent::list($request);
    }

    /**
     * Reabre somente um agendamento finalizado, sem apagar dados operacionais.
     * Diferente de recriar a viagem, preservamos horários, km, destinos,
     * pesagens e vínculos para permitir ajustes posteriores com rastreabilidade.
     */
    public function reabrir(Request $request, $id)
    {
        if (!$this->podeReabrirAgendamento()) {
            return response()->json([
                'error' => 'Somente usuários autorizados com OTP configurado podem reabrir agendamentos.'
            ], 403);
        }

        // Mesmo contrato de entrada (campo "code") e mesma verificação das pesagens.
        $this->validateOtp($request);

        return DB::transaction(function () use ($id) {
            $movimentacao = $this->applyFilialFilter(
                MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
            )->lockForUpdate()->findOrFail($id);

            if (!in_array($movimentacao->status, ['finalizado', 'concluida'], true)) {
                return response()->json([
                    'error' => 'Somente agendamentos finalizados podem ser reabertos.'
                ], 422);
            }

            $antes = $movimentacao->getAttributes();

            // O status volta a permitir edição, sem destruir o histórico de viagem.
            AuditContext::withoutModelAudit(function () use ($movimentacao) {
                $movimentacao->update(['status' => 'iniciado']);
            });

            $this->logService->registrar('reabrir', MovimentacaoVeiculo::class, [
                'registro_id'  => $movimentacao->id,
                'dados_antes'  => $antes,
                'dados_depois' => $movimentacao->getAttributes(),
            ]);

            return response()->json([
                'success' => 'Agendamento reaberto com sucesso.',
                'status'  => 'iniciado',
            ]);
        });
    }

    public function edit($id)
    {
        $movimentacao = $this->applyFilialFilter(
            $this->model::where('empresa_id', $this->empresa_id)
        )->findOrFail($id);
        if (in_array($movimentacao->status, ['finalizado', 'concluida'], true)) {
            session()->flash('mensagem_erro', 'Esta movimentação já foi concluída e não pode ser alterada.');
            return redirect($this->redirectPage);
        }
        return $this->register($id);
    }

    public function register($id = null)
    {
        $data = $id ? $this->applyFilialFilter(
            $this->model::with(['abastecimentos.produto', 'despesas', 'destinos'])
                ->where('empresa_id', $this->empresa_id)
        )->findOrFail($id) : null;

        // =========================================================================
        // INÍCIO DO NOVO BLOCO (Passo 2) - Intercepta os dados da solicitação de coleta
        // =========================================================================
        if (request()->has('coleta_id') && !$data) {
            $coleta = \App\Models\SolicitacaoColeta::with('pessoa')->find(request('coleta_id'));
            if ($coleta) {
                // Cria um objeto temporário apenas para preencher o formulário na tela
                $data = new $this->model();
                $data->observacao = "Gerado via Solicitação de Coleta #{$coleta->id} - Material: " . $coleta->material;

                // Busca o cliente ERP correspondente pelo CPF/CNPJ
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $coleta->pessoa->cpf_cnpj ?? '');
                $clienteERP = \App\Models\Cliente::where('empresa_id', $this->empresa_id)
                    ->whereRaw("REGEXP_REPLACE(cpf_cnpj, '[^0-9]', '') = ?", [$cnpjLimpo])
                    ->first();

                // Monta o destino sugerido (será lido na view para montar a primeira parada)
                $endereco = trim(($coleta->pessoa->rua_coleta ?? '') . ', ' . ($coleta->pessoa->numero_coleta ?? ''));
                $data->destino_sugerido_id = $clienteERP ? $clienteERP->id : null;
                $data->destino_sugerido_endereco = $endereco;
                $data->destino_sugerido_peso = $coleta->peso_estimado ?? '';
                $data->destino_sugerido_produto_id = $coleta->produto_residuo_id ?? null;
                $data->destino_sugerido_caixa_id = $coleta->produto_cacamba_id ?? null;
                $data->coleta_origem_id = $coleta->id; // Guarda o ID para atualizar o status depois
            }
        }
        // =========================================================================
        // FIM DO NOVO BLOCO
        // =========================================================================

        $combustiveis = Produto::where('empresa_id', $this->empresa_id)
            ->where(function($q) {
                $q->where('nome', 'LIKE', '%DIESEL%')
                    ->orWhere('nome', 'LIKE', '%ARLA%')
                    ->orWhere('nome', 'LIKE', '%GASOLINA%');
            })->get();

        // 1. RESÍDUOS: Apenas produtos cuja categoria seja de Resíduo / Descarte / Entulho
        $produtosResiduos = Produto::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->whereHas('categoria', function($q) {
                $q->where('nome', 'LIKE', '%Resíduo%')
                    ->orWhere('nome', 'LIKE', '%Residuo%')
                    ->orWhere('nome', 'LIKE', '%Descarte%')
                    ->orWhere('nome', 'LIKE', '%Entulho%')
                    ->orWhere('nome', 'LIKE', '%Coleta%');
            })
            ->orderBy('nome')
            ->get();

        // 2. CAÇAMBAS / MÁQUINAS: Produtos marcados com opção de Locação (valor_locacao > 0)
        $caixas = Produto::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->where('valor_locacao', '>', 0)
            ->orderBy('nome')
            ->get();

        // Busca as pesagens de SAÍDA que já estão finalizadas (peso líquido apurado)
        // e que ainda não foram vinculadas a nenhuma viagem
        $pesagensDisponiveis = \App\Models\Pesagem::where('empresa_id', $this->empresa_id)
            ->where(function($query) {
                $query->where('tipo', 'LIKE', '%vend%')
                    ->orWhereHas('tickets', function($q) {
                        $q->where('tipo', 'saida');
                    });
            })
            ->where('status', 'LIKE', '%conclu%')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        // Filtra apenas funcionários com função de Motorista ativo (mantendo o atual se for edição)
        // Busca os IDs das funções que contêm "Motorista" na tabela funcoes da empresa
        $funcoesMotoristaIds = DB::table('funcoes')
            ->where('empresa_id', $this->empresa_id)
            ->whereRaw('LOWER(nome) LIKE ?', ['%motorista%'])
            ->pluck('id')
            ->toArray();

        // Filtra apenas funcionários vinculados à função de Motorista (mantendo o atual se for edição)
        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)
            ->where(function ($q) use ($funcoesMotoristaIds, $data) {
                if (!empty($funcoesMotoristaIds)) {
                    $q->whereIn('funcao_id', $funcoesMotoristaIds);
                } else {
                    $q->whereRaw('1 = 0'); // Não traz ninguém se não houver função Motorista cadastrada
                }

                if ($data && !empty($data->motorista_id)) {
                    $q->orWhere('id', $data->motorista_id);
                }
            })
            ->orderBy('nome')
            ->get();

        return view($this->registerView, [
            'data'                => $data,
            'title'               => $this->formTitle,
            'actionSave'          => "{$this->redirectPage}/save",
            'actionUpdate'        => "{$this->redirectPage}/save",
            'actionCancel'        => $this->redirectPage,
            'veiculos'            => Veiculo::where('empresa_id', $this->empresa_id)->get(),
            'funcionarios'        => Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get(),
            'motoristas'          => $motoristas,
            'tipos'               => TipoMovimentacao::where('empresa_id', $this->empresa_id)->get(),
            'clientes'            => Cliente::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get(),
            'fornecedores'        => Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get(),
            'combustiveis'        => $combustiveis,
            'produtosResiduos'    => $produtosResiduos,
            'caixas'              => $caixas,
            'pesagensDisponiveis' => $pesagensDisponiveis,
        ]);
    }

    public function save(Request $request)
    {
        $user_logged = session('user_logged');
        $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'];
        $empresa_id = $this->empresa_id;

        // 🛑 BLOQUEIO OPERACIONAL
        if ($request->filled('veiculo_id') && class_exists(\App\Models\VeiculoAlertaManutencao::class)) {
            $bloqueioAtivo = \App\Models\VeiculoAlertaManutencao::where('veiculo_id', $request->veiculo_id)
                ->where('impedir_circulacao', 1)
                ->where('fase', '!=', 'resolvido')
                ->first();

            if ($bloqueioAtivo) {
                session()->flash('mensagem_erro', "🚫 BLOQUEIO DE FROTA: O veículo selecionado está impedido de circular. Motivo: {$bloqueioAtivo->titulo} (Status: " . strtoupper(str_replace('_', ' ', $bloqueioAtivo->fase)) . ").");
                return redirect()->back()->withInput();
            }
        }

        $filial_id = $request->filial_id ?? ($user_logged['filial_id'] ?? null);
        if ($filial_id <= 0) $filial_id = null;

        try {
            return DB::transaction(function () use ($request, $usuario_id, $empresa_id, $filial_id) {

                // Bloqueia atualização direta de viagem finalizada: só o POST /reabrir
                // com OTP pode alterar o status para permitir uma nova edição.
                $registroExistente = null;
                if ($request->filled('id') && (int) $request->id > 0) {
                    $registroExistente = $this->applyFilialFilter(
                        MovimentacaoVeiculo::where('empresa_id', $empresa_id)
                    )->lockForUpdate()->findOrFail($request->id);

                    if (in_array($registroExistente->status, ['finalizado', 'concluida'], true)) {
                        session()->flash('mensagem_erro', 'A movimentação está finalizada. Utilize Reabrir com OTP antes de alterá-la.');
                        return redirect()->back()->withInput();
                    }
                }

                $dados = $request->all();
                // Grava a opção da balança (se o utilizador marcou a caixa, guarda 1, senão guarda 0)
                $dados['passar_balanca'] = $request->has('passar_balanca') ? 1 : 0;

                $dados['empresa_id'] = $empresa_id;
                $dados['filial_id'] = $filial_id;
                $dados['solicitar_abastecimento'] = $request->solicitar_abastecimento ?? 0;

                // 🚀 DADOS DO TERCEIRO (FROTA EXTERNA)
                $dados['is_terceirizado'] = $request->has('is_terceirizado') ? 1 : 0;

                if ($dados['is_terceirizado']) {
                    $dados['veiculo_id'] = null; // Terceiro não usa placa fixa do sistema
                    $dados['motorista_id'] = null; // Terceiro não é funcionário
                    $dados['terceiro_nome'] = $request->terceiro_nome;
                    $dados['terceiro_documento'] = preg_replace('/[^0-9]/', '', $request->terceiro_documento);
                    $dados['terceiro_telefone'] = preg_replace('/[^0-9]/', '', $request->terceiro_telefone);
                } else {
                    $dados['terceiro_nome'] = null;
                    $dados['terceiro_documento'] = null;
                    $dados['terceiro_telefone'] = null;
                }

                /// 1. Definição do Status (Corrigido para não iniciar agendamento futuro só porque tem KM inicial)
                if ($request->filled('status')) {
                    $dados['status'] = $request->status;
                } else {
                    if ($request->filled('km_final')) {
                        $dados['status'] = 'finalizado';
                    } elseif (!empty($request->data_hora_saida_real)) {
                        $dados['status'] = 'iniciado';
                    } else {
                        $dados['status'] = 'agendado';
                    }
                }

                $dados['gerar_mtr']   = $request->gerar_mtr ?? 0;
                $dados['contrato_id'] = $request->contrato_id ?: null;

                // EFEITO CASCATA DE KM
                if (empty($request->id) && empty($request->filled('km_inicial')) && $request->filled('veiculo_id')) {
                    $ultimaMovimentacao = MovimentacaoVeiculo::where('empresa_id', $empresa_id)
                        ->where('veiculo_id', $request->veiculo_id)
                        ->whereIn('status', ['finalizado', 'iniciado'])
                        ->orderBy('data_hora_saida', 'desc')
                        ->first();

                    if ($ultimaMovimentacao && $ultimaMovimentacao->km_final > 0) {
                        $dados['km_inicial'] = $ultimaMovimentacao->km_final;
                    } else {
                        $veiculoObj = Veiculo::find($request->veiculo_id);
                        $dados['km_inicial'] = $veiculoObj->quilometragem ?? 0;
                    }
                }

                $dados['pesagem_id'] = $request->pesagem_id ?: null;

                $dados['data_hora_chegada_cliente'] = $request->data_hora_chegada_cliente ?? null;
                $dados['data_hora_saida_cliente']   = $request->data_hora_saida_cliente ?? null;

                // =========================================================================
                // 🚀 TRANSIÇÃO PARA MULTI-STOP (Múltiplos Destinos)
                // =========================================================================
                if ($request->has('destinos') && is_array($request->destinos)) {
                    $arrayDestinos = $request->destinos;
                    $primeiroDestino = reset($arrayDestinos);

                    if ($primeiroDestino && (!empty($primeiroDestino['entidade_id']) || !empty($primeiroDestino['endereco_completo']))) {

                        $entidade = $primeiroDestino['entidade_id'] ?? '';
                        if (str_starts_with($entidade, 'cliente_')) {
                            $dados['cliente_id'] = str_replace('cliente_', '', $entidade);
                            $dados['fornecedor_id'] = null;
                        } elseif (str_starts_with($entidade, 'fornecedor_')) {
                            $dados['fornecedor_id'] = str_replace('fornecedor_', '', $entidade);
                            $dados['cliente_id'] = null;
                        } else {
                            $dados['cliente_id'] = null;
                            $dados['fornecedor_id'] = null;
                        }

                        $dados['destino'] = $primeiroDestino['endereco_completo'] ?? null;
                        $dados['latitude_destino'] = $primeiroDestino['latitude'] ?? null;
                        $dados['longitude_destino'] = $primeiroDestino['longitude'] ?? null;
                    }
                }

                if ($request->id > 0) {
                    $registro = $registroExistente;
                    $registro->update($dados);
                    $registro->abastecimentos()->delete();
                    $registro->despesas()->delete();
                    $registro->destinos()->delete(); // Limpa a rota antiga para recriar
                } else {
                    $registro = $this->model::create($dados);
                }

                // GRAVAÇÃO DOS AJUDANTES
                /*
                if ($request->has('ajudantes_ids')) {
                    $registro->ajudantes()->sync($request->ajudantes_ids);
                } else {
                    $registro->ajudantes()->detach();
                }
                */

                // GRAVAÇÃO DOS AJUDANTES
                // Preserva o relacionamento e informa a empresa na tabela pivot.
                if ($request->has('ajudantes_ids')) {
                    $registro->ajudantes()->syncWithPivotValues(
                        $request->ajudantes_ids,
                        ['empresa_id' => $empresa_id]
                    );
                } else {
                    $registro->ajudantes()->detach();
                }

                // =========================================================================
                // 🚀 GRAVAÇÃO DA ROTA (MÚLTIPLOS DESTINOS)
                // =========================================================================
                if ($request->has('destinos')) {
                    $ordem = 1;
                    foreach ($request->destinos as $destino) {
                        if (!empty($destino['entidade_id']) || !empty($destino['endereco_completo'])) {

                            $clienteId = null;
                            $fornecedorId = null;
                            $entidade = $destino['entidade_id'] ?? '';

                            if (str_starts_with($entidade, 'cliente_')) {
                                $clienteId = str_replace('cliente_', '', $entidade);
                            } elseif (str_starts_with($entidade, 'fornecedor_')) {
                                $fornecedorId = str_replace('fornecedor_', '', $entidade);
                            }

                            $latDest = $destino['latitude'] ?? null;
                            $lonDest = $destino['longitude'] ?? null;

                            // Se não veio coordenada da tela, busca automático do cadastro do Cliente ou Fornecedor
                            if ((empty($latDest) || empty($lonDest)) && $clienteId) {
                                $cliObj = Cliente::find($clienteId);
                                $latDest = $cliObj->latitude ?? null;
                                $lonDest = $cliObj->longitude ?? null;
                            } elseif ((empty($latDest) || empty($lonDest)) && $fornecedorId) {
                                $fornObj = Fornecedor::find($fornecedorId);
                                $latDest = $fornObj->latitude ?? null;
                                $lonDest = $fornObj->longitude ?? null;
                            }

                            // 1. Cria o destino principal na tabela pai
                            $destinoCriado = $registro->destinos()->create([
                                'cliente_id'          => $clienteId,
                                'fornecedor_id'       => $fornecedorId,
                                'is_aterro'           => isset($destino['is_aterro']) ? 1 : 0,
                                'cobrar_frete'        => isset($destino['cobrar_frete']) ? (int)$destino['cobrar_frete'] : 1,
                                'tipo_operacao_caixa' => $destino['tipo_operacao_caixa'] ?? 'nenhuma',
                                'caixa_deixada_id'    => !empty($destino['caixa_deixada_id']) ? $destino['caixa_deixada_id'] : null,
                                'caixa_retirada_id'   => !empty($destino['caixa_retirada_id']) ? $destino['caixa_retirada_id'] : null,
                                'endereco_completo'   => $destino['endereco_completo'] ?? null,
                                'latitude'            => $latDest,
                                'longitude'           => $lonDest,
                                'ordem'               => $ordem,
                                'status'              => $destino['status'] ?? 'pendente',
                                'data_hora_chegada'   => $destino['data_hora_chegada'] ?? null,
                                'data_hora_saida'     => $destino['data_hora_saida'] ?? null,
                            ]);

                            // 2. 🚀 GRAVA NA TABELA FILHA (movimentacao_destino_itens)
                            // Se a viagem estiver vinculada a uma pesagem, importa TODOS os itens originais da balança
                            if (!empty($request->pesagem_id)) {
                                $ticketsPesagem = \DB::table('tickets_pesagem')->where('pesagem_id', $request->pesagem_id)->get();

                                foreach ($ticketsPesagem as $tp) {
                                    if (!empty($tp->produto_id)) {
                                        \DB::table('movimentacao_destino_itens')->insert([
                                            'movimentacao_veiculo_id' => $registro->id,
                                            'movimentacao_destino_id' => $destinoCriado->id,
                                            'produto_id'              => $tp->produto_id,
                                            'quantidade'              => (float)($tp->peso ?? 0),
                                            'unidade'                 => 'KG',
                                            'observacao'              => 'Importado 100% via Balança',
                                            'created_at'              => now(),
                                            'updated_at'              => now(),
                                        ]);
                                    }
                                }
                            }
                            // Caso contrário, grava o apontamento manual feito na tela (Agora suporta múltiplos)
                            else {
                                // Se a tela enviou a nova lista de múltiplos resíduos
                                if (isset($destino['residuos']) && is_array($destino['residuos'])) {
                                    foreach ($destino['residuos'] as $res) {
                                        if (!empty($res['produto_id']) || !empty($res['quantidade'])) {
                                            $qtdLimpa = !empty($res['quantidade'])
                                                ? str_replace(',', '.', str_replace('.', '', $res['quantidade']))
                                                : 0;

                                            \DB::table('movimentacao_destino_itens')->insert([
                                                'movimentacao_veiculo_id' => $registro->id,
                                                'movimentacao_destino_id' => $destinoCriado->id,
                                                'produto_id'              => $res['produto_id'] ?? null,
                                                'quantidade'              => (float)$qtdLimpa,
                                                'unidade'                 => 'KG',
                                                'observacao'              => 'Apontado Manualmente na Agenda',
                                                'created_at'              => now(),
                                                'updated_at'              => now(),
                                            ]);
                                        }
                                    }
                                }
                                // Fallback/Segurança caso venha no formato antigo
                                elseif (!empty($destino['produto_residuo_id']) || !empty($destino['quantidade_residuo'])) {
                                    $qtdLimpa = !empty($destino['quantidade_residuo'])
                                        ? str_replace(',', '.', str_replace('.', '', $destino['quantidade_residuo']))
                                        : 0;

                                    \DB::table('movimentacao_destino_itens')->insert([
                                        'movimentacao_veiculo_id' => $registro->id,
                                        'movimentacao_destino_id' => $destinoCriado->id,
                                        'produto_id'              => $destino['produto_residuo_id'] ?? null,
                                        'quantidade'              => (float)$qtdLimpa,
                                        'unidade'                 => 'KG',
                                        'observacao'              => 'Apontado Manualmente na Agenda',
                                        'created_at'              => now(),
                                        'updated_at'              => now(),
                                    ]);
                                }
                            }

                            // Se o gestor já agendou deixando ou retirando caçamba física:
                            if (!empty($destinoCriado->caixa_deixada_id)) {
                                DB::table('produtos')
                                    ->where('id', $destinoCriado->caixa_deixada_id)
                                    ->update([
                                        'status_localizacao' => 'cliente',
                                        'cliente_atual_id'   => $clienteId,
                                        'endereco_atual'     => $destinoCriado->endereco_completo,
                                        'latitude_atual'     => $latDest,
                                        'longitude_atual'    => $lonDest,
                                        'updated_at'         => now()
                                    ]);
                            }

                            if (!empty($destinoCriado->caixa_retirada_id)) {
                                DB::table('produtos')
                                    ->where('id', $destinoCriado->caixa_retirada_id)
                                    ->update([
                                        'status_localizacao' => 'base',
                                        'cliente_atual_id'   => null,
                                        'endereco_atual'     => null,
                                        'latitude_atual'     => null,
                                        'longitude_atual'    => null,
                                        'updated_at'         => now()
                                    ]);

                            }
                            $ordem++;
                        }
                    }
                }

                // GRAVAÇÃO DOS ABASTECIMENTOS
                if ($request->has('abastecimentos')) {
                    foreach ($request->abastecimentos as $item) {
                        if (!empty($item['produto_id']) && $item['quantidade'] > 0) {
                            $qtd = str_replace(',', '.', $item['quantidade']);
                            $unit = str_replace(',', '.', $item['unitario']);
                            $total = str_replace(',', '.', $item['total']);
                            $kmAbast = str_replace(',', '.', $item['km_abastecimento'] ?? 0);

                            AbastecimentoMovimentacao::create([
                                'empresa_id'         => $empresa_id,
                                'usuario_id'         => $usuario_id,
                                'filial_id'          => $filial_id,
                                'movimentacao_id'    => $registro->id,
                                'produto_id'         => $item['produto_id'],
                                'tipo'               => $item['tipo'] ?? 'externo',
                                'data_abastecimento' => $item['data'],
                                'quantidade'         => (float)$qtd,
                                'valor_unitario'     => (float)$unit,
                                'valor_total'        => (float)$total,
                                'km_abastecimento'   => (float)$kmAbast,
                            ]);

                            if (isset($item['tipo']) && $item['tipo'] == 'interno') {
                                $this->baixar($item['produto_id'], (float)$qtd, $filial_id);
                            }
                        }
                    }
                }

                // GRAVAÇÃO DAS DESPESAS
                if ($request->has('despesas')) {
                    foreach ($request->despesas as $desp) {
                        if (!empty($desp['valor']) && $desp['valor'] > 0) {
                            $valorDespesa = str_replace(',', '.', $desp['valor']);

                            $novaDespesa = new DespesaMovimentacao();
                            $novaDespesa->empresa_id      = $empresa_id;
                            $novaDespesa->usuario_id      = $usuario_id;
                            $novaDespesa->filial_id       = $filial_id;
                            $novaDespesa->movimentacao_id = $registro->id;
                            $novaDespesa->tipo            = $desp['tipo'];
                            $novaDespesa->valor           = (float)$valorDespesa;
                            $novaDespesa->descricao       = $desp['descricao'] ?? '';
                            $novaDespesa->save();
                        }
                    }
                }

                // ATUALIZA KM NO VEÍCULO
                if ($dados['status'] == 'finalizado' && $request->filled('km_final')) {
                    $v = Veiculo::find($request->veiculo_id);
                    if ($v) {
                        $v->quilometragem = (int) round((float) str_replace(',', '.', $request->km_final));
                        $v->save();
                    }
                }

                session()->flash('mensagem_sucesso', 'Movimentação guardada com sucesso!');
                return redirect($this->redirectPage);
            });
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao guardar: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    // --- MÉTODOS MANTIDOS INTACTOS (Agenda, Dashboards, Cancelamento, WhatsApp) ---
    // (O resto do código abaixo permanece exatamente igual ao seu original)

    public function agenda(Request $request)
    {
        $mesDesejado = $request->mes ?? date('Y-m');
        $dt = \Carbon\Carbon::parse($mesDesejado . '-01');

        $dataInicio = $dt->copy()->startOfMonth()->format('Y-m-d');
        $dataFim    = $dt->copy()->endOfMonth()->format('Y-m-d');
        $veiculoId  = $request->veiculo_id;

        $query = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);

        // 1. Proteção de Segurança pelo BaseController
        $query = $this->applyFilialFilter($query);

        // 2. Busca as filiais permitidas para exibir as opções no Filtro
        $user_logged = session('user_logged');
        $usuarioObj = \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $user_logged['id'] ?? $user_logged['usuario_id'] ?? 0)->first();

        $locaisPermitidos = $usuarioObj && !empty($usuarioObj->locais) ? json_decode($usuarioObj->locais, true) : ['-1'];
        if (!is_array($locaisPermitidos)) {
            $locaisPermitidos = ['-1'];
        }

        $filiaisQuery = \Illuminate\Support\Facades\DB::table('filials');
        if (\Illuminate\Support\Facades\Schema::hasColumn('filials', 'empresa_id')) {
            $filiaisQuery->where('empresa_id', $this->empresa_id);
        }
        if (!in_array('-1', $locaisPermitidos) && !in_array(-1, $locaisPermitidos)) {
            $filiaisQuery->whereIn('id', $locaisPermitidos);
        }
        $listaFiliais = $filiaisQuery->get();

        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        $totalRealizadas = (clone $query)->whereIn('status', ['finalizado', 'concluida'])->count();
        $totalCanceladas = (clone $query)->where('status', 'cancelado')->count();
        $totalAgendadas  = (clone $query)->where('status', 'agendado')->count();
        $totalEmPercurso = (clone $query)->where('status', 'iniciado')->count();

        $coletasRegistradas = (clone $query)->with(['veiculo', 'motorista', 'cliente', 'destinos.cliente', 'destinos.fornecedor'])->get();
        $coletasPorData = [];

        foreach ($coletasRegistradas as $c) {
            $dataKey = \Carbon\Carbon::parse($c->data_hora_saida)->format('Y-m-d');
            $coletasPorData[$dataKey][] = $c;
        }

        $primeiroDiaSemana = $dt->copy()->startOfMonth()->dayOfWeek;
        $diasNoMes = $dt->daysInMonth;
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $title = "Agenda de Coletas Planejadas";

        $mesAnterior = $dt->copy()->subMonth()->format('Y-m');
        $mesProximo  = $dt->copy()->addMonth()->format('Y-m');
        $podeReabrirAgenda = $this->podeReabrirAgendamento();

        return view('movimentacoes_veiculos.agenda', compact(
            'title', 'totalRealizadas', 'totalCanceladas',
            'totalAgendadas', 'totalEmPercurso', 'coletasPorData',
            'primeiroDiaSemana', 'diasNoMes', 'dt', 'veiculos',
            'dataInicio', 'dataFim', 'veiculoId', 'mesAnterior', 'mesProximo', 'listaFiliais', 'podeReabrirAgenda'
        ));
    }

    public function eventosCalendario(Request $request)
    {
        $start = $request->start;
        $end   = $request->end;

        // ADICIONADO: 'destinos.cliente', 'destinos.fornecedor'
        $coletas = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'cliente', 'destinos.cliente', 'destinos.fornecedor'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$start, $end])
            ->get();

        $eventos = [];

        foreach ($coletas as $c) {
            $cor = match($c->status) {
                'agendado'               => '#ffc107',
                'iniciado'               => '#007bff',
                'finalizado', 'concluida' => '#28a745',
                'cancelado'              => '#dc3545',
                default                  => '#6c757d'
            };

            // LÓGICA MULTI-STOP: Monta o nome de todos os destinos da viagem
            $locais = [];
            if ($c->destinos && $c->destinos->count() > 0) {
                foreach($c->destinos as $dest) {
                    $nome = '';
                    if ($dest->cliente) $nome = $dest->cliente->razao_social ?? $dest->cliente->nome;
                    elseif ($dest->fornecedor) $nome = '[FOR] ' . ($dest->fornecedor->razao_social ?? $dest->fornecedor->nome);
                    else $nome = $dest->endereco_completo;

                    if ($nome) $locais[] = $nome;
                }
                $nomeCliente = implode(' ➔ ', $locais);
            } else {
                $nomeCliente = $c->cliente->razao_social ?? $c->destino ?? '';
            }

            $placaVeiculo = $c->veiculo->placa ?? 'Sem Placa';
            $tituloEvento = $placaVeiculo . ($nomeCliente ? ' - ' . \Illuminate\Support\Str::limit($nomeCliente, 30) : '');

            $eventos[] = [
                'id'       => $c->id,
                'title'    => $tituloEvento,
                'start'    => \Carbon\Carbon::parse($c->data_hora_saida)->format('Y-m-d\TH:i:s'),
                'color'    => $cor,
                'extendedProps' => [
                    'motorista' => $c->motorista->nome ?? 'Não informado',
                    'status'    => ucfirst($c->status),
                    'chegada'   => $c->data_chegada_cliente_formatada,
                    'saida'     => $c->data_saida_cliente_formatada,
                    'destino_completo' => $nomeCliente
                ]
            ];
        }

        return response()->json($eventos);
    }

    public function registrarHorarioCliente(Request $request, $id)
    {
        try {
            $user_logged = session('user_logged');
            $usuarioIdLogado = $user_logged['id'] ?? $user_logged['usuario_id'];
            $isAdmin = $user_logged['adm'] ?? $user_logged['is_admin'] ?? false;

            $coleta = $this->applyFilialFilter(
                MovimentacaoVeiculo::with(['veiculo', 'motorista'])->where('empresa_id', $this->empresa_id)
            )->findOrFail($id);

            if (in_array($coleta->status, ['finalizado', 'concluida'], true)) {
                return response()->json([
                    'success' => false,
                    'mensagem' => 'Agendamento finalizado. É necessário reabri-lo com OTP antes de registrar novos horários.'
                ], 422);
            }

            // Permite se for Administrador, Motorista vinculado, ou se a viagem for Terceirizada
            $funcionarioMotorista = \App\Models\Funcionario::where('id', $coleta->motorista_id)->first();
            $isMotoristaDaColeta = ($funcionarioMotorista && $funcionarioMotorista->usuario_id == $usuarioIdLogado);

            if (!$isAdmin && !$isMotoristaDaColeta && $coleta->is_terceirizado == 0) {
                return response()->json([
                    'success' => false,
                    'mensagem' => 'Acesso negado: Somente o Motorista escalado ou Administradores podem registar horários nesta coleta.'
                ], 403);
            }

            $tipo = $request->tipo;

            // 🚀 LÊ A HORA DIGITADA NO MODAL (Ou assume a hora atual se vier vazio)
            $horaRegistro = $request->filled('data_hora_manual')
                ? \Carbon\Carbon::parse($request->data_hora_manual)
                : now();

            // 🚀 VERIFICA SE O OPERADOR MARCOU "NÃO GERAR PESAGEM"
            $isManual = $request->input('is_manual') == 1;

            if ($tipo == 'saida_garagem') {
                $coleta->data_hora_saida_real = $horaRegistro;
                if ($coleta->status == 'agendado') {
                    $coleta->status = 'iniciado';
                }
            } elseif ($tipo == 'chegada') {
                $coleta->data_hora_chegada_cliente = $horaRegistro;
                if ($coleta->status == 'agendado') {
                    $coleta->status = 'iniciado';
                }
            } elseif ($tipo == 'saida') {
                $coleta->data_hora_saida_cliente = $horaRegistro;
            }
            // LÓGICA DE DESTINOS INDIVIDUAIS (MULTI-STOP)
            elseif ($tipo == 'chegada_destino') {
                $destino = \App\Models\MovimentacaoDestino::find($request->destino_id);
                if ($destino) {
                    $destino->data_hora_chegada = $horaRegistro;
                    $destino->save();

                    // GATILHO 1: WhatsApp do Motorista
                    $primeiroDestinoColeta = $coleta->destinos()->where('is_aterro', 0)->orderBy('ordem', 'asc')->first();
                    if ($primeiroDestinoColeta && $primeiroDestinoColeta->id == $destino->id && empty($coleta->alerta_atraso_enviado_at)) {
                        $this->enviarLinkColetaWhatsApp($coleta->id);
                        $coleta->alerta_atraso_enviado_at = now();
                    }
                }
                if ($coleta->status == 'agendado') { $coleta->status = 'iniciado'; }
            }
            elseif ($tipo == 'saida_destino') {
                $destino = \App\Models\MovimentacaoDestino::find($request->destino_id);
                if ($destino) {
                    $destino->data_hora_saida = $horaRegistro;
                    $destino->status = 'visitado';
                    $destino->save();

                    // GATILHO 2: Resumo WhatsApp (apenas se Gerar MTR? = SIM)
                    $ultimoDestinoColeta = $coleta->destinos()->where('is_aterro', 0)->orderBy('ordem', 'desc')->first();
                    if (
                        (int) ($coleta->gerar_mtr ?? 0) === 1
                        && $ultimoDestinoColeta
                        && $ultimoDestinoColeta->id == $destino->id
                        && $destino->alerta_mtr_enviado == 0
                    ) {
                        $this->enviarAlertaResumoAmbiental($coleta->id);
                        $destino->alerta_mtr_enviado = 1;
                        $destino->save();
                    }
                }
            }

            elseif ($tipo == 'chegada_garagem') {
                $coleta->data_hora_chegada = $horaRegistro;
                $coleta->status = 'finalizado';

                // 🚀 SE FOR LANÇAMENTO MANUAL/RETROATIVO, DESLIGA A IDA PARA A BALANÇA!
                if ($isManual) {
                    $coleta->passar_balanca = 0;
                }

                // (O CÁLCULO DE KM ORIGINAL MANTÉM-SE AQUI INTACTO)
                $base = $coleta->filial_id ? \Illuminate\Support\Facades\DB::table('filials')->where('id', $coleta->filial_id)->first() : \Illuminate\Support\Facades\DB::table('config_notas')->where('empresa_id', $this->empresa_id)->first();
                $latBase = $base->latitude ?? null; $lonBase = $base->longitude ?? null;
                $latOrigem = $latBase; $lonOrigem = $lonBase;
                if ($coleta->tipo_partida == 'residencia' && $coleta->motorista) {
                    $latOrigem = $coleta->motorista->latitude_residencia ?? $latBase;
                    $lonOrigem = $coleta->motorista->longitude_residencia ?? $lonBase;
                }

                $calcDist = function($lat1, $lon1, $lat2, $lon2) {
                    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
                    $raioTerra = 6371000;
                    $latDe = deg2rad($lat1); $lonDe = deg2rad($lon1);
                    $latPara = deg2rad($lat2); $lonPara = deg2rad($lon2);
                    $deltaLat = $latPara - $latDe; $deltaLon = $lonPara - $lonDe;
                    $angulo = 2 * asin(sqrt(pow(sin($deltaLat / 2), 2) + cos($latDe) * cos($latPara) * pow(sin($deltaLon / 2), 2)));
                    return $angulo * $raioTerra;
                };

                $kmPercorrido = 0;
                if ($coleta->veiculo && $coleta->veiculo->traccar_id) {
                    try {
                        $config = \App\Models\TraccarConfig::where('empresa_id', $this->empresa_id)->first();
                        if ($config && !empty($config->base_url)) {
                            $baseUrl = rtrim($config->base_url, '/');
                            $respDevices = \Illuminate\Support\Facades\Http::withBasicAuth($config->mail_user_name, $config->password)->get($baseUrl . '/api/devices');
                            if ($respDevices->successful()) {
                                $device = collect($respDevices->json())->firstWhere('uniqueId', $coleta->veiculo->traccar_id);
                                if ($device) {
                                    $idInterno = $device['id'];
                                    $from = \Carbon\Carbon::parse($coleta->data_hora_saida_real)->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                                    $to = \Carbon\Carbon::now()->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                                    $urlSummary = $baseUrl . "/api/reports/summary?deviceId={$idInterno}&from={$from}&to={$to}";
                                    $respSummary = \Illuminate\Support\Facades\Http::withBasicAuth($config->mail_user_name, $config->password)->withHeaders(['Accept' => 'application/json'])->get($urlSummary);
                                    if ($respSummary->successful() && count($respSummary->json()) > 0) {
                                        $kmPercorrido = ($respSummary->json()[0]['distance'] ?? 0) / 1000;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) { \Illuminate\Support\Facades\Log::error("Erro Traccar Manual KM: " . $e->getMessage()); }
                }

                if ($kmPercorrido <= 0) {
                    $distIda = $calcDist($latOrigem, $lonOrigem, $coleta->latitude_destino, $coleta->longitude_destino);
                    $distVolta = $calcDist($coleta->latitude_destino, $coleta->longitude_destino, $latBase, $lonBase);
                    $kmPercorrido = (($distIda + $distVolta) / 1000) * 1.25;
                }

                $coleta->km_final = (int) round($coleta->km_inicial + max(0, $kmPercorrido));
                if ($coleta->veiculo_id && $coleta->km_final > 0) {
                    $veiculoObj = \App\Models\Veiculo::find($coleta->veiculo_id);
                    if ($veiculoObj) {
                        $veiculoObj->quilometragem = $coleta->km_final;
                        $veiculoObj->save();
                    }
                }
            }

            // Grava a movimentação com a nova hora (e com o passar_balanca possivelmente desligado)
            $coleta->save();

            // 🚀 CHAMA A PRÉ-PESAGEM CASO A VIAGEM TENHA TERMINADO E A BALANÇA ESTEJA LIGADA
            if ($coleta->status === 'finalizado' && $coleta->passar_balanca == 1 && empty($coleta->pesagem_id)) {
                $coleta->gerarPrePesagemAutomatica();
            }

            return response()->json(['success' => true, 'mensagem' => 'Horário e status atualizados com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }

    public function cancelar(Request $request, $id)
    {
        $movimentacao = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)->findOrFail($id);

        if (in_array($movimentacao->status, ['finalizado', 'concluida'], true)) {
            session()->flash('mensagem_erro', 'Não é possível cancelar uma movimentação já finalizada.');
            return redirect()->back();
        }

        $movimentacao->status = 'cancelado';
        $movimentacao->motivo_cancelamento = $request->motivo_cancelamento ?? 'Cancelado pelo utilizador';
        $movimentacao->data_cancelamento = now();
        $movimentacao->save();

        session()->flash('mensagem_sucesso', 'Coleta/Movimentação cancelada com sucesso!');
        return redirect()->back();
    }

    public function relatorioColetas(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim    = $request->data_fim ?? date('Y-m-t');
        $veiculoId  = $request->veiculo_id;
        $status     = $request->status;
        $entidadeId = $request->entidade_id; // 🚀 NOVO FILTRO (Cliente ou Fornecedor)

        // 🚀 ADICIONADO OS DESTINOS NA CONSULTA (Eager Loading)
        $query = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'cliente', 'destinos.cliente', 'destinos.fornecedor'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);

        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // 🚀 LÓGICA DO FILTRO MULTI-STOP (Busca viagens antigas e novas)
        if ($entidadeId) {
            if (str_starts_with($entidadeId, 'cliente_')) {
                $cId = str_replace('cliente_', '', $entidadeId);
                $query->where(function($q) use ($cId) {
                    $q->where('cliente_id', $cId) // Viagens antigas
                    ->orWhereHas('destinos', function($q2) use ($cId) { // Viagens novas (Multi-stop)
                        $q2->where('cliente_id', $cId);
                    });
                });
            } elseif (str_starts_with($entidadeId, 'fornecedor_')) {
                $fId = str_replace('fornecedor_', '', $entidadeId);
                $query->where(function($q) use ($fId) {
                    $q->where('fornecedor_id', $fId) // Viagens antigas
                    ->orWhereHas('destinos', function($q2) use ($fId) { // Viagens novas (Multi-stop)
                        $q2->where('fornecedor_id', $fId);
                    });
                });
            }
        }

        $coletas = $query->orderBy('data_hora_saida', 'asc')->get();

        $totalRealizadas = $coletas->whereIn('status', ['finalizado', 'concluida'])->count();
        $totalCanceladas = $coletas->where('status', 'cancelado')->count();
        $totalAgendadas  = $coletas->where('status', 'agendado')->count();
        $totalEmCurso    = $coletas->where('status', 'iniciado')->count();

        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        // 🚀 Puxando para enviar para o filtro da View
        $clientes = Cliente::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get();
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get();

        $title = "Relatório Gerencial de Coletas";

        return view('movimentacoes_veiculos.relatorio_coletas', compact(
            'coletas', 'totalRealizadas', 'totalCanceladas',
            'totalAgendadas', 'totalEmCurso', 'veiculos',
            'dataInicio', 'dataFim', 'veiculoId', 'status', 'title',
            'clientes', 'fornecedores', 'entidadeId'
        ));
    }



    public function dashboard(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');
        $veiculo_id = $request->veiculo_id;

        $motoristasAlerta = \App\Models\Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_funcionario', 'Ativo')
            ->whereNotNull('vencimento_cnh')
            ->whereDate('vencimento_cnh', '<=', \Carbon\Carbon::now()->addDays(30))
            ->get();

        $veiculosManutencao = DB::table('manutencoes')
            ->join('veiculos', 'manutencoes.veiculo_id', '=', 'veiculos.id')
            ->where('manutencoes.empresa_id', $this->empresa_id)
            ->whereIn('manutencoes.status', ['em andamento', 'aguardando'])
            ->select('veiculos.placa', 'veiculos.modelo', 'manutencoes.status as manutencao_status')
            ->get();

        $queryAbast = DB::table('abastecimentos_movimentacoes')
            ->join('movimentacoes_veiculos', 'abastecimentos_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
            ->join('produtos', 'abastecimentos_movimentacoes.produto_id', '=', 'produtos.id')
            ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
            ->whereBetween('movimentacoes_veiculos.data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id);
            });

        $custoDiesel = (clone $queryAbast)->where('produtos.nome', 'NOT LIKE', '%ARLA%')->sum('valor_total');
        $totalLitrosDiesel = (clone $queryAbast)->where('produtos.nome', 'NOT LIKE', '%ARLA%')->sum('quantidade');
        $totalCustoArla = (clone $queryAbast)->where('produtos.nome', 'LIKE', '%ARLA%')->sum('valor_total');
        $totalLitrosArla = (clone $queryAbast)->where('produtos.nome', 'LIKE', '%ARLA%')->sum('quantidade');

        $custoManutencao = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->where('categoria_id', '!=', 6)
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('veiculo_id', $veiculo_id);
            })->sum('valor_integral');

        $custoViagem = DB::table('despesas_movimentacoes')
            ->join('movimentacoes_veiculos', 'despesas_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
            ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
            ->whereBetween('movimentacoes_veiculos.data_hora_saida', [$dataInicio, $dataFim])
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id);
            })->sum('valor');

        $queryMov = \App\Models\MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('veiculo_id', $veiculo_id);
            });

        $totalKm = (clone $queryMov)->where('status', 'finalizado')->get()->sum(function($m){
            return ($m->km_final > 0) ? ($m->km_final - $m->km_inicial) : 0;
        });

        $mediaKmL = $totalLitrosDiesel > 0 ? ($totalKm / $totalLitrosDiesel) : 0;

        $graficoMeses = []; $graficoKm = []; $graficoFinanceiro = []; $graficoViagem = [];
        for ($i = 3; $i >= 0; $i--) {
            $mesReferencia = date('Y-m', strtotime("-$i months"));
            $graficoMeses[] = date('M/y', strtotime($mesReferencia));

            $graficoKm[] = \App\Models\MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
                ->where('data_hora_saida', 'like', "$mesReferencia%")
                ->when($veiculo_id, function($q) use ($veiculo_id) { return $q->where('veiculo_id', $veiculo_id); })
                ->get()->sum(function($m){ return ($m->km_final - $m->km_inicial); });

            $graficoFinanceiro[] = DB::table('conta_pagars')
                ->where('empresa_id', $this->empresa_id)
                ->where('data_emissao', 'like', "$mesReferencia%")
                ->when($veiculo_id, function($q) use ($veiculo_id) { return $q->where('veiculo_id', $veiculo_id); })
                ->sum('valor_integral');

            $graficoViagem[] = DB::table('despesas_movimentacoes')
                ->join('movimentacoes_veiculos', 'despesas_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
                ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
                ->where('movimentacoes_veiculos.data_hora_saida', 'like', "$mesReferencia%")
                ->when($veiculo_id, function($q) use ($veiculo_id) { return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id); })
                ->sum('valor');
        }

        return view('movimentacoes_veiculos.dashboard', [
            'title' => 'Painel Gerencial de Frota',
            'dataInicio' => $dataInicio, 'dataFim' => $dataFim,
            'veiculoSelecionado' => $veiculo_id,
            'veiculos' => \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->orderBy('placa')->get(),
            'totalKm' => $totalKm, 'mediaKmPorLitro' => number_format($mediaKmL, 2, ',', '.'),
            'totalLitros' => $totalLitrosDiesel, 'totalLitrosArla' => $totalLitrosArla,
            'custoDiesel' => $custoDiesel, 'totalCustoArla' => $totalCustoArla,
            'custoManutencao' => $custoManutencao, 'custoViagem' => $custoViagem,
            'totalDespesas' => $custoDiesel + $totalCustoArla + $custoManutencao + $custoViagem,
            'emPercurso' => (clone $queryMov)->where('status', 'iniciado')->count(),
            'disponiveis' => \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->count() - (clone $queryMov)->where('status', 'iniciado')->count(),
            'motoristasAlerta' => $motoristasAlerta, 'veiculosManutencao' => $veiculosManutencao,
            'graficoMeses' => json_encode($graficoMeses), 'graficoKm' => json_encode($graficoKm),
            'graficoContas' => json_encode($graficoFinanceiro), 'graficoDespesas' => json_encode($graficoViagem),
            'pizzaLabels' => json_encode(['Diesel', 'Arla 32', 'Manutenção', 'Despesas Viagem']),
            'pizzaValores' => json_encode([$custoDiesel, $totalCustoArla, $custoManutencao, $custoViagem]),
        ]);
    }

    public function relatorio(Request $request)
    {
        $dataInicio = $request->data_inicio ?: date('Y-m-01');
        $dataFim = $request->data_fim ?: date('Y-m-t');
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->orderBy('placa')->get();

        $dadosRelatorio = [];

        foreach ($veiculos as $v) {
            $movs = MovimentacaoVeiculo::with(['abastecimentos.produto', 'despesas'])
                ->where('veiculo_id', $v->id)
                ->whereBetween('data_hora_saida', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59'])
                ->get();

            $statusTexto = 'Disponível';
            $kmRodado = 0;
            $custoCombustivel = 0;
            $custoArla = 0;
            $litrosCombustivel = 0;
            $litrosArla = 0;

            $kmRodado = $movs->where('status', 'finalizado')->sum(function($m) {
                return $m->km_final - $m->km_inicial;
            });

            if ($movs->where('status', 'iniciado')->count() > 0) {
                $statusTexto = 'Em Viagem';
            }

            foreach($movs as $m) {
                foreach($m->abastecimentos as $abast) {
                    if (str_contains(strtoupper($abast->produto->nome), 'ARLA')) {
                        $custoArla += $abast->valor_total;
                        $litrosArla += $abast->quantidade;
                    } else {
                        $custoCombustivel += $abast->valor_total;
                        $litrosCombustivel += $abast->quantidade;
                    }
                }
            }

            $custoManutencao = DB::table('conta_pagars')
                ->where('veiculo_id', $v->id)
                ->where('empresa_id', $this->empresa_id)
                ->whereBetween('data_emissao', [$dataInicio, $dataFim])
                ->sum('valor_integral');

            $custoTotalGeral = $custoCombustivel + $custoArla + $custoManutencao;
            $custoPorKm = ($kmRodado > 0) ? ($custoTotalGeral / $kmRodado) : 0;

            if ($kmRodado > 0 || $custoCombustivel > 0 || $custoArla > 0) {
                $dadosRelatorio[] = (object)[
                    'placa' => $v->placa,
                    'modelo' => $v->modelo,
                    'status' => $statusTexto,
                    'km_rodado' => $kmRodado,
                    'custo_manutencao' => $custoManutencao,
                    'custo_combustivel' => $custoCombustivel,
                    'litros_combustivel' => $litrosCombustivel,
                    'custo_arla' => $custoArla,
                    'litros_arla' => $litrosArla,
                    'custo_total' => $custoTotalGeral,
                    'custo_por_km' => $custoPorKm,
                    'media_kml' => $litrosCombustivel > 0 ? ($kmRodado / $litrosCombustivel) : 0,
                ];
            }
        }
        $title = "Relatório Consolidado de Frota";
        return view('movimentacoes_veiculos.relatorio', compact('dadosRelatorio', 'dataInicio', 'dataFim', 'title'));
    }

    private function baixar($id, $qtd, $filial) {
        (new StockMove())->pluStock($id, (float)$qtd * -1, -1, $filial);
    }

    public function imprimir($id) {
        $data = $this->model::with(['veiculo', 'motorista', 'abastecimentos.produto'])->findOrFail($id);
        return view('movimentacoes_veiculos.print', compact('data'));
    }

    public function enviarChecklistWhatsApp($id)
    {
        $mov = MovimentacaoVeiculo::with(['motorista', 'veiculo'])->where('empresa_id', $this->empresa_id)->findOrFail($id);

        if (!$mov->motorista || (empty($mov->motorista->celular) && empty($mov->motorista->telefone))) {
            session()->flash('mensagem_erro', 'Motorista sem telefone cadastrado.');
            return redirect()->back();
        }

        $telefone = $mov->motorista->celular ?: $mov->motorista->telefone;
        $linkChecklist = url('/checklist/veiculo/' . $mov->id);

        $msg = "📋 *CHECKLIST DE PRÉ-VIAGEM*\n\nOlá, *{$mov->motorista->nome}*! Por favor, preencha o checklist do veículo *{$mov->veiculo->placa}* clicando no link abaixo antes ou logo no início da sua rota:\n\n👉 {$linkChecklist}";

        try {
            // Instância e número são resolvidos pelo serviço da Connect|API
            // para a empresa autenticada, sem consultar transportes legados.
            $resultado = app(ConnectApiMessageService::class)->sendText(
                (int) $this->empresa_id,
                (string) $telefone,
                $msg
            );

            if (empty($resultado['success'])) {
                Log::warning('Frota: falha ao enviar checklist de pré-viagem pelo WhatsApp.', [
                    'empresa_id' => $this->empresa_id,
                    'movimentacao_id' => $mov->id,
                    'status' => $resultado['status'] ?? null,
                    'erro' => $resultado['error'] ?? null,
                ]);
                session()->flash('mensagem_erro', 'Não foi possível enviar o checklist pelo WhatsApp. Verifique a conexão da instância.');
                return redirect()->back();
            }
        } catch (\Throwable $e) {
            Log::error('Frota: erro ao enviar checklist de pré-viagem pelo WhatsApp.', [
                'empresa_id' => $this->empresa_id,
                'movimentacao_id' => $mov->id,
                'erro' => $e->getMessage(),
            ]);
            session()->flash('mensagem_erro', 'Não foi possível enviar o checklist pelo WhatsApp. Verifique a conexão da instância.');
            return redirect()->back();
        }

        session()->flash('mensagem_sucesso', 'Link do checklist enviado com sucesso para o WhatsApp do motorista!');
        return redirect()->back();
    }

    public function verChecklist($id)
    {
        $movimentacao = MovimentacaoVeiculo::with(['veiculo', 'motorista'])->where('empresa_id', $this->empresa_id)->findOrFail($id);

        $checklist = DB::table('checklists_movimentacoes')
            ->where('movimentacao_veiculo_id', $id)
            ->first();

        $fotos = [];
        if ($checklist) {
            $fotos = DB::table('checklist_fotos')
                ->where('checklist_movimentacao_id', $checklist->id)
                ->get();
        }

        $title = "Detalhes do Checklist - Veículo: " . ($movimentacao->veiculo->placa ?? '');
        return view('movimentacoes_veiculos.ver_checklist', compact('movimentacao', 'checklist', 'fotos', 'title'));
    }

    public function enviarLinkAbastecimentoWhatsApp($id)
    {
        $mov = MovimentacaoVeiculo::with(['motorista', 'veiculo'])->where('empresa_id', $this->empresa_id)->findOrFail($id);

        if (!$mov->motorista || (empty($mov->motorista->celular) && empty($mov->motorista->telefone))) {
            session()->flash('mensagem_erro', 'Motorista sem telefone cadastrado.');
            return redirect()->back();
        }

        $telefone = $mov->motorista->celular ?: $mov->motorista->telefone;
        $linkAbastecimento = url('/abastecimento-motorista/' . $mov->id);

        $msg = "⛽ *REGISTRO DE ABASTECIMENTO*\n\nOlá, *{$mov->motorista->nome}*! Registre o abastecimento do veículo *{$mov->veiculo->placa}* clicando no link abaixo.\n\n📸 *Dica:* Tire a foto do cupom fiscal e da bomba direto pelo link!\n\n👉 {$linkAbastecimento}";

        try {
            // Instância e número são resolvidos pelo serviço da Connect|API
            // para a empresa autenticada, sem consultar transportes legados.
            $resultado = app(ConnectApiMessageService::class)->sendText(
                (int) $this->empresa_id,
                (string) $telefone,
                $msg
            );

            if (empty($resultado['success'])) {
                Log::warning('Frota: falha ao enviar link de abastecimento pelo WhatsApp.', [
                    'empresa_id' => $this->empresa_id,
                    'movimentacao_id' => $mov->id,
                    'status' => $resultado['status'] ?? null,
                    'erro' => $resultado['error'] ?? null,
                ]);
                session()->flash('mensagem_erro', 'Não foi possível enviar o link de abastecimento pelo WhatsApp. Verifique a conexão da instância.');
                return redirect()->back();
            }
        } catch (\Throwable $e) {
            Log::error('Frota: erro ao enviar link de abastecimento pelo WhatsApp.', [
                'empresa_id' => $this->empresa_id,
                'movimentacao_id' => $mov->id,
                'erro' => $e->getMessage(),
            ]);
            session()->flash('mensagem_erro', 'Não foi possível enviar o link de abastecimento pelo WhatsApp. Verifique a conexão da instância.');
            return redirect()->back();
        }

        session()->flash('mensagem_sucesso', 'Link de abastecimento enviado com sucesso para o WhatsApp do motorista!');
        return redirect()->back();
    }

    public function historico($id)
    {
        // Aqui criamos a variável $movimentacao corretamente, já puxando Clientes e Fornecedores
        $movimentacao = MovimentacaoVeiculo::with([
            'veiculo',
            'motorista',
            'destinos.cliente',
            'destinos.fornecedor',
            'historicoParadas'
        ])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        $title = "Histórico e Linha do Tempo da Viagem";

        return view('movimentacoes_veiculos.historico', compact('movimentacao', 'title'));
    }

    /**
     * ITEM 6: Tela Pública (Mobile) para o motorista informar Resíduo, Peso e Caçambas
     */
    public function exibirTelaColetaMotorista($id, $destino_id)
    {
        $movimentacao = MovimentacaoVeiculo::with(['veiculo', 'destinos.cliente', 'destinos.fornecedor'])->findOrFail($id);

        // Busca segura do destino vinculado à viagem para evitar o erro 404/ModelNotFound
        $destino = \App\Models\MovimentacaoDestino::where('movimentacao_veiculo_id', $id)
            ->where('id', $destino_id)
            ->first();

        if (!$destino) {
            $destino = $movimentacao->destinos->first();

            if (!$destino) {
                abort(404, 'Nenhum destino/parada encontrado para esta viagem.');
            }
        }

        // Caçambas cadastradas como produtos com valor de locação
        $caixas = \App\Models\Produto::where('empresa_id', $movimentacao->empresa_id)
            ->where('inativo', false)
            ->where('valor_locacao', '>', 0)
            ->orderBy('nome')
            ->get();

        // Resíduos disponíveis
        $produtos = \App\Models\Produto::where('empresa_id', $movimentacao->empresa_id)
            ->where('inativo', false)
            ->orderBy('nome')
            ->get();

        return view('movimentacoes_veiculos.coleta_motorista', compact('movimentacao', 'destino', 'caixas', 'produtos'));
    }


    /**
     * ITEM 6: Salva os dados preenchidos pelo motorista
     */
    public function salvarColetaMotorista(Request $request, $id, $destino_id)
    {
        $movimentacao = MovimentacaoVeiculo::findOrFail($id);
        $destino = \App\Models\MovimentacaoDestino::findOrFail($destino_id);

        // 1. Atualiza Caçambas e Operação na Parada
        if ($request->filled('tipo_operacao_caixa')) {
            $destino->tipo_operacao_caixa = $request->tipo_operacao_caixa;
            $destino->caixa_deixada_id    = $request->caixa_deixada_id ?: null;
            $destino->caixa_retirada_id   = $request->caixa_retirada_id ?: null;
        }

        // 2. Upload da Foto do Ticket se houver
        if ($request->hasFile('foto_ticket_balanca')) {
            $arquivo = $request->file('foto_ticket_balanca');
            $nomeArquivo = 'ticket_' . $destino->id . '_' . time() . '.' . $arquivo->getClientOriginalExtension();
            $arquivo->move(public_path('uploads/tickets'), $nomeArquivo);
            $destino->foto_ticket_balanca = $nomeArquivo;
        }

        // 3. Adiciona o Resíduo na tabela de itens (estilo itens de NF)
        if (!empty($request->produto_residuo_id) && !empty($request->quantidade_residuo)) {
            \App\Models\MovimentacaoDestinoItem::create([
                'movimentacao_veiculo_id' => $movimentacao->id,
                'movimentacao_destino_id' => $destino->id,
                'produto_id'              => $request->produto_residuo_id,
                'quantidade'              => str_replace(',', '.', $request->quantidade_residuo),
                'unidade'                 => $request->unidade_residuo ?? 'KG'
            ]);

            // Mantém também na tabela mãe para compatibilidade direta de listagens antigas
            $destino->produto_residuo_id = $request->produto_residuo_id;
            $destino->quantidade_residuo = str_replace(',', '.', $request->quantidade_residuo);
            $destino->unidade_residuo    = $request->unidade_residuo ?? 'KG';
        }

        $destino->save();

        // =========================================================================
        // 🔄 SINCRONIZAÇÃO DE CAÇAMBAS/EQUIPAMENTOS (Pátio x Cliente)
        // =========================================================================

        // Caçamba Deixada no Cliente -> Passa a 'cliente'
        if (!empty($destino->caixa_deixada_id)) {
            DB::table('produtos')
                ->where('id', $destino->caixa_deixada_id)
                ->update([
                    'status_localizacao' => 'cliente',
                    'cliente_atual_id'   => $destino->cliente_id,
                    'endereco_atual'     => $destino->endereco_completo,
                    'latitude_atual'     => $destino->latitude,
                    'longitude_atual'    => $destino->longitude,
                    'updated_at'         => now()
                ]);

            DB::table('patrimonio_movimentacoes')->insert([
                'empresa_id'              => $movimentacao->empresa_id,
                'patrimonio_id'           => $destino->caixa_deixada_id,
                'cliente_id'              => $destino->cliente_id,
                'tipo_acao'               => 'Entrega',
                'movimentacao_veiculo_id' => $movimentacao->id,
                'observacao'              => 'Entregue pelo motorista na Viagem #' . $movimentacao->id,
                'created_at'              => now()
            ]);
        }

        // Caçamba Retirada do Cliente -> Regressa à 'base'
        if (!empty($destino->caixa_retirada_id)) {
            DB::table('produtos')
                ->where('id', $destino->caixa_retirada_id)
                ->update([
                    'status_localizacao' => 'base',
                    'cliente_atual_id'   => null,
                    'endereco_atual'     => null,
                    'latitude_atual'     => null,
                    'longitude_atual'    => null,
                    'updated_at'         => now()
                ]);

            DB::table('patrimonio_movimentacoes')->insert([
                'empresa_id'              => $movimentacao->empresa_id,
                'patrimonio_id'           => $destino->caixa_retirada_id,
                'cliente_id'              => $destino->cliente_id,
                'tipo_acao'               => 'Retirada',
                'movimentacao_veiculo_id' => $movimentacao->id,
                'observacao'              => 'Recolhida pelo motorista na Viagem #' . $movimentacao->id,
                'created_at'              => now()
            ]);
        }

        $destino->save();

        // =========================================================================
        // 🚀 GATILHO: DISPARA O RESUMO AMBIENTAL / MTR APÓS O MOTORISTA SALVAR A COLETA
        // =========================================================================
        try {
            $this->enviarAlertaResumoAmbiental($movimentacao->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao disparar MTR pós-coleta: " . $e->getMessage());
        }

        // 4. Se clicou em "Adicionar Mais Resíduo"
        if ($request->input('acao') == 'adicionar_mais') {
            return back()->with('sucesso', 'Material registado com sucesso! Indique o próximo resíduo.');
        }

        return back()->with('sucesso', 'Todos os materiais e equipamentos deste local foram gravados com sucesso!');
    }

    // Método para excluir um item digitado por engano
    public function removerItemColetaMotorista($item_id)
    {
        $item = \App\Models\MovimentacaoDestinoItem::findOrFail($item_id);
        $item->delete();

        return back()->with('sucesso', 'Item removido com sucesso!');
    }

    public function enviarLinkColetaWhatsApp($id)
    {
        // 1. Busca a viagem sem depender da sessão web
        $mov = MovimentacaoVeiculo::with(['motorista', 'veiculo', 'destinos'])->findOrFail($id);

        if (!$mov->motorista || (empty($mov->motorista->celular) && empty($mov->motorista->telefone))) {
            if (!app()->runningInConsole()) session()->flash('mensagem_erro', 'Motorista sem telefone cadastrado.');
            return redirect()->back();
        }

        $telefone = $mov->motorista->celular ?: $mov->motorista->telefone;
        $destino = $mov->destinos->first();

        if (!$destino) {
            if (!app()->runningInConsole()) session()->flash('mensagem_erro', 'Esta movimentação não possui destinos/paradas cadastradas.');
            return redirect()->back();
        }

        // 🚀 FORÇA O DOMÍNIO CORRETO DE PRODUÇÃO EM VEZ DE LOCALHOST
        $dominioBase = 'https://web.fersofterp.com.br';
        $linkColeta = $dominioBase . '/coleta-motorista/' . $mov->id . '/destino/' . $destino->id;

        $msg = "♻️ *REGISTRO DE COLETA E RESÍDUO*\n\n";
        $msg .= "Olá, *{$mov->motorista->nome}*! Por favor, informe o resíduo e a quantidade coletada para o veículo *{$mov->veiculo->placa}* clicando no link abaixo:\n\n";
        $msg .= "👉 {$linkColeta}";

        try {
            $resultado = app(\App\Services\ConnectApi\ConnectApiMessageService::class)->sendText(
                (int) $mov->empresa_id,
                (string) $telefone,
                $msg
            );

            if (empty($resultado['success'])) {
                if (!app()->runningInConsole()) session()->flash('mensagem_erro', 'Não foi possível enviar o link de coleta pelo WhatsApp.');
                return redirect()->back();
            }
        } catch (\Throwable $e) {
            if (!app()->runningInConsole()) session()->flash('mensagem_erro', 'Erro ao enviar WhatsApp: ' . $e->getMessage());
            return redirect()->back();
        }

        if (!app()->runningInConsole()) session()->flash('mensagem_sucesso', 'Link de coleta de resíduos enviado com sucesso para o motorista!');
        return redirect()->back();
    }

    public function verificarAtrasosFrota($empresa_id)
    {
        $param = \Illuminate\Support\Facades\DB::table('parametros_agendamento_logistica')
            ->where('empresa_id', $empresa_id)
            ->first();

        if (!$param || empty($param->notificar_atraso_whatsapp)) {
            return;
        }

        $tolerancia = (int) ($param->minutos_tolerancia_atraso ?? 20);
        $limiteHora = now()->subMinutes($tolerancia);

        // Busca movimentações com saída prevista vencida que continuam agendadas
        $movimentacoesAtrasadas = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'destinos.cliente'])
            ->where('empresa_id', $empresa_id)
            ->where('status', 'agendado')
            ->where('data_hora_saida', '<=', $limiteHora)
            ->whereNull('alerta_atraso_enviado_at')
            ->get();

        $gestor = $param->funcionario_gestor_frota_id
            ? \App\Models\Funcionario::find($param->funcionario_gestor_frota_id)
            : null;

        $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);

        foreach ($movimentacoesAtrasadas as $mov) {
            $placa = $mov->veiculo->placa ?? 'Sem Placa';
            $motoristaNome = $mov->motorista->nome ?? 'Não informado';
            $horaPrevista = date('H:i d/m', strtotime($mov->data_hora_saida));

            // 1. WhatsApp para o Motorista
            if ($mov->motorista && (!empty($mov->motorista->celular) || !empty($mov->motorista->telefone))) {
                $numMotorista = preg_replace('/[^0-9]/', '', $mov->motorista->celular ?: $mov->motorista->telefone);
                if (strlen($numMotorista) >= 10) {
                    if (!str_starts_with($numMotorista, '55')) $numMotorista = '55' . $numMotorista;
                    $msgMotorista = "⏰ *LEMBRETE DE SAÍDA - FROTA*\n\n"
                        . "Olá, *{$motoristaNome}*!\n"
                        . "A viagem com o veículo *{$placa}* estava prevista para sair às *{$horaPrevista}* e ainda consta como pendente de saída.\n"
                        . "Favor iniciar o checklist ou comunicar o encarregado.";

                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numMotorista, $msgMotorista, $empresa_id);
                    }
                }
            }

            // 2. WhatsApp para o Gestor da Frota
            if ($gestor && (!empty($gestor->celular) || !empty($gestor->telefone))) {
                $numGestor = preg_replace('/[^0-9]/', '', $gestor->celular ?: $gestor->telefone);
                if (strlen($numGestor) >= 10) {
                    if (!str_starts_with($numGestor, '55')) $numGestor = '55' . $numGestor;
                    $msgGestor = "⚠️ *ALERTA DE ATRASO NA FROTA*\n\n"
                        . "A saída do veículo *{$placa}* ultrapassou o limite de tolerância ({$tolerancia} min).\n"
                        . "📌 *Saída Prevista:* {$horaPrevista}\n"
                        . "👤 *Motorista:* {$motoristaNome}\n"
                        . "Viagem ID: #{$mov->id}";

                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numGestor, $msgGestor, $empresa_id);
                    }
                }
            }

            // Marca como enviado para evitar spam repetido
            $mov->alerta_atraso_enviado_at = now();
            $mov->save();
        }
    }

    /**
     * Envia o Resumo de Rota / Ordem de Coleta para o WhatsApp do motorista
     */
    public function enviarResumoViagemWhatsApp($id)
    {
        $mov = MovimentacaoVeiculo::with(['motorista', 'veiculo', 'destinos.cliente', 'destinos.fornecedor'])
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail($id);

        if (!$mov->motorista || (empty($mov->motorista->celular) && empty($mov->motorista->telefone))) {
            session()->flash('mensagem_erro', 'Motorista sem telefone cadastrado.');
            return redirect()->back();
        }

        $telefone = $mov->motorista->celular ?: $mov->motorista->telefone;
        $placa = $mov->veiculo->placa ?? 'Sem Placa';
        $modelo = $mov->veiculo->modelo ?? '';
        $horaSaida = date('H:i d/m', strtotime($mov->data_hora_saida));

        $msg = "🚚 *ORDEM DE COLETA DIGITAL* | Viagem #{$mov->id}\n";
        $msg .= "Olá, *{$mov->motorista->nome}*! Sua rota está agendada para sair em breve.\n\n";

        $msg .= "📋 *DETALHES DA ESCALA*\n";
        $msg .= "• *Veículo:* {$placa} ({$modelo})\n";
        $msg .= "• *Saída Prevista:* {$horaSaida}\n\n";

        $msg .= "🗺️ *ITINERÁRIO DE PARADAS*\n";

        if ($mov->destinos && $mov->destinos->count() > 0) {
            foreach ($mov->destinos as $idx => $dest) {
                $n = $idx + 1;
                $nomeCliente = $dest->cliente->razao_social ?? ($dest->cliente->nome ?? ($dest->fornecedor->razao_social ?? 'Parada Sem Nome'));
                $endereco = $dest->endereco_completo ?: 'Endereço não informado';

                $opcoes = [
                    'nenhuma' => 'Apenas Coleta de Resíduo',
                    'colocar' => 'Deixar Caçamba Vazia',
                    'retirar' => 'Recolher Caçamba Cheia',
                    'trocar'  => 'Trocar Caçamba'
                ];
                $missao = $opcoes[$dest->tipo_operacao_caixa] ?? 'Conforme Planejamento';

                // Gera link do Google Maps por coordenadas ou endereço formatado
                if (!empty($dest->latitude) && !empty($dest->longitude)) {
                    $linkMaps = "https://www.google.com/maps/search/?api=1&query={$dest->latitude},{$dest->longitude}";
                } else {
                    $linkMaps = "https://www.google.com/maps/search/?api=1&query=" . urlencode($endereco);
                }

                $msg .= "*Parada {$n}:* {$nomeCliente}\n";
                $msg .= "• *Missão:* {$missao}\n";
                $msg .= "• *Endereço:* {$endereco}\n";
                $msg .= "📍 *Abrir no GPS:* {$linkMaps}\n\n";
            }
        } else {
            $msg .= "• Destino: " . ($mov->destino ?: 'Conforme instrução interna') . "\n\n";
        }

        $linkColeta = url('/coleta-motorista/' . $mov->id . '/destino/' . ($mov->destinos->first()->id ?? 0));
        $linkChecklist = url('/checklist/veiculo/' . $mov->id);

        $msg .= "📲 *LINKS RÁPIDOS*\n";
        $msg .= "👉 *Checklist do Veículo:* {$linkChecklist}\n";
        $msg .= "👉 *Apontamento de Balança / Caçambas:* {$linkColeta}\n\n";
        $msg .= "_Tenha uma excelente e segura rota!_";

        try {
            $resultado = app(\App\Services\ConnectApi\ConnectApiMessageService::class)->sendText(
                (int) $this->empresa_id,
                (string) $telefone,
                $msg
            );

            if (empty($resultado['success'])) {
                session()->flash('mensagem_erro', 'Falha ao enviar resumo pelo WhatsApp.');
                return redirect()->back();
            }
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Erro ao disparar mensagem: ' . $e->getMessage());
            return redirect()->back();
        }

        session()->flash('mensagem_sucesso', 'Resumo da rota enviado com sucesso para o WhatsApp do motorista!');
        return redirect()->back();
    }

    public function sincronizarRetroativoTraccar($id)
    {
        $mov = \App\Models\MovimentacaoVeiculo::with(['destinos.cliente', 'destinos.fornecedor', 'veiculo', 'motorista', 'cliente', 'fornecedor'])->find($id);

        if (!$mov || !in_array(strtolower($mov->status), ['agendado', 'iniciado'])) {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_erro', 'Viagem não encontrada ou já finalizada.');
        }

        $config = \Illuminate\Support\Facades\DB::table('traccar_configs')->where('empresa_id', $mov->empresa_id)->orderBy('id', 'desc')->first();
        if (!$config) {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_erro', 'Traccar não configurado para esta empresa.');
        }

        $baseUrl = rtrim($config->base_url, '/');
        $username = $config->mail_user_name;
        $password = $config->password;
        if (!empty($password)) {
            try { $password = \Illuminate\Support\Facades\Crypt::decryptString($password); }
            catch (\Throwable $e) { $password = $config->password; }
        }

        $imeiDispositivo = $mov->motorista->traccar_id ?? $mov->veiculo->traccar_id ?? null;
        if (!$imeiDispositivo) {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_erro', 'Nenhum ID Traccar vinculado ao motorista/veículo.');
        }

        $req = \Illuminate\Support\Facades\Http::acceptJson()->connectTimeout(5)->timeout(30);
        $req = $config->token_traccar ? $req->withToken($config->token_traccar) : $req->withBasicAuth($username, $password);

        $respDevices = $req->get("{$baseUrl}/api/devices");
        if ($respDevices->failed()) {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_erro', 'Falha ao conectar no Traccar.');
        }

        $traccarDevice = collect($respDevices->json())->first(fn($d) =>
            (string)($d['uniqueId'] ?? '') === (string)$imeiDispositivo || (string)($d['id'] ?? '') === (string)$imeiDispositivo
        );

        if (!$traccarDevice) {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_erro', 'Dispositivo não encontrado no Traccar.');
        }

        $inicioBusca = \Carbon\Carbon::parse($mov->data_hora_saida)->subHours(2)->utc()->format('Y-m-d\TH:i:s\Z');
        $fimBusca = \Carbon\Carbon::now()->utc()->format('Y-m-d\TH:i:s\Z');

        $respRoute = $req->get("{$baseUrl}/api/reports/route", [
            'deviceId' => $traccarDevice['id'], 'from' => $inicioBusca, 'to' => $fimBusca
        ]);

        $rota = collect($respRoute->json());
        if ($rota->isEmpty()) {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_warning', 'Nenhum histórico de GPS encontrado no Traccar para este horário.');
        }

        $fuso = config('app.timezone', 'America/Bahia');
        $filialObj = $mov->filial_id ? \Illuminate\Support\Facades\DB::table('filials')->where('id', $mov->filial_id)->first() : null;
        $configNotas = \Illuminate\Support\Facades\DB::table('config_notas')->where('empresa_id', $mov->empresa_id)->first();

        $latBase = $filialObj->latitude ?? $configNotas->latitude ?? null;
        $lonBase = $filialObj->longitude ?? $configNotas->longitude ?? null;

        if (isset($mov->tipo_partida) && $mov->tipo_partida == 'residencia' && $mov->motorista) {
            $latBase = $mov->motorista->latitude_residencia ?? $latBase;
            $lonBase = $mov->motorista->longitude_residencia ?? $lonBase;
        }

        $temDestinos = method_exists($mov, 'destinos') && $mov->destinos && $mov->destinos->isNotEmpty();
        $atualizouAlgo = false;

        $dist = function($l1, $ln1, $l2, $ln2) {
            if (!is_numeric($l1) || !is_numeric($ln1) || !is_numeric($l2) || !is_numeric($ln2)) return null;
            return (2 * asin(sqrt(pow(sin(deg2rad((float)$l2 - (float)$l1) / 2), 2) + cos(deg2rad((float)$l1)) * cos(deg2rad((float)$l2)) * pow(sin(deg2rad((float)$ln2 - (float)$ln1) / 2), 2)))) * 6371000;
        };

        $primeiraLatGPS = null;
        $primeiraLonGPS = null;
        $saidaBaseEncontrada = false;

        $janelaAceitacaoSaida = \Carbon\Carbon::parse($mov->data_hora_saida)->subMinutes(60);

        // =========================================================================
        // VARREDURA DE PONTOS DA ROTA (GPS)
        // =========================================================================
        foreach ($rota as $ponto) {
            if (!isset($ponto['latitude'], $ponto['longitude'])) continue;

            $latAtual = (float) $ponto['latitude'];
            $lonAtual = (float) $ponto['longitude'];
            $velocidadeKmH = ((float) ($ponto['speed'] ?? 0)) * 1.852;
            $horarioPonto = \Carbon\Carbon::parse($ponto['fixTime'])->setTimezone($fuso);

            if ($primeiraLatGPS === null) {
                $primeiraLatGPS = $latAtual;
                $primeiraLonGPS = $lonAtual;
            }

            $latRefSaida = $latBase ?? $primeiraLatGPS;
            $lonRefSaida = $lonBase ?? $primeiraLonGPS;

            // 1. SAÍDA DA BASE (Nova lógica enxuta e corrigida)
            if (!$saidaBaseEncontrada && $horarioPonto->greaterThanOrEqualTo($janelaAceitacaoSaida)) {
                $db = $dist($latAtual, $lonAtual, $latRefSaida, $lonRefSaida);
                $foraDaBase = ($db !== null && $db > 250);
                $emMovimento = ($velocidadeKmH >= 8);

                if (($foraDaBase && $emMovimento) || ($latBase === null && $emMovimento)) {
                    $saidaBaseEncontrada = true;
                    if (empty($mov->data_hora_saida_real) || \Carbon\Carbon::parse($mov->data_hora_saida_real)->greaterThan($horarioPonto->copy()->addMinutes(2))) {
                        $mov->data_hora_saida_real = $horarioPonto;
                        $mov->status = 'iniciado';
                        $atualizouAlgo = true;
                    }
                }
            }

            // 2. DESTINOS (MULTI-STOP)
            if ($temDestinos) {
                foreach ($mov->destinos as $destino) {
                    $ld = $destino->latitude ?? $destino->cliente->latitude ?? $destino->fornecedor->latitude ?? null;
                    $lnd = $destino->longitude ?? $destino->cliente->longitude ?? $destino->fornecedor->longitude ?? null;
                    $dd = $dist($latAtual, $lonAtual, $ld, $lnd);

                    if ($dd !== null && $dd <= 250 && empty($destino->data_hora_chegada)) {
                        $destino->data_hora_chegada = $horarioPonto;
                        $destino->save();
                        if (empty($mov->data_hora_chegada_cliente)) $mov->data_hora_chegada_cliente = $horarioPonto;
                        $atualizouAlgo = true;
                    }

                    if ($dd !== null && $dd > 250 && !empty($destino->data_hora_chegada) && empty($destino->data_hora_saida)) {
                        if ($horarioPonto->greaterThan(\Carbon\Carbon::parse($destino->data_hora_chegada))) {
                            $destino->data_hora_saida = $horarioPonto;
                            $destino->status = 'visitado';
                            $destino->save();
                            $mov->data_hora_saida_cliente = $horarioPonto;
                            $atualizouAlgo = true;
                        }
                    }
                }
            }
            // 3. DESTINO SIMPLES
            else {
                $ld = $mov->latitude_destino ?? $mov->cliente->latitude ?? $mov->fornecedor->latitude ?? null;
                $lnd = $mov->longitude_destino ?? $mov->cliente->longitude ?? $mov->fornecedor->longitude ?? null;
                $dd = $dist($latAtual, $lonAtual, $ld, $lnd);

                if ($dd !== null && $dd <= 250 && empty($mov->data_hora_chegada_cliente)) {
                    $mov->data_hora_chegada_cliente = $horarioPonto;
                    $atualizouAlgo = true;
                }

                if ($dd !== null && $dd > 250 && !empty($mov->data_hora_chegada_cliente) && empty($mov->data_hora_saida_cliente)) {
                    if ($horarioPonto->greaterThan(\Carbon\Carbon::parse($mov->data_hora_chegada_cliente))) {
                        $mov->data_hora_saida_cliente = $horarioPonto;
                        $atualizouAlgo = true;
                    }
                }
            }

            // 4. RETORNO PARA A BASE
            $todosVisitados = $temDestinos
                ? $mov->destinos->every(fn($d) => !empty($d->data_hora_saida) && $d->status === 'visitado')
                : !empty($mov->data_hora_saida_cliente);

            $horaUltimaSaida = $temDestinos && $mov->destinos->count() > 0
                ? \Carbon\Carbon::parse($mov->destinos->max('data_hora_saida'))
                : ($mov->data_hora_saida_cliente ? \Carbon\Carbon::parse($mov->data_hora_saida_cliente) : null);

            if ($todosVisitados && $horaUltimaSaida && $horarioPonto->greaterThan($horaUltimaSaida) && empty($mov->data_hora_chegada)) {
                $db = $dist($latAtual, $lonAtual, $latRefSaida, $lonRefSaida);
                if ($db !== null && $db <= 300) {
                    $mov->data_hora_chegada = $horarioPonto;
                    $mov->status = 'finalizado';
                    $atualizouAlgo = true;
                }
            }
        }

        // =========================================================================
        // GATILHO DE SEGURANÇA: CORREÇÃO FORÇADA DE HORÁRIOS INCOERENTES
        // =========================================================================
        $chegadaAvaliar = $temDestinos ? $mov->destinos->min('data_hora_chegada') : $mov->data_hora_chegada_cliente;
        if (!empty($chegadaAvaliar)) {
            if (empty($mov->data_hora_saida_real) || \Carbon\Carbon::parse($mov->data_hora_saida_real)->greaterThan(\Carbon\Carbon::parse($chegadaAvaliar))) {
                // CORREÇÃO: Recua matematicamente 20 minutos antes de chegar no cliente, para evitar saltos!
                $mov->data_hora_saida_real = \Carbon\Carbon::parse($chegadaAvaliar)->subMinutes(20);
                $mov->status = 'iniciado';
                $atualizouAlgo = true;
            }
        }

        $todosVisitados = $temDestinos
            ? $mov->destinos->every(fn($d) => !empty($d->data_hora_saida) && $d->status === 'visitado')
            : !empty($mov->data_hora_saida_cliente);

        if ($todosVisitados && !empty($mov->data_hora_saida_real) && empty($mov->data_hora_chegada)) {
            $ultimaSaida = $temDestinos ? $mov->destinos->max('data_hora_saida') : $mov->data_hora_saida_cliente;
            $ultimaSaidaCarbon = $ultimaSaida ? \Carbon\Carbon::parse($ultimaSaida) : null;
            if ($ultimaSaidaCarbon) {
                $mov->data_hora_chegada = $ultimaSaidaCarbon->copy()->addMinutes(30);
                $mov->status = 'finalizado';
                $atualizouAlgo = true;
            }
        }

        // =========================================================================
        // SINCRONIZAR PARADAS RETROATIVAS E KM
        // =========================================================================
        try {
            $inicioBuscaParadas = $mov->data_hora_saida_real ?: $mov->data_hora_saida;
            $fimBuscaParadas    = $mov->data_hora_chegada ?: now();

            $fromStops = \Carbon\Carbon::parse($inicioBuscaParadas)->subHours(2)->utc()->format('Y-m-d\TH:i:s\Z');
            $toStops   = \Carbon\Carbon::parse($fimBuscaParadas)->addHours(2)->utc()->format('Y-m-d\TH:i:s\Z');

            $reqStops = \Illuminate\Support\Facades\Http::acceptJson()->connectTimeout(10)->timeout(30);
            $reqStops = $config->token_traccar ? $reqStops->withToken($config->token_traccar) : $reqStops->withBasicAuth($username, $password);

            $respStops = $reqStops->get("{$baseUrl}/api/reports/stops", [
                'deviceId' => $traccarDevice['id'], 'from' => $fromStops, 'to' => $toStops
            ]);

            if ($respStops->successful()) {
                $paradasTraccar = (array) $respStops->json();
                foreach ($paradasTraccar as $parada) {
                    if (empty($parada['startTime']) || empty($parada['endTime'])) continue;

                    $duracao = (int) floor(((float) ($parada['duration'] ?? 0)) / 60000);
                    if ($duracao < 5) continue;

                    $inicioParada = \Carbon\Carbon::parse($parada['startTime'])->setTimezone($fuso);
                    $fimParada = \Carbon\Carbon::parse($parada['endTime'])->setTimezone($fuso);
                    $enderecoParada = $parada['address'] ?? 'Endereço desconhecido';

                    $existe = \Illuminate\Support\Facades\DB::table('movimentacao_historico_paradas')
                        ->where('movimentacao_veiculo_id', $mov->id)
                        ->where('data_hora_inicio', $inicioParada)
                        ->first();

                    if (!$existe) {
                        \Illuminate\Support\Facades\DB::table('movimentacao_historico_paradas')->insert([
                            'movimentacao_veiculo_id' => $mov->id,
                            'data_hora_inicio'        => $inicioParada,
                            'data_hora_fim'           => $fimParada,
                            'duracao_minutos'         => $duracao,
                            'endereco'                => $enderecoParada,
                            'latitude'                => $parada['latitude'] ?? null,
                            'longitude'               => $parada['longitude'] ?? null,
                            'created_at'              => now(),
                            'updated_at'              => now(),
                        ]);
                    } else {
                        \Illuminate\Support\Facades\DB::table('movimentacao_historico_paradas')
                            ->where('id', $existe->id)
                            ->update(['data_hora_fim' => $fimParada, 'duracao_minutos' => $duracao, 'updated_at' => now()]);
                    }
                }
                $atualizouAlgo = true;
            }
        } catch (\Throwable $e) {}

        if ($atualizouAlgo) {
            if ($mov->status === 'finalizado' && (empty($mov->km_final) || $mov->km_final <= $mov->km_inicial)) {
                $kmPercorrido = 0;
                try {
                    $fromKm = \Carbon\Carbon::parse($mov->data_hora_saida_real)->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                    $toKm = \Carbon\Carbon::parse($mov->data_hora_chegada)->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                    $reqKm = \Illuminate\Support\Facades\Http::acceptJson()->connectTimeout(5)->timeout(15);
                    $reqKm = $config->token_traccar ? $reqKm->withToken($config->token_traccar) : $reqKm->withBasicAuth($username, $password);

                    $respSummary = $reqKm->get("{$baseUrl}/api/reports/summary", [
                        'deviceId' => $traccarDevice['id'], 'from' => $fromKm, 'to' => $toKm
                    ]);

                    if ($respSummary->successful() && count($respSummary->json()) > 0) {
                        $kmPercorrido = ($respSummary->json()[0]['distance'] ?? 0) / 1000;
                    }
                } catch (\Throwable $e) {}

                if ($kmPercorrido <= 0) {
                    if ($temDestinos) {
                        $metros = 0; $antLat = $latBase; $antLon = $lonBase;
                        foreach ($mov->destinos->sortBy('ordem') as $d) {
                            if (is_numeric($d->latitude) && is_numeric($d->longitude)) {
                                $metros += (float)$dist($antLat, $antLon, $d->latitude, $d->longitude);
                                $antLat = $d->latitude; $antLon = $d->longitude;
                            }
                        }
                        $metros += (float)$dist($antLat, $antLon, $latBase, $lonBase);
                        $kmPercorrido = ($metros / 1000) * 1.25;
                    } else {
                        $distIda = (float)$dist($latBase, $lonBase, $mov->latitude_destino, $mov->longitude_destino);
                        $distVolta = (float)$dist($mov->latitude_destino, $mov->longitude_destino, $latBase, $lonBase);
                        $kmPercorrido = (($distIda + $distVolta) / 1000) * 1.25;
                    }
                }

                $mov->km_final = (int) round((float)$mov->km_inicial + max(0, $kmPercorrido));

                if ($mov->veiculo_id && $mov->km_final > 0) {
                    \Illuminate\Support\Facades\DB::table('veiculos')->where('id', $mov->veiculo_id)->update(['quilometragem' => $mov->km_final]);
                }
            }

            $mov->save();
            $mov->gerarPrePesagemAutomatica();

            if (app()->runningInConsole()) return true;
            return redirect()->back()->with('mensagem_sucesso', 'Viagem sincronizada! Horários, Paradas e Quilometragem corrigidos com precisão.');
        } else {
            if (app()->runningInConsole()) return false;
            return redirect()->back()->with('mensagem_warning', 'Aviso: GPS processado, mas não houve alteração no trajeto.');
        }
    }

    public function enviarAlertaResumoAmbiental($id)
    {
        $mov = \App\Models\MovimentacaoVeiculo::with(['veiculo', 'destinos.cliente', 'destinos.fornecedor'])->find($id);
        if (!$mov) return;

        // Só envia o alerta se a opção "Gerar MTR?" estiver marcada como SIM (1)
        if ((int) ($mov->gerar_mtr ?? 0) !== 1) {
            return;
        }

        // Procura os funcionários que têm a flag "Alertas Coleta" ativada
        $funcionarios = \App\Models\Funcionario::where('empresa_id', $mov->empresa_id)
            ->where('recebe_alerta_coleta', 1)
            ->get();

        if ($funcionarios->isEmpty()) return;

        $msg = "♻️ *RESUMO DE COLETA FINALIZADA* ♻️\n\n";
        $msg .= "🚚 *Veículo:* " . ($mov->veiculo->placa ?? 'N/A') . "\n";
        $msg .= "Viagem ID: #{$mov->id}\n";
        $msg .= "📦 *Materiais Coletados:*\n";

        $temMaterial = false;

        foreach ($mov->destinos as $dest) {
            if ($dest->is_aterro) continue; // Ignora o descarte final

            $local = $dest->cliente->razao_social ?? ($dest->fornecedor->razao_social ?? 'Local não identificado');

            // Busca os itens apontados pelo motorista na nova tabela de itens
            $itens = \Illuminate\Support\Facades\DB::table('movimentacao_destino_itens')
                ->join('produtos', 'movimentacao_destino_itens.produto_id', '=', 'produtos.id')
                ->where('movimentacao_destino_id', $dest->id)
                ->get();

            if ($itens->count() > 0) {
                $msg .= "\n📍 *" . $local . "*\n";
                foreach ($itens as $item) {
                    $msg .= " - " . (float)$item->quantidade . " " . $item->unidade . " de " . $item->nome . "\n";
                    $temMaterial = true;
                }
            } elseif (!empty($dest->quantidade_residuo) && !empty($dest->produto_residuo_id)) {
                // Fallback caso usem o método de lançamento antigo na capa do destino
                $produto = \App\Models\Produto::find($dest->produto_residuo_id);
                $nomeProd = $produto ? $produto->nome : 'Resíduo';
                $msg .= "\n📍 *" . $local . "*\n";
                $msg .= " - " . (float)$dest->quantidade_residuo . " " . $dest->unidade_residuo . " de " . $nomeProd . "\n";
                $temMaterial = true;
            }
        }

        if (!$temMaterial) {
            $msg .= "\nNenhum material foi lançado pelo motorista durante a rota.\n";
        }

        $msg .= "\nPor favor, providencie a emissão do MTR e a documentação necessária.";

        $apiService = app(\App\Services\ConnectApi\ConnectApiMessageService::class);

        foreach ($funcionarios as $func) {
            $telefone = preg_replace('/[^0-9]/', '', $func->celular ?: $func->telefone);
            if (!empty($telefone)) {
                try {
                    $apiService->sendText($mov->empresa_id, $telefone, $msg);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Erro ao enviar resumo MTR WhatsApp: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * API INTERNA: Busca os dados do ticket de pesagem para preencher a agenda
     */
    public function getDadosPesagem($id)
    {
        try {
            // Busca a capa da pesagem principal
            $pesagem = \App\Models\Pesagem::where('empresa_id', $this->empresa_id)->findOrFail($id);

            // Busca os itens/produtos detalhados na tabela tickets_pesagem
            $tickets = \DB::table('tickets_pesagem')->where('pesagem_id', $pesagem->id)->get();

            $itens = [];
            $pesoTotalCalculado = 0;

            foreach ($tickets as $t) {
                if (!empty($t->produto_id)) {
                    $itens[] = [
                        'produto_id' => $t->produto_id,
                        'quantidade' => $t->peso ?? 0,
                        'unidade'    => 'KG'
                    ];
                }
                $pesoTotalCalculado += (float)($t->peso ?? 0);
            }

            return response()->json([
                'success'       => true,
                'veiculo_id'    => $pesagem->veiculo_id,
                'motorista_id'  => $pesagem->motorista_id,
                'cliente_id'    => $pesagem->cliente_id ?? null,
                'fornecedor_id' => $pesagem->fornecedor_id ?? null,
                'peso_liquido'  => $pesagem->peso_liquido > 0 ? $pesagem->peso_liquido : $pesoTotalCalculado,
                'itens'         => $itens
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensagem' => 'Ticket de pesagem não encontrado: ' . $e->getMessage()
            ], 404);
        }
    }

    public function vincularDocumentos(Request $request, $id)
    {
        try {
            $mov = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $mov->nfe_numero = $request->nfe_numero;
            $mov->mtr_numero = $request->mtr_numero;
            $mov->save();

            return response()->json(['success' => true, 'message' => 'Documentos vinculados com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
