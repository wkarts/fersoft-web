<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PortariaAgendamento;
use App\Models\PortariaMovimento;
use App\Models\Visitante;
use App\Models\Funcionario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PortariaController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index()
    {
        $title = 'Controle de Portaria';

        // Cadastros Base
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();

        // Trazendo Clientes e Fornecedores (assumindo que o model de fornecedor tenha razao_social)
        $clientes = \App\Models\Cliente::where('empresa_id', $this->empresa_id)->where('inativo', false)->orderBy('razao_social')->get();
        $fornecedores = \App\Models\Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get();

        $agendamentosHoje = PortariaAgendamento::with(['visitante', 'funcionario'])
            ->where('empresa_id', $this->empresa_id)
            ->where('status', 'pendente')
            ->whereDate('data_hora_prevista', now()->toDateString())
            ->orderBy('data_hora_prevista', 'asc')
            ->get();

        $presentes = PortariaMovimento::with(['visitante', 'funcionarioVisitado'])
            ->where('empresa_id', $this->empresa_id)
            ->whereNull('data_hora_saida')
            ->orderBy('data_hora_entrada', 'desc')
            ->get();

        // Envia as novas variáveis no compact()
        return view('portaria.index', compact('funcionarios', 'agendamentosHoje', 'presentes', 'title', 'clientes', 'fornecedores'));
    }


    // --- 1. CRIAR AGENDAMENTO ---
    public function agendarVisita(Request $request)
    {
        $visitante_id = $request->visitante_id;

        // Se não veio o ID, tenta achar ou cadastrar pelo CPF
        if (!$visitante_id && $request->cpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $request->cpf);
            $visitante = Visitante::where('cpf', $cpfLimpo)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            if (!$visitante) {
                $visitante = Visitante::create([
                    'nome' => $request->nome,
                    'cpf' => $cpfLimpo,
                    'empresa_id' => $this->empresa_id
                ]);
            }
            $visitante_id = $visitante->id;
        }

        if (!$visitante_id) {
            session()->flash("mensagem_erro", "Erro: Visitante não identificado.");
            return redirect()->back();
        }

        // NOVO: Atualiza o vínculo do visitante com Cliente ou Fornecedor
        $visitanteParaAtualizar = Visitante::find($visitante_id);
        if ($request->tipo_vinculo == 'cliente') {
            $visitanteParaAtualizar->update(['cliente_id' => $request->cliente_id, 'fornecedor_id' => null]);
        } elseif ($request->tipo_vinculo == 'fornecedor') {
            $visitanteParaAtualizar->update(['fornecedor_id' => $request->fornecedor_id, 'cliente_id' => null]);
        }

        // O resto do código continua igual...
        $agendamento = PortariaAgendamento::create([
            'visitante_id' => $visitante_id,
            'funcionario_id' => $request->funcionario_id,
            'usuario_criador_id' => session('user_logged')['id'],
            'empresa_id' => $this->empresa_id,
            'data_hora_prevista' => $request->data_hora_prevista,
            'placa_veiculo_prevista' => $request->placa_veiculo,
            'status' => 'pendente'
        ]);



        $visitante = Visitante::find($visitante_id);

        // B. Enviar WhatsApp para o Visitante (confirmando o agendamento)
        if (!empty($visitante->telefone)) {
            $mensagem  = "🏢 *Agendamento Confirmado!*\n\n";
            $mensagem .= "Olá, *{$visitante->nome}*.\n";
            $mensagem .= "Sua visita está agendada para: " . Carbon\Carbon::parse($request->data_hora_prevista)->format('d/m/Y às H:i') . ".\n";
            $mensagem .= "Por favor, apresente-se na portaria.";

            $this->enviarWhatsApp($visitante->telefone, $mensagem);
        }

        session()->flash("mensagem_sucesso", "Agendamento criado e notificação enviada!");
        return redirect()->back();
    }

    // --- 2. REGISTRAR ENTRADA NA PORTARIA ---
    // --- 2. REGISTRAR ENTRADA NA PORTARIA ---
    public function registrarEntrada(Request $request)
    {
        $visitante_id = $request->visitante_id;

        // 1. Acha ou Cria o Visitante Principal pelo CPF
        if (!$visitante_id && $request->cpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $request->cpf);
            $visitante = \App\Models\Visitante::where('cpf', $cpfLimpo)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            if (!$visitante) {
                $visitante = \App\Models\Visitante::create([
                    'nome' => $request->nome,
                    'cpf' => $cpfLimpo,
                    'empresa_id' => $this->empresa_id
                ]);
            }
            $visitante_id = $visitante->id;
        }

        if (!$visitante_id) {
            session()->flash("mensagem_erro", "Erro: Visitante não identificado.");
            return redirect()->back();
        }

        $jaEstaNaEmpresa = \App\Models\PortariaMovimento::where('visitante_id', $visitante_id)
            ->where('empresa_id', $this->empresa_id)
            ->whereNull('data_hora_saida')
            ->exists();

        if ($jaEstaNaEmpresa) {
            session()->flash("mensagem_erro", "Atenção: Este visitante já registrou entrada e consta como presente.");
            return redirect()->back();
        }

        $visitanteParaAtualizar = \App\Models\Visitante::find($visitante_id);

        // 2. Foto do visitante principal salva na pasta PUBLIC
        if ($request->foto_base64) {
            try {
                $image_parts = explode(";base64,", $request->foto_base64);
                $image_base64 = base64_decode($image_parts[1]);
                $nomeArquivo = 'visitantes/foto_' . $visitante_id . '_' . time() . '.jpg';

                // Garante que a pasta public/visitantes existe
                $caminhoPasta = public_path('visitantes');
                if (!file_exists($caminhoPasta)) {
                    mkdir($caminhoPasta, 0755, true);
                }

                // Salva o arquivo nativamente
                file_put_contents(public_path($nomeArquivo), $image_base64);

                $visitanteParaAtualizar->foto = $nomeArquivo;
                $visitanteParaAtualizar->save();
            } catch (\Exception $e) {
                \Log::error("Erro ao salvar foto principal da portaria: " . $e->getMessage());
            }
        }

        // 3. Grava a Movimentação Oficial do Principal
        $movimento = \App\Models\PortariaMovimento::create([
            'visitante_id' => $visitante_id,
            'funcionario_visitado_id' => $request->funcionario_id,
            'agendamento_id' => $request->agendamento_id ?? null,
            'usuario_porteiro_id' => session('user_logged')['id'],
            'empresa_id' => $this->empresa_id,
            'placa_veiculo' => $request->placa_veiculo,
            'data_hora_entrada' => now(),
            'observacoes' => $request->motivo_visita ?? null
        ]);

        $agendamentoId = $request->agendamento_id;

        if (!$agendamentoId) {
            $agendaHj = \App\Models\PortariaAgendamento::where('visitante_id', $visitante_id)
                ->where('empresa_id', $this->empresa_id)
                ->where('status', 'pendente')
                ->whereDate('data_hora_prevista', now()->toDateString())
                ->first();

            if ($agendaHj) {
                $agendamentoId = $agendaHj->id;
                $movimento->agendamento_id = $agendamentoId;
                $movimento->save();
            }
        }

        if ($agendamentoId) {
            \App\Models\PortariaAgendamento::where('id', $agendamentoId)->update(['status' => 'realizado']);
        }

        // 4. --- PROCESSAMENTO INDIVIDUAL DOS ACOMPANHANTES ---
        $acompanhantesValidos = [];
        if ($request->has('acompanhantes_nome')) {
            $nomesAcomp = $request->acompanhantes_nome;
            $cpfsAcomp = $request->acompanhantes_cpf;
            $fotosAcomp = $request->acompanhantes_foto_base64;

            foreach ($nomesAcomp as $index => $nomeAcomp) {
                if (empty($nomeAcomp)) continue;
                $acompanhantesValidos[] = $nomeAcomp; // Guarda o nome para mandar no WhatsApp

                $cpfAcomp = null;
                if (!empty($cpfsAcomp[$index])) {
                    $cpfAcomp = preg_replace('/[^0-9]/', '', $cpfsAcomp[$index]);
                }

                // Busca ou Cria o Acompanhante
                $visitanteAcomp = null;
                if ($cpfAcomp) {
                    $visitanteAcomp = \App\Models\Visitante::where('cpf', $cpfAcomp)
                        ->where('empresa_id', $this->empresa_id)->first();
                }

                if (!$visitanteAcomp) {
                    $visitanteAcomp = \App\Models\Visitante::create([
                        'nome' => $nomeAcomp,
                        'cpf' => $cpfAcomp,
                        'empresa_id' => $this->empresa_id
                    ]);
                }

                // Salva a foto do acompanhante na pasta public
                if (!empty($fotosAcomp[$index])) {
                    try {
                        $image_parts = explode(";base64,", $fotosAcomp[$index]);
                        $image_base64 = base64_decode($image_parts[1]);
                        $nomeArquivoAcomp = 'visitantes/foto_' . $visitanteAcomp->id . '_' . time() . '.jpg';

                        $caminhoPasta = public_path('visitantes');
                        if (!file_exists($caminhoPasta)) { mkdir($caminhoPasta, 0755, true); }

                        file_put_contents(public_path($nomeArquivoAcomp), $image_base64);

                        $visitanteAcomp->foto = $nomeArquivoAcomp;
                        $visitanteAcomp->save();
                    } catch (\Exception $e) {
                        \Log::error("Erro foto acompanhante: " . $e->getMessage());
                    }
                }

                // Registra a Entrada Oficial do Acompanhante Individualmente
                \App\Models\PortariaMovimento::create([
                    'visitante_id' => $visitanteAcomp->id,
                    'funcionario_visitado_id' => $request->funcionario_id,
                    'agendamento_id' => $agendamentoId ?? null,
                    'usuario_porteiro_id' => session('user_logged')['id'],
                    'empresa_id' => $this->empresa_id,
                    'placa_veiculo' => $request->placa_veiculo,
                    'data_hora_entrada' => now(),
                    // Coloca uma observação para indicar que ele entrou de carona com o visitante principal
                    'observacoes' => 'Acompanhante do titular: ' . $visitanteParaAtualizar->nome . ($request->motivo_visita ? ' | Motivo: ' . $request->motivo_visita : '')
                ]);
            }
        }

        // 5. Enviar WhatsApp confirmando entrada
        $funcionario = \App\Models\Funcionario::find($request->funcionario_id);
        $telefoneFuncionario = $funcionario->celular ?? $funcionario->telefone;

        if (!empty($telefoneFuncionario)) {
            $mensagem  = "🚨 *Aviso da Portaria*\n\n";
            $mensagem .= "Olá, *{$funcionario->nome}*.\n";
            $mensagem .= "O visitante principal *{$visitanteParaAtualizar->nome}* teve a entrada autorizada e está se dirigindo até você.";

            if ($request->placa_veiculo) {
                $mensagem .= "\n🚗 Veículo Placa: {$request->placa_veiculo}";
            }

            if (count($acompanhantesValidos) > 0) {
                $mensagem .= "\n👥 Acompanhantes: " . implode(', ', $acompanhantesValidos);
            }

            if ($request->motivo_visita) {
                $mensagem .= "\n📝 Motivo: {$request->motivo_visita}";
            }

            $this->enviarWhatsApp($telefoneFuncionario, $mensagem);
        }

        // Configurado para imprimir a etiqueta apenas do Titular/Motorista
        session()->flash("imprimir_etiqueta_id", $movimento->id);
        session()->flash("mensagem_sucesso", "Entrada de todos registrada com sucesso!");

        return redirect()->back();
    }

    // --- MÉTODO AUXILIAR PARA WHATSAPP ---
    private function enviarWhatsApp($numeroOriginal, $mensagem)
    {
        try {
            $numero = preg_replace('/[^0-9]/', '', $numeroOriginal);

            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') {
                    $numero = "55" . $numero;
                }

                if (class_exists('\App\Utils\WhatsAppUtil')) {
                    $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);

                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numero, $mensagem, $this->empresa_id);
                    } elseif (method_exists($instanciaWhats, 'send')) {
                        $instanciaWhats->send($numero, $mensagem);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro ao enviar WhatsApp Portaria: " . $e->getMessage());
        }
    }


    // --- BUSCA AJAX: CPF ---
    // --- BUSCA AJAX: CPF (COM INTELIGÊNCIA ENTERPRISE) ---
    public function buscaCpf($cpf)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

        $cpfFormatado = $cpfLimpo;
        if (strlen($cpfLimpo) === 11) {
            $cpfFormatado = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpfLimpo);
        }

        $empresa_id = $this->empresa_id;

        // 1. Busca Visitante filtrando obrigatoriamente pela empresa logada
        $queryVisitante = \App\Models\Visitante::where(function($q) use ($cpfLimpo, $cpfFormatado) {
            $q->where('cpf', $cpfLimpo)->orWhere('cpf', $cpfFormatado);
        })->where('empresa_id', $empresa_id); // <-- ISOLAMENTO MULTIEMPRESA GARANTIDO

        $visitante = $queryVisitante->first();

        if ($visitante) {
            // A. VERIFICA LISTA NEGRA
            if ($visitante->bloqueado == 1) {
                return response()->json([
                    'encontrado' => true,
                    'bloqueado' => true,
                    'nome' => $visitante->nome,
                    'motivo_bloqueio' => $visitante->motivo_bloqueio ?? 'Motivo não informado.'
                ]);
            }

            // B. MEMÓRIA DE VISITA (Filtrada pela empresa atual)
            $ultimoAcesso = \App\Models\PortariaMovimento::where('visitante_id', $visitante->id)
                ->where('empresa_id', $empresa_id)
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'encontrado' => true,
                'bloqueado' => false,
                'nome' => $visitante->nome,
                // NOVO: Remove a palavra 'storage/', puxando direto do asset
                'foto' => $visitante->foto ? asset($visitante->foto) : null,
                'ultimo_visitado' => $ultimoAcesso ? $ultimoAcesso->funcionario_visitado_id : null,
                'ultima_placa' => $ultimoAcesso ? $ultimoAcesso->placa_veiculo : null
            ]);
        }

        // 2. Busca Cliente do ERP (caso use a base de clientes da empresa)
        $queryCliente = \App\Models\Cliente::where(function($q) use ($cpfLimpo, $cpfFormatado) {
            $q->where('cpf_cnpj', $cpfLimpo)->orWhere('cpf_cnpj', $cpfFormatado);
        })->where('empresa_id', $empresa_id); // <-- Isolado por empresa

        $cliente = $queryCliente->first();

        if ($cliente) {
            return response()->json([
                'encontrado' => true,
                'bloqueado' => false,
                'nome' => $cliente->razao_social ?? $cliente->nome_fantasia,
                'foto' => null,
                'ultimo_visitado' => null,
                'ultima_placa' => null
            ]);
        }

        return response()->json(['encontrado' => false]);
    }


    // --- BUSCA AJAX: PLACA ---
    public function buscaPlaca($placa)
    {
        $placaLimpa = preg_replace('/[^a-zA-Z0-9]/', '', $placa);

        $placaFormatada = $placaLimpa;
        if (strlen($placaLimpa) >= 7) {
            $placaFormatada = substr($placaLimpa, 0, 3) . '-' . substr($placaLimpa, 3);
        }

        $empresa_id = $this->empresa_id;
        $veiculo = null;

        // Tenta achar no Model de Veículos padrão
        if (class_exists('\App\Models\Veiculo')) {
            $queryVeiculo = \App\Models\Veiculo::where(function($q) use ($placaLimpa, $placaFormatada) {
                $q->where('placa', $placaLimpa)->orWhere('placa', $placaFormatada);
            });
            if ($empresa_id) {
                $queryVeiculo->where('empresa_id', $empresa_id);
            }
            $veiculo = $queryVeiculo->first();
        }

        // Tenta no Model ClienteVeiculo
        if (!$veiculo && class_exists('\App\Models\ClienteVeiculo')) {
            $veiculo = \App\Models\ClienteVeiculo::where(function($q) use ($placaLimpa, $placaFormatada) {
                $q->where('placa', $placaLimpa)->orWhere('placa', $placaFormatada);
            })->first();
        }

        if ($veiculo) {
            return response()->json([
                'encontrado' => true,
                'descricao' => $veiculo->modelo ?? 'Veículo Localizado',
                'cor' => $veiculo->cor ?? ''
            ]);
        }

        return response()->json([
            'encontrado' => false,
            'debug' => [
                'placa_limpa' => $placaLimpa,
                'placa_formatada' => $placaFormatada,
                'empresa_id_detectada' => $empresa_id ?? 'Nula'
            ]
        ]);
    }


    // --- 3. REGISTRAR SAÍDA DA PORTARIA ---
    public function saida($id)
    {
        $movimento = \App\Models\PortariaMovimento::find($id);

        if ($movimento && is_null($movimento->data_hora_saida)) {
            $movimento->data_hora_saida = now();
            $movimento->save();

            session()->flash("mensagem_sucesso", "Saída registrada com sucesso!");
        } else {
            session()->flash("mensagem_erro", "Movimento não encontrado ou saída já registrada.");
        }

        // Redireciona direto para a portaria (se for pelo celular, a tela abrirá confirmando a saída)
        return redirect('/portaria');
    }


    // --- 4. HISTÓRICO DE PORTARIA ---
    public function historico(Request $request)
    {
        $title = 'Histórico de Portaria';

        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $nomeVisitante = $request->nome_visitante;
        $placaVeiculo = $request->placa_veiculo;

        // Inicia a query base
        $query = \App\Models\PortariaMovimento::with(['visitante', 'funcionarioVisitado', 'porteiro'])
            ->where('empresa_id', $this->empresa_id);

        // Filtro de Data
        if ($dataInicial && $dataFinal) {
            $query->whereBetween('data_hora_entrada', [$dataInicial . ' 00:00:00', $dataFinal . ' 23:59:59']);
        } elseif (!$nomeVisitante && !$placaVeiculo) {
            // Padrão: traz apenas os de hoje se não houver NENHUM filtro preenchido
            $query->whereDate('data_hora_entrada', now()->toDateString());
        }

        // Filtro de Nome (Pesquisa dentro do relacionamento Visitante)
        if ($nomeVisitante) {
            $query->whereHas('visitante', function($q) use ($nomeVisitante) {
                $q->where('nome', 'like', "%{$nomeVisitante}%");
            });
        }

        // Filtro de Placa (Na própria tabela de movimento)
        if ($placaVeiculo) {
            $query->where('placa_veiculo', 'like', "%{$placaVeiculo}%");
        }

        $movimentos = $query->orderBy('data_hora_entrada', 'desc')->get();

        return view('portaria.historico', compact(
            'movimentos',
            'title',
            'dataInicial',
            'dataFinal',
            'nomeVisitante',
            'placaVeiculo'
        ));
    }


    /*// --- DISPARO DE WHATSAPP AVULSO (BOTÃO AVISAR) ---
    public function avisarChegadaAjax(Request $request)
    {
        $funcionario = \App\Models\Funcionario::find($request->funcionario_id);

        if ($funcionario) {
            $telefoneFuncionario = $funcionario->celular ?? $funcionario->telefone;

            if (!empty($telefoneFuncionario)) {
                $mensagem  = "🚨 *Aviso da Portaria - Liberação de Visitante*\n\n";
                $mensagem .= "Olá, *{$funcionario->nome}*.\n";
                $mensagem .= "O visitante principal *{$request->nome_visitante}* está na portaria.\n";

                if ($request->placa) {
                    $mensagem .= "🚗 Veículo Placa: {$request->placa}\n";
                }

                // Trata e adiciona os acompanhantes na mensagem
                if (!empty($request->acompanhantes) && is_array($request->acompanhantes)) {
                    $mensagem .= "👥 *Acompanhantes (" . count($request->acompanhantes) . "):*\n";
                    foreach ($request->acompanhantes as $index => $nomeAcomp) {
                        $mensagem .= "   • " . ($index + 1) . ". {$nomeAcomp}\n";
                    }
                }

                if (!empty($request->motivo_visita)) {
                    $mensagem .= "📝 Motivo: {$request->motivo_visita}\n";
                }

                $mensagem .= "\nPor favor, responda:\n";
                $mensagem .= "1️⃣ Para *LIBERAR*\n";
                $mensagem .= "2️⃣ Para *NEGAR*";

                $this->enviarWhatsApp($telefoneFuncionario, $mensagem);

                DB::table('portaria_respostas_temp')->updateOrInsert(
                    [
                        'funcionario_id' => $funcionario->id,
                        'empresa_id' => $this->empresa_id
                    ],
                    [
                        'visitante_nome' => $request->nome_visitante,
                        'status' => 'pendente',
                        'created_at' => now()
                    ]
                );

                return response()->json(['sucesso' => true]);
            }
        }

        return response()->json(['sucesso' => false], 400);
    }*/

    // --- BLOQUEAR / DESBLOQUEAR VISITANTE (LISTA NEGRA) ---
    public function bloquearVisitanteAjax(Request $request)
    {
        // Trava de segurança no backend: só admin pode fazer isso
        if (session('user_logged')['super'] != 1) {
            return response()->json(['sucesso' => false, 'msg' => 'Sem permissão.'], 403);
        }

        $visitante = \App\Models\Visitante::find($request->visitante_id);

        if ($visitante) {
            // Se estiver bloqueando (1), salva o motivo. Se estiver desbloqueando (0), limpa o motivo.
            $visitante->bloqueado = $request->acao_bloquear;
            $visitante->motivo_bloqueio = $request->acao_bloquear == 1 ? $request->motivo : null;
            $visitante->save();

            return response()->json(['sucesso' => true]);
        }

        return response()->json(['sucesso' => false, 'msg' => 'Visitante não encontrado.'], 404);
    }

    // --- GERAR ETIQUETA TÉRMICA (CRACHÁ) ---
    public function imprimirEtiqueta($id)
    {
        $movimento = \App\Models\PortariaMovimento::with(['visitante', 'funcionarioVisitado'])->find($id);

        if (!$movimento) {
            return "Registro não encontrado.";
        }

        return view('portaria.etiqueta', compact('movimento'));
    }

    // --- EDITA O AGENDAMENTO (ABRE A TELA) ---
    public function editarAgendamento($id)
    {
        if (session('user_logged')['adm'] != 1) {
            return redirect('/portaria')->with('erro', 'Acesso negado. Apenas administradores podem editar agendamentos.');
        }

        $agendamento = \App\Models\PortariaAgendamento::with('visitante')->findOrFail($id);
        $empresa_id = $agendamento->empresa_id;

        $funcionarios = \App\Models\Funcionario::where('empresa_id', $empresa_id)->get();

        return view('portaria.editar_agendamento', compact('agendamento', 'funcionarios'));
    }

    // --- SALVA AS ALTERAÇÕES DO AGENDAMENTO ---
    public function atualizarAgendamento(Request $request, $id)
    {
        if (session('user_logged')['adm'] != 1) {
            return redirect('/portaria')->with('erro', 'Acesso negado.');
        }

        $agendamento = \App\Models\PortariaAgendamento::findOrFail($id);

        // Atualiza os dados do agendamento
        $agendamento->funcionario_id = $request->funcionario_id;
        $agendamento->data_hora_prevista = $request->data_hora_prevista;
        $agendamento->placa_veiculo_prevista = $request->placa_veiculo;
        $agendamento->save();

        // Atualiza os dados do visitante associado
        $visitante = \App\Models\Visitante::find($agendamento->visitante_id);
        if ($visitante) {
            $visitante->nome = $request->nome;
            $visitante->cpf = preg_replace('/[^0-9]/', '', $request->cpf);
            $visitante->save();
        }

        return redirect('/portaria')->with('sucesso', 'Agendamento atualizado com sucesso!');
    }

    // --- EXCLUI O AGENDAMENTO ---
    public function excluirAgendamento($id)
    {
        if (session('user_logged')['adm'] != 1) {
            return redirect('/portaria')->with('erro', 'Acesso negado.');
        }

        $agendamento = \App\Models\PortariaAgendamento::findOrFail($id);
        $agendamento->delete();

        return back()->with('sucesso', 'Agendamento excluído com sucesso!');
    }

    /*public function checarRespostaAjax($funcionario_id)
    {
        // 🚀 Procura a resposta mais recente para este funcionário OU a última pendente geral dos últimos 5 minutos
        $resposta = DB::table('portaria_respostas_temp')
            ->where(function($q) use ($funcionario_id) {
                $q->where('funcionario_id', $funcionario_id)
                    ->orWhere('status', 'liberado')
                    ->orWhere('status', 'negado');
            })
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('id', 'desc')
            ->first();

        if ($resposta && ($resposta->status === 'liberado' || $resposta->status === 'negado')) {
            return response()->json([
                'respondido' => true,
                'status' => $resposta->status
            ]);
        }

        return response()->json(['respondido' => false]);
    }*/

    // --- DISPARO DE WHATSAPP AVULSO (COM REENVIO E MOTIVO) ---
    public function avisarChegadaAjax(Request $request)
    {
        $funcionario = \App\Models\Funcionario::find($request->funcionario_id);

        if ($funcionario) {
            $telefoneFuncionario = $funcionario->celular ?? $funcionario->telefone;

            if (!empty($telefoneFuncionario)) {
                // Se for um reenvio, muda o cabeçalho
                $mensagem  = $request->reenvio ? "⚠️ *REENVIO:* Aviso da Portaria\n\n" : "🚨 *Aviso da Portaria - Liberação de Visitante*\n\n";
                $mensagem .= "Olá, *{$funcionario->nome}*.\n";
                $mensagem .= "O visitante principal *{$request->nome_visitante}* está na portaria.\n";

                if ($request->placa) {
                    $mensagem .= "🚗 Veículo Placa: {$request->placa}\n";
                }

                if (!empty($request->acompanhantes) && is_array($request->acompanhantes)) {
                    $mensagem .= "👥 *Acompanhantes (" . count($request->acompanhantes) . "):*\n";
                    foreach ($request->acompanhantes as $index => $nomeAcomp) {
                        $mensagem .= "   • " . ($index + 1) . ". {$nomeAcomp}\n";
                    }
                }

                if (!empty($request->motivo_visita)) {
                    $mensagem .= "📝 Motivo: {$request->motivo_visita}\n";
                }

                $mensagem .= "\nPor favor, responda:\n";
                $mensagem .= "1️⃣ Para *LIBERAR*\n";
                $mensagem .= "2️⃣ Para *NEGAR*\n";
                $mensagem .= "\n_Se negar, pode digitar o motivo na mesma mensagem (Ex: 2 Estou em reunião)_";

                $this->enviarWhatsApp($telefoneFuncionario, $mensagem);

                DB::table('portaria_respostas_temp')->updateOrInsert(
                    [
                        'funcionario_id' => $funcionario->id,
                        'empresa_id' => $this->empresa_id
                    ],
                    [
                        'visitante_nome' => $request->nome_visitante,
                        'status' => 'pendente',
                        'motivo' => null, // Limpa o motivo a cada novo aviso
                        'created_at' => now()
                    ]
                );

                return response()->json(['sucesso' => true]);
            }
        }

        return response()->json(['sucesso' => false], 400);
    }

    // --- VERIFICA SE O FUNCIONÁRIO RESPONDEU ---
    public function checarRespostaAjax($funcionario_id)
    {
        $resposta = DB::table('portaria_respostas_temp')
            ->where(function($q) use ($funcionario_id) {
                $q->where('funcionario_id', $funcionario_id)
                    ->whereIn('status', ['liberado', 'negado']);
            })
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderBy('id', 'desc')
            ->first();

        if ($resposta) {
            return response()->json([
                'respondido' => true,
                'status' => $resposta->status,
                'motivo' => $resposta->motivo
            ]);
        }

        return response()->json(['respondido' => false]);
    }

    // --- CANCELA A ESPERA (QUANDO O PORTEIRO DESISTE OU DÁ TIMEOUT) ---
    public function cancelarEsperaAjax(Request $request)
    {
        $pendente = DB::table('portaria_respostas_temp')
            ->where('funcionario_id', $request->funcionario_id)
            ->where('empresa_id', $this->empresa_id)
            ->where('status', 'pendente')
            ->first();

        if ($pendente) {
            DB::table('portaria_respostas_temp')
                ->where('id', $pendente->id)
                ->update(['status' => 'cancelado']);

            $funcionario = \App\Models\Funcionario::find($request->funcionario_id);
            if ($funcionario) {
                $tel = $funcionario->celular ?? $funcionario->telefone;
                if ($tel) {
                    $this->enviarWhatsApp($tel, "⚠️ *Aviso Cancelado*\n\nO pedido de autorização do visitante *{$pendente->visitante_nome}* foi encerrado na portaria (Timeout ou Desistência).");
                }
            }
        }
        return response()->json(['sucesso' => true]);
    }
}
