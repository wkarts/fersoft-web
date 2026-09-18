<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PessoaPreCadastro;
use Illuminate\Support\Facades\Mail;

class PessoaPreCadastroController extends Controller
{
    // ==========================================
    // ÁREA PÚBLICA (Sem Login)
    // ==========================================

    public function createPublico($nome_empresa)
    {
        $nomeBusca = strtolower($nome_empresa);

        // Busca na tabela ConfigNota ignorando espaços (Ex: metalsuca acha Metal Suca)
        $config = \App\Models\ConfigNota::whereRaw("REPLACE(LOWER(nome_fantasia), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->orWhereRaw("REPLACE(LOWER(razao_social), ' ', '') LIKE ?", ['%' . $nomeBusca . '%'])
            ->first();

        if (!$config) {
            return abort(404, 'Link de cadastro inválido ou empresa não encontrada.');
        }

        $empresa_id = $config->empresa_id;

        return view('pre_cadastro.formulario', compact('empresa_id', 'config'));
    }

    public function storePublico(Request $request)
    {
        $dados = $request->all();
        $dados['status'] = 'pendente';

        $pessoa = PessoaPreCadastro::create($dados);

        // ==============================================================
        // 1. DISPARO DE WHATSAPP AUTOMÁTICO PARA O CLIENTE
        // ==============================================================
        try {
            $numero = preg_replace('/[^0-9]/', '', $pessoa->telefone);
            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                $mensagem = "Olá, *{$pessoa->nome_completo}*!\n\nRecebemos suas informações com sucesso. Em breve nossa equipe fará a validação do seu cadastro para liberação do portal de coletas.";

                $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                if (method_exists($instanciaWhats, 'sendMessage')) {
                    $instanciaWhats->sendMessage($numero, $mensagem, $pessoa->empresa_id);
                } elseif (method_exists($instanciaWhats, 'send')) {
                    $instanciaWhats->send($numero, $mensagem);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro Whatsapp Recebimento: " . $e->getMessage());
        }

        // ==============================================================
        // 2. DISPARO DE E-MAIL DINÂMICO PARA A EQUIPE DA EMPRESA
        // ==============================================================
        try {
            $config = \App\Models\ConfigNota::where('empresa_id', $pessoa->empresa_id)->first();

            if ($config && !empty($config->email)) {
                $emailEquipe = $config->email;
                $assunto = "Novo Parceiro Pendente: " . $pessoa->nome_completo;

                $corpoEmail = "Olá equipe,\n\n";
                $corpoEmail .= "Um novo parceiro acabou de se cadastrar no portal e está aguardando aprovação.\n\n";
                $corpoEmail .= "Empresa/Nome: {$pessoa->nome_completo}\n";
                $corpoEmail .= "Documento: {$pessoa->cpf_cnpj}\n";
                $corpoEmail .= "Telefone: {$pessoa->telefone}\n\n";
                $corpoEmail .= "Acesse o Fersoft ERP > Gestão de Frota > Pré-cadastros para aprovar e liberar o acesso.";

                Mail::raw($corpoEmail, function ($message) use ($emailEquipe, $assunto) {
                    $message->to($emailEquipe)->subject($assunto);
                });
            }
        } catch (\Throwable $e) {
            \Log::error("Erro ao enviar email de alerta interno: " . $e->getMessage());
        }

        // ==============================================================
        // 3. AVISAR OS FUNCIONÁRIOS MARCADOS VIA WHATSAPP (Novo Cadastro)
        // ==============================================================
        try {
            $funcionarios = \App\Models\Funcionario::where('empresa_id', $pessoa->empresa_id)
                ->where('recebe_alerta_coleta', 1)
                ->get();

            foreach ($funcionarios as $func) {
                $numeroFunc = preg_replace('/[^0-9]/', '', $func->celular ?? $func->telefone);

                if (strlen($numeroFunc) >= 10) {
                    if (substr($numeroFunc, 0, 2) !== '55') $numeroFunc = "55" . $numeroFunc;

                    $mensagem = "🚨 *Novo Parceiro Cadastrado!*\n\n";
                    $mensagem .= "A empresa *{$pessoa->nome_completo}* preencheu o formulário de captação.\n";
                    $mensagem .= "Acesse o ERP > Gestão de Frota > Pré-cadastros para aprovar e liberar o acesso dele.";

                    $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                    if (method_exists($instanciaWhats, 'sendMessage')) {
                        $instanciaWhats->sendMessage($numeroFunc, $mensagem, $pessoa->empresa_id);
                    } elseif (method_exists($instanciaWhats, 'send')) {
                        $instanciaWhats->send($numeroFunc, $mensagem);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro Whatsapp Notificação Equipe (Cadastro): " . $e->getMessage());
        }

        return response()->json(['sucesso' => true, 'mensagem' => 'Cadastro enviado para análise! Em breve entraremos em contato.']);
    }


    // ==========================================
    // ÁREA INTERNA DO ERP (Com Login)
    // ==========================================

    public function index()
    {
        // Pega a empresa da sessão/token atual do usuário logado
        $empresa_id = request()->empresa_id;

        $pendentes = PessoaPreCadastro::where('empresa_id', $empresa_id)
            ->where('status', 'pendente')
            ->get();

        // Variável de título exigida pelo layout do Fersoft ERP
        $title = 'Pré-Cadastros Pendentes';

        // Agora enviamos o $title junto com os $pendentes
        return view('pre_cadastro.index', compact('pendentes', 'title'));
    }



    public function aprovar(Request $request, $id)
    {
        $pessoa = \App\Models\PessoaPreCadastro::findOrFail($id);

        $docLimpo = preg_replace('/[^0-9]/', '', $pessoa->cpf_cnpj);
        $senhaGerada = substr($docLimpo, 0, 6);

        $pessoa->status = 'aprovado';
        $pessoa->senha = bcrypt($senhaGerada);
        $pessoa->save();

        $tipoCadastro = $request->input('tipo_cadastro', 'cliente');

        try {
            // BUSCA EXATA DA CIDADE (Cruzando Nome e UF para evitar pegar cidade de outro estado)
            $cidadeObj = null;
            if (!empty($pessoa->cidade_coleta) && !empty($pessoa->estado_coleta)) {
                $cidadeObj = \App\Models\Cidade::where('uf', strtoupper(trim($pessoa->estado_coleta)))
                    ->where(function($q) use ($pessoa) {
                        $q->where('nome', 'like', trim($pessoa->cidade_coleta))
                            ->orWhere('nome', 'like', '%' . trim($pessoa->cidade_coleta) . '%');
                    })->first();
            }
            $cidadeId = $cidadeObj ? $cidadeObj->id : 1; // Fallback seguro

            // Dados comuns mapeados do pré-cadastro
            $dadosParceiro = [
                'empresa_id'    => $pessoa->empresa_id,
                'razao_social'  => $pessoa->nome_completo,
                'nome_fantasia' => $pessoa->nome_completo,
                'cpf_cnpj'      => $pessoa->cpf_cnpj,
                'ie_rg'         => $pessoa->rg_ie ?? 'ISENTO',
                'rua'           => $pessoa->rua_coleta ?? 'Não informada',
                'numero'        => $pessoa->numero_coleta ?? 'S/N',
                'bairro'        => $pessoa->bairro_coleta ?? 'Não informado',
                'cep'           => $pessoa->cep_coleta ?? '00000-000',
                'cidade_id'     => $cidadeId,
                'telefone'      => $pessoa->telefone ?? '',
                'celular'       => $pessoa->telefone ?? '',
                'email'         => $pessoa->email ?? '',
                'latitude'      => $pessoa->latitude ?? null,
                'longitude'     => $pessoa->longitude ?? null,
            ];

            // ==========================================================
            // 1. IMPORTAR OU ATUALIZAR CLIENTES (Evita duplicidade / reativa)
            // ==========================================================
            if ($tipoCadastro == 'cliente' || $tipoCadastro == 'ambos') {
                $cliente = \App\Models\Cliente::where('empresa_id', $pessoa->empresa_id)
                    ->where('cpf_cnpj', $pessoa->cpf_cnpj)
                    ->first();

                $dadosCliente = array_merge($dadosParceiro, [
                    'consumidor_final' => 1,
                    'contribuinte'     => 1,
                    'inativo'          => 0, // Reativa ou mantém ativo
                    'rua_cobranca'     => $pessoa->rua_coleta ?? '',
                    'numero_cobranca'  => $pessoa->numero_coleta ?? '',
                    'bairro_cobranca'  => $pessoa->bairro_coleta ?? '',
                    'cep_cobranca'     => $pessoa->cep_coleta ?? '',
                    'cidade_cobranca_id' => $cidadeId,
                    'rua_entrega'      => $pessoa->rua_coleta ?? '',
                    'nome_entrega'     => $pessoa->nome_completo ?? '',
                    'cpf_cnpj_entrega' => $pessoa->cpf_cnpj ?? '',
                    'numero_entrega'   => $pessoa->numero_coleta ?? '',
                    'bairro_entrega'   => $pessoa->bairro_coleta ?? '',
                    'cep_entrega'      => $pessoa->cep_coleta ?? '',
                    'cidade_entrega_id'=> $cidadeId,
                ]);

                if ($cliente) {
                    $cliente->update($dadosCliente); // Atualiza e reativa o existente
                } else {
                    \App\Models\Cliente::create($dadosCliente); // Cria se não existir
                }
            }

            // ==========================================================
            // 2. IMPORTAR OU ATUALIZAR FORNECEDORES (Evita duplicidade / reativa)
            // ==========================================================
            if ($tipoCadastro == 'fornecedor' || $tipoCadastro == 'ambos') {
                $fornecedor = \App\Models\Fornecedor::where('empresa_id', $pessoa->empresa_id)
                    ->where('cpf_cnpj', $pessoa->cpf_cnpj)
                    ->first();

                $dadosFornecedor = array_merge($dadosParceiro, [
                    'ativo'        => 1, // Reativa ou mantém ativo
                    'pix'          => $pessoa->chave_pix ?? '',
                    'tipo_pix'     => 'chave aleatória',
                    'banco'        => $pessoa->banco ?? '',
                    'agencia'      => $pessoa->agencia ?? '',
                    'conta'        => $pessoa->conta ?? '',
                    'contribuinte' => 1,
                ]);

                if ($fornecedor) {
                    $fornecedor->update($dadosFornecedor); // Atualiza e reativa o existente
                } else {
                    \App\Models\Fornecedor::create($dadosFornecedor); // Cria se não existir
                }
            }

        } catch (\Throwable $e) {
            \Log::error("Erro ao importar dados para Cliente/Fornecedor: " . $e->getMessage());
        }

        // ==========================================================
        // 3. DISPARO DO WHATSAPP DE LIBERAÇÃO DO PORTAL
        // ==========================================================
        try {
            $numero = preg_replace('/[^0-9]/', '', $pessoa->telefone);
            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                $config = \App\Models\ConfigNota::where('empresa_id', $pessoa->empresa_id)->first();
                $nomeEmpresaSlug = $config ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $config->nome_fantasia ?? $config->razao_social)) : $pessoa->empresa_id;

                $linkAcesso = url('/portal-cliente/' . $nomeEmpresaSlug . '/login');

                $mensagem = "Parabéns, *{$pessoa->nome_completo}*! 🎉\n\n";
                $mensagem .= "Seu cadastro foi *aprovado* e integrado ao nosso sistema. Você já pode acessar nosso portal para solicitar suas coletas!\n\n";
                $mensagem .= "🌐 *Link de Acesso:* {$linkAcesso}\n";
                $mensagem .= "👤 *Usuário (CPF/CNPJ):* {$docLimpo}\n";
                $mensagem .= "🔑 *Senha:* {$senhaGerada}\n\n";
                $mensagem .= "Aguardamos suas solicitações!";

                $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                if (method_exists($instanciWhats ?? $instanciaWhats, 'sendMessage')) {
                    $instanciaWhats->sendMessage($numero, $mensagem, $pessoa->empresa_id);
                } elseif (method_exists($instanciaWhats, 'send')) {
                    $instanciaWhats->send($numero, $mensagem);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro Whatsapp Aprovação: " . $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Cadastro aprovado, integrado ao ERP (com verificação de duplicidade e cidade exata) e acesso enviado!');
    }


    public function aprovados(Request $request)
    {
        // Pega o empresa_id da requisição ou do usuário logado para garantir que não venha vazio
        $empresa_id = $request->empresa_id ?? (session('user_logged')['empresa_id'] ?? null);

        $query = PessoaPreCadastro::query();

        if ($empresa_id) {
            $query->where('empresa_id', $empresa_id);
        }

        // Busca aceitando variações de maiúsculas/minúsculas no status
        $aprovados = $query->where(function($q) {
            $q->where('status', 'aprovado')
                ->orWhere('status', 'Aprovado');
        })
            ->orderBy('updated_at', 'desc')
            ->get();

        $title = 'Parceiros Aprovados';
        return view('pre_cadastro.aprovados', compact('aprovados', 'title'));
    }

    public function rejeitar(Request $request, $id)
    {
        $pessoa = \App\Models\PessoaPreCadastro::findOrFail($id);

        $pessoa->status = 'rejeitado';
        $pessoa->motivo_rejeicao = $request->input('motivo');
        $pessoa->save();

        try {
            $numero = preg_replace('/[^0-9]/', '', $pessoa->telefone);
            if (strlen($numero) >= 10) {
                if (substr($numero, 0, 2) !== '55') $numero = "55" . $numero;

                $mensagem = "Olá, *{$pessoa->nome_completo}*.\n\n";
                $mensagem .= "Analisamos seu cadastro, mas precisamos de alguns ajustes para liberá-lo.\n";
                $mensagem .= "📌 *Motivo / Correção necessária:* " . $request->input('motivo') . "\n\n";
                $mensagem .= "Por favor, preencha o formulário novamente com a correção solicitada.";

                $instanciaWhats = app(\App\Utils\WhatsAppUtil::class);
                if (method_exists($instanciaWhats, 'sendMessage')) {
                    $instanciaWhats->sendMessage($numero, $mensagem, $pessoa->empresa_id);
                } elseif (method_exists($instanciaWhats, 'send')) {
                    $instanciaWhats->send($numero, $mensagem);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Erro Whatsapp Rejeição: " . $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Cadastro rejeitado e aviso enviado ao cliente via WhatsApp.');
    }
}
