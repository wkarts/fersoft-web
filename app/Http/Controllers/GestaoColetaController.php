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

        $coleta->status = 'agendado';
        $coleta->data_agendada = $request->data_agendada;
        $coleta->save();

        $user_logged = session('user_logged');
        $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'];

        if ($request->tipo_transporte == 'frota') {
            // A. INSERE NA FROTA
            \App\Models\MovimentacaoVeiculo::create([
                'empresa_id'           => $coleta->empresa_id,
                'veiculo_id'           => $request->veiculo_id,
                'tipo_movimentacao_id' => 1,
                'data_hora_saida'      => $request->data_agendada,
                'destino'              => $coleta->pessoa->nome_completo,
                'observacao'           => "COLETA DE MATERIAL: " . $coleta->material,
                'status'               => 'agendado'
            ]);
            $msgSucesso = 'Coleta agendada e enviada para a Agenda da Frota!';
        } else {
            // B. INSERE NA PORTARIA

            // Verifica se o cliente já existe como visitante
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

            // Cria o Agendamento na Portaria utilizando o Funcionario informado pelo gestor
            \App\Models\PortariaAgendamento::create([
                'visitante_id'       => $visitante->id,
                'funcionario_id'     => $request->funcionario_id, // Pega o valor do novo campo Select
                'usuario_criador_id' => $usuario_id,
                'empresa_id'         => $coleta->empresa_id,
                'data_hora_prevista' => $request->data_agendada,
                'status'             => 'pendente'
            ]);

            $msgSucesso = 'Coleta agendada! O cliente foi inserido na lista de Visitantes Esperados na Portaria.';
        }

        // ==============================================================
        // DISPARO AUTOMÁTICO DE WHATSAPP DO AGENDAMENTO
        // ==============================================================
        try {
            $numero = preg_replace('/[^0-9]/', '', $coleta->pessoa->telefone);
            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                $dataFmt = date('d/m/Y H:i', strtotime($request->data_agendada));

                if ($request->tipo_transporte == 'frota') {
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
        } catch (\Throwable $e) {
            \Log::error('Erro ao enviar whats no agendamento: ' . $e->getMessage());
        }

        return back()->with('sucesso', $msgSucesso);
    }

    public function recusar($id)
    {
        $coleta = SolicitacaoColeta::findOrFail($id);
        $coleta->status = 'recusado';
        $coleta->save();

        return back()->with('sucesso', 'Solicitação de coleta recusada.');
    }
}
