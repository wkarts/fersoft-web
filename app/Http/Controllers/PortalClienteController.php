<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PessoaPreCadastro;
use App\Models\SolicitacaoColeta;
use App\Models\Produto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PortalClienteController extends Controller
{
    // --- TELA DE LOGIN ---
    public function login($nome_empresa)
    {
        $nomeBusca = strtolower($nome_empresa);

        $config = \App\Models\ConfigNota::whereRaw("REPLACE(LOWER(nome_fantasia), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->orWhereRaw("REPLACE(LOWER(razao_social), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->first();

        if (!$config) {
            return abort(404, 'Link inválido ou empresa não encontrada.');
        }

        $slug = $nome_empresa;

        return view('portal_cliente.login', compact('config', 'slug'));
    }

    // --- AUTENTICAÇÃO ---
    public function autenticar(Request $request, $nome_empresa)
    {
        $nomeBusca = strtolower($nome_empresa);

        $config = \App\Models\ConfigNota::whereRaw("REPLACE(LOWER(nome_fantasia), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->orWhereRaw("REPLACE(LOWER(razao_social), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->first();

        if (!$config) {
            return abort(404, 'Link inválido ou empresa não encontrada.');
        }

        $empresa_id = $config->empresa_id;

        $cliente = \App\Models\PessoaPreCadastro::where('cpf_cnpj', $request->cpf_cnpj)
            ->where('empresa_id', $empresa_id)
            ->where('status', 'aprovado')
            ->first();

        if ($cliente && Hash::check($request->senha, $cliente->senha)) {
            Session::put('cliente_id', $cliente->id);
            Session::put('cliente_nome', $cliente->nome_completo);
            Session::put('cliente_empresa_id', $cliente->empresa_id);
            Session::put('cliente_portal_slug', $nome_empresa);

            return redirect('/portal-cliente/painel');
        }

        return back()->with('erro', 'Documento ou senha inválidos para esta empresa.');
    }

    // --- LOGOUT ---
    public function logout()
    {
        $slug = Session::get('cliente_portal_slug');
        $empresaId = Session::get('cliente_empresa_id');

        if (empty($slug) && !empty($empresaId)) {
            $config = \App\Models\ConfigNota::where('empresa_id', $empresaId)->first();
            $nomeBase = $config->nome_fantasia ?? $config->razao_social ?? '';
            $slug = strtolower(str_replace(' ', '', $nomeBase));
        }

        Session::forget(['cliente_id', 'cliente_nome', 'cliente_empresa_id', 'cliente_portal_slug']);

        if (!empty($slug)) {
            return redirect("/portal-cliente/{$slug}/login");
        }

        return redirect('/');
    }

    // --- HELPER: BUSCAR OU CRIAR PARÂMETROS PADRÃO DA EMPRESA ---
    private function obterParametrosEmpresa($empresa_id)
    {
        if (!Schema::hasTable('parametros_agendamento_logistica')) {
            return (object) [
                'vagas_manha' => 3,
                'horario_manha_inicio' => '08:00:00',
                'horario_manha_fim' => '12:00:00',
                'vagas_tarde' => 3,
                'horario_tarde_inicio' => '13:30:00',
                'horario_tarde_fim' => '17:30:00',
                'tempo_franquia_horas' => 4.00,
                'valor_taxa_hora_extra' => 150.00,
                'dias_atendimento' => '1,2,3,4,5',
                'antecedencia_minima_horas' => 2,
                'tipos_veiculos_disponiveis' => 'Caminhão Roll-on/Roll-off,Caminhão Poliguindaste (Brooks),Caminhão Munck,Caminhão Baú / Carga Seca',
                'observacoes_regras' => 'Caso o veículo permaneça retido nas dependências do parceiro além do limite da franquia do turno, será cobrada taxa adicional por hora excedente.'
            ];
        }

        $param = DB::table('parametros_agendamento_logistica')->where('empresa_id', $empresa_id)->first();
        if (!$param) {
            DB::table('parametros_agendamento_logistica')->insert([
                'empresa_id' => $empresa_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $param = DB::table('parametros_agendamento_logistica')->where('empresa_id', $empresa_id)->first();
        }
        return $param;
    }

    // --- PAINEL PRINCIPAL DO PARCEIRO ---
    // Adicione a importação do Request lá no topo do arquivo se não tiver:
    // use Illuminate\Http\Request;

    public function painel(Request $request)
    {
        if (!Session::has('cliente_id')) {
            return redirect('/');
        }

        $cliente_id = Session::get('cliente_id');
        $empresa_id = Session::get('cliente_empresa_id');

        $cliente = PessoaPreCadastro::find($cliente_id);
        $cnpjCpfCliente = preg_replace('/[^0-9]/', '', $cliente->cpf_cnpj ?? '');

        // --- CAPTURA DOS FILTROS ---
        $filtroDataIni = $request->input('data_inicial');
        $filtroDataFim = $request->input('data_final');
        $filtroBusca   = $request->input('busca'); // Serve para Ticket ou Lote

        // 1. DADOS BASE E NOTIFICAÇÕES
        $coletas = SolicitacaoColeta::where('pessoa_id', $cliente_id)->orderBy('id', 'desc')->get();
        $config  = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $funcionarios = \App\Models\Funcionario::where('empresa_id', $empresa_id)->orderBy('nome')->get();
        $parametros = $this->obterParametrosEmpresa($empresa_id);
        $tiposVeiculos = array_filter(array_map('trim', explode(',', $parametros->tipos_veiculos_disponiveis ?? '')));

        // 2. BUSCAR O CADASTRO REAL DO ERP
        $clienteErpId = null;
        $fornecedorErpId = null;

        if (!empty($cnpjCpfCliente)) {
            $clienteErp = \App\Models\Cliente::where('empresa_id', $empresa_id)
                ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$cnpjCpfCliente])
                ->first();
            if ($clienteErp) $clienteErpId = $clienteErp->id;

            $fornecedorErp = \App\Models\Fornecedor::where('empresa_id', $empresa_id)
                ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$cnpjCpfCliente])
                ->first();
            if ($fornecedorErp) $fornecedorErpId = $fornecedorErp->id;
        }

        // 3. LOTES DE RASTREABILIDADE (COM FILTROS)
        $lotesRastreabilidade = \App\Models\ProducaoLote::where('empresa_id', $empresa_id)
            ->where(function($q) use ($cliente_id, $cnpjCpfCliente) {
                $q->where('fornecedor_id', $cliente_id)
                    ->orWhere('cliente_origem_id', $cliente_id);
                if (!empty($cnpjCpfCliente)) {
                    $q->orWhere('cnpj_raiz', substr($cnpjCpfCliente, 0, 8));
                }
            })
            ->when($filtroBusca, function($q) use ($filtroBusca) {
                $q->where('codigo_lote', 'like', "%{$filtroBusca}%");
            })
            ->when($filtroDataIni, function($q) use ($filtroDataIni) {
                $q->whereDate('created_at', '>=', $filtroDataIni);
            })
            ->when($filtroDataFim, function($q) use ($filtroDataFim) {
                $q->whereDate('created_at', '<=', $filtroDataFim);
            })
            ->with(['produtoOrigem', 'cortes.produto', 'ticketsSaida.pesagem.cliente', 'ticketsSaida.produto'])
            ->orderBy('id', 'desc')
            ->get();

        // 4. PESAGENS DO PARCEIRO (COM FILTROS)
        $pesagens = collect();
        if ($clienteErpId || $fornecedorErpId) {
            $pesagens = \App\Models\Pesagem::with(['veiculo', 'tickets.produto', 'compra', 'venda'])
                ->where('empresa_id', $empresa_id)
                ->where(function($q) use ($clienteErpId, $fornecedorErpId) {
                    if ($clienteErpId) $q->orWhere('cliente_id', $clienteErpId);
                    if ($fornecedorErpId) $q->orWhere('fornecedor_id', $fornecedorErpId);
                })
                ->when($filtroBusca, function($q) use ($filtroBusca) {
                    $q->where(function($sub) use ($filtroBusca) {
                        $sub->where('id', $filtroBusca)
                            ->orWhere('token', 'like', "%{$filtroBusca}%");
                    });
                })
                ->when($filtroDataIni, function($q) use ($filtroDataIni) {
                    $q->whereDate('dt_registro', '>=', $filtroDataIni);
                })
                ->when($filtroDataFim, function($q) use ($filtroDataFim) {
                    $q->whereDate('dt_registro', '<=', $filtroDataFim);
                })
                ->orderByDesc('dt_registro')
                ->take(30)
                ->get();
        }

        // 5. CALENDÁRIO DA AGENDA (BLINDADO)
        $mesDesejado = request('mes', date('Y-m'));
        $dt = \Carbon\Carbon::parse($mesDesejado . '-01');
        $dataInicio = $dt->copy()->startOfMonth()->format('Y-m-d');
        $dataFim    = $dt->copy()->endOfMonth()->format('Y-m-d');

        $agendaColetas = collect();
        if ($clienteErpId || $fornecedorErpId) {
            $agendaColetas = \App\Models\MovimentacaoVeiculo::with(['veiculo', 'motorista', 'cliente', 'destinos.cliente', 'destinos.fornecedor'])
                ->where('empresa_id', $empresa_id)
                ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
                ->where(function($query) use ($clienteErpId, $fornecedorErpId) {
                    // Força o isolamento dos parênteses no SQL para não vazar clientes
                    if ($clienteErpId) {
                        $query->orWhere('cliente_id', $clienteErpId);
                    }
                    if ($fornecedorErpId) {
                        $query->orWhere('fornecedor_id', $fornecedorErpId);
                    }

                    $query->orWhereHas('destinos', function($subQuery) use ($clienteErpId, $fornecedorErpId) {
                        $subQuery->where(function($q3) use ($clienteErpId, $fornecedorErpId) {
                            if ($clienteErpId) {
                                $q3->orWhere('cliente_id', $clienteErpId);
                            }
                            if ($fornecedorErpId) {
                                $q3->orWhere('fornecedor_id', $fornecedorErpId);
                            }
                        });
                    });
                })->get();
        }

        $coletasPorData = [];
        foreach ($agendaColetas as $c) {
            $dataKey = \Carbon\Carbon::parse($c->data_hora_saida)->format('Y-m-d');
            $coletasPorData[$dataKey][] = $c;
        }

        $primeiroDiaSemana = $dt->copy()->startOfMonth()->dayOfWeek;
        $diasNoMes = $dt->daysInMonth;
        $mesAnterior = $dt->copy()->subMonth()->format('Y-m');
        $mesProximo  = $dt->copy()->addMonth()->format('Y-m');

        // 6. CAÇAMBAS E RESÍDUOS (MANTIDO IGUAL)
        $cacambasProdutos = Produto::where('empresa_id', $empresa_id)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'inativo'), function($q) { $q->where('inativo', 0); })
            ->when(!empty($parametros->categoria_cacambas_id), function($q) use ($parametros) {
                $q->where('categoria_id', $parametros->categoria_cacambas_id);
            }, function($q) {
                $q->where(function ($sub) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'locacao')) $sub->orWhere('locacao', 1);
                    if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'tipo_locacao')) $sub->orWhere('tipo_locacao', 1);
                    $sub->orWhere('nome', 'LIKE', '%CAÇAMBA%')->orWhere('nome', 'LIKE', '%CONTAINER%');
                });
            })->orderBy('nome')->get();

        $produtosResiduos = Produto::where('empresa_id', $empresa_id)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'inativo'), function($q) { $q->where('inativo', 0); })
            ->when(!empty($parametros->categoria_residuos_id), function($q) use ($parametros) {
                $q->where('categoria_id', $parametros->categoria_residuos_id);
            }, function($q) { $q->whereRaw('1 = 0'); })->orderBy('nome')->get();

        // 7. CONTRATOS E MEDIÇÕES (AGORA BUSCANDO AS MÚLTIPLAS NOTAS DIRETAMENTE)
        $medicoes = collect();
        if ($cliente && $cliente->exibe_contratos && $clienteErpId) {

            // Busca as medições
            $medicoesResult = \DB::table('faturas_engenharia')
                ->join('contratos_engenharia', 'faturas_engenharia.contrato_eng_id', '=', 'contratos_engenharia.id')
                ->where('contratos_engenharia.cliente_id', $clienteErpId)
                ->where('faturas_engenharia.empresa_id', $empresa_id)
                ->select('faturas_engenharia.*', 'contratos_engenharia.numero_contrato')
                ->orderBy('faturas_engenharia.created_at', 'desc')
                ->get();

            // Anexa as notas fiscais oficiais correspondentes a cada medição
            foreach($medicoesResult as $med) {
                // Em vez de ler apenas o vínculo simples, buscamos as Notas Fiscais reais e aprovadas
                $notasOficiais = \App\Models\Nfse::whereIn('id', function($query) use ($med) {
                    $query->selectRaw("CAST(REPLACE(chave_nfse, 'RASCUNHO-NFSE-', '') AS UNSIGNED)")
                        ->from('fatura_eng_notas')
                        ->where('fatura_eng_id', $med->id);
                })->where('estado', 'aprovado')->get();

                $med->notas_fiscais = $notasOficiais;

                // LÓGICA DO STATUS: Se foi aprovada, verifica se tem NF-e
                if ($med->status_aprovacao == 'aprovado') {
                    if (count($notasOficiais) > 0 || !empty($med->numero_nfse)) {
                        $med->status_final_portal = 'concluido';
                    } else {
                        $med->status_final_portal = 'aguardando_nfse';
                    }
                } else {
                    $med->status_final_portal = $med->status_aprovacao; // pendente ou rejeitado
                }
                $medicoes->push($med);
            }
        }

        return view('portal_cliente.painel', compact(
            'cliente', 'coletas', 'config', 'funcionarios', 'parametros',
            'tiposVeiculos', 'cacambasProdutos', 'produtosResiduos',
            'lotesRastreabilidade', 'pesagens', 'coletasPorData',
            'primeiroDiaSemana', 'diasNoMes', 'dt', 'mesAnterior', 'mesProximo',
            'medicoes'
        ));
    }


    // --- API AJAX: CONSULTAR VAGAS DISPONÍVEIS EM UMA DATA ---
    public function consultarDisponibilidade(Request $request)
    {
        if (!Session::has('cliente_empresa_id')) {
            return response()->json(['erro' => 'Sessão expirada'], 401);
        }

        $empresa_id = Session::get('cliente_empresa_id');
        $data = $request->get('data', date('Y-m-d'));
        $param = $this->obterParametrosEmpresa($empresa_id);

        $diaSemana = Carbon::parse($data)->dayOfWeek; // 0 (Dom) a 6 (Sab)
        $diasPermitidos = array_map('trim', explode(',', $param->dias_atendimento ?? '1,2,3,4,5'));

        if (!in_array((string) $diaSemana, $diasPermitidos, true)) {
            return response()->json([
                'dia_util' => false,
                'mensagem' => 'Não realizamos agendamentos de frota neste dia da semana.',
                'manha' => ['total' => 0, 'ocupadas' => 0, 'disponiveis' => 0],
                'tarde' => ['total' => 0, 'ocupadas' => 0, 'disponiveis' => 0],
            ]);
        }

        $statusAtivos = ['pendente', 'agendado', 'aprovado', 'iniciado', 'em_analise'];

        $ocupadasManha = SolicitacaoColeta::where('empresa_id', $empresa_id)
            ->where('tipo_transporte', 'frota')
            ->whereDate('data_reserva', $data)
            ->where('turno', 'manha')
            ->whereIn('status', $statusAtivos)
            ->count();

        $ocupadasTarde = SolicitacaoColeta::where('empresa_id', $empresa_id)
            ->where('tipo_transporte', 'frota')
            ->whereDate('data_reserva', $data)
            ->where('turno', 'tarde')
            ->whereIn('status', $statusAtivos)
            ->count();

        $dispManha = max(0, (int) $param->vagas_manha - $ocupadasManha);
        $dispTarde = max(0, (int) $param->vagas_tarde - $ocupadasTarde);

        return response()->json([
            'dia_util' => true,
            'data_formatada' => Carbon::parse($data)->format('d/m/Y'),
            'franquia_horas' => number_format((float) $param->tempo_franquia_horas, 1, ',', '.'),
            'taxa_extra' => number_format((float) $param->valor_taxa_hora_extra, 2, ',', '.'),
            'manha' => [
                'total'       => (int) $param->vagas_manha,
                'ocupadas'    => $ocupadasManha,
                'disponiveis' => $dispManha,
                'horario'     => substr($param->horario_manha_inicio, 0, 5) . ' às ' . substr($param->horario_manha_fim, 0, 5),
            ],
            'tarde' => [
                'total'       => (int) $param->vagas_tarde,
                'ocupadas'    => $ocupadasTarde,
                'disponiveis' => $dispTarde,
                'horario'     => substr($param->horario_tarde_inicio, 0, 5) . ' às ' . substr($param->horario_tarde_fim, 0, 5),
            ]
        ]);
    }

    // --- SALVAR A COLETA COM FOTOS, EPIs E RESERVA DE TURNO ---
    public function storeColeta(Request $request)
    {
        if (!Session::has('cliente_id')) {
            return redirect('/');
        }

        $empresa_id  = Session::get('cliente_empresa_id');
        $cliente_id  = Session::get('cliente_id');
        $nomeCliente = Session::get('cliente_nome');
        $param       = $this->obterParametrosEmpresa($empresa_id);

        if ($request->tipo_transporte === 'frota') {
            if (empty($request->data_reserva) || empty($request->turno)) {
                return back()->with('erro', 'Por favor, selecione uma data no calendário e o turno desejado.')->withInput();
            }

            $statusAtivos = ['pendente', 'agendado', 'aprovado', 'iniciado', 'em_analise'];
            $ocupadas = SolicitacaoColeta::where('empresa_id', $empresa_id)
                ->where('tipo_transporte', 'frota')
                ->whereDate('data_reserva', $request->data_reserva)
                ->where('turno', $request->turno)
                ->whereIn('status', $statusAtivos)
                ->count();

            $limiteTurno = ($request->turno === 'manha') ? (int) $param->vagas_manha : (int) $param->vagas_tarde;

            if ($ocupadas >= $limiteTurno) {
                return back()->with('erro', '⚠️ Ops! As vagas para este turno acabaram de ser preenchidas.')->withInput();
            }
        }

        $caminhosFotos = [];
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('coletas', 'public');
                $caminhosFotos[] = $path;
            }
        }

        $produtoResiduoNome = $request->material;
        if ($request->filled('produto_residuo_id')) {
            $prodObj = Produto::find($request->produto_residuo_id);
            if ($prodObj) {
                $produtoResiduoNome = $prodObj->nome . ($request->filled('material_complemento') ? ' - ' . $request->material_complemento : '');
            }
        }

        $cacambaNome = null;
        if ($request->filled('produto_cacamba_id')) {
            $cacObj = Produto::find($request->produto_cacamba_id);
            if ($cacObj) $cacambaNome = $cacObj->nome;
        }

        $turnoFormatado = '';
        if ($request->tipo_transporte === 'frota' && $request->filled('data_reserva')) {
            $faixaHorario = $request->turno === 'manha'
                ? substr($param->horario_manha_inicio, 0, 5) . ' às ' . substr($param->horario_manha_fim, 0, 5)
                : substr($param->horario_tarde_inicio, 0, 5) . ' às ' . substr($param->horario_tarde_fim, 0, 5);
            $turnoLabel = $request->turno === 'manha' ? 'MANHÃ' : 'TARDE';
            $turnoFormatado = Carbon::parse($request->data_reserva)->format('d/m/Y') . " - Turno {$turnoLabel} ({$faixaHorario})";
        }

        $episSelecionados = '';
        if ($request->has('epi_obrigatorio') && is_array($request->epi_obrigatorio)) {
            $episSelecionados = "\n- EPIs Obrigatórios no Local: " . implode(', ', $request->epi_obrigatorio);
        }

        $obsFinal = trim((string) $request->observacao);
        if ($request->tipo_transporte === 'frota') {
            $obsFinal .= "\n\n[DADOS DO AGENDAMENTO DE FROTA]"
                . "\n- Data/Turno Reservado: {$turnoFormatado}"
                . "\n- Tipo de Veículo: " . ($request->tipo_veiculo ?: 'Padrão')
                . "\n- Equipamento/Caçamba: " . ($cacambaNome ? "{$request->qtd_cacambas}x {$cacambaNome}" : 'Não solicitado')
                . "\n- Peso Estimado: " . ($request->peso_estimado ? "{$request->peso_estimado} {$request->unidade_peso}" : 'Não informado')
                . "\n- Franquia de Permanência: {$param->tempo_franquia_horas}h (Taxa excedente: R$ " . number_format($param->valor_taxa_hora_extra, 2, ',', '.') . "/h)"
                . $episSelecionados;
        } elseif ($request->tipo_transporte === 'cliente' && $request->filled('data_hora_prevista')) {
            $obsFinal .= "\n\n[ENTREGA NA PORTARIA PELO CLIENTE]\n- Data/Hora prevista: " . date('d/m/Y H:i', strtotime($request->data_hora_prevista));
            if ($request->filled('funcionario_id')) {
                $func = \App\Models\Funcionario::find($request->funcionario_id);
                if ($func) $obsFinal .= "\n- Procurar por: " . $func->nome;
            }
        }

        $dadosInsert = [
            'empresa_id'           => $empresa_id,
            'pessoa_id'            => $cliente_id,
            'material'             => $produtoResiduoNome ?: 'Resíduo / Coleta',
            'dias_disponiveis'     => $turnoFormatado ?: ($request->dias_disponiveis ?? 'A combinar'),
            'observacao'           => trim($obsFinal),
            'fotos'                => json_encode($caminhosFotos),
            'tipo_transporte'      => $request->tipo_transporte,
            'status'               => 'pendente',
            'data_reserva'         => $request->data_reserva ?: null,
            'turno'                => $request->turno ?: null,
            'tipo_veiculo'         => $request->tipo_veiculo ?: null,
            'produto_residuo_id'   => $request->produto_residuo_id ?: null,
            'produto_cacamba_id'   => $request->produto_cacamba_id ?: null,
            'qtd_cacambas'         => $request->qtd_cacambas ?: 1,
            'peso_estimado'        => !empty($request->peso_estimado) ? str_replace(',', '.', $request->peso_estimado) : null,
            'unidade_peso'         => $request->unidade_peso ?? 'KG',
            'tempo_franquia_horas' => $param->tempo_franquia_horas ?? 4,
            'valor_taxa_extra'     => $param->valor_taxa_hora_extra ?? 150,
            'aceitou_termo_taxa'   => 1
        ];

        $coleta = SolicitacaoColeta::create($dadosInsert);

        try {
            $funcionariosAviso = \App\Models\Funcionario::where('empresa_id', $empresa_id)
                ->where(function ($q) {
                    $q->where('recebe_alerta_coleta', 1);
                    if (Schema::hasColumn('funcionarios', 'recebe_alerta_frota')) {
                        $q->orWhere('recebe_alerta_frota', 1);
                    }
                })->get();

            foreach ($funcionariosAviso as $funcAviso) {
                $numero = preg_replace('/[^0-9]/', '', $funcAviso->celular ?? $funcAviso->telefone);
                if (strlen($numero) >= 10) {
                    if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                    $mensagem = "🔔 *Nova Reserva de Agendamento (Portal do Parceiro)*\n\n";
                    $mensagem .= "👤 *Parceiro:* {$nomeCliente}\n";
                    $mensagem .= "📦 *Produto/Resíduo:* {$dadosInsert['material']}\n";

                    if ($request->tipo_transporte === 'frota') {
                        $mensagem .= "📅 *Data/Turno:* {$turnoFormatado}\n";
                        $mensagem .= "🚛 *Veículo:* " . ($request->tipo_veiculo ?: 'Padrão') . "\n";
                        if ($cacambaNome) {
                            $mensagem .= "🗑️ *Caçamba:* {$request->qtd_cacambas}x {$cacambaNome}\n";
                        }
                        if ($request->filled('peso_estimado')) {
                            $mensagem .= "⚖️ *Peso Estimado:* {$request->peso_estimado} {$request->unidade_peso}\n";
                        }
                    } else {
                        $mensagem .= "🏢 *Modalidade:* Entrega na Portaria\n";
                    }

                    $mensagem .= "\n⚠️ _Vaga pré-reservada no calendário. Acesse o ERP para aprovar ou rejeitar._";

                    $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numero, $mensagem, $empresa_id);
                    } elseif (method_exists($instanciaWhats, 'send')) {
                        $instanciaWhats->send($numero, $mensagem);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Erro Whatsapp Notificação Equipe (Coleta): " . $e->getMessage());
        }

        return back()->with('sucesso', 'Solicitação enviada e vaga pré-reservada com sucesso! Nossa equipe confirmará a programação em breve.');
    }

    // --- TELA DE ESQUECI A SENHA ---
    public function esqueciSenha($slug)
    {
        return view('portal_cliente.esqueci_senha', compact('slug'));
    }

    // --- GERAR NOVA SENHA E ENVIAR ---
    public function recuperarSenha(Request $request, $slug)
    {
        $docDigitado = preg_replace('/[^0-9]/', '', $request->cpf_cnpj);

        $cliente = PessoaPreCadastro::where('status', 'aprovado')
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$docDigitado])
            ->first();

        if (!$cliente) {
            return back()->with('erro', 'Documento não encontrado ou cadastro ainda não aprovado.');
        }

        $novaSenha = rand(100000, 999999);
        $cliente->senha = bcrypt($novaSenha);
        $cliente->save();

        try {
            $numero = preg_replace('/[^0-9]/', '', $cliente->telefone);
            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;
                $mensagem = "Olá, *{$cliente->nome_completo}*!\n\nSua nova senha de acesso ao portal de coletas é: *{$novaSenha}*";

                $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                if (method_exists($instanciaWhats, 'sendMessage')) {
                    $instanciaWhats->sendMessage($numero, $mensagem, $cliente->empresa_id);
                } elseif (method_exists($instanciaWhats, 'send')) {
                    $instanciaWhats->send($numero, $mensagem);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Erro Whatsapp Recuperação de Senha: " . $e->getMessage());
        }

        return redirect("/portal-cliente/{$slug}/login")->with('sucesso', 'Uma nova senha foi gerada e enviada para o seu WhatsApp!');
    }

    // =========================================================================
    // ⚙️ PAINEL INTERNO DO ERP: PARÂMETROS DE AGENDAMENTO DA LOGÍSTICA
    // =========================================================================
    public function parametrosIndex()
    {
        $user_logged = session('user_logged');
        $empresa_id = $user_logged['empresa_id'] ?? $user_logged['empresa'] ?? 1;

        $parametros = $this->obterParametrosEmpresa($empresa_id);
        $title = "Parâmetros de Agendamento e SLA (Portal do Parceiro)";

        return view('portal_cliente.parametros_logistica', compact('parametros', 'title'));
    }

    public function parametrosSalvar(Request $request)
    {
        $user_logged = session('user_logged');
        $empresa_id = $user_logged['empresa_id'] ?? $user_logged['empresa'] ?? 1;

        $dias = is_array($request->dias_atendimento)
            ? implode(',', $request->dias_atendimento)
            : ($request->dias_atendimento ?? '1,2,3,4,5');

        $dados = [
            'vagas_manha'                => (int) ($request->vagas_manha ?? 3),
            'horario_manha_inicio'       => $request->horario_manha_inicio ?: '08:00:00',
            'horario_manha_fim'          => $request->horario_manha_fim ?: '12:00:00',
            'vagas_tarde'                => (int) ($request->vagas_tarde ?? 3),
            'horario_tarde_inicio'       => $request->horario_tarde_inicio ?: '13:30:00',
            'horario_tarde_fim'          => $request->horario_tarde_fim ?: '17:30:00',
            'tempo_franquia_horas'       => (float) str_replace(',', '.', $request->tempo_franquia_horas ?? 4),
            'valor_taxa_hora_extra'      => (float) str_replace(',', '.', $request->valor_taxa_hora_extra ?? 150),
            'dias_atendimento'           => $dias,
            'antecedencia_minima_horas'  => (int) ($request->antecedencia_minima_horas ?? 2),
            'tipos_veiculos_disponiveis' => $request->tipos_veiculos_disponiveis ?: 'Caminhão Roll-on/Roll-off,Caminhão Poliguindaste (Brooks)',

            'notificar_atraso_whatsapp'  => (int) ($request->notificar_atraso_whatsapp ?? 1),
            'minutos_tolerancia_atraso'  => (int) ($request->minutos_tolerancia_atraso ?? 20),
            'funcionario_gestor_frota_id'=> $request->funcionario_gestor_frota_id ?: null,

            'categoria_residuos_id'      => $request->categoria_residuos_id ?: null,
            'categoria_cacambas_id'      => $request->categoria_cacambas_id ?: null,

            'observacoes_regras'         => $request->observacoes_regras,
            'updated_at'                 => now(),
        ];

        DB::table('parametros_agendamento_logistica')->updateOrInsert(
            ['empresa_id' => $empresa_id],
            $dados
        );

        session()->flash('mensagem_sucesso', 'Parâmetros de agendamento e regras salvos com sucesso!');
        return redirect()->back();
    }

    public function downloadMtrSaida($ticketId)
    {
        if (!Session::has('cliente_id')) {
            return redirect('/');
        }
        $empresa_id = Session::get('cliente_empresa_id');

        $ticket = \App\Models\TicketPesagem::with(['pesagem.cliente', 'produto', 'lote'])
            ->where('empresa_id', $empresa_id)
            ->findOrFail($ticketId);

        $relatorioController = app(\App\Http\Controllers\RelatorioController::class);

        if ($ticket->pesagem_id) {
            return $relatorioController->imprimirPesagemA4Whats($ticket->pesagem_id, $empresa_id);
        }

        return back()->with('erro', 'Documento MTR não disponível para este registo.');
    }

    public function baixarTicketPesagem($id)
    {
        if (!Session::has('cliente_id')) {
            return redirect('/');
        }

        $empresa_id = Session::get('cliente_empresa_id');

        try {
            // Busca a pesagem validando a empresa e o ID
            $pesagem = \App\Models\Pesagem::with(['veiculo', 'tickets.produto', 'cliente', 'fornecedor', 'motorista', 'filial'])
                ->where('empresa_id', $empresa_id)
                ->findOrFail($id);

            $resumo = \App\Support\PesagemReportCalculator::summarize($pesagem);

            // Identifica se é Filial ou Matriz (config_notas)
            $emitente = null;
            if (!empty($pesagem->filial_id)) {
                $emitente = \App\Models\Filial::where('id', $pesagem->filial_id)->first();
            }
            if (!$emitente) {
                $emitente = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
            }

            $viewData = compact('pesagem', 'resumo', 'emitente');

            // Renderiza o PDF A4 da Opção 4 utilizando o Dompdf
            $dompdf = new \Dompdf\Dompdf(['enable_remote' => true]);
            $dompdf->loadHtml(view('relatorios.pesagem_opcao_4', $viewData)->render());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="ticket-pesagem-' . $pesagem->id . '.pdf"')
                ->header('Cache-Control', 'no-cache, must-revalidate');

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar PDF da pesagem no portal do cliente: ' . $e->getMessage());
            return redirect()->back()->with('erro', 'Não foi possível gerar o PDF do ticket de pesagem.');
        }
    }

    public function aprovarMedicao($id)
    {
        try {
            // Valida se o cliente da sessão é realmente dono desta fatura (Segurança)
            $clienteId = session('cliente_id');

            \DB::table('faturas_engenharia')
                ->where('id', $id)
                ->update(['status_aprovacao' => 'aprovado']);

            return response()->json(['success' => true, 'message' => 'Medição (Fatura) aprovada com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao aprovar a medição.'], 500);
        }
    }

    public function rejeitarMedicao(Request $request, $id)
    {
        try {
            \DB::table('faturas_engenharia')
                ->where('id', $id)
                ->update([
                    'status_aprovacao' => 'rejeitado',
                    'motivo_rejeicao'  => $request->input('motivo')
                ]);

            return response()->json(['success' => true, 'message' => 'Medição (Fatura) rejeitada. A equipe será notificada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao rejeitar a medição.'], 500);
        }
    }

    public function imprimirContrato($id)
    {
        if (!\Illuminate\Support\Facades\Session::has('cliente_id')) {
            return redirect('/');
        }

        $empresaId = \Illuminate\Support\Facades\Session::get('cliente_empresa_id');
        $clienteId = \Illuminate\Support\Facades\Session::get('cliente_id');

        $pessoa = \App\Models\PessoaPreCadastro::find($clienteId);
        $cpfCnpjLimpo = $pessoa ? preg_replace('/[^0-9]/', '', $pessoa->cpf_cnpj) : '';

        $clienteErp = \App\Models\Cliente::where('empresa_id', $empresaId)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$cpfCnpjLimpo])
            ->first();

        if (!$clienteErp) {
            return abort(403, 'Acesso negado. Cliente não vinculado no ERP.');
        }

        // 🚀 Mudamos o nome para $contratoObj
        $contratoObj = \App\Models\ContratoEngenharia::where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteErp->id)
            ->with(['cliente.cidade', 'filial', 'itens.servico', 'itens.produto'])
            ->findOrFail($id);

        $equipeAlocada = \Illuminate\Support\Facades\DB::table('contrato_eng_funcionarios as cf')
            ->join('funcionarios as f', 'f.id', '=', 'cf.funcionario_id')
            ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
            ->where('cf.contrato_eng_id', $contratoObj->id)
            ->where('cf.status', 'Ativo')
            ->select('f.nome', \Illuminate\Support\Facades\DB::raw("COALESCE(fn.nome, 'Operacional') as cargo_nome"))
            ->get();

        $empresa = \Illuminate\Support\Facades\DB::table('empresas')->where('id', $empresaId)->first();
        $configNota = \Illuminate\Support\Facades\DB::table('config_notas')->where('empresa_id', $empresaId)->first();

        $numeroExibicao = !empty($contratoObj->numero_contrato) ? $contratoObj->numero_contrato : str_pad($contratoObj->id, 4, '0', STR_PAD_LEFT);

        return view('contratos.print', [
            'dadosContrato'  => $contratoObj, // 🚀 AQUI: Garante o envio correto
            'equipeAlocada'  => $equipeAlocada,
            'empresa'        => $empresa,
            'configNota'     => $configNota,
            'numeroExibicao' => $numeroExibicao,
            'title'          => 'Contrato #' . $numeroExibicao
        ]);
    }
}
