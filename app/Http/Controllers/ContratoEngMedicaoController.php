<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContratoEngenharia;
use App\Models\FaturaEngenharia;
use App\Models\FaturaEngFuncionario;
use App\Models\FaturaEngItem;
use App\Models\Cliente;
use App\Models\Filial;
use App\Models\Servico;
use App\Models\Produto;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use App\Models\Funcionario;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Models\Cidade;

class ContratoEngMedicaoController extends Controller
{
    /**
     * Lista as medições e lançamentos de Locação / Serviços
     */
    public function index(Request $request, $contrato_id = null)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;

        $query = FaturaEngenharia::where('empresa_id', $empresa_id);

        if ($contrato_id) {
            $query->where('contrato_eng_id', $contrato_id);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('contrato_eng_id')) {
            $query->where('contrato_eng_id', $request->contrato_eng_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('data_inicial')) {
            $query->whereDate('data_faturamento', '>=', $request->data_inicial);
        }

        if ($request->filled('data_final')) {
            $query->whereDate('data_faturamento', '<=', $request->data_final);
        }

        $medicoes = $query->with(['contrato', 'cliente'])->orderBy('id', 'desc')->paginate(15);

        $clientes = Cliente::where('empresa_id', $empresa_id)->get();
        $contratos = ContratoEngenharia::where('empresa_id', $empresa_id)->get();
        $contratoSelecionado = $contrato_id ? ContratoEngenharia::find($contrato_id) : null;

        return view('contratos.medicoes.index', [
            'medicoes' => $medicoes,
            'clientes' => $clientes,
            'contratos' => $contratos,
            'contrato_id' => $contrato_id,
            'contratoSelecionado' => $contratoSelecionado,
            'title' => 'Locação e Serviços - Medições e Faturamento'
        ]);
    }

    /**
     * Exibe o formulário de cadastro (Com Contrato ou Avulso)
     */
    public function create($contrato_id = null)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;

        $contratos = ContratoEngenharia::where('empresa_id', $empresa_id)
            ->where('status', 'Ativo')
            ->with(['cliente', 'itens'])
            ->get()
            ->map(function($con) {
                $con->funcionarios = DB::table('contrato_eng_funcionarios as cf')
                    ->join('funcionarios as f', 'f.id', '=', 'cf.funcionario_id')
                    ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
                    ->where('cf.contrato_eng_id', $con->id)
                    ->select(
                        'f.id as funcionario_id',
                        'f.nome',
                        DB::raw("COALESCE(fn.nome, '') as funcao")
                    )
                    ->get();

                return $con;
            });

        $clientes   = Cliente::where('empresa_id', $empresa_id)->get();
        $servicos   = Servico::where('empresa_id', $empresa_id)->get();
        $produtos   = Produto::where('empresa_id', $empresa_id)->get();
        $categorias = CategoriaConta::where('empresa_id', $empresa_id)->get();
        $cidades    = Cidade::orderBy('nome', 'asc')->get();

        $funcionarios = DB::table('funcionarios')
            ->leftJoin('funcoes', 'funcoes.id', '=', 'funcionarios.funcao_id')
            ->where('funcionarios.empresa_id', $empresa_id)
            ->select(
                'funcionarios.id',
                'funcionarios.nome',
                DB::raw("COALESCE(funcoes.nome, 'Sem Função') as funcao_nome")
            )
            ->orderBy('funcionarios.nome')
            ->get();

        $tiposPagamento = ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'];
        $contratoSelecionado = $contrato_id ? ContratoEngenharia::find($contrato_id) : null;

        return view('contratos.medicoes.create', [
            'contratos'           => $contratos,
            'cidades'             => $cidades,
            'clientes'            => $clientes,
            'servicos'            => $servicos,
            'produtos'            => $produtos,
            'categorias'          => $categorias,
            'funcionarios'        => $funcionarios,
            'tiposPagamento'      => $tiposPagamento,
            'contratoSelecionado' => $contratoSelecionado,
            'title'               => 'Novo Lançamento - Locação e Serviços'
        ]);
    }

    /**
     * Salva o lançamento (Vinculado ao contrato ou avulso)
     */
    public function store(Request $request, $contrato_id = null)
    {
        try {
            DB::beginTransaction();

            $empresa_id = session('user_logged')['empresa'] ?? null;
            $usuario_id = session('user_logged')['id'] ?? null;

            $contratoEngId = $request->input('contrato_eng_id') ?? $contrato_id;

            $contrato = null;
            if ($contratoEngId) {
                $contrato = ContratoEngenharia::where('empresa_id', $empresa_id)->find($contratoEngId);
            }

            $clienteId = $request->input('cliente_id') ?? ($contrato ? $contrato->cliente_id : null);

            $valorTotalMedicao = str_replace(['.', ','], ['', '.'], $request->input('valor_total', '0'));
            $valorRetencao = str_replace(['.', ','], ['', '.'], $request->input('valor_retencao', '0'));
            $valorLiquido = $valorTotalMedicao - $valorRetencao;

            $dataFaturamento = $request->input('nf_data_emissao') ?? date('Y-m-d');
            $filialId = $request->input('filial_id') ?? (session('user_logged')['filial'] ?? 1);

            // 1. Cria a Fatura principal com os dados de NFS-e / Obra
            $fatura = FaturaEngenharia::create([
                'empresa_id'             => $empresa_id,
                'filial_id'              => $filialId,
                'contrato_eng_id'        => $contratoEngId ?: null,
                'cliente_id'             => $clienteId,
                'vendedor_id'            => $request->input('vendedor_id'),
                'condicao_pagamento_id'  => $request->input('condicao_pagamento_id'),
                'categoria_conta_id'     => $request->input('categoria_conta_id'),
                'usuario_id'             => $usuario_id,
                'valor_total'            => $valorTotalMedicao,
                'valor_retencao'         => $valorRetencao,
                'valor_liquido'          => $valorLiquido,
                'data_faturamento'       => $dataFaturamento,
                'observacao'             => $request->input('observacao'),
                'servico_id'             => $request->input('servico_id'),
                'codigo_obra'            => $request->input('codigo_obra'),
                'municipio_prestacao_id' => $request->input('cidade_prestacao_id'),
                'status'                 => 'Pendente'
            ]);

            // 2. Salva os ITENS / SERVIÇOS da Medição
            $itensInput = $request->input('itens') ?? $request->input('servicos') ?? [];
            if (!empty($itensInput) && is_array($itensInput)) {
                foreach ($itensInput as $item) {
                    if (!empty($item['servico_id']) || !empty($item['descricao'])) {
                        $qtd = str_replace(['.', ','], ['', '.'], ($item['quantidade'] ?? '1'));
                        $vlUnit = str_replace(['.', ','], ['', '.'], ($item['valor_unitario'] ?? $item['valor'] ?? '0'));
                        $subTotal = str_replace(['.', ','], ['', '.'], ($item['sub_total'] ?? $item['subtotal'] ?? ($qtd * $vlUnit)));

                        FaturaEngItem::create([
                            'fatura_eng_id'  => $fatura->id,
                            'servico_id'     => $item['servico_id'] ?? null,
                            'descricao'      => $item['descricao'] ?? null,
                            'quantidade'     => $qtd,
                            'valor_unitario' => $vlUnit,
                            'sub_total'      => $subTotal,
                        ]);
                    }
                }
            }

            // 3. Salva as Parcelas no Contas a Receber
            if ($request->has('parcelas') && is_array($request->parcelas)) {
                foreach ($request->parcelas as $key => $parcela) {
                    $vlParcela = str_replace(['.', ','], ['', '.'], ($parcela['valor'] ?? '0'));

                    $numParcela = $key + 1;
                    $totalParcelas = count($request->parcelas);

                    $referenciaTexto = $contrato
                        ? "Contrato Nº " . ($contrato->numero_contrato ?? $contrato->id) . " (Medição #" . $fatura->id . ") - Parcela {$numParcela}/{$totalParcelas}"
                        : "Serviço Avulso (Faturamento #" . $fatura->id . ") - Parcela {$numParcela}/{$totalParcelas}";

                    DB::table('conta_recebers')->insert([
                        'empresa_id'      => $empresa_id,
                        'cliente_id'      => $clienteId,
                        'usuario_id'      => $usuario_id,
                        'categoria_id'    => $request->input('categoria_conta_id'),
                        'valor_integral'  => $vlParcela,
                        'data_vencimento' => $parcela['vencimento'],
                        'nf_data_emissao' => $dataFaturamento,
                        'status'          => 0,
                        'referencia'      => $referenciaTexto,
                        'observacao'      => $request->input('observacao'),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            // 4. Salva os Funcionários/Equipe da Medição
            if ($request->has('funcionarios') && is_array($request->funcionarios)) {
                foreach ($request->funcionarios as $func) {
                    if (!empty($func['funcionario_id'])) {
                        $diarias = str_replace(['.', ','], ['', '.'], ($func['diarias'] ?? '1'));
                        $vlDiaria = str_replace(['.', ','], ['', '.'], ($func['valor_diaria'] ?? '0'));

                        FaturaEngFuncionario::create([
                            'fatura_eng_id'  => $fatura->id,
                            'funcionario_id' => $func['funcionario_id'],
                            'funcao'         => $func['funcao'] ?? null,
                            'diarias'        => $diarias,
                            'valor_diaria'   => $vlDiaria,
                            'valor_total'    => $diarias * $vlDiaria,
                        ]);
                    }
                }
            }

            // 5. Se tiver contrato vinculado, atualiza o acumulado faturado
            if ($contrato) {
                $contrato->increment('valor_faturado', $valorTotalMedicao);
            }

            DB::commit();

            session()->flash('mensagem_sucesso', 'Lançamento gerado e integrado ao financeiro com sucesso!');
            session()->flash('imprimir_fatura_id', $fatura->id);

            return redirect()->route('contratos.medicoes.index')
                ->with('mensagem_sucesso', 'Lançamento e financeiro atualizados com sucesso!')
                ->with('imprimir_id', $fatura->id);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Exibe o formulário de edição da medição
     */
    public function edit($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;

        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)
            ->with(['itens.servico', 'funcionarios'])
            ->findOrFail($id);

        if ($fatura->status == 'Finalizado') {
            session()->flash('mensagem_erro', 'Este lançamento está finalizado e não pode ser editado.');
            return redirect('/contratos/medicoes');
        }

        $contratos      = ContratoEngenharia::where('empresa_id', $empresa_id)->get();
        $clientes       = Cliente::where('empresa_id', $empresa_id)->get();
        $categorias     = CategoriaConta::where('empresa_id', $empresa_id)->get();
        $servicos       = Servico::where('empresa_id', $empresa_id)->get();
        $produtos       = Produto::where('empresa_id', $empresa_id)->get();
        $cidades        = Cidade::orderBy('nome', 'asc')->get();
        $funcionarios   = DB::table('funcionarios')->where('empresa_id', $empresa_id)->get();
        $tiposPagamento = ['Dinheiro', 'Boleto', 'Cartão de Crédito', 'Cartão de Débito', 'Pix', 'Transferência'];

        return view('contratos.medicoes.edit', [
            'fatura'         => $fatura,
            'contratos'      => $contratos,
            'clientes'       => $clientes,
            'categorias'     => $categorias,
            'servicos'       => $servicos,
            'produtos'       => $produtos,
            'cidades'        => $cidades,
            'funcionarios'   => $funcionarios,
            'tiposPagamento' => $tiposPagamento,
            'title'          => 'Editar Medição / Faturamento #' . $fatura->id
        ]);
    }

    /**
     * Atualiza os dados da medição
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $empresa_id = session('user_logged')['empresa'] ?? null;

            $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

            $valorTotalMedicao = str_replace(['.', ','], ['', '.'], $request->input('valor_total', '0'));
            $valorRetencao = str_replace(['.', ','], ['', '.'], $request->input('valor_retencao', '0'));
            $valorLiquido = $valorTotalMedicao - $valorRetencao;

            // Atualiza os dados principais incluindo os campos da NFS-e/Obra
            $fatura->update([
                'contrato_eng_id'        => $request->input('contrato_eng_id'),
                'cliente_id'             => $request->input('cliente_id'),
                'categoria_conta_id'     => $request->input('categoria_conta_id'),
                'valor_total'            => $valorTotalMedicao,
                'valor_retencao'         => $valorRetencao,
                'valor_liquido'          => $valorLiquido,
                'data_faturamento'       => $request->input('nf_data_emissao'),
                'observacao'             => $request->input('observacao'),
                'servico_id'             => $request->input('servico_id'),
                'codigo_obra'            => $request->input('codigo_obra'),
                'municipio_prestacao_id' => $request->input('cidade_prestacao_id'),
                'status'                 => $request->input('status') ?? $fatura->status
            ]);

            // Atualiza os ITENS (Deleta e recria)
            $itensInput = $request->input('itens') ?? $request->input('servicos') ?? [];
            if (is_array($itensInput)) {
                FaturaEngItem::where('fatura_eng_id', $id)->delete();
                foreach ($itensInput as $item) {
                    if (!empty($item['servico_id']) || !empty($item['descricao'])) {
                        $qtd = str_replace(['.', ','], ['', '.'], ($item['quantidade'] ?? '1'));
                        $vlUnit = str_replace(['.', ','], ['', '.'], ($item['valor_unitario'] ?? $item['valor'] ?? '0'));
                        $subTotal = str_replace(['.', ','], ['', '.'], ($item['sub_total'] ?? $item['subtotal'] ?? ($qtd * $vlUnit)));

                        FaturaEngItem::create([
                            'fatura_eng_id'  => $id,
                            'servico_id'     => $item['servico_id'] ?? null,
                            'descricao'      => $item['descricao'] ?? null,
                            'quantidade'     => $qtd,
                            'valor_unitario' => $vlUnit,
                            'sub_total'      => $subTotal,
                        ]);
                    }
                }
            }

            // Sincroniza o valor alterado no Contas a Receber
            DB::table('conta_recebers')
                ->where('empresa_id', $empresa_id)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->update([
                    'valor_integral' => $valorTotalMedicao,
                    'categoria_id'   => $request->input('categoria_conta_id'),
                    'observacao'     => $request->input('observacao'),
                    'updated_at'     => now()
                ]);

            DB::commit();

            session()->flash('mensagem_sucesso', 'Lançamento e financeiro atualizados com sucesso!');
            return redirect('/contratos/medicoes');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('mensagem_erro', 'Erro ao atualizar: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function imprimir($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;

        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)
            ->with(['contrato.itens.servico', 'contrato.itens.produto', 'cliente', 'funcionarios.funcionario', 'categoriaConta', 'itens.servico'])
            ->findOrFail($id);

        $empresa = DB::table('empresas')->where('id', $empresa_id)->first();
        $configNota = DB::table('config_notas')->where('empresa_id', $empresa_id)->first();

        $parcelas = DB::table('conta_recebers')
            ->where('empresa_id', $empresa_id)
            ->where('referencia', 'like', '%(Medição #' . $id . ')%')
            ->get();

        return view('contratos.medicoes.print', [
            'fatura'     => $fatura,
            'empresa'    => $empresa,
            'configNota' => $configNota,
            'parcelas'   => $parcelas,
            'title'      => 'Impressão de Medição / Faturamento #' . $fatura->id
        ]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $empresa_id = session('user_logged')['empresa'] ?? null;

            $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

            DB::table('conta_recebers')
                ->where('empresa_id', $empresa_id)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->delete();

            FaturaEngItem::where('fatura_eng_id', $id)->delete();
            FaturaEngFuncionario::where('fatura_eng_id', $id)->delete();

            $fatura->delete();

            DB::commit();

            session()->flash('mensagem_sucesso', 'Lançamento e títulos financeiros excluídos com sucesso!');
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('mensagem_erro', 'Erro ao excluir: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function mudarStatus(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        $fatura->update(['status' => $request->input('status')]);

        session()->flash('mensagem_sucesso', 'Status atualizado com sucesso!');
        return redirect()->back();
    }

    public function enviarWhatsapp(Request $request, $id)
    {
        Log::info("=== INICIANDO ENVIO DE WHATSAPP COM PDF (Medição #{$id}) ===");

        try {
            set_time_limit(60);

            $empresaId = session('user_logged')['empresa'] ?? 1;

            $medicao = FaturaEngenharia::with(['cliente', 'contrato'])->where('empresa_id', $empresaId)->find($id);

            if (!$medicao || !$medicao->cliente) {
                return redirect()->back()->with('mensagem_erro', "Medição ou cliente não encontrado.");
            }

            $numeroOriginal = $medicao->cliente->celular ?? $medicao->cliente->whatsapp ?? $medicao->cliente->telefone ?? '';
            $numero = preg_replace('/[^0-9]/', '', $numeroOriginal);

            if (empty($numero) || strlen($numero) < 10) {
                $nomeCliente = $medicao->cliente->razao_social ?? $medicao->cliente->nome ?? 'Cliente';
                return redirect()->back()->with('mensagem_erro', "Atenção: O cliente '{$nomeCliente}' não possui número de WhatsApp válido.");
            }

            if (substr($numero, 0, 2) !== '55') {
                $numero = "55" . $numero;
            }

            $empresa = DB::table('empresas')->where('id', $empresaId)->first();
            $configNota = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
            $parcelas = DB::table('conta_recebers')
                ->where('empresa_id', $empresaId)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->get();

            $html = view('contratos.medicoes.print', [
                'fatura'     => $medicao,
                'empresa'    => $empresa,
                'configNota' => $configNota,
                'parcelas'   => $parcelas,
                'title'      => 'Medição #' . $medicao->id
            ])->render();

            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'sans-serif');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $diretorio = public_path('pdf/faturas');
            if (!file_exists($diretorio)) {
                mkdir($diretorio, 0755, true);
            }

            $nomeArquivo = "Medicao_Faturamento_{$id}.pdf";
            $caminhoPdf = $diretorio . '/' . $nomeArquivo;

            file_put_contents($caminhoPdf, $dompdf->output());

            $nomeEmpresa = $empresa->nome ?? $empresa->razao_social ?? 'FerSoft ERP';
            $nomeCliente = $medicao->cliente->razao_social ?? $medicao->cliente->nome;
            $numContrato = $medicao->contrato ? ($medicao->contrato->numero_contrato ?? $medicao->contrato->id) : 'Avulso';
            $valorFormatado = number_format($medicao->valor_total, 2, ',', '.');
            $dataFaturamento = date('d/m/Y', strtotime($medicao->data_faturamento ?? $medicao->created_at));

            $mensagem  = "📄 *COMPROVANTE DE MEDIÇÃO / FATURAMENTO #{$medicao->id}*\n\n";
            $mensagem .= "Olá, *{$nomeCliente}*!\n\n";
            $mensagem .= "Segue em anexo o PDF da fatura e o resumo da medição:\n\n";
            $mensagem .= "• *Contrato:* Nº {$numContrato}\n";
            $mensagem .= "• *Data da Medição:* {$dataFaturamento}\n";
            $mensagem .= "• *Valor Total:* R$ {$valorFormatado}\n";

            if (!empty($medicao->observacao)) {
                $mensagem .= "• *Observação:* {$medicao->observacao}\n";
            }

            $mensagem .= "\nFicamos à disposição para dúvidas.\n\n";
            $mensagem .= "Atenciosamente,\n*{$nomeEmpresa}*";

            $instanciaWhats = app('\App\Utils\WhatsAppUtil');
            $retornoJson = $instanciaWhats->sendMessage($numero, $mensagem, $empresaId, $caminhoPdf);

            $res = json_decode($retornoJson, true);
            if (isset($res['success']) && $res['success'] === false) {
                $msgErro = $res['message'] ?? 'Falha ao enviar arquivo via WhatsApp';
                return redirect()->back()->with('mensagem_erro', "Erro no envio do WhatsApp: " . $msgErro);
            }

            return redirect()->back()->with('mensagem_sucesso', "Fatura em PDF enviada por WhatsApp para {$nomeCliente}!");

        } catch (\Throwable $e) {
            Log::error("WhatsApp Medição #{$id}: Erro - " . $e->getMessage());
            return redirect()->back()->with('mensagem_erro', 'Falha ao gerar/enviar PDF por WhatsApp: ' . $e->getMessage());
        }
    }

    public function gerarPdf($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;

        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)
            ->with(['contrato.itens.servico', 'contrato.itens.produto', 'cliente', 'funcionarios.funcionario', 'categoriaConta', 'itens.servico'])
            ->findOrFail($id);

        $empresa = DB::table('empresas')->where('id', $empresa_id)->first();
        $configNota = DB::table('config_notas')->where('empresa_id', $empresa_id)->first();

        $parcelas = DB::table('conta_recebers')
            ->where('empresa_id', $empresa_id)
            ->where('referencia', 'like', '%(Medição #' . $id . ')%')
            ->get();

        $html = view('contratos.medicoes.print', [
            'fatura'     => $fatura,
            'empresa'    => $empresa,
            'configNota' => $configNota,
            'parcelas'   => $parcelas,
            'title'      => 'Medicação_' . $fatura->id
        ])->render();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Medicao_Faturamento_' . $id . '.pdf"'
        ]);
    }

    public function enviarEmail(Request $request, $id)
    {
        Log::info("=== INICIANDO ENVIO DE E-MAIL MULTIEMPRESA (Medição #{$id}) ===");

        try {
            set_time_limit(60);

            $empresaId = session('user_logged')['empresa'] ?? 1;

            $medicao = FaturaEngenharia::with(['cliente', 'contrato'])->where('empresa_id', $empresaId)->findOrFail($id);

            if (!$medicao->cliente || empty($medicao->cliente->email)) {
                $nomeCliente = $medicao->cliente->razao_social ?? $medicao->cliente->nome ?? 'Cliente';
                return redirect()->back()->with('mensagem_erro', "Atenção: O cliente '{$nomeCliente}' não possui um e-mail cadastrado.");
            }

            $emailCliente = trim($medicao->cliente->email);

            $empresa    = DB::table('empresas')->where('id', $empresaId)->first();
            $configNota = DB::table('config_notas')->where('empresa_id', $empresaId)->first();

            $configEmail = null;
            if (Schema::hasTable('config_email')) {
                $configEmail = DB::table('config_email')->where('empresa_id', $empresaId)->first();
            } elseif (Schema::hasTable('config_emails')) {
                $configEmail = DB::table('config_emails')->where('empresa_id', $empresaId)->first();
            } elseif (Schema::hasTable('email_configs')) {
                $configEmail = DB::table('email_configs')->where('empresa_id', $empresaId)->first();
            }

            $nomeEmpresa = $empresa->nome ?? $empresa->razao_social ?? 'FerSoft ERP';

            $usarEmailProprio = ($configNota->usar_email_proprio ?? 0) == 1
                && $configEmail
                && !empty($configEmail->email)
                && !empty($configEmail->host);

            if ($usarEmailProprio) {
                config([
                    'mail.mailers.smtp.host'       => $configEmail->host,
                    'mail.mailers.smtp.port'       => $configEmail->porta ?? $configEmail->port ?? 587,
                    'mail.mailers.smtp.encryption' => strtolower($configEmail->criptografia ?? $configEmail->encryption ?? 'tls'),
                    'mail.mailers.smtp.username'   => $configEmail->email ?? $configEmail->usuario,
                    'mail.mailers.smtp.password'   => $configEmail->senha ?? $configEmail->password,
                    'mail.from.address'            => $configEmail->email,
                    'mail.from.name'               => $configEmail->nome ?? $nomeEmpresa,
                ]);

                $emailRemetente = $configEmail->email;
                $nomeRemetente  = $configEmail->nome ?? $nomeEmpresa;
            } else {
                $emailRemetente = env('MAIL_USERNAME', 'notafiscal@fersofterp.com.br');
                $nomeRemetente  = $nomeEmpresa;

                config([
                    'mail.from.address' => $emailRemetente,
                    'mail.from.name'    => $nomeRemetente,
                ]);
            }

            $parcelas = DB::table('conta_recebers')
                ->where('empresa_id', $empresaId)
                ->where('referencia', 'like', '%(Medição #' . $id . ')%')
                ->get();

            $html = view('contratos.medicoes.print', [
                'fatura'     => $medicao,
                'empresa'    => $empresa,
                'configNota' => $configNota,
                'parcelas'   => $parcelas,
                'title'      => 'Medição #' . $medicao->id
            ])->render();

            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'sans-serif');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfContent  = $dompdf->output();
            $nomeArquivo = "Medicao_Faturamento_{$id}.pdf";

            $nomeCliente     = $medicao->cliente->razao_social ?? $medicao->cliente->nome;
            $numContrato     = $medicao->contrato ? ($medicao->contrato->numero_contrato ?? $medicao->contrato->id) : 'Avulso';
            $valorFormatado  = number_format($medicao->valor_total, 2, ',', '.');
            $dataFaturamento = date('d/m/Y', strtotime($medicao->data_faturamento ?? $medicao->created_at));

            $dadosEmail = [
                'nomeCliente'     => $nomeCliente,
                'id'              => $id,
                'numContrato'     => $numContrato,
                'dataFaturamento' => $dataFaturamento,
                'valorFormatado'  => $valorFormatado,
                'observacao'      => $medicao->observacao,
                'nomeEmpresa'     => $nomeEmpresa
            ];

            Mail::send([], [], function ($message) use ($emailCliente, $nomeCliente, $id, $pdfContent, $nomeArquivo, $dadosEmail, $emailRemetente, $nomeRemetente) {
                $body  = "<p>Olá, <strong>{$dadosEmail['nomeCliente']}</strong>!</p>";
                $body .= "<p>Segue em anexo o comprovante referente à <strong>Medição / Faturamento #{$dadosEmail['id']}</strong>.</p>";
                $body .= "<ul>";
                $body .= "<li><strong>Contrato:</strong> Nº {$dadosEmail['numContrato']}</li>";
                $body .= "<li><strong>Data da Medição:</strong> {$dadosEmail['dataFaturamento']}</li>";
                $body .= "<li><strong>Valor Total:</strong> R$ {$dadosEmail['valorFormatado']}</li>";
                if (!empty($dadosEmail['observacao'])) {
                    $body .= "<li><strong>Observação:</strong> {$dadosEmail['observacao']}</li>";
                }
                $body .= "</ul>";
                $body .= "<p>Ficamos à disposição para dúvidas ou esclarecimentos.</p>";
                $body .= "<p>Atenciosamente,<br><strong>{$dadosEmail['nomeEmpresa']}</strong></p>";

                $message->from($emailRemetente, $nomeRemetente)
                    ->to($emailCliente, $nomeCliente)
                    ->subject("Comprovante de Medição / Faturamento #{$id} - {$dadosEmail['nomeEmpresa']}")
                    ->html($body)
                    ->attachData($pdfContent, $nomeArquivo, [
                        'mime' => 'application/pdf',
                    ]);
            });

            return redirect()->back()->with('mensagem_sucesso', "E-mail com a fatura em PDF enviado com sucesso para {$emailCliente}!");

        } catch (\Throwable $e) {
            Log::error("E-mail Medição #{$id}: Erro - " . $e->getMessage());
            return redirect()->back()->with('mensagem_erro', 'Falha ao enviar e-mail: ' . $e->getMessage());
        }
    }

    public function emitirNfse(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::with(['cliente', 'itens.servico'])->where('empresa_id', $empresa_id)->findOrFail($id);

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $certRes = \App\Models\Certificado::where('empresa_id', $empresa_id)->first();
        $tributacao = \App\Models\Tributacao::where('empresa_id', $empresa_id)->first();

        if (!$config || !$certRes) {
            return redirect()->back()->with('mensagem_erro', 'Configurações ou certificado digital não encontrados.');
        }

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = \NFePHP\Common\Certificate::readPfx($certContent, $certRes->senha);

            // Identificação dinâmica do município emitente
            $codigoIbgeEmitente = null;
            if (!empty($config->cidade_id)) {
                $cidadeObj = DB::table('cidades')->where('id', $config->cidade_id)->first();
                if ($cidadeObj) {
                    $codigoIbgeEmitente = $cidadeObj->codigo ?? $cidadeObj->codigo_ibge ?? null;
                }
            }
            if (!$codigoIbgeEmitente) {
                $codigoIbgeEmitente = $config->codMun ?? $config->codigo_ibge_municipio ?? '2919207';
            }
            $codigoIbgeEmitente = str_pad(preg_replace('/\D/', '', $codigoIbgeEmitente), 7, '0', STR_PAD_LEFT);

            // Seleção de Provedor
            $configArray = $config->toArray();
            switch ((string) $codigoIbgeEmitente) {
                case '2910057': // Dias d'Ávila - BA
                    $provedor = 'diasdavila';
                    $configArray['usuario_saatri'] = '05950998669';
                    $configArray['senha_saatri'] = 'Ciclos24';
                    break;
                case '2927408': // Salvador - BA
                    $provedor = 'salvador';
                    break;
                default:
                    $provedor = 'nacional';
                    break;
            }

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $configArray, $certificate);

            // Busca dados do serviço principal selecionado na medição
            $servicoMaster = $fatura->servico;
            if (!$servicoMaster && $fatura->itens->count() > 0) {
                $servicoMaster = $fatura->itens->first()->servico;
            }

            $cTribNac  = preg_replace('/\D/', '', $servicoMaster->codigo_tributacao_nacional ?? $servicoMaster->codigo_servico ?? '140501');
            $cNBS      = preg_replace('/\D/', '', $servicoMaster->codigo_nbs ?? '120029000');
            $cstIbsCbs = $servicoMaster->cst_ibscbs ?? $tributacao->cst_ibs_cbs ?? '000';
            $classTrib = $servicoMaster->codigo_class_trib ?? $tributacao->class_trib_ibs_cbs ?? '000001';

            $aliquotaIss = (float) ($servicoMaster->aliquota_iss ?? 3.00);
            $issRetido   = (int) ($servicoMaster->iss_retido ?? 2);
            $tpRetISSQN  = ($issRetido === 1) ? 2 : 1;
            $aliquotaInss = (float) ($servicoMaster->aliquota_inss ?? 0);

            // Constrói discriminação detalhada dos itens
            $discriminacaoItens = "MEDIÇÃO DE CONTRATO #" . ($fatura->contrato_eng_id ?? $fatura->id) . "\n";
            if ($fatura->itens && count($fatura->itens) > 0) {
                foreach ($fatura->itens as $it) {
                    $nomeItem = $it->servico->nome ?? $it->produto->nome ?? $it->descricao ?? 'Serviço/Locação';
                    $discriminacaoItens .= "- {$nomeItem} | Qtd: {$it->quantidade} | Vl. Unit: R$ " . number_format($it->valor_unitario, 2, ',', '.') . " | Subtotal: R$ " . number_format($it->sub_total, 2, ',', '.') . "\n";
                }
            }
            if (!empty($fatura->observacao)) {
                $discriminacaoItens .= "Obs: " . $fatura->observacao;
            }

            $cliente = $fatura->cliente;
            $docTomador = preg_replace('/\D/', '', $cliente->cpf_cnpj ?? '');
            $tipoDocTomador = strlen($docTomador) > 11 ? 'CNPJ' : 'CPF';

            $valorServico = (float) $fatura->valor_total;
            $valorIss = round($valorServico * ($aliquotaIss / 100), 2);
            $baseCalculoIbsCbs = round($valorServico - $valorIss, 2);
            $vRetINSS = $aliquotaInss > 0 ? round($valorServico * ($aliquotaInss / 100), 2) : 0.00;

            $dpsData = [
                'infDPS' => [
                    'dhEmi'    => date('Y-m-d\TH:i:sP'),
                    'dCompet'  => date('Y-m-d', strtotime($fatura->data_faturamento ?? now())),
                    'tpEmit'   => 1,
                    'cLocEmi'  => $codigoIbgeEmitente,
                    'serie'    => '1',
                    'nDPS'     => (int) $fatura->id,
                    'prest' => [
                        'CNPJ'    => preg_replace('/\D/', '', $config->cnpj),
                        'IM'      => preg_replace('/\D/', '', $config->inscricao_municipal ?? ''),
                        'regTrib' => ['opSimpNac' => 2, 'regApTribSN' => 1, 'regEspTrib' => 0]
                    ],
                    'toma' => [
                        $tipoDocTomador => $docTomador,
                        'xNome' => $cliente->razao_social ?? 'CONSUMIDOR',
                        'ender' => [
                            'cMun'    => $cliente->cidade->codigo ?? $codigoIbgeEmitente,
                            'CEP'     => preg_replace('/\D/', '', $cliente->cep ?? '40000000'),
                            'xLgr'    => $cliente->rua ?? 'Logradouro',
                            'nro'     => $cliente->numero ?? 'S/N',
                            'xBairro' => $cliente->bairro ?? 'Centro'
                        ]
                    ],
                    'serv' => [
                        'locPrest' => ['cLocPrest' => $codigoIbgeEmitente],
                        'cServ' => [
                            'cTribNac'  => $cTribNac,
                            'xDescServ' => trim($discriminacaoItens),
                            'cNBS'      => $cNBS,
                        ]
                    ],
                    'valores' => [
                        'vServPrest' => ['vServ' => number_format($valorServico, 2, '.', '')],
                        'trib' => [
                            'tribMun' => [
                                'tribISSQN'  => 1,
                                'tpRetISSQN' => $tpRetISSQN,
                                'pAliq'      => number_format($aliquotaIss, 4, '.', '')
                            ],
                            'vDescIncond' => '0.00',
                            'vDed'        => '0.00',
                            'tribFed'     => [
                                'vRetCP'    => number_format($vRetINSS, 2, '.', ''),
                                'vRetIRRF'  => '0.00',
                                'vRetCSLL'  => '0.00',
                                'piscofins' => ['CST' => '07', 'vPIS' => '0.00', 'vCOFINS' => '0.00']
                            ]
                        ]
                    ],
                    'ibscbs' => [
                        'CST'         => $cstIbsCbs,
                        'cClassTrib'  => $classTrib,
                        'cIndOp'      => '050101',
                        'baseCalculo' => number_format($baseCalculoIbsCbs, 2, '.', ''),
                        'percIbsMun'  => '0.00', 'valIbsMun' => '0.00',
                        'percIbsEst'  => '0.10', 'valIbsEst' => number_format(round($baseCalculoIbsCbs * 0.001, 2), 2, '.', ''),
                        'percCbs'     => '0.90', 'valCbs'    => number_format(round($baseCalculoIbsCbs * 0.009, 2), 2, '.', ''),
                    ]
                ]
            ];

            $resultado = $emissor->emitir($dpsData);
            $xmlRetorno = $resultado['xml_retorno'] ?? '';

            $numeroNfse = $resultado['numero_nfse'] ?? $fatura->id;
            $codVerificacao = $resultado['codigo_verificacao'] ?? 'APROVADO';
            $chaveNfse = $resultado['chave_nfse'] ?? $resultado['chave'] ?? ('DPS-' . $fatura->id);
            $protocoloNfse = $resultado['protocolo'] ?? $resultado['nProt'] ?? 'AUTORIZADO';

            $xmlName = 'Medicao_NFSe_' . $fatura->id . '.xml';
            file_put_contents(public_path('nfse_doc/' . $xmlName), $xmlRetorno);

            // Grava os dados oficiais nas colunas da tabela faturas_engenharia
            $fatura->update([
                'numero_nfse'        => $numeroNfse,
                'codigo_verificacao' => $codVerificacao,
                'chave_nfse'         => $chaveNfse,
                'protocolo_nfse'     => $protocoloNfse,
                'xml_nfse'           => $xmlRetorno,
                'url_xml'            => asset('nfse_doc/' . $xmlName),
                'estado_nfse'        => 'aprovado'
            ]);

            // Redireciona diretamente para a impressão oficial da nota (idêntico à emissão normal de serviços)
            return redirect('nfse/imprimir/' . $fatura->id);

        } catch (\Throwable $th) {
            Log::error("Erro NFS-e Medição #{$id}: " . $th->getMessage());
            $fatura->update(['estado_nfse' => 'rejeitado']);

            // Exibe o motivo exato da rejeição na tela para o usuário saber o que corrigir
            return redirect()->back()->with('mensagem_erro', 'Rejeição NFS-e: ' . $th->getMessage());
        }
    }

    public function consultarNfse(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $certRes = \App\Models\Certificado::where('empresa_id', $empresa_id)->first();

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = \NFePHP\Common\Certificate::readPfx($certContent, $certRes->senha);

            $codigoIbge = $config->codMun ?? '2910057';
            $provedor = ($codigoIbge == '2910057') ? 'diasdavila' : 'nacional';

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $config->toArray(), $certificate);

            $retorno = $emissor->consultar($fatura->id);

            $statusRetorno = strtoupper($retorno['status'] ?? '');
            $isCancelada   = $retorno['cancelada'] ?? false;
            $numeroOficial = $retorno['numero_nfse'] ?? null;
            $codigoVerif   = $retorno['codigo_verificacao'] ?? null;

            if ($isCancelada || $statusRetorno === 'CANCELADO') {
                $fatura->update(['estado_nfse' => 'cancelado']);
                return redirect()->back()->with('error', 'A NFS-e encontra-se cancelada.');
            }

            if ($statusRetorno === 'AUTORIZADO' || !empty($numeroOficial)) {
                $fatura->update([
                    'estado_nfse'        => 'aprovado',
                    'numero_nfse'        => $numeroOficial,
                    'codigo_verificacao' => $codigoVerif ?? $fatura->codigo_verificacao
                ]);

                return redirect()->back()->with('sucesso', "NFS-e aprovada! Número oficial: {$numeroOficial}");
            }

            return redirect()->back()->with('warning', 'A nota ainda consta como processando.');

        } catch (\Throwable $th) {
            // AQUI ESTÁ A CORREÇÃO PRINCIPAL DO ERRO NA TELA BRANCA
            return redirect()->back()->with('error', 'Erro ao consultar: ' . $th->getMessage());
        }
    }

    public function baixarXml($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        $path = public_path('nfse_doc/Medicao_NFSe_' . $fatura->id . '.xml');

        if (file_exists($path)) {
            return response()->download($path);
        } else {
            return redirect()->back()->with('mensagem_erro', 'Arquivo XML não encontrado!');
        }
    }

    public function imprimirDanfeNfse($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::with(['cliente', 'itens.servico'])->where('empresa_id', $empresa_id)->findOrFail($id);

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();

        $emitente = (object) [
            'razao_social'        => $config->razao_social ?? $config->nome ?? 'CICLOS SUSTENTABILIDADE AMBIENTAL LTDA',
            'cnpj'                => $config->cnpj ?? '57425351000161',
            'inscricao_municipal' => $config->inscricao_municipal ?? $config->im ?? '16228',
            'optante_simples'     => $config->optante_simples ?? '0',
            'endereco'            => $config->logradouro ?? $config->endereco ?? 'RUA DOIS DE JULHO, S N',
            'numero'              => $config->numero ?? '',
            'bairro'              => $config->bairro ?? 'POLO DE SERVICOS',
            'cidade'              => $config->cidade_nome ?? 'Dias d\'Ávila',
            'uf'                  => $config->uf ?? 'BA',
            'cep'                 => $config->cep ?? '',
            'telefone'            => $config->telefone ?? '',
            'email'               => $config->email ?? ''
        ];

        // Se a base de dados ainda tiver as colunas a null, tentamos extrair do XML ou atribuímos o retorno
        if (empty($fatura->chave_nfse)) {
            $fatura->chave_nfse = 'DPS-' . $fatura->id . '-' . ($fatura->numero_nfse ?? '1');
        }
        if (empty($fatura->protocolo_nfse)) {
            $fatura->protocolo_nfse = $fatura->codigo_verificacao ?? 'AUTORIZADO';
        }

        $title = 'Impressão NFS-e - Medição #' . $fatura->id;

        // IMPORTANTE: Deve ser 'contratos.medicoes.danfse' e NÃO 'nfse.imprimir'
        return view('contratos.medicoes.danfse', compact('fatura', 'config', 'emitente', 'title'));
    }

    public function cancelarNfse(Request $request, $id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? null;
        $fatura = FaturaEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

        if (empty($fatura->numero_nfse)) {
            return redirect()->back()->with('error', 'Esta medição não possui uma nota autorizada para cancelar.');
        }

        $config = \App\Models\ConfigNota::where('empresa_id', $empresa_id)->first();
        $certRes = \App\Models\Certificado::where('empresa_id', $empresa_id)->first();

        // Motivo do cancelamento vindo do formulário (se aplicável, ou fixo)
        $motivo = $request->input('motivo_cancelamento', 'Erro na emissao');

        try {
            $certContent = file_exists(storage_path('app/public/certificados/' . $certRes->arquivo))
                ? file_get_contents(storage_path('app/public/certificados/' . $certRes->arquivo))
                : $certRes->arquivo;
            $certificate = \NFePHP\Common\Certificate::readPfx($certContent, $certRes->senha);

            $codigoIbge = $config->codMun ?? '2910057';
            $provedor = ($codigoIbge == '2910057') ? 'diasdavila' : 'nacional';

            $emissor = \App\Services\Nfse\NfseServiceFactory::criar($provedor, $config->toArray(), $certificate);

            // Chama o método de cancelar no seu provedor (adapte se o método se chamar de outra forma)
            $retorno = $emissor->cancelar([
                'numero_nfse' => $fatura->numero_nfse,
                'codigo_verificacao' => $fatura->codigo_verificacao,
                'motivo' => $motivo
            ]);

            // Se o retorno indicar sucesso ou erro mas que já estava cancelada
            if ((isset($retorno['sucesso']) && $retorno['sucesso']) || (isset($retorno['status']) && $retorno['status'] == 'CANCELADO')) {
                // APENAS ATUALIZA O STATUS, NUNCA FAZ DELETE
                $fatura->update([
                    'estado_nfse' => 'cancelado',
                    'status' => 'Cancelado' // se usar este campo
                ]);

                return redirect()->back()->with('sucesso', 'Nota Fiscal cancelada com sucesso na prefeitura!');
            }

            return redirect()->back()->with('error', 'Erro ao cancelar: ' . ($retorno['mensagem'] ?? 'Erro desconhecido.'));

        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Falha na comunicação de cancelamento: ' . $th->getMessage());
        }
    }
}
