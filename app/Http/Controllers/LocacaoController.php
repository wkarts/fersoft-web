<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Locacao;
use App\Models\ConfigNota;
use App\Models\ItemLocacao;
use App\Models\ItemLocacaoDisponibilidade;
use Dompdf\Dompdf;
use Carbon\Carbon;
use App\Models\MovimentacaoVeiculo;
use App\Models\Funcionario;
use App\Models\Veiculo;
use App\Models\TipoMovimentacao;
use App\Models\ContaReceber;
use App\Models\CategoriaConta;
use App\Models\Fornecedor;

class LocacaoController extends Controller
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

    public function index(){
        $locacoes = Locacao::where('empresa_id', $this->empresa_id)
            ->orderBy('id', 'desc')
            ->paginate(30);

        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $motoristas = Funcionario::where('empresa_id', $this->empresa_id)->get();

        return view('locacao/list')
            ->with('locacoes', $locacoes)
            ->with('veiculos', $veiculos)
            ->with('motoristas', $motoristas)
            ->with('links', true)
            ->with('title', 'Locações');
    }

    public function pesquisa(Request $request){
        $produto = null;
        $locacoes = Locacao::
        select('locacaos.*')
            ->where('locacaos.empresa_id', $this->empresa_id);

        // FILTRO DE CLIENTE
        if($request->cliente){
            $locacoes->join('clientes', 'clientes.id', '=', 'locacaos.cliente_id')
                ->where('clientes.razao_social', 'LIKE', "%$request->cliente%");
        }

        // FILTRO DE FORNECEDOR (NOVO)
        if($request->fornecedor){
            $locacoes->join('fornecedores', 'fornecedores.id', '=', 'locacaos.fornecedor_id')
                ->where('fornecedores.razao_social', 'LIKE', "%$request->fornecedor%");
        }

        if($request->data_inicial && $request->data_final){
            $locacoes->whereDate('inicio', '>=', $this->parseDate($request->data_inicial))
                ->whereDate('fim', '<=', $this->parseDate($request->data_final));
        }

        if($request->estado !== null && $request->estado !== ''){
            $locacoes->where('locacaos.status', $request->estado);
        }

        if($request->tipo){
            $locacoes->where('locacaos.tipo', $request->tipo);
        }

        if($request->produto){
            $produto = Produto::findOrFail($request->produto);
            $locacoes->join('item_locacaos', 'item_locacaos.locacao_id', '=', 'locacaos.id')
                ->where('item_locacaos.produto_id', $request->produto);
        }

        $locacoes = $locacoes->orderBy('locacaos.id', 'desc')
            ->get();

        return view('locacao/list')
            ->with('locacoes', $locacoes)
            ->with('produto', $produto)
            ->with('cliente', $request->cliente)
            ->with('fornecedor', $request->fornecedor) // Passa o parâmetro para a view
            ->with('dataInicial', $request->data_inicial)
            ->with('dataFinal', $request->data_final)
            ->with('estado', $request->estado)
            ->with('tipo', $request->tipo)
            ->with('pesquisa', true)
            ->with('title', 'Locações');
    }
    public function novo(){
        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if($config == null){
            session()->flash('mensagem_erro', 'Configure o emitente');
            return redirect('configNF');
        }

        return view('locacao/register')
            ->with('title', 'Nova locação')
            ->with('config', $config)
            ->with('pessoaFisicaOuJuridica', true)
            ->with('clientes', $clientes)
            ->with('fornecedores', $fornecedores);
    }

    public function edit($id){
        $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->get();

        $locacao = Locacao::find($id);

        if(valida_objeto($locacao)){
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

            return view('locacao/register')
                ->with('title', 'Editar locação')
                ->with('locacao', $locacao)
                ->with('config', $config)
                ->with('clientes', $clientes)
                ->with('fornecedores', $fornecedores);
        }else{
            return redirect('/403');
        }
    }

    public function salvar(Request $request){
        $this->_validate($request);

        $userLogged = session('user_logged');
        $usuario_id = $userLogged['id'] ?? null;
        $filial_id = $userLogged['filial_id'] ?? null;

        $dados = [
            'finalidade' => $request->finalidade ?? 'locacao_cliente',
            'cliente_id' => $request->finalidade == 'locacao_cliente' ? $request->cliente_id : null,
            'fornecedor_id' => $request->finalidade == 'coleta_fornecedor' ? $request->fornecedor_id : null,
            'material_previsto' => $request->material_previsto ?? '',
            'tipo' => $request->tipo ?? 'cacamba',
            'inicio' => $this->parseDate($request->inicio),
            'fim' => $request->fim ? $this->parseDate($request->fim) : '1969-12-31',
            'tipo_calculo' => $request->tipo_calculo ?? 'fechado',
            'faturado' => $request->faturado ?? 0,
            'valor_frete' => $request->valor_frete ? str_replace(',', '.', str_replace('.', '', $request->valor_frete)) : 0.00,
            'quantidade_parcelas' => $request->quantidade_parcelas ?? 1,
            'primeiro_vencimento' => $request->primeiro_vencimento ? $this->parseDate($request->primeiro_vencimento) : date('Y-m-d'),
            'forma_pagamento' => $request->forma_pagamento ?? 'boleto',
            'observacao' => $request->observacao ?? '',
            'rua_entrega' => $request->rua_entrega ?? '',
            'numero_entrega' => $request->numero_entrega ?? '',
            'bairro_entrega' => $request->bairro_entrega ?? '',
            'cep_entrega' => $request->cep_entrega ?? '',
            'referencia_entrega' => $request->referencia_entrega ?? '',
            'cidade_id_entrega' => $request->cidade_id_entrega ?: null,
            'usuario_id' => $usuario_id,
            'filial_id' => $filial_id,
        ];

        if($request->id > 0){
            $locacao = Locacao::find($request->id);
            if(valida_objeto($locacao)){
                $locacao->update($dados);

                ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)
                    ->update(['data' => $dados['inicio']]);

                $this->recalculaTotalLocacao($locacao);

                session()->flash('mensagem_sucesso', 'Locação/Coleta atualizada com sucesso');
                return redirect('/locacao/itens/'. $locacao->id);
            }else{
                return redirect('/403');
            }
        }else{
            $dados['empresa_id'] = $this->empresa_id;
            $dados['status'] = 0;
            $dados['total'] = $dados['valor_frete'];

            try{
                $l = Locacao::create($dados);
                session()->flash('mensagem_sucesso', 'Operação criada com sucesso!');
                return redirect('/locacao/itens/'. $l->id);
            }catch(\Exception $e){
                session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
                return redirect()->back();
            }
        }
    }

    // CONTROLE LOGÍSTICO DE STATUS E GERADOR FINANCEIRO

    public function marcarComoEntregue(Request $request, $id){
        try{
            $locacao = Locacao::find($id);
            if(valida_objeto($locacao)){

                // 1. Se veio do Modal (POST com motorista/veiculo)
                if($request->isMethod('post') && $request->veiculo_id){
                    $conflito = $this->validarConflitoAgenda(
                        $request->veiculo_id,
                        $request->motorista_id,
                        $request->data_hora_saida
                    );

                    if($conflito['has_error']){
                        session()->flash('mensagem_erro', $conflito['message']);
                        return redirect()->back();
                    }

                    $this->gerarAgendamentoVeiculo(
                        $locacao,
                        'ENTREGA DE CAÇAMBA/EQUIPAMENTO',
                        $request->veiculo_id,
                        $request->motorista_id,
                        $request->data_hora_saida
                    );
                }

                $locacao->status = 1; // 1 = Entregue / Em Uso
                $locacao->data_entrega = Carbon::now();
                $locacao->save();

                // 2. DISPARO AUTOMÁTICO DO CONTAS A RECEBER
                if($locacao->finalidade == 'locacao_cliente' && $locacao->faturado == 1){
                    $this->processarFaturamentoContaReceber($locacao);
                }

                session()->flash('mensagem_sucesso', 'Status alterado para ENTREGUE NO CLIENTE e Faturamento lançado!');
                return redirect()->back();
            }
            return redirect('/403');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function solicitarRetirada(Request $request, $id){
        $request->validate([
            'veiculo_id' => 'required',
            'motorista_id' => 'required',
            'data_hora_saida' => 'required'
        ], [
            'veiculo_id.required' => 'Informe o veículo para a retirada.',
            'motorista_id.required' => 'Informe o motorista responsável.',
            'data_hora_saida.required' => 'Informe a data e horário de saída.'
        ]);

        try{
            $locacao = Locacao::find($id);
            if(valida_objeto($locacao)){

                $conflito = $this->validarConflitoAgenda(
                    $request->veiculo_id,
                    $request->motorista_id,
                    $request->data_hora_saida
                );

                if($conflito['has_error']){
                    session()->flash('mensagem_erro', $conflito['message']);
                    return redirect()->back();
                }

                $locacao->status = 2; // 2 = Aguardando Retirada
                $locacao->data_solicitacao_retirada = Carbon::now();
                $locacao->save();

                $this->gerarAgendamentoVeiculo(
                    $locacao,
                    'RETIRADA DE CAÇAMBA/EQUIPAMENTO',
                    $request->veiculo_id,
                    $request->motorista_id,
                    $request->data_hora_saida
                );

                session()->flash('mensagem_sucesso', 'Solicitação de retirada e agendamento registrados!');
                return redirect()->back();
            }
            return redirect('/403');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function finalizarLocacao($id){
        try{
            $locacao = Locacao::find($id);
            if(valida_objeto($locacao)){
                $locacao->status = 3; // 3 = Finalizado / Retirado
                $locacao->data_retirada = Carbon::now();
                $locacao->fim = Carbon::now()->format('Y-m-d');
                $locacao->save();

                ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)->delete();

                session()->flash('mensagem_sucesso', 'Locação finalizada e recolhida!');
                return redirect()->back();
            }
            return redirect('/403');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function alterarStatus($id){
        return $this->finalizarLocacao($id);
    }

    // ITENS DE LOCAÇÃO

    public function itens($id){
        $locacao = Locacao::find($id);
        if(valida_objeto($locacao)){
            $produtos = Produto::
            where('empresa_id', $this->empresa_id)
                ->where('valor_locacao', '>', 0)
                ->get();

            return view('locacao/itens')
                ->with('title', 'Locação itens')
                ->with('produtos', $produtos)
                ->with('locacao', $locacao);
        }else{
            return redirect('/403');
        }
    }

    public function salvarItem(Request $request){
        $this->_validateItem($request);

        try{
            $locacao = Locacao::find($request->locacao_id);

            $itemData = $request->all();
            $itemData['observacao'] = $request->observacao ?? '';
            $itemData['codigo_patrimonio'] = $request->codigo_patrimonio ?? null;
            $itemData['horimetro_inicial'] = $request->horimetro_inicial ?? null;

            $l = ItemLocacao::create($itemData);

            if($locacao->fim != '1969-12-31' && $locacao->fim != null){
                $diferenca = strtotime($locacao->fim) - strtotime($locacao->inicio);
                $dias = floor($diferenca / (60 * 60 * 24));
                $date = $locacao->inicio;

                for($i=0; $i<=$dias; $i++){
                    ItemLocacaoDisponibilidade::create([
                        'produto_id' => $request->produto_id,
                        'data' => $date,
                        'locacao_id' => $locacao->id
                    ]);
                    $date = date('Y-m-d', strtotime("+1 days", strtotime($date)));
                }
            }

            $this->recalculaTotalLocacao($locacao);
            session()->flash('mensagem_sucesso', 'Item adicionado com sucesso!');

            return redirect()->back();
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao adicionar item: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function deleteItem($id){
        $item = ItemLocacao::find($id);
        $locacao = Locacao::find($item->locacao_id);
        if(valida_objeto($locacao)){
            ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)
                ->where('produto_id', $item->produto_id)->delete();

            $item->delete();
            $this->recalculaTotalLocacao($locacao);

            session()->flash('mensagem_sucesso', 'Item removido');
            return redirect()->back();
        }else{
            return redirect('/403');
        }
    }

    public function delete($id){
        $locacao = Locacao::find($id);

        if(valida_objeto($locacao)){
            ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)->delete();
            $locacao->delete();

            session()->flash('mensagem_sucesso', 'Registro removido com sucesso');
            return redirect()->back();
        }else{
            return redirect('/403');
        }
    }

    public function saveObs(Request $request){
        try{
            $locacao = Locacao::find($request->id);
            $locacao->observacao = $request->observacao;
            $locacao->save();
            session()->flash('mensagem_sucesso', 'Observação atualizada');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    public function validaEstoque($produto_id, $locacao_id){
        try{
            $produto = Produto::find($produto_id);
            $locacao = Locacao::find($locacao_id);

            $semEstoqueData = "";
            if($locacao->fim != '1969-12-31' && $locacao->fim != null){
                $diferenca = strtotime($locacao->fim) - strtotime($locacao->inicio);
                $dias = floor($diferenca / (60 * 60 * 24));
                $date = $locacao->inicio;
                $estoqueDisponivel = $produto->estoqueAtual();

                for($i=0; $i<=$dias; $i++){
                    $countTemp = ItemLocacaoDisponibilidade::where('produto_id', $produto_id)
                        ->whereDate('data', $date)
                        ->count();
                    if($countTemp >= $estoqueDisponivel && $semEstoqueData == ""){
                        $semEstoqueData = $date;
                    }
                    $date = date('Y-m-d', strtotime("+1 days", strtotime($date)));
                }
            }

            $arr = [
                'valor_locacao' => $produto->valor_locacao,
                'semEstoqueData' => $semEstoqueData != "" ? Carbon::parse($semEstoqueData)->format('d/m/Y') : ""
            ];

            return response()->json($arr, 200);
        }catch(\Exception $e){
            return response()->json("erro: ". $e->getMessage(), 401);
        }
    }

    public function relatorio(Request $request){
        $locacoes = Locacao::
        select('locacaos.*')
            ->where('locacaos.empresa_id', $this->empresa_id);

        if($request->cliente){
            $locacoes->join('clientes', 'clientes.id', '=', 'locacaos.cliente_id')
                ->where('clientes.razao_social', 'LIKE', "%$request->cliente%");
        }

        if($request->data_inicial && $request->data_final){
            $locacoes->whereDate('inicio', '>=', $this->parseDate($request->data_inicial))
                ->whereDate('fim', '<=', $this->parseDate($request->data_final));
        }

        if($request->estado !== null && $request->estado !== ''){
            $locacoes->where('locacaos.status', $request->estado);
        }

        $locacoes = $locacoes->orderBy('locacaos.inicio')->get();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        if (ob_get_length()) {
            ob_end_clean();
        }

        $p = view('locacao/print')
            ->with('locacoes', $locacoes)
            ->with('cliente', $request->cliente)
            ->with('dataInicial', $request->data_inicial)
            ->with('dataFinal', $request->data_final)
            ->with('estado', $request->estado)
            ->with('config', $config)
            ->render();

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);
        $domPdf->setPaper("A4");
        $domPdf->render();

        return response($domPdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="relatorio_locacoes.pdf"'
        ]);
    }

    public function comprovante($id){
        try {
            $locacao = Locacao::find($id);
            if(valida_objeto($locacao)){
                $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

                if (ob_get_length()) {
                    ob_end_clean();
                }

                $p = view('locacao/comprovante2')
                    ->with('config', $config)
                    ->with('locacao', $locacao)
                    ->render();

                $domPdf = new Dompdf(["enable_remote" => true]);
                $domPdf->loadHtml($p);
                $domPdf->setPaper("A4");
                $domPdf->render();

                return response($domPdf->output(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="locacao_'.$id.'.pdf"'
                ]);
            } else {
                return redirect('/403');
            }
        } catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao gerar comprovante: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    private function somaItens($locacao){
        $total = 0;
        foreach($locacao->itens as $i){
            $total += $i->valor;
        }
        return $total;
    }

    private function parseDate($date){
        return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    }

    private function _validate(Request $request){
        $rules = [
            'inicio' => 'required'
        ];

        $messages = [
            'inicio.required' => 'O campo data início é obrigatório.',
        ];

        if($request->finalidade == 'coleta_fornecedor'){
            $rules['fornecedor_id'] = 'required|numeric|min:1';
            $messages['fornecedor_id.required'] = 'Selecione o fornecedor.';
            $messages['fornecedor_id.min'] = 'Selecione o fornecedor.';
        } else {
            $rules['cliente_id'] = 'required|numeric|min:1';
            $messages['cliente_id.required'] = 'Selecione o cliente.';
            $messages['cliente_id.min'] = 'Selecione o cliente.';
        }

        $this->validate($request, $rules, $messages);
    }

    private function _validateItem(Request $request){
        $rules = [
            'produto_id' => 'required|numeric|min:1',
            'valor' => 'required'
        ];

        $messages = [
            'produto_id.required' => 'O campo produto é obrigatório.',
            'produto_id.min' => 'O campo produto é obrigatório.',
            'valor.required' => 'O campo valor é obrigatório.',
        ];
        $this->validate($request, $rules, $messages);
    }

    private function validarConflitoAgenda($veiculoId, $motoristaId, $dataHoraSaidaStr) {
        $dataHoraSaida = Carbon::createFromFormat('Y-m-d\TH:i', $dataHoraSaidaStr);
        $inicioJanela = $dataHoraSaida->copy()->subMinutes(30);
        $fimJanela = $dataHoraSaida->copy()->addMinutes(60);

        $conflitoVeiculo = MovimentacaoVeiculo::where('veiculo_id', $veiculoId)
            ->whereIn('status', ['agendado', 'em_curso'])
            ->whereBetween('data_hora_saida', [$inicioJanela, $fimJanela])
            ->first();

        if ($conflitoVeiculo) {
            $veiculo = Veiculo::find($veiculoId);
            $placa = $veiculo ? $veiculo->placa : '';
            return [
                'has_error' => true,
                'message' => "CONFLITO DE AGENDA: O veículo {$placa} já possui uma movimentação agendada próxima a este horário (" . $conflitoVeiculo->data_hora_saida_formatada . ")."
            ];
        }

        $conflitoMotorista = MovimentacaoVeiculo::where('motorista_id', $motoristaId)
            ->whereIn('status', ['agendado', 'em_curso'])
            ->whereBetween('data_hora_saida', [$inicioJanela, $fimJanela])
            ->first();

        if ($conflitoMotorista) {
            $motorista = Funcionario::find($motoristaId);
            $nome = $motorista ? $motorista->nome : '';
            return [
                'has_error' => true,
                'message' => "CONFLITO DE AGENDA: O motorista {$nome} já possui um agendamento no mesmo horário (" . $conflitoMotorista->data_hora_saida_formatada . ")."
            ];
        }

        return ['has_error' => false];
    }

    private function gerarAgendamentoVeiculo($locacao, $tipoOperacao, $veiculoId, $motoristaId, $dataHoraSaidaStr) {
        $nomeTipoMovimentacao = ($tipoOperacao == 'ENTREGA DE CAÇAMBA/EQUIPAMENTO')
            ? 'ENTREGA DE CAÇAMBA/EQUIPAMENTO'
            : 'RETIRADA DE CAÇAMBA/EQUIPAMENTO';

        $tipoMovimentacao = TipoMovimentacao::firstOrCreate(
            [
                'empresa_id' => $locacao->empresa_id,
                'nome'       => $nomeTipoMovimentacao
            ],
            [
                'status'     => 1
            ]
        );

        $patrimonios = [];
        foreach($locacao->itens as $item) {
            $patrimonios[] = $item->produto->nome . ($item->codigo_patrimonio ? " ({$item->codigo_patrimonio})" : "");
        }
        $strPatrimonios = count($patrimonios) > 0 ? implode(', ', $patrimonios) : "Locação #{$locacao->id}";

        $enderecoObra = "{$locacao->rua_entrega}, {$locacao->numero_entrega} - {$locacao->bairro_entrega}";
        if($locacao->cidadeEntrega) {
            $enderecoObra .= " - {$locacao->cidadeEntrega->nome} ({$locacao->cidadeEntrega->uf})";
        }

        $observacaoOperacao = "{$tipoOperacao} | Itens: {$strPatrimonios}";
        if($locacao->referencia_entrega) {
            $observacaoOperacao .= " | Ref: {$locacao->referencia_entrega}";
        }

        MovimentacaoVeiculo::create([
            'empresa_id'            => $locacao->empresa_id,
            'filial_id'             => $locacao->filial_id,
            'cliente_id'            => $locacao->cliente_id,
            'veiculo_id'            => $veiculoId,
            'motorista_id'          => $motoristaId,
            'tipo_movimentacao_id'  => $tipoMovimentacao->id,
            'data_hora_saida'       => Carbon::createFromFormat('Y-m-d\TH:i', $dataHoraSaidaStr),
            'status'                => 'agendado',
            'destino'               => $enderecoObra,
            'observacao'            => $observacaoOperacao,
        ]);
    }

    private function recalculaTotalLocacao($locacao){
        $subtotalItens = $this->somaItens($locacao);
        $totalGeral = $subtotalItens;

        if($locacao->finalidade == 'locacao_cliente' && $locacao->fim != '1969-12-31' && $locacao->fim != null){
            $d1 = Carbon::parse($locacao->inicio);
            $d2 = Carbon::parse($locacao->fim);
            $dias = $d1->diffInDays($d2);
            if($dias <= 0) $dias = 1;

            if($locacao->tipo_calculo == 'dia'){
                $totalGeral = $subtotalItens * $dias;
            } elseif($locacao->tipo_calculo == 'mes'){
                $meses = ceil($dias / 30);
                if($meses <= 0) $meses = 1;
                $totalGeral = $subtotalItens * $meses;
            }
        }

        $locacao->total = $totalGeral + $locacao->valor_frete;
        $locacao->save();
    }

    // LÓGICA DO CONTAS A RECEBER ALINHADA COM A TABELA 'conta_recebers'
    private function processarFaturamentoContaReceber($locacao){
        if($locacao->total <= 0) return;

        // 1. Localiza a Categoria de Conta de Receita para a empresa (se não achar, pega a 1ª da empresa)
        $categoria = CategoriaConta::where('empresa_id', $locacao->empresa_id)
            ->where('tipo', 'receber')
            ->first();

        $categoria = CategoriaConta::where('empresa_id', $locacao->empresa_id)
            ->where('tipo', 'receber')
            ->where(function($q) {
                $q->where('nome', 'LIKE', '%Loca%')
                    ->orWhere('nome', 'LIKE', '%Servic%');
            })
            ->first();

        // Fallback: se não achar pelo nome, pega a primeira categoria de receita da empresa
        if (!$categoria) {
            $categoria = CategoriaConta::where('empresa_id', $locacao->empresa_id)
                ->where('tipo', 'receber')
                ->first();
        }

        $categoriaId = $categoria ? $categoria->id : 1;

        $qtdParcelas = $locacao->quantidade_parcelas > 0 ? $locacao->quantidade_parcelas : 1;
        $valorParcela = round($locacao->total / $qtdParcelas, 2);
        $vencimento = Carbon::parse($locacao->primeiro_vencimento ?? date('Y-m-d'));

        for($i = 1; $i <= $qtdParcelas; $i++){
            ContaReceber::create([
                'empresa_id'      => $locacao->empresa_id,
                'cliente_id'      => $locacao->cliente_id,
                'categoria_id'    => $categoriaId,
                'referencia'      => "Locação #{$locacao->id} (Parc {$i}/{$qtdParcelas})",
                'valor_integral'  => $valorParcela,
                'valor_recebido'  => 0,
                'data_vencimento' => $vencimento->format('Y-m-d'),
                'status'          => 0, // 0 = Em aberto
                'tipo_pagamento'  => $locacao->forma_pagamento ?? 'boleto',
                'filial_id'       => $locacao->filial_id,
                'usuario_id'      => $locacao->usuario_id
            ]);

            $vencimento->addMonth();
        }
    }

    public function gerarFaturamentoFinanceiro($id){
        try{
            $locacao = Locacao::find($id);

            if($locacao->finalidade == 'coleta_fornecedor'){
                session()->flash('mensagem_erro', 'Operações em fornecedor não geram contas a receber.');
                return redirect()->back();
            }

            if($locacao->total <= 0){
                session()->flash('mensagem_erro', 'Adicione itens com valor para faturar.');
                return redirect()->back();
            }

            $this->processarFaturamentoContaReceber($locacao);

            session()->flash('mensagem_sucesso', 'Faturamento gerado no Contas a Receber!');
            return redirect()->back();
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao faturar: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function imprimirFatura($id){
        try{
            $locacao = Locacao::find($id);
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

            if (ob_get_length()) ob_end_clean();

            $p = view('locacao/fatura_pdf')
                ->with('config', $config)
                ->with('locacao', $locacao)
                ->render();

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);
            $domPdf->setPaper("A4");
            $domPdf->render();

            return response($domPdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Fatura_Locacao_'.$id.'.pdf"'
            ]);
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
