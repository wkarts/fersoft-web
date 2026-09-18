<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agendamento;
use App\Models\Funcionario;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ConfigCaixa;
use App\Models\Cidade;
use App\Models\Servico;
use App\Models\ItemAgendamento;
use App\Models\CategoriaServico;
use App\Models\Usuario;
use App\Models\VendaCaixa;
use App\Models\Certificado;
use App\Models\AberturaCaixa;
use App\Models\Acessor;
use App\Models\Pais;
use App\Models\GrupoCliente;
use App\Models\ItemVendaCaixa;
use App\Models\Tarefa;
use Carbon\Carbon;

class AgendamentoController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }


// --- TELA PRINCIPAL (INDEX) ---
    public function index(){
        $usuario_id = session('user_logged')['id'];
        $usuario = Usuario::find($usuario_id);

        $funcionarioLogado = Funcionario::where('usuario_id', $usuario_id)->first();
        $funcionarioIdLogado = $funcionarioLogado->id ?? 0;

        $funcionarios = Funcionario::where('funcionarios.empresa_id', $this->empresa_id)
            ->select('funcionarios.*')
            ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
            ->get();

        $clientes = Cliente::where('empresa_id', $this->empresa_id)->where('inativo', false)->get();
        $servicos = Servico::where('empresa_id', $this->empresa_id)->get();
        $categorias = CategoriaServico::where('empresa_id', $this->empresa_id)->get();

        // 1. Métricas da Ordem de Serviço
        $totalPendentes   = Agendamento::where('empresa_id', $this->empresa_id)->where('status', 0)->count();
        $totalFinalizados = Agendamento::where('empresa_id', $this->empresa_id)->where('status', 1)->count();
        $totalExecucao    = Agendamento::where('empresa_id', $this->empresa_id)->where('status', 3)->count();
        $totalCancelados  = Agendamento::where('empresa_id', $this->empresa_id)->where('status', 2)->count();

        // 2. Query de Tarefas respeitando Trava Admin vs Usuário Comum
        $queryTarefas = Tarefa::where('empresa_id', $this->empresa_id);
        if (!$usuario->adm) {
            $queryTarefas->where('funcionario_id', $funcionarioIdLogado);
        }
        $todasTarefas = $queryTarefas->get();

        $tarefasPendentes  = $todasTarefas->where('status', 'pendente')->count();
        $tarefasAndamento  = $todasTarefas->where('status', 'em_andamento')->count();
        $tarefasConcluidas = $todasTarefas->where('status', 'concluida')->count();

        $tarefasAtrasadas  = $todasTarefas->filter(function($t) {
            if ($t->status == 'concluida') return false;
            if (empty($t->data_limite)) return false;

            $dataApenas = Carbon::parse($t->data_limite)->format('Y-m-d');
            $limite = Carbon::parse($dataApenas . ' ' . ($t->hora_limite ?: '23:59:59'));

            return Carbon::now()->greaterThan($limite);
        });

        return view('agendamentos/view')
            ->with('fullcalendar', true)
            ->with('funcionarios', $funcionarios)
            ->with('clientes', $clientes)
            ->with('servicos', $servicos)
            ->with('categorias', $categorias)
            ->with('totalPendentes', $totalPendentes)
            ->with('totalFinalizados', $totalFinalizados)
            ->with('totalExecucao', $totalExecucao)
            ->with('totalCancelados', $totalCancelados)
            ->with('tarefasPendentes', $tarefasPendentes)
            ->with('tarefasAndamento', $tarefasAndamento)
            ->with('tarefasConcluidas', $tarefasConcluidas)
            ->with('tarefasAtrasadas', $tarefasAtrasadas)
            ->with('ehAdmin', $usuario->adm)
            ->with('title', 'Agendamentos e Tarefas');
    }

// --- BUSCA DINÂMICA DE EVENTOS (ALL) ---
    public function all(Request $request)
    {
        $userLogged = session('user_logged');
        $usuario_id = $userLogged['id'] ?? $userLogged['usuario_id'] ?? null;
        $usuario = \App\Models\Usuario::find($usuario_id);

        // Identificação de Administrador
        $ehAdmin = false;
        if ($usuario && ((int)$usuario->adm === 1 || (int)$usuario->is_admin === 1)) {
            $ehAdmin = true;
        } elseif (!empty($userLogged['adm']) || !empty($userLogged['is_admin'])) {
            $ehAdmin = true;
        }

        $funcionarioVinculado = \App\Models\Funcionario::where('usuario_id', $usuario_id)->first();
        $meuFuncionarioId = $funcionarioVinculado ? $funcionarioVinculado->id : 0;

        // Normalização das datas enviadas pelo calendário
        if ($request->filled('start') && $request->filled('end')) {
            $dataInicio = substr($request->start, 0, 10);
            $dataFim    = substr($request->end, 0, 10);
        } else {
            $dataInicio = \Carbon\Carbon::now()->startOfMonth()->subDays(15)->format('Y-m-d');
            $dataFim    = \Carbon\Carbon::now()->endOfMonth()->addDays(15)->format('Y-m-d');
        }

        $eventos = [];

        // =========================================================================
        // 1. TAREFAS
        // =========================================================================
        $queryTarefas = \App\Models\Tarefa::with('funcionario')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data', [$dataInicio, $dataFim]);

        // Se NÃO for Admin, mostra apenas as atribuídas a ele ou criadas por ele
        if (!$ehAdmin) {
            $queryTarefas->where(function($q) use ($meuFuncionarioId, $usuario_id) {
                if ($meuFuncionarioId > 0) {
                    $q->where('funcionario_id', $meuFuncionarioId);
                }
                if ($usuario_id) {
                    $q->orWhere('usuario_id', $usuario_id)
                        ->orWhere('user_id', $usuario_id);
                }
            });
        }

        // Filtro manual da barra superior (se o usuário selecionar no select)
        if ($request->filled('funcionario_id') && $request->funcionario_id !== 'null') {
            $queryTarefas->where('funcionario_id', $request->funcionario_id);
        }

        $tarefas = $queryTarefas->get();
        $agora = \Carbon\Carbon::now();

        foreach ($tarefas as $t) {
            $isAtrasada = false;

            if ($t->status !== 'concluida' && !empty($t->data_limite)) {
                $dataApenas = \Carbon\Carbon::parse($t->data_limite)->format('Y-m-d');
                $horaLim = !empty($t->hora_limite) ? $t->hora_limite : '23:59:59';
                $limite = \Carbon\Carbon::parse($dataApenas . ' ' . $horaLim);
                if ($agora->greaterThan($limite)) {
                    $isAtrasada = true;
                }
            }

            // Cor por Status da Tarefa
            if ($t->status === 'concluida') {
                $cor = '#10B981'; // Verde
                $icon = '✓';
            } elseif ($t->status === 'em_andamento') {
                $cor = '#0EA5E9'; // Azul
                $icon = '⏱';
            } elseif ($isAtrasada) {
                $cor = '#DC2626'; // Vermelho
                $icon = '⚠️';
            } else {
                $cor = '#8B5CF6'; // Roxo (Pendente)
                $icon = '👤';
            }

            // Nome do responsável (apenas primeiro nome ou nome curto para caber no card)
            $nomeCompleto = $t->funcionario->nome ?? 'Não atribuído';
            $partesNome = explode(' ', trim($nomeCompleto));
            $primeiroNome = $partesNome[0] . (isset($partesNome[1]) ? ' ' . substr($partesNome[1], 0, 1) . '.' : '');

            // Título compacto no card do calendário
            $tituloCard = "{$icon} {$t->titulo} ({$primeiroNome})";

            $dataTarefa = \Carbon\Carbon::parse($t->data)->format('Y-m-d');
            $horaIni = !empty($t->hora_estimada) ? substr($t->hora_estimada, 0, 5) : null;
            $horaFim = !empty($t->hora_limite) ? substr($t->hora_limite, 0, 5) : '18:00';

            $dataLimiteFormatada = !empty($t->data_limite) ? \Carbon\Carbon::parse($t->data_limite)->format('d/m/Y') : 'Não definido';

            $eventos[] = [
                'id'              => 'tarefa_' . $t->id,
                'title'           => $tituloCard,
                'start'           => $horaIni ? "{$dataTarefa}T{$horaIni}:00" : $dataTarefa,
                'allDay'          => empty($horaIni),
                'color'           => $cor,
                'backgroundColor' => $cor,
                'borderColor'     => $cor,
                'textColor'       => '#FFFFFF',
                // Propriedades completas para o Modal
                'extendedProps'   => [
                    'tipo'                 => 'tarefa',
                    'tarefa_id'            => $t->id,
                    'titulo'               => $t->titulo,
                    'funcionario'          => $nomeCompleto,
                    'prioridade'           => $t->prioridade ?? 'Normal',
                    'status'               => $t->status,
                    'is_atrasada'          => $isAtrasada,
                    'data_inicio'          => \Carbon\Carbon::parse($t->data)->format('d/m/Y'),
                    'data_limite'          => $dataLimiteFormatada,
                    'hora_limite'          => $horaFim,
                    'tempo_gasto'          => $t->tempo_gasto_minutos ?? 0,
                    'justificativa_atraso' => $t->justificativa_atraso ?? ''
                ]
            ];
        }

        // =========================================================================
        // 2. AGENDAMENTOS DE OS
        // =========================================================================
        $queryAgendamentos = \App\Models\Agendamento::with(['cliente', 'veiculo', 'funcionario'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data', [$dataInicio, $dataFim]);

        if (!$ehAdmin) {
            $queryAgendamentos->where('funcionario_id', $meuFuncionarioId);
        }

        if ($request->filled('funcionario_id') && $request->funcionario_id !== 'null') {
            $queryAgendamentos->where('funcionario_id', $request->funcionario_id);
        }
        if ($request->filled('cliente_id') && $request->cliente_id !== 'null') {
            $queryAgendamentos->where('cliente_id', $request->cliente_id);
        }

        foreach ($queryAgendamentos->get() as $a) {
            $placa = !empty($a->veiculo->placa) ? "[{$a->veiculo->placa}] " : "";
            $clienteNome = $a->cliente->razao_social ?? 'Cliente';
            $atendenteNome = $a->funcionario->nome ?? 'Atendente';

            $cor = match((int)$a->status) {
                1 => '#10B981',
                2 => '#EF4444',
                3 => '#3B82F6',
                default => '#F59E0B'
            };

            $horaInicio = !empty($a->inicio) ? substr($a->inicio, 0, 5) : '08:00';
            $horaTermino = !empty($a->termino) ? substr($a->termino, 0, 5) : '09:00';
            $dataOS = \Carbon\Carbon::parse($a->data)->format('Y-m-d');

            $eventos[] = [
                'id'              => 'agendamento_' . $a->id,
                'title'           => "🚗 {$placa}{$clienteNome} ({$atendenteNome})",
                'start'           => "{$dataOS}T{$horaInicio}:00",
                'end'             => "{$dataOS}T{$horaTermino}:00",
                'url'             => "/agendamentos/detalhes/{$a->id}",
                'color'           => $cor,
                'backgroundColor' => $cor,
                'borderColor'     => $cor,
                'textColor'       => '#FFFFFF',
                'allDay'          => false,
                'tipo'            => 'agendamento',
                'extendedProps'   => [
                    'tipo' => 'agendamento',
                    'id'   => $a->id
                ]
            ];
        }

        return response()->json($eventos, 200);
    }

    public function saveCliente(Request $request){
        $cliente = $request->cliente;

        $arr = [
            'razao_social' => $cliente['nome'],
            'nome_fantasia' => $cliente['nome'],
            'bairro' => '',
            'numero' => '',
            'rua' => '',
            'cpf_cnpj' => '',
            'telefone' => $cliente['telefone'],
            'celular' => $cliente['telefone'],
            'email' => '',
            'cep' => '',
            'ie_rg' => '',
            'consumidor_final' => 1,
            'limite_venda' => 0,
            'cidade_id' => 1,
            'contribuinte' => 1,
            'rua_cobranca' => '',
            'numero_cobranca' => '',
            'bairro_cobranca' => '',
            'cep_cobranca' => '',
            'cidade_cobranca_id' => null,
            'empresa_id' => $this->empresa_id
        ];
        $res = Cliente::create($arr);

        return response()->json($res, 200);
    }

    public function save(Request $request){
        $agendamento = $request->agendamento;

        // Recupera o ID do usuário logado na sessão atual
        $usuarioIdLogado = session('user_logged')['id'] ?? null;

        // 1. Grava o agendamento e os itens no banco incluindo empresa_id e usuario_id
        $arr = [
            'funcionario_id'     => $agendamento['funcionario_id'],
            'cliente_id'         => $agendamento['cliente_id'],
            'cliente_veiculo_id' => $agendamento['cliente_veiculo_id'] ?? null,
            'data'               => $this->parseDate($agendamento['data']),
            'inicio'             => $agendamento['inicio'],
            'termino'            => $agendamento['termino'],
            'observacao'         => $agendamento['observacao'] ?? '',
            'total'              => __replace($agendamento['total']) - __replace($agendamento['desconto']) + __replace($agendamento['acrescimo']),
            'desconto'           => __replace($agendamento['desconto']),
            'acrescimo'          => __replace($agendamento['acrescimo']),
            'status'             => false,
            'empresa_id'         => $this->empresa_id,      // Garante o ID correto da empresa atual
            'usuario_id'         => $usuarioIdLogado        // Salva o ID do usuário que realizou a ação
        ];

        $result = Agendamento::create($arr);

        foreach($agendamento['itens'] as $i){
            ItemAgendamento::create([
                'agendamento_id' => $result->id,
                'servico_id'     => $i['id'],
                'quantidade'     => 1
            ]);
        }

        // 2. DISPARO AUTOMÁTICO DE WHATSAPP (PADRÃO ÓTICA)
        try {
            $cliente = Cliente::find($agendamento['cliente_id']);

            if ($cliente) {
                $numeroOriginal = !empty($cliente->whatsapp) ? $cliente->whatsapp : ($cliente->telefone ?? '');
                $numero = preg_replace('/[^0-9]/', '', $numeroOriginal);

                if (strlen($numero) >= 10) {
                    if (substr($numero, 0, 2) !== '55') {
                        $numero = "55" . $numero;
                    }

                    $dataFmt = date('d/m/Y', strtotime($result->data));
                    $nomeCliente = $cliente->razao_social ?? 'Cliente';

                    $mensagem  = "🚗 *Agendamento Confirmado!*\n\n";
                    $mensagem .= "Olá, *{$nomeCliente}*!\n";
                    $mensagem .= "Seu agendamento em nossa oficina foi realizado com sucesso.\n\n";
                    $mensagem .= "📅 *Data:* {$dataFmt}\n";
                    $mensagem .= "⏰ *Horário:* {$result->inicio} às {$result->termino}\n";

                    if (!empty($result->observacao)) {
                        $mensagem .= "📋 *Queixa/Obs:* {$result->observacao}\n";
                    }

                    $mensagem .= "\nFicamos no seu aguardo!";

                    // Utiliza a classe utilitária do sistema
                    if (class_exists('\App\Utils\WhatsAppUtil')) {
                        $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);

                        if (method_exists($instanciaWhats, 'sendMessage')) {
                            $instanciaWhats->sendMessage($numero, $mensagem, $this->empresa_id);
                        } elseif (method_exists($instanciaWhats, 'send')) {
                            $instanciaWhats->send($numero, $mensagem);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro ao enviar WhatsApp automático no agendamento #{$result->id}: " . $e->getMessage());
        }

        return response()->json($result, 200);
    }

    private function parseDate($date){
        return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    }

    private function menos10Dias(){
        return date('Y-m-d', strtotime("-10 days",strtotime(str_replace("/", "-",
            date('Y-m-d')))));
    }

    private function mais20Dias(){
        return date('Y-m-d', strtotime("+20 days",strtotime(str_replace("/", "-",
            date('Y-m-d')))));
    }

    /*public function all(){
        $mais20 = $this->mais20Dias();
        $menos10 = $this->menos10Dias();

        $agendamentos = Agendamento::
        whereBetween('data', [$menos10,
            $mais20])
        ->where('empresa_id', $this->empresa_id)
        ->get();
        $temp = [];

        // 1. Monta os Agendamentos no calendário
        foreach($agendamentos as $a){
            $titulo = $a->cliente->razao_social . " - ";
            foreach($a->itens as $key => $i){
                $titulo .= $i->servico->nome . ($key < sizeof($a->itens)-1 ? "|" : "");
            }

            $arr = [
                'title' => $titulo,
                'start' => $a->data.'T'.$a->inicio,
                'end' => $a->data.'T'.$a->termino,
                'url' => "/agendamentos/detalhes/".$a->id,
                'backgroundColor' => $a->status ? '#4db6ac' : '#ef5350'
            ];

            array_push($temp, $arr);
        }

        // 2. Monta as Tarefas no calendário (NOVO)
        $tarefas = Tarefa::whereBetween('data', [$menos10, $mais20])
            ->where('empresa_id', $this->empresa_id)
            ->get();

        foreach($tarefas as $t){
            $cor = '#ff9800'; // Laranja (Pendente)
            if($t->status == 'em_andamento') $cor = '#2196f3'; // Azul (Em andamento)
            if($t->status == 'concluida') $cor = '#4caf50'; // Verde (Concluída)

            $arr = [
                'title' => '[Tarefa] ' . $t->titulo,
                'start' => $t->data . ($t->hora_estimada ? 'T'.$t->hora_estimada : ''),
                'url' => "/tarefas/edit/".$t->id, // Ao clicar, vai para a edição da tarefa
                'backgroundColor' => $cor
            ];

            array_push($temp, $arr);
        }

        return response()->json($temp, 200);
    }*/

    public function filtro(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $funcionario = $request->funcionario;
        $cliente = $request->cliente;
        $status = $request->status;

        $agendamentos = Agendamento::select('*')
            ->where('empresa_id', $this->empresa_id);

        if($dataInicial && $dataFinal){
            $data1 = $this->parseDate($dataInicial);
            $data2 = $this->parseDate($dataFinal);
            $agendamentos->whereBetween('data', [$data1,
                $data2]);
        }

        if($funcionario != 'null'){
            $agendamentos->where('funcionario_id', $funcionario);
        }
        if($cliente != 'null'){
            $agendamentos->where('cliente_id', $cliente);
        }
        if($status != 'todos'){
            $agendamentos->where('status', $status);
        }

        $agendamentos = $agendamentos->get();
        $temp = [];

        // 1. Agendamentos Filtrados
        foreach($agendamentos as $a){
            $titulo = $a->cliente->razao_social . " - ";
            foreach($a->itens as $key => $i){
                $titulo .= $i->servico->nome . ($key < sizeof($a->itens)-1 ? "|" : "");
            }

            $arr = [
                'title' => $titulo,
                'start' => $a->data.'T'.$a->inicio,
                'end' => $a->data.'T'.$a->termino,
                'url' => "/agendamentos/detalhes/".$a->id,
                'backgroundColor' => $a->status ? '#4db6ac' : '#ef5350'
            ];

            array_push($temp, $arr);
        }

        // 2. Tarefas Filtradas (NOVO)
        // Só mostra tarefas se NÃO estiver filtrando por um cliente específico
        if($cliente == 'null'){
            $tarefas = Tarefa::where('empresa_id', $this->empresa_id);

            if($dataInicial && $dataFinal){
                $tarefas->whereBetween('data', [$data1, $data2]);
            }

            if($funcionario != 'null'){
                $tarefas->where('funcionario_id', $funcionario);
            }

            // Adaptação do status (0 = false/pendente, 1 = true/concluida)
            if($status != 'todos'){
                if($status == 0) $tarefas->whereIn('status', ['pendente', 'em_andamento']);
                if($status == 1) $tarefas->where('status', 'concluida');
            }

            $tarefas = $tarefas->get();

            foreach($tarefas as $t){
                $cor = '#ff9800'; // Laranja
                if($t->status == 'em_andamento') $cor = '#2196f3'; // Azul
                if($t->status == 'concluida') $cor = '#4caf50'; // Verde

                $arr = [
                    'title' => '[Tarefa] ' . $t->titulo,
                    'start' => $t->data . ($t->hora_estimada ? 'T'.$t->hora_estimada : ''),
                    'url' => "/tarefas/edit/".$t->id,
                    'backgroundColor' => $cor
                ];

                array_push($temp, $arr);
            }
        }

        return response()->json($temp, 200);
    }

    public function detalhes($id){
        $agendamento = Agendamento::find($id);
        if(valida_objeto($agendamento)){
            return view('agendamentos/detalhes')
                ->with('agendamento', $agendamento)
                ->with('title', 'Detalhe agendamento');
        }else{
            return redirect('/403');
        }
    }

    public function delete($id){
        $agendamento = Agendamento::find($id);
        if(valida_objeto($agendamento)){
            ItemAgendamento::where('agendamento_id', $id)->delete();

            $agendamento->delete();
            session()->flash("mensagem_sucesso", "Agendamento removido!");

            return redirect('agendamentos');
        }else{
            return redirect('/403');
        }
    }

    public function alterarStatus($id){

        $agendamento = Agendamento::find($id);
        if(valida_objeto($agendamento)){

            $agendamento->status = 1;

            $valorComissao = $this->calculaComissao($agendamento);

            $agendamento->valor_comissao = $valorComissao;
            $agendamento->save();
            session()->flash("mensagem_sucesso", "Agendamento alterado para finalizado!");

            return redirect('agendamentos');
        }else{
            return redirect('/403');
        }
    }

    private function calculaComissao($agendamento){
        $soma = 0;
        $somaDesconto = 0;
        $total = $agendamento->total + $agendamento->acrescimo - $agendamento->desconto;

        foreach($agendamento->itens as $key => $i){
            $tempDesc = 0;
            $valorServico = $i->servico->valor;

            if($key < sizeof($agendamento->itens)-1){
                $media = (((($valorServico - $total)/$total))*100);

                $media = 100 - ($media * -1);
                $tempDesc = ($agendamento->desconto*$media)/100;

                $somaDesconto += $tempDesc;

            }else{
                $tempDesc = $agendamento->desconto - $somaDesconto;
            }

            $comissao = $i->servico->comissao;

            $valorComissao = ($valorServico - $tempDesc) * ($comissao/100);
            $soma += $valorComissao;
        }

        return number_format($soma,2);
    }

    public function irParaFrenteCaixa($id){
        $agendamento = Agendamento::find($id);
        if(valida_objeto($agendamento)){

            $produto_agendamento_id = $this->verificaProdutoServicoCadastrado();

            $atributes = $this->addAtributes($agendamento, $produto_agendamento_id);

            $usuario = Usuario::find(get_id_user());
            $tiposPagamento = VendaCaixa::tiposPagamento();
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
                ->first();

            $certificado = Certificado::
            where('empresa_id', $this->empresa_id)
                ->first();

            $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
            $produtos = Produto::
            where('empresa_id', $this->empresa_id)
                ->get();

            $categorias = Categoria::
            where('empresa_id', $this->empresa_id)
                ->get();

            $clientes = Cliente::
            where('empresa_id', $this->empresa_id)
                ->where('inativo', false)
                ->orderBy('razao_social')->get();

            $abertura = AberturaCaixa::
            where('status', 0)
                ->where('empresa_id', $this->empresa_id)
                ->orderBy('id', 'desc')
                ->first();

            if($abertura != null){

                $produtosGroup = Produto::
                where('empresa_id', $this->empresa_id)
                    ->where('inativo', false)
                    ->where('valor_venda', '>', 0)
                    ->groupBy('referencia_grade')
                    ->get();

                $atalhos = ConfigCaixa::
                where('usuario_id', get_id_user())
                    ->first();

                $funcionarios = Funcionario::
                where('funcionarios.empresa_id', $this->empresa_id)
                    ->select('funcionarios.*')
                    ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
                    ->get();

                $view = 'main3';
                // if($atalhos != null && $atalhos->modelo_pdv == 1){
                // 	$view = 'main2';
                // }

                $consignadas = $this->getConsignadas();
                $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();

                $usuarios = Usuario::where('empresa_id', $this->empresa_id)
                    ->where('ativo', 1)
                    ->orderBy('nome', 'asc')
                    ->get();
                $vendedores = [];
                foreach($usuarios as $u){
                    if($u->funcionario){
                        array_push($vendedores, $u);
                    }
                }

                $estados = Cliente::estados();
                $cidades = Cidade::all();
                $pais = Pais::all();
                $grupos = GrupoCliente::get();

                $rascunhos = $this->getRascunhos();
                $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
                $produtosMaisVendidos = $this->produtosMaisVendidos();


                return view('frontBox/'.$view)
                    ->with('atalhos', $atalhos)
                    ->with('rascunhos', $rascunhos)
                    ->with('consignadas', $consignadas)
                    ->with('itens', $atributes)
                    ->with('funcionarios', $funcionarios)
                    ->with('frenteCaixa', true)
                    ->with('tiposPagamento', $tiposPagamento)
                    ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
                    ->with('config', $config)
                    ->with('usuario', $usuario)
                    ->with('clientes', $clientes)
                    ->with('acessores', $acessores)
                    ->with('produtos', $produtos)
                    ->with('produtosMaisVendidos', $produtosMaisVendidos)
                    ->with('vendedores', $vendedores)
                    ->with('produtosGroup', $produtosGroup)
                    ->with('pais', $pais)
                    ->with('grupos', $grupos)
                    ->with('cidades', $cidades)
                    ->with('estados', $estados)
                    ->with('agendamento_id', $agendamento->id)
                    ->with('categorias', $categorias)
                    ->with('certificado', $certificado)
                    ->with('title', 'Finalizar Agendamento '.$id);

            }else{
                echo "É necessário abrir o caixa no PDV primeiramente";
                echo " <a href='/frenteCaixa'>ir para PDV</a>";
            }
        }else{
            return redirect('/403');
        }
    }

    private function produtosMaisVendidos(){

        $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
            ->where('usuario_id', get_id_user())
            ->where('status', 0)
            ->orderBy('id', 'desc')
            ->first();
        $filial = -1;

        if($abertura){
            $filial = $abertura->filial_id;
            if($filial == null){
                $filial = -1;
            }
        }
        $itens = ItemVendaCaixa::
        selectRaw('item_venda_caixas.*, count(quantidade) as qtd')
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->groupBy('item_venda_caixas.produto_id')
            ->orderBy('qtd')
            ->when(empresaComFilial(), function ($q) use ($filial) {
                return $q->where(function($query) use ($filial){
                    $query->where('produtos.locais', 'like', "%{$filial}%");
                });
            })
            ->limit(21)
            ->get();

        $produtos = [];
        foreach($itens as $i){
            $p = Produto::find($i->produto_id);
            if(!$p->inativo){
                array_push($produtos, $p);
            }
        }
        return $produtos;
    }

    private function getRascunhos(){
        return VendaCaixa::
        where('rascunho', 1)
            ->where('empresa_id', $this->empresa_id)
            ->limit(20)
            ->orderBy('id', 'desc')
            ->get();
    }

    private function getConsignadas(){
        return VendaCaixa::
        where('consignado', 1)
            ->where('empresa_id', $this->empresa_id)
            ->limit(20)
            ->orderBy('id', 'desc')
            ->get();
    }

    private function verificaProdutoServicoCadastrado(){
        $categoria_id = $this->verificaCategoriaServicoCadastrado();
        $produto = Produto::where('nome', 'Agendamento de serviço')
            ->where('empresa_id', $this->empresa_id)->first();
        if($produto != null) return $produto->id;

        $produtoFirst = Produto::where('empresa_id', $this->empresa_id)->first();
        $arr = [
            'nome' => 'Agendamento de serviço',
            'categoria_id' => $categoria_id,
            'cor' => '',
            'valor_venda' => 0,
            'NCM' => $produtoFirst != null ? $produtoFirst->NCM : '4407.11.00',
            'CST_CSOSN' => $produtoFirst != null ? $produtoFirst->CST_CSOSN : '102',
            'CST_PIS' => $produtoFirst != null ? $produtoFirst->CST_PIS : '49',
            'CST_COFINS' => $produtoFirst != null ? $produtoFirst->CST_COFINS : '49',
            'CST_IPI' => $produtoFirst != null ? $produtoFirst->CST_IPI : '99',
            'unidade_compra' => $produtoFirst != null ? $produtoFirst->unidade_compra : 'UNID',
            'unidade_venda' => $produtoFirst != null ? $produtoFirst->unidade_venda : 'UNID',
            'composto' => 0,
            'codBarras' => 'SEM GTIN',
            'conversao_unitaria' => 1,
            'valor_livre' => 0,
            'perc_icms' => 0,
            'perc_pis' => 0,
            'perc_cofins' => 0,
            'perc_ipi' => 0,
            'CFOP_saida_estadual' => $produtoFirst != null ? $produtoFirst->CFOP_saida_estadual : '5101',
            'CFOP_saida_inter_estadual' => $produtoFirst != null ? $produtoFirst->CFOP_saida_inter_estadual : '6101',
            'codigo_anp' => '',
            'descricao_anp' => '',
            'perc_iss' => 0,
            'cListServ' => '',
            'imagem' => '',
            'alerta_vencimento' => 0,
            'valor_compra' => 0,
            'gerenciar_estoque' => 0,
            'estoque_minimo' => 0,
            'referencia' => '',
            'tela_id' => NULL,
            'largura' => 0,
            'comprimento' => 0,
            'altura' => 0,
            'peso_liquido' => 0,
            'peso_bruto' => 0,
            'empresa_id' => $this->empresa_id
        ];
        $result = Produto::create($arr);
        return $result->id;
    }

    private function verificaCategoriaServicoCadastrado(){
        $categoria = Categoria::where('nome', 'serviços')
            ->where('empresa_id', $this->empresa_id)->first();
        if($categoria != null) return $categoria->id;

        $arr = [
            'nome' => 'serviços',
            'empresa_id' => $this->empresa_id
        ];
        $result = Categoria::create($arr);
        return $result->id;
    }

    private function addAtributes($agendamento, $produto_agendamento_id){
        $temp = [];

        $produto = Produto::find($produto_agendamento_id);


        $produto->valor_venda = $agendamento->total;

        array_push($temp, $produto);


        return $temp;
    }

    public function comissao(){
        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
            ->get();

        return view('agendamentos/comissao')
            ->with('funcionarios', $funcionarios)
            ->with('title', 'Comissão %');
    }

    public function filtrarComissao(Request $request){

        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $funcionario = $request->funcionario;

        $agendamentos = Agendamento::select('*');

        if($dataInicial && $dataFinal){
            $data1 = $this->parseDate($dataInicial);
            $data2 = $this->parseDate($dataFinal);
            $agendamentos->whereBetween('data', [$data1,
                $data2]);
        }

        if($funcionario != 'null'){
            $agendamentos->where('funcionario_id', $funcionario);
        }

        $agendamentos->where('valor_comissao', '>', 0);
        $agendamentos->where('empresa_id', $this->empresa_id);
        $agendamentos->where('status', 1);

        $agendamentos = $agendamentos->get();


        $arrAgrupado = null;
        if($funcionario == 'null'){
            $arrAgrupado = $this->agrupa($agendamentos);
        }

        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
            ->get();

        return view('agendamentos/comissao')
            ->with('funcionarios', $funcionarios)
            ->with('agendamentos', $agendamentos)
            ->with('arrAgrupado', $arrAgrupado)
            ->with('dataFinal', $request->data_final)
            ->with('dataInicial', $request->data_inicial)
            ->with('funcionario', $request->funcionario)

            ->with('title', 'Comissão %');
    }

    private function agrupa($agendamentos){
        $arr = $this->criarArrayFuncionarios();
        $len = sizeof($arr);
        foreach($agendamentos as $a){
            for($i=0; $i<$len; $i++){
                if($a->funcionario->id == $arr[$i]['id']){
                    $arr[$i]['valor_agendamento'] += $a->total;
                    $arr[$i]['valor_comissao'] += $a->valor_comissao;
                    $arr[$i]['total_de_servicos'] += sizeof($a->itens);
                }
            }
        }
        return $arr;
    }

    private function criarArrayFuncionarios(){
        $funcionaios = Funcionario::
        where('empresa_id', $this->empresa_id)
            ->get();

        $temp = [];
        foreach($funcionaios as $f){
            $arr = [
                'id' => $f->id,
                'nome' => $f->nome,
                'valor_agendamento' => 0,
                'valor_comissao' => 0,
                'total_de_servicos' => 0
            ];
            array_push($temp, $arr);
        }
        return $temp;
    }

    public function servicos(){
        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
            ->get();
        return view('agendamentos/servicos')
            ->with('funcionarios', $funcionarios)
            ->with('title', 'Serviços do agendamento');
    }

    public function filtrarServicos(Request $request){

        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $funcionario = $request->funcionario;

        if($funcionario == 'null'){
            session()->flash("mensagem_erro", "Selecione o atendente!");
            return redirect('/agendamentos/servicos');
        }

        $agendamentos = Agendamento::select('*');

        if($dataInicial && $dataFinal){
            $data1 = $this->parseDate($dataInicial);
            $data2 = $this->parseDate($dataFinal);
            $agendamentos->whereBetween('data', [$data1,
                $data2]);
        }

        if($funcionario != 'null'){
            $agendamentos->where('funcionario_id', $funcionario);
        }

        $agendamentos->where('valor_comissao', '>', 0);
        $agendamentos->where('status', 1);
        $agendamentos->where('empresa_id', $this->empresa_id);

        $agendamentos = $agendamentos->get();


        $arrServicos = $this->criaArrayDeServicos();
        $len = sizeof($arrServicos);
        $servicos = [];

        foreach($agendamentos as $a){
            foreach($a->itens as $item){
                array_push($servicos, $item);
                for($i=0; $i < $len; $i++){
                    if($item->servico->id == $arrServicos[$i]['id']){

                        $arrServicos[$i]['valor'] += $item->servico->valor;
                        $arrServicos[$i]['quantidade'] += 1;

                    }
                }
            }
        }

        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
            ->get();
        return view('agendamentos/servicos')
            ->with('funcionarios', $funcionarios)
            ->with('grupo', $arrServicos)
            ->with('servicos', $servicos)
            ->with('dataInicial', $request->data_inicial)
            ->with('dataFinal', $request->data_final)
            ->with('funcionario', $request->funcionario)
            ->with('title', 'Serviços do agendamento');
    }

    private function criaArrayDeServicos(){
        $servicos = Servico::
        where('empresa_id', $this->empresa_id)
            ->get();
        $temp = [];
        foreach($servicos as $s){
            $arr = [
                'id' => $s->id,
                'servico' => $s->nome,
                'valor' => 0,
                'quantidade' => 0,
            ];
            array_push($temp, $arr);
        }
        return $temp;
    }

    // Converte o Agendamento diretamente em uma Ordem de Serviço
    public function gerarOs($id)
    {
        $agendamento = Agendamento::with(['cliente', 'itens.servico'])->findOrFail($id);

        if (!valida_objeto($agendamento)) {
            return redirect('/403');
        }

        $os = \App\Models\OrdemServico::create([
            'cliente_id'         => $agendamento->cliente_id,
            'usuario_id'         => get_id_user(),
            'vendedor_id'        => get_id_user(),
            'tecnico_id'         => $agendamento->funcionario_id,
            'cliente_veiculo_id' => $agendamento->cliente_veiculo_id ?? null,
            'defeito_relatado'   => $agendamento->observacao ?? 'Agendamento de serviço',
            'garantia_dias'      => 90,
            'status_aprovacao'   => 'em_andamento', // Define a OS como Em Andamento
            'estado'             => 'pd',
            'desconto'           => $agendamento->desconto,
            'acrescimo'          => $agendamento->acrescimo,
            'observacao'         => 'Gerado a partir do Agendamento #' . $agendamento->id,
            'empresa_id'         => $this->empresa_id,
            'filial_id'          => $agendamento->filial_id ?? null
        ]);

        foreach ($agendamento->itens as $item) {
            \App\Models\ServicoOs::create([
                'ordem_servico_id' => $os->id,
                'servico_id'       => $item->servico_id,
                'quantidade'       => $item->quantidade ?? 1,
                'valor_unitario'   => $item->servico->valor,
                'sub_total'        => $item->servico->valor * ($item->quantidade ?? 1),
                'status'           => false
            ]);
        }

        // Define o status do agendamento como 3 = EM EXECUÇÃO (AZUL)
        $agendamento->status = 3;
        $agendamento->save();

        session()->flash('mensagem_sucesso', 'Ordem de Serviço #' . $os->id . ' gerada com sucesso!');
        return redirect("/ordemServico/servicosordem/{$os->id}");
    }




    // Método para Cancelar com Motivo/Justificativa
    public function cancelar(Request $request, $id)
    {
        $agendamento = Agendamento::find($id);

        if (valida_objeto($agendamento)) {
            $agendamento->status = 2; // 2 = CANCELADO
            $agendamento->motivo_cancelamento = $request->motivo_cancelamento;
            $agendamento->save();

            session()->flash("mensagem_sucesso", "Agendamento #{$id} cancelado com sucesso!");
            return redirect('/agendamentos');
        } else {
            return redirect('/403');
        }
    }
}
