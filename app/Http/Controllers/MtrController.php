<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SinirIemaService;
use Exception;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Http\Controllers\MtrController;

class MtrController extends Controller
{
    private function getDadosSessao()
    {
        $session = session('user_logged');
        return [
            'empresa_id' => $session['empresa'] ?? null,
            'usuario_id' => $session['id'] ?? null,
        ];
    }

    public function index(Request $request)
{
    $sessao = $this->getDadosSessao();

    $query = DB::table('mtr_manifestos')
        ->where('empresa_id', $sessao['empresa_id']);

    // Filtros de Pesquisa
    if ($request->filled('numero_mtr')) {
        $query->where(function($q) use ($request) {
            $q->where('numero_mtr', 'like', '%' . $request->numero_mtr . '%')
              ->orWhere('seu_codigo', 'like', '%' . $request->numero_mtr . '%');
        });
    }

    if ($request->filled('envolvido')) {
        $query->where(function($q) use ($request) {
            $q->where('gerador_nome', 'like', '%' . $request->envolvido . '%')
              ->orWhere('destinador_nome', 'like', '%' . $request->envolvido . '%');
        });
    }

    if ($request->filled('transportadora')) {
        $query->where('transportador_nome', 'like', '%' . $request->transportadora . '%');
    }

    if ($request->filled('motorista')) {
        $query->where('motorista_nome', 'like', '%' . $request->motorista . '%');
    }

    if ($request->filled('placa')) {
        $placaLimpa = preg_replace('/[^a-zA-Z0-9]/', '', $request->placa);
        $query->where('veiculo_placa', 'like', '%' . $placaLimpa . '%');
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('data_inicio') && $request->filled('data_fim')) {
        $query->whereBetween('data_expedicao', [
            $request->data_inicio . ' 00:00:00',
            $request->data_fim . ' 23:59:59'
        ]);
    }

    $manifestos = $query->orderBy('id', 'desc')->paginate(15)->appends($request->all());

    // Busca Vendas (Oculta as que já têm MTR gerado)
    $vendasImportacao = DB::table('vendas')
        ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
        ->where('vendas.empresa_id', $sessao['empresa_id'])
        // Verifica se a venda já não foi importada
        ->whereNotIn('vendas.id', function($q) use ($sessao) {
            $q->select('origem_id')->from('mtr_manifestos')
              ->where('empresa_id', $sessao['empresa_id'])
              ->where('tipo_origem', 'nfe')
              ->whereNotNull('origem_id');
        })
        ->select('vendas.*', 'clientes.razao_social as cliente_nome')
        ->orderBy('vendas.id', 'desc')
        ->limit(50)
        ->get();

    // Busca Pesagens (Apenas Finalizadas e que não foram importadas)
    $ticketsImportacao = DB::table('pesagens')
        ->leftJoin('clientes', 'clientes.id', '=', 'pesagens.cliente_id')
        ->where('pesagens.empresa_id', $sessao['empresa_id'])
        ->where(function($query) {
            // Regra para considerar a pesagem como finalizada
            $query->whereNotNull('pesagens.peso_final')
                  ->orWhere('pesagens.peso_final', '>', 0)
                  ->orWhere('pesagens.status', 'FINALIZADA'); 
        })
        // Verifica se a pesagem já não foi importada
        ->whereNotIn('pesagens.id', function($q) use ($sessao) {
            $q->select('origem_id')->from('mtr_manifestos')
              ->where('empresa_id', $sessao['empresa_id'])
              ->where('tipo_origem', 'pesagem')
              ->whereNotNull('origem_id');
        })
        ->select(
            'pesagens.*', 
            'clientes.razao_social as cliente_nome',
            DB::raw('COALESCE(pesagens.peso_final, pesagens.peso_liquido_bruto, pesagens.peso) as peso_calculado')
        )
        ->orderBy('pesagens.id', 'desc')
        ->limit(50)
        ->get();

    $title = 'Central de Emissão de MTR';
    
    return view('mtr.emissao.index', compact('manifestos', 'vendasImportacao', 'ticketsImportacao', 'title'));
}

    public function createAvulso()
    {
        $sessao = $this->getDadosSessao();

        $unidadesGeradoras = DB::table('mtr_configs')
            ->where('empresa_id', $sessao['empresa_id'])
            ->where('perfil', 'Gerador')
            ->where('ativo', 1)
            ->get();

        $clientes = DB::table('clientes')
            ->where('empresa_id', $sessao['empresa_id'])
            ->select('id', 'razao_social', 'cpf_cnpj')
            ->orderBy('razao_social')
            ->get();

        $title = 'Emissão de MTR';

        return view('mtr.emissao.create', compact('unidadesGeradoras', 'clientes', 'title'))->with('origem', 'avulso');
    }

    public function createPesagem($pesagem_id)
    {
        $sessao = $this->getDadosSessao();
        
        $pesagem = DB::table('pesagens')->where('id', $pesagem_id)->where('empresa_id', $sessao['empresa_id'])->first();
        if (!$pesagem) {
            return redirect()->route('mtr.emissao.index')->with('erro', 'Pesagem não encontrada.');
        }

        $tickets = DB::table('tickets_pesagem')->where('pesagem_id', $pesagem_id)->get();
        $pesoBruto = $tickets->where('tipo', 'entrada')->sum('peso');
        $pesoTara  = $tickets->where('tipo', 'saida')->sum('peso');
        $pesoBag   = $tickets->sum('peso_bag');
        
        $pesoLiquido = max(0, ($pesoBruto - $pesoTara - $pesoBag));
        if ($pesagem->impureza_desconto > 0) {
            $pesoLiquido -= ($pesoLiquido * ($pesagem->impureza_desconto / 100));
        }

        $produtoId = $tickets->first()->produto_id ?? null;
        $depara = null;
        $produtoNome = 'Diversos/Sucatas';

        if ($produtoId) {
            $produto = DB::table('produtos')->where('id', $produtoId)->first();
            
            if ($produto) {
                $produtoNome = $produto->nome;
                
                // Busca a regra De-Para (Por Produto, Subcategoria ou NCM)
                $depara = DB::table('mtr_depara_residuos')
                    ->where('empresa_id', $sessao['empresa_id'])
                    ->where(function($query) use ($produto) {
                        $query->where('produto_id', $produto->id)
                              ->orWhere('sub_categoria_id', $produto->sub_categoria_id)
                              ->orWhere('ncm', $produto->NCM);
                    })
                    ->first();
            }
        }

        $residuosParaMtr = [
            [
                'codigo_ibama'     => $depara->cod_ibama ?? '',
                'quantidade'       => $pesoLiquido,
                'unidade'          => $depara->unidade_medida ?? 2,
                'estado_fisico'    => $depara->estado_fisico ?? 1,
                'classe_residuo'   => $depara->classe_residuo ?? 42,
                'acondicionamento' => $depara->acondicionamento_id ?? 8,
                'tratamento'       => $depara->tratamento_id ?? 43
            ]
        ];

        $cliente = DB::table('clientes')->where('id', $pesagem->cliente_id)->first();

        // Monta a Observação Automática
        $nfeNum = $pesagem->nfe ?? $pesagem->numero_nfe ?? '';
        $obsText = "Produto: {$produtoNome} | Ticket Pesagem: #{$pesagem_id}";
        if (!empty($nfeNum)) {
            $obsText .= " | NF-e: {$nfeNum}";
        }

        return redirect()->route('mtr.emissao.create.avulso')
            ->with('destinador_cnpj', $cliente->cpf_cnpj ?? null)
            ->with('placa_veiculo', $pesagem->placa_veiculo ?? '')
            ->with('motorista_nome', $pesagem->motorista_nome ?? '')
            ->with('origem_tipo', 'pesagem')
            ->with('origem_id', $pesagem_id)
            ->with('residuos_importados', $residuosParaMtr)
            ->with('observacao_importada', $obsText) // Passa a observação para a tela
            ->with('sucesso', 'Ticket de Pesagem importado com sucesso!');
    }

    public function createNfe($venda_id)
    {
        $sessao = $this->getDadosSessao();
        
        $venda = DB::table('vendas')->where('id', $venda_id)->where('empresa_id', $sessao['empresa_id'])->first();
        if (!$venda) {
            return redirect()->route('mtr.emissao.index')->with('erro', 'Venda não encontrada.');
        }

        $itensVenda = DB::table('item_vendas')->where('venda_id', $venda_id)->get();
        $residuosParaMtr = [];
        $nomesProdutos = [];

        foreach ($itensVenda as $item) {
            $produto = DB::table('produtos')->where('id', $item->produto_id)->first();
            $depara = null;

            if ($produto) {
                $nomesProdutos[] = $produto->nome;
                
                // Busca a regra De-Para (Por Produto, Subcategoria ou NCM)
                $depara = DB::table('mtr_depara_residuos')
                    ->where('empresa_id', $sessao['empresa_id'])
                    ->where(function($query) use ($produto) {
                        $query->where('produto_id', $produto->id)
                              ->orWhere('sub_categoria_id', $produto->sub_categoria_id)
                              ->orWhere('ncm', $produto->NCM);
                    })
                    ->first();
            }

            $residuosParaMtr[] = [
                'codigo_ibama'     => $depara->cod_ibama ?? '',
                'quantidade'       => $item->quantidade,
                'unidade'          => $depara->unidade_medida ?? 2,
                'estado_fisico'    => $depara->estado_fisico ?? 1,
                'classe_residuo'   => $depara->classe_residuo ?? 42,
                'acondicionamento' => $depara->acondicionamento_id ?? 8,
                'tratamento'       => $depara->tratamento_id ?? 43
            ];
        }

        $cliente = DB::table('clientes')->where('id', $venda->cliente_id)->first();
        
        // Monta a Observação Automática
        $nfeNum = $venda->numero_nfe ?? $venda->nfe ?? $venda->id;
        $produtosUnicos = implode(', ', array_unique($nomesProdutos));
        $obsText = "NF-e: {$nfeNum} | Produtos: {$produtosUnicos}";

        return redirect()->route('mtr.emissao.create.avulso')
            ->with('destinador_cnpj', $cliente->cpf_cnpj ?? null)
            ->with('origem_tipo', 'nfe')
            ->with('origem_id', $venda_id)
            ->with('residuos_importados', $residuosParaMtr)
            ->with('observacao_importada', $obsText) // Passa a observação para a tela
            ->with('sucesso', 'Itens da Nota Fiscal importados com sucesso!');
    }
  
    public function salvarRascunho(Request $request)
    {
        $sessao = $this->getDadosSessao();

        try {
            DB::beginTransaction();

            $gerador = DB::table('mtr_configs')->where('id', $request->gerador_id)->first();
            $transportador = DB::table('clientes')->where('cpf_cnpj', preg_replace('/\D/', '', $request->transportador_cnpj))->first();
            $destinador = DB::table('clientes')->where('cpf_cnpj', preg_replace('/\D/', '', $request->destinador_cnpj))->first();

            $tipoOrigem = $request->input('origem_tipo', 'avulso');
            
            $armazenadorCnpj = $request->has('possui_armazenamento') ? preg_replace('/\D/', '', $request->armazenador_cnpj) : null;

            // Tratamento da Data de Expedição
            $dataExpInput = empty($request->data_expedicao) ? now() : $request->data_expedicao;
            $dataExpedicaoSegura = Carbon::parse($dataExpInput)->format('Y-m-d H:i:s');

            $mtrId = DB::table('mtr_manifestos')->insertGetId([
                'empresa_id'         => $sessao['empresa_id'],
                'usuario_id'         => $sessao['usuario_id'],
                'orgao'              => $gerador->orgao ?? 'SINIR',
                'tipo_origem'        => $tipoOrigem,
             	'origem_id'          => $request->input('origem_id') ?: null,
                'seu_codigo'         => 'MTR-' . time(),
                'data_expedicao'     => $dataExpedicaoSegura,
                
                'gerador_cnpj'       => preg_replace('/\D/', '', $gerador->cpf_cnpj ?? ''),
                'gerador_nome'       => $gerador->descricao ?? 'GERADOR',
                
                'transportador_cnpj' => preg_replace('/\D/', '', $request->transportador_cnpj),
                'transportador_nome' => $transportador->razao_social ?? 'TRANSPORTADOR',
                
                'destinador_cnpj'    => preg_replace('/\D/', '', $request->destinador_cnpj),
                'destinador_nome'    => $destinador->razao_social ?? 'DESTINADOR',
                
                'armazenador_cnpj'   => $armazenadorCnpj,
                
                'veiculo_placa'      => preg_replace('/[^a-zA-Z0-9]/', '', $request->placa_veiculo),
                'motorista_nome'     => $request->nome_motorista,
                'observacao'         => $request->observacao,
                'status'             => 'rascunho',
                
                'created_at'         => now(),
                'updated_at'         => now()
            ]);

            if ($request->has('residuos')) {
                foreach ($request->residuos as $residuo) {
                    DB::table('mtr_manifesto_itens')->insert([
                        'mtr_manifesto_id'    => $mtrId,
                        'cod_ibama'           => preg_replace('/\D/', '', $residuo['codigo_ibama']),
                        'descricao_residuo'   => 'RESÍDUO DECLARADO',
                        'quantidade'          => (float) $residuo['quantidade'],
                        'unidade_medida'      => $residuo['unidade'] ?? 2,
                        'estado_fisico'       => $residuo['estado_fisico'] ?? 1,
                        'classe_residuo' 	  => $residuo['classe_residuo'] ?? 43,
                        'acondicionamento_id' => $residuo['acondicionamento_id'] ?? $residuo['acondicionamento'] ?? null,
						'tratamento_id'       => $residuo['tratamento_id'] ?? $residuo['tratamento'] ?? null,
                        'created_at'          => now(),
                        'updated_at'          => now()
                    ]);
                }
            }

            DB::commit();

            if ($request->acao === 'transmitir') {
                return $this->transmitir($request, $mtrId);
            }

            return redirect()->route('mtr.emissao.index')->with('sucesso', 'MTR salvo como rascunho com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('erro', 'Erro ao salvar: ' . $e->getMessage())->withInput();
        
        }
    }

    public function edit($id)
    {
        $sessao = $this->getDadosSessao();

        $mtr = DB::table('mtr_manifestos')->where('id', $id)->where('empresa_id', $sessao['empresa_id'])->first();
        if (!$mtr) return redirect()->route('mtr.emissao.index')->with('erro', 'MTR não encontrado.');

        if ($mtr->status == 'transmitido') {
            return redirect()->route('mtr.emissao.index')->with('erro', 'MTRs já transmitidos não podem ser editados. Cancele no SINIR se necessário.');
        }

        $itens = DB::table('mtr_manifesto_itens')->where('mtr_manifesto_id', $id)->get();

        $unidadesGeradoras = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->where('perfil', 'Gerador')->where('ativo', 1)->get();
        $clientes = DB::table('clientes')->where('empresa_id', $sessao['empresa_id'])->orderBy('razao_social')->get();

        $title = 'Editar Rascunho MTR';

        return view('mtr.emissao.edit', compact('mtr', 'itens', 'unidadesGeradoras', 'clientes', 'title'));
    }

    public function update(Request $request, $id)
    {
        $sessao = $this->getDadosSessao();

        try {
            DB::beginTransaction();

            $mtr = DB::table('mtr_manifestos')->where('id', $id)->where('empresa_id', $sessao['empresa_id'])->first();
            if (!$mtr || $mtr->status == 'transmitido') throw new Exception("Edição não permitida.");

            $gerador = DB::table('mtr_configs')->where('id', $request->gerador_id)->first();
            $transportador = DB::table('clientes')->where('cpf_cnpj', preg_replace('/\D/', '', $request->transportador_cnpj))->first();
            $destinador = DB::table('clientes')->where('cpf_cnpj', preg_replace('/\D/', '', $request->destinador_cnpj))->first();

            $armazenadorCnpj = $request->has('possui_armazenamento') ? preg_replace('/\D/', '', $request->armazenador_cnpj) : null;

            $dataExpInput = empty($request->data_expedicao) ? now() : $request->data_expedicao;
            $dataExpedicaoSegura = Carbon::parse($dataExpInput)->format('Y-m-d H:i:s');

            DB::table('mtr_manifestos')->where('id', $id)->update([
                'gerador_cnpj'       => preg_replace('/\D/', '', $gerador->cpf_cnpj ?? ''),
                'gerador_nome'       => $gerador->descricao ?? 'GERADOR',
                'transportador_cnpj' => preg_replace('/\D/', '', $request->transportador_cnpj),
                'transportador_nome' => $transportador->razao_social ?? 'TRANSPORTADOR',
                'destinador_cnpj'    => preg_replace('/\D/', '', $request->destinador_cnpj),
                'destinador_nome'    => $destinador->razao_social ?? 'DESTINADOR',
                'armazenador_cnpj'   => $armazenadorCnpj,
                'veiculo_placa'      => preg_replace('/[^a-zA-Z0-9]/', '', $request->placa_veiculo),
                'motorista_nome'     => $request->nome_motorista,
                'data_expedicao'     => $dataExpedicaoSegura,
                'observacao'         => $request->observacao,
                'updated_at'         => now()
            ]);

            DB::table('mtr_manifesto_itens')->where('mtr_manifesto_id', $id)->delete();

            if ($request->has('residuos')) {
                foreach ($request->residuos as $residuo) {
                    DB::table('mtr_manifesto_itens')->insert([
                        'mtr_manifesto_id'    => $id,
                        'cod_ibama'           => preg_replace('/\D/', '', $residuo['codigo_ibama']),
                        'descricao_residuo'   => 'RESÍDUO DECLARADO',
                        'quantidade'          => (float) $residuo['quantidade'],
                        'unidade_medida'      => $residuo['unidade'] ?? 2,
                        'estado_fisico'       => $residuo['estado_fisico'] ?? 1,
                        'classe_residuo' 	  => $residuo['classe_residuo'] ?? 43,
                        'acondicionamento_id' => $residuo['acondicionamento'] ?? null,
                        'tratamento_id'       => $residuo['tratamento'] ?? null,
                        'created_at'          => now(),
                        'updated_at'          => now()
                    ]);
                }
            }

            DB::commit();

            if ($request->acao === 'transmitir') {
                return $this->transmitir($request, $id);
            }

            return redirect()->route('mtr.emissao.index')->with('sucesso', 'Rascunho atualizado com sucesso!');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('erro', 'Erro ao atualizar: ' . $e->getMessage());
        }
    }
  
    public function transmitir(Request $request, $id)
    {
        try {
            $sessao = $this->getDadosSessao();

            $mtr = DB::table('mtr_manifestos')->where('id', $id)->where('empresa_id', $sessao['empresa_id'])->first();
            if (!$mtr) throw new Exception("MTR não encontrado.");

            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->where('cpf_cnpj', $mtr->gerador_cnpj)->first();
            if (!$credencial) throw new Exception("Credenciais não configuradas para o Gerador.");

            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $itensMtr = DB::table('mtr_manifesto_itens')->where('mtr_manifesto_id', $id)->get();
            $listaManifestoResiduos = [];

            foreach ($itensMtr as $item) {
                $uniCodigo = (int) ($item->unidade_medida ?? 0);
                if ($uniCodigo <= 0) {
                    throw new Exception("A Unidade de Medida não foi informada corretamente para o item do resíduo.");
                }

                $tiaCodigo = (int) ($item->acondicionamento_id ?? 0);
                if ($tiaCodigo <= 0) {
                    throw new Exception("O Acondicionamento não foi informado corretamente para o item do resíduo.");
                }

                $traCodigo = (int) ($item->tratamento_id ?? 0);
                if ($traCodigo <= 0) {
                    throw new Exception("O Tratamento do resíduo é obrigatório e não foi informado no item.");
                }

                $tieCodigo = (int) ($item->estado_fisico ?? 1);
                
                $claCodigo = (int) ($item->classe_residuo ?? 43);
                if ($claCodigo <= 0) {
                    throw new Exception("A Classe do resíduo é obrigatória e não foi informada.");
                }

                $listaManifestoResiduos[] = [
                    'resCodigoIbama' => (string) preg_replace('/\D/', '', $item->cod_ibama),
                    'marQuantidade'  => (float) $item->quantidade,
                    'uniCodigo'      => $uniCodigo,
                    'tiaCodigo'      => $tiaCodigo,
                    'traCodigo'      => $traCodigo,
                    'tieCodigo'      => $tieCodigo,
                    'claCodigo'      => $claCodigo,
                ];
            }

            $dataExpedicao = empty($mtr->data_expedicao) ? Carbon::now() : Carbon::parse($mtr->data_expedicao);
            $timestampMilissegundos = (int) ($dataExpedicao->timestamp * 1000);
            
            $possuiArmazenamento = !empty($mtr->armazenador_cnpj);

            $unidadeGerador = (int) $credencial->unidade_id;

            $unidadeTransportador = DB::table('mtr_configs')
                ->where('cpf_cnpj', preg_replace('/\D/', '', $mtr->transportador_cnpj))
                ->value('unidade_id');

            $unidadeDestinador = DB::table('mtr_configs')
                ->where('cpf_cnpj', preg_replace('/\D/', '', $mtr->destinador_cnpj))
                ->value('unidade_id');

            if (!$unidadeTransportador) throw new Exception("Código de Unidade do Transportador não encontrado no cadastro de Credenciais MTR.");
            if (!$unidadeDestinador) throw new Exception("Código de Unidade do Destinador não encontrado no cadastro de Credenciais MTR.");

            $manifestoData = [
                'gerador' => [
                    'cpfCnpj' => (string) preg_replace('/\D/', '', $mtr->gerador_cnpj),
                    'unidade' => $unidadeGerador
                ],
                'transportador' => [
                    'cpfCnpj' => (string) preg_replace('/\D/', '', $mtr->transportador_cnpj),
                    'unidade' => (int) $unidadeTransportador
                ],
                'destinador' => [
                    'cpfCnpj' => (string) preg_replace('/\D/', '', $mtr->destinador_cnpj),
                    'unidade' => (int) $unidadeDestinador
                ],
                'listaManifestoResiduos'        => $listaManifestoResiduos,
                'nomeMotorista'                 => (string) mb_strtoupper($mtr->motorista_nome ?? 'NAO INFORMADO'),
                'placaVeiculo'                  => (string) strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $mtr->veiculo_placa ?? 'AAA0000')),
                'dataExpedicao'                 => $timestampMilissegundos,
                'possuiArmazenamentoTemporario' => $possuiArmazenamento,
                'nomeResponsavel'               => 'Responsável Técnico',
                'tipoManifesto'                 => 0,
                'seuCodigo'                     => (string) ($mtr->id . '-' . time()),
                'observacoes'                   => (string) ($mtr->observacao ?? "")
            ];

            if ($possuiArmazenamento) {
                $unidadeArmazenador = DB::table('mtr_configs')
                    ->where('cpf_cnpj', preg_replace('/\D/', '', $mtr->armazenador_cnpj))
                    ->value('unidade_id');

                $manifestoData['armazenadorTemporario'] = [
                    'cpfCnpj' => (string) preg_replace('/\D/', '', $mtr->armazenador_cnpj),
                    'unidade' => (int) ($unidadeArmazenador ?? 1)
                ];
            }

            $payloadMtr = [ $manifestoData ];

            $resposta = $service->transmitirManifesto($token, $credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id, $payloadMtr);
            
            \Log::error('RESPOSTA COMPLETA SINIR:', ['response' => $resposta]);
          
            $numeroMtrGerado = $resposta['respostaApiwsManifestoDTO'][0]['manifestoNumeroNacional'] 
                        ?? $resposta['respostaApiwsManifestoDTO'][0]['manNumero'] 
                        ?? $resposta[0]['manifestoNumeroNacional']
                        ?? $resposta['manNumero'] 
                        ?? null;

            if ($numeroMtrGerado) {
                DB::table('mtr_manifestos')->where('id', $id)->update(['status' => 'transmitido', 'numero_mtr' => $numeroMtrGerado]);
                return redirect()->route('mtr.emissao.index')->with('sucesso', "MTR Oficial Gerado com Sucesso: " . $numeroMtrGerado);
            }

            if (isset($resposta['respostaApiwsManifestoDTO'][0]['resposta']['mensagem'])) {
                throw new Exception("Rejeição SINIR: " . $resposta['respostaApiwsManifestoDTO'][0]['resposta']['mensagem']);
            }

            if (isset($resposta['respostaApiwsManifestoDTO'][0]['restResponseMensagem'])) {
                throw new Exception("Rejeição SINIR: " . $resposta['respostaApiwsManifestoDTO'][0]['restResponseMensagem']);
            }

            throw new Exception("Rejeição SINIR. JSON Bruto: " . json_encode($resposta));

        } catch (Exception $e) {
            return redirect()->route('mtr.emissao.index')->with('erro', "Erro na transmissão: " . $e->getMessage());
        }
    }
  
    public function downloadPdf($numero_mtr)
    {
        try {
            $sessao = $this->getDadosSessao();
            
            $mtr = DB::table('mtr_manifestos')
                ->where('empresa_id', $sessao['empresa_id'])
                ->where(function($query) use ($numero_mtr) {
                    $query->where('id', $numero_mtr)
                          ->orWhere('numero_mtr', $numero_mtr);
                })->first();

            if (!$mtr || empty($mtr->numero_mtr)) {
                return redirect()->back()->with('erro', 'MTR não encontrado ou ainda não transmitido para gerar PDF.');
            }

            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->where('cpf_cnpj', $mtr->gerador_cnpj)->first();
            if (!$credencial) throw new Exception("Credenciais não configuradas.");

            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = "https://admin.sinir.gov.br/api/downloadManifesto/{$mtr->numero_mtr}";
            if ($credencial->ambiente !== 'producao') {
                $endpoint = "https://admin-homologacao.sinir.gov.br/api/downloadManifesto/{$mtr->numero_mtr}";
            }

            $response = \Illuminate\Support\Facades\Http::withToken($token)->withHeaders(['Accept' => 'application/pdf'])->post($endpoint);

            if ($response->failed()) {
                throw new Exception("Não foi possível baixar o PDF do MTR no SINIR.");
            }

            return response($response->body(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="MTR_' . $mtr->numero_mtr . '.pdf"',
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with('erro', 'Erro ao baixar PDF: ' . $e->getMessage());
        }
    }

    public function downloadCdf($numero_mtr)
    {
        try {
            $sessao = $this->getDadosSessao();
            
            $mtr = DB::table('mtr_manifestos')
                ->where('empresa_id', $sessao['empresa_id'])
                ->where(function($query) use ($numero_mtr) {
                    $query->where('id', $numero_mtr)
                          ->orWhere('numero_mtr', $numero_mtr);
                })->first();

            if (!$mtr || empty($mtr->numero_mtr)) {
                return redirect()->back()->with('erro', 'Manifesto não encontrado para emissão do CDF.');
            }

            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->where('cpf_cnpj', $mtr->gerador_cnpj)->first();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = "https://admin.sinir.gov.br/api/downloadCertificado/{$mtr->numero_mtr}";
            if ($credencial->ambiente !== 'producao') {
                $endpoint = "https://admin-homologacao.sinir.gov.br/api/downloadCertificado/{$mtr->numero_mtr}";
            }

            $response = \Illuminate\Support\Facades\Http::withToken($token)->withHeaders(['Accept' => 'application/pdf'])->post($endpoint);

            if ($response->failed()) {
                throw new Exception("CDF ainda não disponível ou não gerado pelo destinador.");
            }

            return response($response->body(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="CDF_' . $mtr->numero_mtr . '.pdf"',
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with('erro', 'Erro ao baixar CDF: ' . $e->getMessage());
        }
    }

    public function cancelar(Request $request, $id)
    {
        try {
            $sessao = $this->getDadosSessao();
            
            $mtr = DB::table('mtr_manifestos')->where('id', $id)->where('empresa_id', $sessao['empresa_id'])->first();
            if (!$mtr) throw new Exception("MTR não encontrado.");

            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->where('cpf_cnpj', $mtr->gerador_cnpj)->first();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api/cancelarManifesto"
                : "https://admin-homologacao.sinir.gov.br/api/cancelarManifesto";

            $payload = [
                'manNumero'     => (string) $mtr->numero_mtr,
                'justificativa' => (string) ($request->input('justificativa', 'Cancelamento solicitado pelo gerador no sistema Fersoft.'))
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($token)->post($endpoint, $payload);

            if ($response->failed()) {
                throw new Exception("Falha ao cancelar no SINIR: " . strip_tags($response->body()));
            }

            DB::table('mtr_manifestos')->where('id', $id)->update([
                'status'     => 'cancelado',
                'updated_at' => now()
            ]);

            return redirect()->route('mtr.emissao.index')->with('sucesso', 'Manifesto cancelado com sucesso no SINIR!');

        } catch (Exception $e) {
            return redirect()->route('mtr.emissao.index')->with('erro', 'Erro ao cancelar MTR: ' . $e->getMessage());
        }
    }

    public function consultarStatus($id)
    {
        try {
            $sessao = $this->getDadosSessao();
            $mtr = DB::table('mtr_manifestos')->where('id', $id)->where('empresa_id', $sessao['empresa_id'])->first();

            if (!$mtr || empty($mtr->numero_mtr)) {
                return response()->json(['success' => false, 'message' => 'MTR não encontrado ou sem número oficial.']);
            }

            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->where('cpf_cnpj', $mtr->gerador_cnpj)->first();
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api/consultarManifesto/{$mtr->numero_mtr}"
                : "https://admin-homologacao.sinir.gov.br/api/consultarManifesto/{$mtr->numero_mtr}";

            $response = \Illuminate\Support\Facades\Http::withToken($token)->get($endpoint);

            if ($response->successful()) {
                $dados = $response->json();
                
                $situacao = $dados['situacao'] ?? $dados['status'] ?? 'Emitido';
                $novoStatus = 'transmitido';

                if (stripos($situacao, 'Recebido') !== false) {
                    $novoStatus = 'recebido';
                } elseif (stripos($situacao, 'Cancelado') !== false) {
                    $novoStatus = 'cancelado';
                }

                DB::table('mtr_manifestos')->where('id', $id)->update(['status' => $novoStatus]);

                return response()->json([
                    'success' => true,
                    'status'  => $novoStatus,
                    'situacao_descricao' => $situacao,
                    'message' => "Status atualizado no portal MTR: {$situacao}"
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Não foi possível consultar a situação no SINIR.']);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function enviarWhatsAppMtr(Request $request)
    {
        try {
            $empresaId = session('user_logged')['empresa'] ?? 1;
            $numero = preg_replace('/[^0-9]/', '', $request->whatsapp);

            if (strlen($numero) < 10) {
                return response()->json(['success' => false, 'message' => 'Número de WhatsApp inválido.']);
            }

            if (substr($numero, 0, 2) !== '55') {
                $numero = "55" . $numero;
            }

            $mtr = DB::table('mtr_manifestos')->where('id', $request->mtr_id)->first();
            if (!$mtr) {
                return response()->json(['success' => false, 'message' => 'MTR não encontrado.']);
            }

            $urlPdf = route('mtr.emissao.pdf', $mtr->numero_mtr ?? $mtr->id);
            $urlCdf = route('mtr.emissao.cdf', $mtr->numero_mtr ?? $mtr->id);

            $mensagem  = "📄 *MANIFESTO DE TRANSPORTE DE RESÍDUOS (MTR)*\n";
            $mensagem .= "----------------------------------------\n";
            $mensagem .= "*Nº MTR Oficial:* " . ($mtr->numero_mtr ?? 'Pendente') . "\n";
            $mensagem .= "*Gerador:* " . $mtr->gerador_nome . "\n";
            $mensagem .= "*Destinador:* " . $mtr->destinador_nome . "\n";
            $mensagem .= "*Placa Veículo:* " . $mtr->veiculo_placa . " | *Motorista:* " . $mtr->motorista_nome . "\n\n";
            $mensagem .= "🔗 *Link do MTR Oficial (PDF):*\n" . $urlPdf . "\n\n";
            
            if ($mtr->status === 'recebido') {
                $mensagem .= "🎓 *Certificado de Destinação Final (CDF):*\n" . $urlCdf . "\n\n";
            }
            
            $mensagem .= "_Enviado via Fersoft ERP System_";

            if (!class_exists('\App\Utils\WhatsAppUtil')) {
                return response()->json(['success' => false, 'message' => 'Módulo de WhatsApp não disponível no servidor.']);
            }

            $instanciaWhats = app('\App\Utils\WhatsAppUtil');

            if (method_exists($instanciaWhats, 'sendMessage')) {
                $instanciaWhats->sendMessage($numero, $mensagem, $empresaId);
            } elseif (method_exists($instanciaWhats, 'send')) {
                $instanciaWhats->send($numero, $mensagem);
            }

            return response()->json(['success' => true, 'message' => 'MTR enviado via WhatsApp com sucesso!']);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao enviar WhatsApp: ' . $e->getMessage()]);
        }
    }
  
    public function destroy($id)
    {
        $sessao = $this->getDadosSessao();
        $mtr = DB::table('mtr_manifestos')->where('id', $id)->where('empresa_id', $sessao['empresa_id'])->first();
        
        if (!$mtr) return redirect()->back()->with('erro', 'MTR não encontrado.');
        if ($mtr->status == 'transmitido') return redirect()->back()->with('erro', 'MTRs já transmitidos não podem ser excluídos, apenas cancelados.');

        DB::table('mtr_manifesto_itens')->where('mtr_manifesto_id', $id)->delete();
        DB::table('mtr_manifestos')->where('id', $id)->delete();

        return redirect()->route('mtr.emissao.index')->with('sucesso', 'Rascunho excluído com sucesso.');
    }
  
    // --- CENTRAL DE RECEPÇÃO E TRANSPORTE DE MTR (COM VALIDAÇÃO DE 5 DIAS E SALVAMENTO LOCAL) ---
    public function recepcaoIndex(Request $request)
    {
        $sessao = $this->getDadosSessao();
        $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->first();

        $mtrsTransportador = [];
        $mtrsDestinador = [];

        // Define período padrão de 5 dias se não informado
        $dtInicio = $request->input('dt_inicio_dest', date('Y-m-d', strtotime('-5 days')));
        $dtFim    = $request->input('dt_fim_dest', date('Y-m-d'));

        // Valida se o período excede 5 dias (regra exigida pelo SINIR/Órgãos)
        $diffDias = Carbon::parse($dtInicio)->diffInDays(Carbon::parse($dtFim));
        if ($diffDias > 5) {
            return redirect()->back()->with('erro', 'O período máximo permitido para consulta no SINIR é de 5 dias.');
        }

        try {
            if ($credencial) {
                $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
                $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

                $baseUri = ($credencial->ambiente === 'producao')
                    ? "https://admin.sinir.gov.br/api"
                    : "https://admin-homologacao.sinir.gov.br/api";

                // Consulta Transportador
                $paramsTransp = ['dataInicio' => $dtInicio, 'dataFim' => $dtFim];
                if ($request->filled('mtr_transp')) {
                    $paramsTransp['mtr'] = trim($request->input('mtr_transp'));
                }
                $resTransp = \Illuminate\Support\Facades\Http::withToken($token)->get("{$baseUri}/manifestosTransportador", $paramsTransp);
                if ($resTransp->successful()) {
                    $mtrsTransportador = $resTransp->json() ?? [];
                }

                // 2. Consulta MTRs como Destinador (Com Log de Diagnóstico)
                $paramsDest = [
                    'dataInicio' => $dtInicio,
                    'dataFim'    => $dtFim,
                ];
                if ($request->filled('mtr_dest')) {
                    $paramsDest['mtr'] = trim($request->input('mtr_dest'));
                }

                $urlDest = "{$baseUri}/manifestosDestinador";
                $resDest = \Illuminate\Support\Facades\Http::withToken($token)->get($urlDest, $paramsDest);

                // LOG PARA VERIFICAR O RETORNO NO ARQUIVO laravel.log
                \Log::info('DIAGNOSTICO SINIR DESTINADOR', [
                    'url'    => $urlDest,
                    'params' => $paramsDest,
                    'status' => $resDest->status(),
                    'body'   => $resDest->body()
                ]);

                if ($resDest->successful()) {
                    $mtrsDestinador = $resDest->json() ?? [];
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Aviso ao buscar MTRs externos no SINIR: " . $e->getMessage());
        }

        $title = 'Recepção e Transporte de MTR (SINIR)';
        return view('mtr.recepcao.index', compact('mtrsTransportador', 'mtrsDestinador', 'dtInicio', 'dtFim', 'title'));
    }

    // --- SALVAR O MTR RECEBIDO NO BANCO DE DADOS LOCAL DO ERP ---
    public function salvarRecebimentoMtr(Request $request)
    {
        try {
            $sessao = $this->getDadosSessao();
            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->first();
            
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api/receberManifesto"
                : "https://admin-homologacao.sinir.gov.br/api/receberManifesto";

            $dataRec = empty($request->data_recebimento) ? Carbon::now() : Carbon::parse($request->data_recebimento);
            $timestampRec = (int) ($dataRec->timestamp * 1000);

            $payload = [
                'manNumero'              => (string) $request->numero_mtr,
                'responsavelRecebimento' => (string) $request->responsavel,
                'dataRecebimento'        => $timestampRec,
                'residuos'               => $request->residuos ?? []
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($token)->post($endpoint, $payload);

            if ($response->failed()) {
                throw new \Exception(strip_tags($response->body()));
            }

            // GRAVAÇÃO LOCAL NO BANCO DE DADOS (mtr_manifestos e mtr_manifesto_itens)
            DB::beginTransaction();
            
            $existe = DB::table('mtr_manifestos')
                ->where('empresa_id', $sessao['empresa_id'])
                ->where('numero_mtr', $request->numero_mtr)
                ->first();

            if (!$existe) {
                $mtrId = DB::table('mtr_manifestos')->insertGetId([
                    'empresa_id'       => $sessao['empresa_id'],
                    'usuario_id'       => $sessao['usuario_id'],
                    'orgao'            => $credencial->orgao ?? 'SINIR',
                    'tipo_origem'      => 'recebimento_externo',
                    'numero_mtr'       => $request->numero_mtr,
                    'data_expedicao'   => now(),
                    'destinador_cnpj'  => preg_replace('/\D/', '', $credencial->cpf_cnpj),
                    'destinador_nome'  => 'EMPRESA DESTINATÁRIA',
                    'motorista_nome'   => $request->motorista ?? 'N/INF',
                    'veiculo_placa'    => $request->placa ?? '',
                    'status'           => 'recebido',
                    'created_at'       => now(),
                    'updated_at'       => now()
                ]);

                if ($request->has('residuos')) {
                    foreach ($request->residuos as $residuo) {
                        DB::table('mtr_manifesto_itens')->insert([
                            'mtr_manifesto_id'    => $mtrId,
                            'cod_ibama'           => $residuo['cod_ibama'] ?? '000000',
                            'descricao_residuo'   => $residuo['descricao'] ?? 'RESÍDUO RECEBIDO',
                            'quantidade'          => (float) ($residuo['quantidadeRecebida'] ?? 0),
                            'unidade_medida'      => 2,
                            'created_at'          => now(),
                            'updated_at'          => now()
                        ]);
                    }
                }
            } else {
                DB::table('mtr_manifestos')->where('id', $existe->id)->update([
                    'status'     => 'recebido',
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'MTR recebido e salvo com sucesso no ERP!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro ao processar recebimento: ' . $e->getMessage()]);
        }
    }

    public function receberPorCodigoBarras(Request $request)
    {
        try {
            parse_str($request->getContent(), $dadosForm);
            $numeroMtr = $request->input('codigo_barras') ?? ($dadosForm['codigo_barras'] ?? null);

            if (empty($numeroMtr)) {
                throw new Exception("Informe o número do MTR para recebimento.");
            }

            $sessao = $this->getDadosSessao();
            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->first();
            
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api/receberManifesto"
                : "https://admin-homologacao.sinir.gov.br/api/receberManifesto";

            $response = \Illuminate\Support\Facades\Http::withToken($token)->post($endpoint, [
                'manNumero' => trim($numeroMtr)
            ]);

            if ($response->failed()) {
                throw new Exception(strip_tags($response->body()));
            }

            return redirect()->route('mtr.recepcao.index')->with('sucesso', 'MTR recebido e confirmado com sucesso no SINIR!');
        } catch (\Exception $e) {
            return redirect()->route('mtr.recepcao.index')->with('erro', 'Erro ao receber MTR: ' . $e->getMessage());
        }
    }
  
    public function receberPorProvisorio(Request $request)
    {
        try {
            $sessao = $this->getDadosSessao();
            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->first();
            
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api/receberManifestoProvisorio"
                : "https://admin-homologacao.sinir.gov.br/api/receberManifestoProvisorio";

            $response = \Illuminate\Support\Facades\Http::withToken($token)->post($endpoint, [
                'numeroMtrProvisorio' => $request->numero_provisorio,
                'cnpjGerador'         => preg_replace('/[^0-9]/', '', $request->cnpj_gerador)
            ]);

            if ($response->failed()) {
                throw new Exception(strip_tags($response->body()));
            }

            return redirect()->route('mtr.recepcao.index')->with('sucesso', 'MTR Provisório recebido com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('mtr.recepcao.index')->with('erro', 'Erro ao receber MTR Provisório: ' . $e->getMessage());
        }
    }

    public function detalhesMtrExterno($numeroMtr)
    {
        try {
            $sessao = $this->getDadosSessao();
            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->first();
            
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $baseUri = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api"
                : "https://admin-homologacao.sinir.gov.br/api";

            $response = \Illuminate\Support\Facades\Http::withToken($token)->get("{$baseUri}/consultarManifesto/{$numeroMtr}");

            if ($response->successful()) {
                return response()->json(['success' => true, 'data' => $response->json()]);
            }

            return response()->json(['success' => false, 'message' => 'Manifesto não encontrado na base do SINIR.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /*public function salvarRecebimentoMtr(Request $request)
    {
        try {
            $sessao = $this->getDadosSessao();
            $credencial = DB::table('mtr_configs')->where('empresa_id', $sessao['empresa_id'])->first();
            
            $service = new SinirIemaService($credencial->orgao, $credencial->ambiente);
            $token = $service->getToken($credencial->cpf_cnpj, $credencial->senha, $credencial->unidade_id);

            $endpoint = ($credencial->ambiente === 'producao')
                ? "https://admin.sinir.gov.br/api/receberManifesto"
                : "https://admin-homologacao.sinir.gov.br/api/receberManifesto";

            $dataRec = empty($request->data_recebimento) ? Carbon::now() : Carbon::parse($request->data_recebimento);
            $timestampRec = (int) ($dataRec->timestamp * 1000);

            $payload = [
                'manNumero'              => (string) $request->numero_mtr,
                'responsavelRecebimento' => (string) $request->responsavel,
                'dataRecebimento'        => $timestampRec,
                'residuos'               => $request->residuos ?? []
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($token)->post($endpoint, $payload);

            if ($response->failed()) {
                throw new \Exception(strip_tags($response->body()));
            }

            return response()->json(['success' => true, 'message' => 'MTR recebido e baixado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao processar recebimento: ' . $e->getMessage()]);
        }
    }*/
}