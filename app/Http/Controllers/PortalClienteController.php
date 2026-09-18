<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PessoaPreCadastro;
use App\Models\SolicitacaoColeta;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class PortalClienteController extends Controller
{

    // --- TELA DE LOGIN ---
    public function login($nome_empresa)
    {
        $nomeBusca = strtolower($nome_empresa);

        // Busca na tabela ConfigNota ignorando espaços (Ex: metalsucaltda acha METAL SUCA LTDA)
        $config = \App\Models\ConfigNota::whereRaw("REPLACE(LOWER(nome_fantasia), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->orWhereRaw("REPLACE(LOWER(razao_social), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->first();

        if (!$config) {
            return abort(404, 'Link inválido ou empresa não encontrada.');
        }

        // Variável $slug criada para manter a compatibilidade com o <form action="..."> da sua view
        $slug = $nome_empresa;

        return view('portal_cliente.login', compact('config', 'slug'));
    }

    // --- AUTENTICAÇÃO ---
    public function autenticar(Request $request, $nome_empresa)
    {
        $nomeBusca = strtolower($nome_empresa);

        // Repete a busca para garantir que está logando na empresa correta
        $config = \App\Models\ConfigNota::whereRaw("REPLACE(LOWER(nome_fantasia), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->orWhereRaw("REPLACE(LOWER(razao_social), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->first();

        if (!$config) {
            return abort(404, 'Link inválido ou empresa não encontrada.');
        }

        $empresa_id = $config->empresa_id;

        // Valida o cliente usando o ID da empresa que encontramos
        $cliente = \App\Models\PessoaPreCadastro::where('cpf_cnpj', $request->cpf_cnpj)
            ->where('empresa_id', $empresa_id)
            ->where('status', 'aprovado')
            ->first();

        if ($cliente && \Illuminate\Support\Facades\Hash::check($request->senha, $cliente->senha)) {
            \Illuminate\Support\Facades\Session::put('cliente_id', $cliente->id);
            \Illuminate\Support\Facades\Session::put('cliente_nome', $cliente->nome_completo);
            \Illuminate\Support\Facades\Session::put('cliente_empresa_id', $cliente->empresa_id);

            return redirect('/portal-cliente/painel');
        }

        return back()->with('erro', 'Documento ou senha inválidos para esta empresa.');
    }

    // --- LOGOUT ---
    public function logout()
    {
        Session::forget(['cliente_id', 'cliente_nome', 'cliente_empresa_id']);
        return redirect('/portal-cliente/login');
    }

    // --- PAINEL PRINCIPAL ---
    public function painel()
    {
        if (!\Illuminate\Support\Facades\Session::has('cliente_id')) {
            return redirect('/');
        }

        $cliente_id = \Illuminate\Support\Facades\Session::get('cliente_id');
        $empresa_id = \Illuminate\Support\Facades\Session::get('cliente_empresa_id');

        $cliente = \App\Models\PessoaPreCadastro::find($cliente_id);
        $coletas = \App\Models\SolicitacaoColeta::where('pessoa_id', $cliente_id)->orderBy('id', 'desc')->get();

        // Busca os dados visuais da empresa (Logo e Nome)
        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();

        // NOVO: Busca os funcionários para o cliente escolher na portaria
        $funcionarios = \App\Models\Funcionario::where('empresa_id', $empresa_id)->orderBy('nome')->get();

        return view('portal_cliente.painel', compact('cliente', 'coletas', 'config', 'funcionarios'));
    }

    // --- SALVAR A COLETA COM FOTOS E AVISAR A EQUIPE ---
    public function storeColeta(Request $request)
    {
        if (!\Illuminate\Support\Facades\Session::has('cliente_id')) {
            return redirect('/portal-cliente/login');
        }

        $caminhosFotos = [];
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('coletas', 'public');
                $caminhosFotos[] = $path;
            }
        }

        $empresa_id = \Illuminate\Support\Facades\Session::get('cliente_empresa_id');
        $cliente_id = \Illuminate\Support\Facades\Session::get('cliente_id');
        $nomeCliente = \Illuminate\Support\Facades\Session::get('cliente_nome');

        // Formata os desejos do cliente e joga no campo Observação para o Gestor ler
        $obsFinal = $request->observacao;
        if ($request->tipo_transporte == 'cliente' && $request->filled('data_hora_prevista')) {
            $obsFinal .= "\n\n[SUGESTÃO DO CLIENTE]\n- Data desejada para trazer: " . date('d/m/Y H:i', strtotime($request->data_hora_prevista));

            if ($request->filled('funcionario_id')) {
                $func = \App\Models\Funcionario::find($request->funcionario_id);
                if($func) {
                    $obsFinal .= "\n- Deseja procurar por: " . $func->nome;
                }
            }
        }

        // 1. Cria a solicitação e TRAVA o status como "pendente" (Em Análise)
        $coleta = \App\Models\SolicitacaoColeta::create([
            'empresa_id'       => $empresa_id,
            'pessoa_id'        => $cliente_id,
            'material'         => $request->material,
            'dias_disponiveis' => $request->dias_disponiveis,
            'observacao'       => $obsFinal, // Salva o que ele quer aqui
            'fotos'            => json_encode($caminhosFotos),
            'tipo_transporte'  => $request->tipo_transporte,
            'status'           => 'pendente' // Nada vai pra Portaria nem pra Frota ainda!
        ]);

        // 2. Avisar os Gestores por WhatsApp para eles aprovarem
        try {
            $funcionariosAviso = \App\Models\Funcionario::where('empresa_id', $empresa_id)
                ->where('recebe_alerta_coleta', 1)
                ->get();

            foreach ($funcionariosAviso as $funcAviso) {
                $numero = preg_replace('/[^0-9]/', '', $funcAviso->celular ?? $funcAviso->telefone);

                if (strlen($numero) >= 10) {
                    if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                    $modalidadeTexto = ($request->tipo_transporte == 'frota') ? "🚚 Frota da Empresa" : "🏢 Cliente quer trazer na Portaria";
                    $dataVisita = ($request->tipo_transporte == 'cliente' && $request->filled('data_hora_prevista')) ? "\n📅 Data sugerida: " . date('d/m/Y H:i', strtotime($request->data_hora_prevista)) : "";

                    $mensagem = "🔔 *Nova Solicitação de Coleta (EM ANÁLISE)!*\n\n";
                    $mensagem .= "Cliente: *{$nomeCliente}*\n";
                    $mensagem .= "📦 Material: *{$request->material}*\n";
                    $mensagem .= "⚡ Modalidade: *{$modalidadeTexto}*{$dataVisita}\n\n";
                    $mensagem .= "Acesse a Gestão de Solicitações no ERP para aprovar e agendar oficialmente.";

                    $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numero, $mensagem, $empresa_id);
                    } elseif (method_exists($instanciaWhats, 'send')) {
                        $instanciaWhats->send($numero, $mensagem);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro Whatsapp Notificação Equipe (Coleta): " . $e->getMessage());
        }

        // 3. O cliente vê que está "Em Análise"
        return back()->with('sucesso', 'Sua solicitação foi enviada e está em análise! Nossa equipe confirmará o agendamento e o horário em breve.');
    }


    // --- TELA DE ESQUECI A SENHA ---
    public function esqueciSenha($slug)
    {
        return view('portal_cliente.esqueci_senha', compact('slug'));
    }



    // --- GERAR NOVA SENHA E ENVIAR ---
    public function recuperarSenha(Request $request, $slug)
    {
        // 1. Limpa os números do documento digitado
        $docDigitado = preg_replace('/[^0-9]/', '', $request->cpf_cnpj);

        // 2. Busca o cliente aprovado ignorando formatação
        $cliente = \App\Models\PessoaPreCadastro::where('status', 'aprovado')
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$docDigitado])
            ->first();

        if (!$cliente) {
            return back()->with('erro', 'Documento não encontrado ou cadastro ainda não aprovado.');
        }

        // Gera a nova senha
        $novaSenha = rand(100000, 999999);
        $cliente->senha = bcrypt($novaSenha);
        $cliente->save();

        // 3. Disparo de WhatsApp robusto para o cliente
        try {
            $numero = preg_replace('/[^0-9]/', '', $cliente->telefone);
            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') {
                    $numero = "55" . $numero;
                }

                $mensagem = "Olá, *{$cliente->nome_completo}*!\n\nSua nova senha de acesso ao portal de coletas é: *{$novaSenha}*";

                $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                if (method_exists($instanciaWhats, 'sendMessage')) {
                    $instanciaWhats->sendMessage($numero, $mensagem, $cliente->empresa_id);
                } elseif (method_exists($instanciaWhats, 'send')) {
                    $instanciaWhats->send($numero, $mensagem);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro Whatsapp Recuperação de Senha: " . $e->getMessage());
        }

        return redirect("/portal-cliente/{$slug}/login")->with('sucesso', 'Uma nova senha foi gerada e enviada para o seu WhatsApp!');
    }


}
