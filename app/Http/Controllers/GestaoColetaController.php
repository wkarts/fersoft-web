<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SolicitacaoColeta;
use App\Models\Veiculo;
use App\Models\Funcionario; // Adicionado para carregar os funcionários

class GestaoColetaController extends Controller
{
    public function index()
    {
        $empresa_id = request()->empresa_id;

        // Busca as coletas pendentes e carrega os dados do cliente junto
        $coletas = SolicitacaoColeta::with('pessoa')
            ->where('empresa_id', $empresa_id)
            ->where('status', 'pendente')
            ->orderBy('created_at', 'asc')
            ->get();

        // Busca os veículos para o atendente escolher qual vai fazer a coleta
        $veiculos = Veiculo::where('empresa_id', $empresa_id)->get();

        // NOVO: Busca todos os funcionários da empresa para a portaria
        $funcionarios = Funcionario::where('empresa_id', $empresa_id)->orderBy('nome')->get();

        $title = "Gestão de Coletas";

        return view('gestao_coletas.index', compact('coletas', 'veiculos', 'funcionarios', 'title'));
    }

    public function agendar(Request $request, $id)
    {
        $coleta = \App\Models\SolicitacaoColeta::with('pessoa')->findOrFail($id);

        $user_logged = session('user_logged');
        $usuario_id = $user_logged['id'] ?? ($user_logged['usuario_id'] ?? null);

        // Define o tipo de transporte: prioriza o que o atendente escolheu no modal, ou o que o cliente pediu
        $tipoTransporte = $request->input('tipo_transporte') ?: ($coleta->tipo_transporte ?: 'frota');

        \DB::beginTransaction();

        try {
            $coleta->status = 'agendado';
            $coleta->data_agendada = $request->data_agendada;
            $coleta->save();

            $movimentacaoCriada = null;

            if ($tipoTransporte == 'frota') {
                // Monta o endereço da coleta
                $enderecoColeta = trim(($coleta->pessoa->rua_coleta ?? 'Endereço não informado') . ', ' .
                    ($coleta->pessoa->numero_coleta ?? 'S/N') . ' - ' .
                    ($coleta->pessoa->bairro_coleta ?? ''));

                // Busca o cliente pelo CPF/CNPJ limpo (apenas números)
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $coleta->pessoa->cpf_cnpj ?? '');
                $clienteERP = \App\Models\Cliente::where('empresa_id', $coleta->empresa_id)
                    ->where(function($q) use ($cnpjLimpo, $coleta) {
                        if (!empty($cnpjLimpo)) {
                            $q->whereRaw("REGEXP_REPLACE(cpf_cnpj, '[^0-9]', '') = ?", [$cnpjLimpo])
                                ->orWhere('cpf_cnpj', $coleta->pessoa->cpf_cnpj);
                        } else {
                            $q->where('razao_social', 'like', '%' . $coleta->pessoa->nome_completo . '%');
                        }
                    })->first();

                // Antes de criar a movimentação de frota (bloco "// A. CRIAÇÃO DA MOVIMENTAÇÃO DE FROTA"), faça a busca:
                $tipoMovimentacao = \App\Models\TipoMovimentacao::where('empresa_id', $coleta->empresa_id)
                    ->where('nome', 'Coleta') // ou 'COLETA', dependendo de como está cadastrado
                    ->first();

                // Se não encontrar, você pode definir uma tratativa de erro ou criar um na hora
                if (!$tipoMovimentacao) {
                    throw new \Exception("Tipo de movimentação 'Coleta' não encontrado para esta empresa.");
                }

                // A. CRIAÇÃO DA MOVIMENTAÇÃO DE FROTA
                $movimentacaoCriada = \App\Models\MovimentacaoVeiculo::create([
                    'empresa_id'           => $coleta->empresa_id,
                    'usuario_id'           => $usuario_id,
                    'veiculo_id'           => $request->veiculo_id,
                    'tipo_movimentacao_id' => $tipoMovimentacao->id, // <- Usa o ID buscado dinamicamente aqui
                    'motorista_id'         => $request->motorista_id ?: null,
                    'cliente_id'           => $clienteERP ? $clienteERP->id : null,
                    'destino'              => $coleta->pessoa->nome_completo . ' - ' . $enderecoColeta,
                    'data_hora_saida'      => $request->data_agendada,
                    'status'               => 'agendado',
                    'gerar_mtr'            => 1,
                    'observacao'           => "Gerado via Solicitação de Coleta #{$coleta->id} - Material: " . $coleta->material
                ]);

                // B. INSERE O DESTINO MULTI-STOP VINCULADO
                \App\Models\MovimentacaoDestino::create([
                    'movimentacao_veiculo_id' => $movimentacaoCriada->id,
                    'cliente_id'              => $clienteERP ? $clienteERP->id : null,
                    'endereco_completo'       => $enderecoColeta,
                    'latitude'                => $coleta->pessoa->latitude ?? null,
                    'longitude'               => $coleta->pessoa->longitude ?? null,
                    'ordem'                   => 1,
                    'status'                  => 'pendente',
                    'produto_residuo_id'      => $coleta->produto_residuo_id ?: null,
                    'caixa_deixada_id'        => $coleta->produto_cacamba_id ?: null,
                    'quantidade_residuo'      => $coleta->peso_estimado ?: null,
                    'unidade_residuo'         => $coleta->unidade_peso ?? 'KG',
                    'tipo_operacao_caixa'     => $coleta->produto_cacamba_id ? 'colocar' : 'nenhuma'
                ]);

                $msgSucesso = 'Coleta agendada e Movimentação de Frota gerada com sucesso!';
            } else {
                // C. INSERE NA PORTARIA (se o cliente trouxer)
                $visitante = \App\Models\Visitante::where('cpf', $coleta->pessoa->cpf_cnpj)
                    ->where('empresa_id', $coleta->empresa_id)
                    ->first();

                if (!$visitante) {
                    $visitante = \App\Models\Visitante::create([
                        'nome'       => substr($coleta->pessoa->nome_completo, 0, 100),
                        'cpf'        => $coleta->pessoa->cpf_cnpj,
                        'telefone'   => $coleta->pessoa->telefone,
                        'empresa_id' => $coleta->empresa_id
                    ]);
                }

                \App\Models\PortariaAgendamento::create([
                    'visitante_id'       => $visitante->id,
                    'funcionario_id'     => $request->funcionario_id,
                    'usuario_criador_id' => $usuario_id,
                    'empresa_id'         => $coleta->empresa_id,
                    'data_hora_prevista' => $request->data_agendada,
                    'status'             => 'pendente'
                ]);

                $msgSucesso = 'Coleta agendada! O cliente foi inserido na lista da Portaria.';
            }

            \DB::commit();

        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Erro ao agendar coleta: ' . $e->getMessage() . ' na linha ' . $e->getLine());
            return back()->with('mensagem_erro', 'Erro ao salvar agendamento: ' . $e->getMessage());
        }

        // ==============================================================
        // DISPARO AUTOMÁTICO DE WHATSAPP DO AGENDAMENTO
        // ==============================================================
        try {
            if ($coleta->pessoa && !empty($coleta->pessoa->telefone)) {
                $numero = preg_replace('/[^0-9]/', '', $coleta->pessoa->telefone);
                if (strlen($numero) >= 10) {
                    if (!str_starts_with($numero, '55')) $numero = "55" . $numero;

                    $dataFmt = date('d/m/Y H:i', strtotime($request->data_agendada));

                    if ($tipoTransporte == 'frota') {
                        $mensagem = "Olá, *{$coleta->pessoa->nome_completo}*!\nSua coleta de *{$coleta->material}* foi confirmada. Nossa frota passará no dia *{$dataFmt}*.";
                    } else {
                        $mensagem = "Olá, *{$coleta->pessoa->nome_completo}*!\nO recebimento do seu material (*{$coleta->material}*) foi agendado para o dia *{$dataFmt}*. Seu acesso já está liberado na nossa portaria!";
                    }

                    $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numero, $mensagem, $coleta->empresa_id);
                    } elseif (method_exists($instanciaWhats, 'send')) {
                        $instanciaWhats->send($numero, $mensagem);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Erro ao enviar whats no agendamento: ' . $e->getMessage());
        }

        // Se criou movimentação de frota, abre direto a tela de edição
        if ($movimentacaoCriada) {
            return redirect('/movimentacaoVeiculo/edit/' . $movimentacaoCriada->id)
                ->with('sucesso', $msgSucesso);
        }

        return back()->with('sucesso', $msgSucesso);
    }

    public function recusar(Request $request, $id)
    {
        $coleta = SolicitacaoColeta::findOrFail($id);

        $motivo = $request->input('motivo', 'Ajuste necessário na programação');

        $coleta->status = 'recusado';
        $coleta->observacao .= "\n\n[MOTIVO DA REJEIÇÃO]: " . $motivo;
        $coleta->save();

        // ==============================================================
        // DISPARO AUTOMÁTICO DE WHATSAPP DA RECUSA / REJEIÇÃO
        // ==============================================================
        try {
            if ($coleta->pessoa && !empty($coleta->pessoa->telefone)) {
                $numero = preg_replace('/[^0-9]/', '', $coleta->pessoa->telefone);
                if (strlen($numero) >= 10) {
                    if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                    $mensagem = "Olá, *{$coleta->pessoa->nome_completo}*.\n\n";
                    $mensagem .= "Sua solicitação de coleta/caçamba (#{$coleta->id}) não pôde ser aprovada neste momento.\n";
                    $mensagem .= "📌 *Motivo:* " . $motivo . "\n\n";
                    $mensagem .= "Acesse o portal para realizar um novo agendamento com as correções.";

                    $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numero, $mensagem, $coleta->empresa_id);
                    } elseif (method_exists($instanciaWhats, 'send')) {
                        $instanciaWhats->send($numero, $mensagem);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Erro ao enviar whats na recusa de coleta: ' . $e->getMessage());
        }

        return back()->with('sucesso', 'Solicitação de coleta recusada e aviso enviado ao parceiro via WhatsApp.');
    }
}
