<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApuracaoDifal;
use App\Models\ApuracaoDifalItem;
use App\Models\ContaPagar;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApuracaoDifalController extends BaseController
{
    public function __construct(ApuracaoDifal $model)
    {
        parent::__construct();
        $this->model = $model;
        $this->formTitle = 'Apuração de ICMS Difal (Uso e Consumo)';
        $this->Prefix_Route = 'compraFiscal.difal'; 
        $this->listView = 'compraFiscal.apuracao_difal.index';
        $this->registerView = 'compraFiscal.apuracao_difal.create';
        $this->redirectPage = 'compras/apuracao-difal';
    }

    public function rules(): array { return []; }
    public function messages(): array { return []; }

    public function index()
    {
        $data = $this->model->where('empresa_id', $this->empresa_id)->orderBy('id', 'desc')->get();
        return view($this->listView, compact('data'))->with([
            'formTitle' => $this->formTitle,
            'title'     => $this->formTitle,
            'Prefix_Route' => $this->Prefix_Route
        ]);
    }

    public function create()
    {
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social', 'asc')->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->orderBy('nome', 'asc')->get();

        return view($this->registerView)->with([
            'formTitle'    => $this->formTitle,
            'title'        => 'Nova Apuração',
            'fornecedores' => $fornecedores,
            'categorias'   => $categorias,
            'Prefix_Route' => $this->Prefix_Route
        ]);
    }

    public function processar(Request $request)
    {
        $data_inicial = $request->data_inicial;
        $data_final   = $request->data_final;

        $notas = DB::table('item_compras') 
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->join('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
            ->join('cidades', 'cidades.id', '=', 'fornecedors.cidade_id')
            ->whereBetween('compras.data_emissao', [$data_inicial, $data_final])
            ->where('item_compras.cfop_entrada', '2556')
            ->whereIn('item_compras.cst_icms', ['000', '010', '020', '070', '090'])
            ->where('compras.empresa_id', $this->empresa_id)
            ->select(
                'compras.id as nota_id', 'compras.nf as numero_nota',
                'fornecedors.razao_social as fornecedor', 'cidades.uf as uf_origem', 
                'item_compras.valor_unitario', 'item_compras.quantidade'
            )->get();

        if ($notas->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Nenhuma nota tributada encontrada.'], 404);
        }

        $itensCalculados = [];
        $totalGeralDifal = 0;
        $aliqInternaBA = 0.205; 

        foreach ($notas as $n) {
            $valorOperacao = $n->valor_unitario * $n->quantidade;
            $aliqOrigem = in_array($n->uf_origem, ['SP', 'RJ', 'MG', 'PR', 'RS', 'SC']) ? 0.07 : 0.12;

            $baseDupla = ($valorOperacao * (1 - $aliqOrigem)) / (1 - $aliqInternaBA);
            $difal = ($baseDupla * $aliqInternaBA) - ($baseDupla * $aliqOrigem);

            $itensCalculados[] = [
                'nota_fiscal_id' => $n->nota_id, 'numero_nota' => $n->numero_nota,
                'emitente_nome' => $n->fornecedor, 'valor_operacao' => number_format($valorOperacao, 2, ',', '.'),
                'aliquota_origem' => ($aliqOrigem * 100), 'aliquota_destino' => ($aliqInternaBA * 100),
                'base_calculo_dupla' => number_format($baseDupla, 2, ',', '.'), 'valor_difal' => number_format($difal, 2, ',', '.'),
                'base_raw' => $baseDupla, 'difal_raw' => $difal,
                'icms_origem' => number_format($baseDupla * $aliqOrigem, 2, ',', '.'),
                'icms_destino' => number_format($baseDupla * $aliqInternaBA, 2, ',', '.')
            ];
            $totalGeralDifal += $difal;
        }

        if ($request->confirmar_gravacao) {
            return $this->salvarApuracao($request, $itensCalculados, $totalGeralDifal);
        }

        return response()->json(['status' => 'success', 'itens' => $itensCalculados, 'total_difal' => number_format($totalGeralDifal, 2, ',', '.')]);
    }

    private function salvarApuracao($request, $itens, $total)
    {
        return DB::transaction(function () use ($request, $itens, $total) {
            $apuracao = $this->model->create([
                'empresa_id' => $this->empresa_id, 'filial_id' => $this->filial_id, 'usuario_id' => Auth::id(),
                'referencia' => date('m/Y', strtotime($request->data_inicial)), 'data_inicial' => $request->data_inicial,
                'data_final' => $request->data_final, 'valor_total_difal' => $total, 'status' => 'fechada'
            ]);

            foreach ($itens as $i) {
                ApuracaoDifalItem::create([
                    'apuracao_difal_id' => $apuracao->id, 'empresa_id' => $this->empresa_id, 'filial_id' => $this->filial_id,
                    'nota_fiscal_id' => $i['nota_fiscal_id'], 'numero_nota' => $i['numero_nota'], 'emitente_nome' => $i['emitente_nome'],
                    'valor_operacao' => str_replace(['.', ','], ['', '.'], $i['valor_operacao']), 'aliquota_origem' => $i['aliquota_origem'],
                    'aliquota_destino' => $i['aliquota_destino'], 'base_calculo_dupla' => $i['base_raw'], 'valor_difal' => $i['difal_raw']
                ]);
            }

            $financeiro = ContaPagar::create([
                'empresa_id' => $this->empresa_id, 'filial_id' => $this->filial_id, 'usuario_id' => Auth::id(),
                'fornecedor_id' => $request->fornecedor_id, 'categoria_id' => $request->categoria_id,
                'valor_integral' => $total, 'data_emissao' => date('Y-m-d'), 'data_vencimento' => date('Y-m-15', strtotime('+1 month')),
                'referencia' => "DIFAL USO/CONSUMO - " . $apuracao->referencia, 'status' => false
            ]);

            $apuracao->update(['contas_a_pagar_id' => $financeiro->id]);
            return response()->json(['status' => 'success']);
        });
    }

    public function cancelar($id)
    {
        return DB::transaction(function () use ($id) {
            $apuracao = $this->model->findOrFail($id);
            if ($apuracao->contas_a_pagar_id) {
                $cp = ContaPagar::find($apuracao->contas_a_pagar_id);
                if ($cp && $cp->status == true) return response()->json(['message' => 'Título já pago!'], 422);
                if ($cp) $cp->delete();
            }
            ApuracaoDifalItem::where('apuracao_difal_id', $id)->delete();
            $apuracao->delete();
            return response()->json(['status' => 'success']);
        });
    }

    public function show($id)
    {
        $items = ApuracaoDifalItem::where('apuracao_difal_id', $id)->get();
        return view('compraFiscal.apuracao_difal.detalhes_modal', compact('items'));
    }
  
  	public function imprimir($id)
    {
        $apuracao = $this->model->findOrFail($id);
        $items = \App\Models\ApuracaoDifalItem::where('apuracao_difal_id', $id)->get();

        // Adicionamos o 'title' aqui para o layout não reclamar
        return view('compraFiscal.apuracao_difal.relatorio_pdf', compact('apuracao', 'items'))->with([
            'title' => 'Impressão de Apuração DIFAL - ' . $apuracao->referencia
        ]);
    }
}