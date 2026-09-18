<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Models\Venda;
use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\VendaBalcao;
use App\Models\AlteracaoEstoque;
use App\Exports\RelatorioExport;
use App\Exports\RelatorioListaPrecoExport;
use App\Exports\RelatorioLucroAnaliticoExport;
use App\Exports\ClienteExport;
use App\Models\ItemVenda;
use App\Models\Devolucao;
use App\Models\RemessaNfe;
use App\Models\Locacao;
use App\Models\ItemCompra;
use App\Models\VendaCaixa;
use App\Models\TaxaPagamento;
use App\Models\ItemVendaCaixa;
use App\Models\Compra;
use App\Models\SangriaCaixa;
use App\Models\NaturezaOperacao;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Estoque;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\Funcionario;
use App\Models\ComissaoVenda;
use App\Models\ContaReceber;
use App\Models\Categoria;
use App\Models\GrupoCliente;
use App\Models\Acessor;
use App\Models\SubCategoria;
use App\Models\ListaPreco;
use App\Models\Fornecedor;
use App\Models\Marca;
use App\Models\ComissaoAssessor;
use Dompdf\Dompdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Veiculo;
use App\Models\Pesagem;
use App\Models\TicketPesagem;
use App\Prints\PesagemPrint80;
use App\Prints\PesagemPrint80Simples;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{

    private function obterConfigEmitentePesagemA4(?\App\Models\Pesagem $pesagem = null, ?int $empresaId = null)
    {
        $empresaId = $empresaId ?: ($pesagem->empresa_id ?? null) ?: ($this->empresa_id ?? null);

        if ($empresaId) {
            $config = \App\Models\ConfigNota::where('empresa_id', $empresaId)->first();
            if ($config) {
                return $config;
            }
        }

        $config = \App\Models\ConfigNota::configStatic();
        if ($config) {
            return $config;
        }

        return \App\Models\ConfigNota::first();
    }

    private function adicionarRodapePaginadoPesagemA4(\Dompdf\Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        if (!$canvas) {
            return;
        }

        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('Helvetica', 'normal');
        $fontBold = $fontMetrics->getFont('Helvetica', 'bold');

        $appName = trim((string) config('app.name', ''));
        $siteSuporte = trim((string) config('app.site_suporte', ''));
        $emailSuporte = trim((string) config('app.email_suporte', ''));
        $suporte = trim(implode('  |  ', array_values(array_filter([$siteSuporte, $emailSuporte], static function ($valor) {
            return $valor !== '';
        }))));
        $emitidoEm = now()->format('d/m/Y H:i');

        $pageWidth = (float) $canvas->get_width();
        $y = (float) $canvas->get_height() - 18;
        $fontSize = 7.0;
        $muted = [0.35, 0.35, 0.35];

        if ($appName !== '') {
            $canvas->page_text(24, $y, $appName, $fontBold, $fontSize, $muted);
        }

        if ($suporte !== '') {
            $supportWidth = (float) $fontMetrics->getTextWidth($suporte, $font, $fontSize);
            $supportX = max(24, ($pageWidth - $supportWidth) / 2);
            $canvas->page_text($supportX, $y, $suporte, $font, $fontSize, $muted);
        }

        $pagina = 'Página {PAGE_NUM} de {PAGE_COUNT}';
        $paginaWidth = (float) $fontMetrics->getTextWidth('Página 888 de 888', $font, $fontSize);
        $paginaX = max(24, $pageWidth - 24 - $paginaWidth);
        $canvas->page_text($paginaX, $y, $pagina, $font, $fontSize, $muted);

        $dateText = 'Emitido em ' . $emitidoEm;
        $dateWidth = (float) $fontMetrics->getTextWidth($dateText, $font, $fontSize);
        $dateX = max(24, $paginaX - 18 - $dateWidth);
        $canvas->page_text($dateX, $y, $dateText, $font, $fontSize, $muted);
    }

    private function renderPesagemA4Pdf(array $dadosRelatorio)
    {
        $html = view('relatorios.pesagem', $dadosRelatorio)->render();

        $dompdf = new \Dompdf\Dompdf(["enable_remote" => true]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $this->adicionarRodapePaginadoPesagemA4($dompdf);

        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="relatorio_pesagem_a4.pdf"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

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
        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
            ->get();

        $produtosLocacao = Produto::
        where('empresa_id', $this->empresa_id)
            ->where('valor_locacao', '>', 0)
            ->get();

        $funcionarios = Funcionario::
        where('empresa_id', $this->empresa_id)
            ->get();

        $fornecedores = Fornecedor::
        where('empresa_id', $this->empresa_id)
            ->get();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
            ->get();

        $marcas = Marca::
        where('empresa_id', $this->empresa_id)
            ->get();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
            ->get();

        $subs = SubCategoria::
        select('sub_categorias.*')
            ->join('categorias', 'categorias.id', '=', 'sub_categorias.categoria_id')
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $listaPrecos = ListaPreco::
        where('empresa_id', $this->empresa_id)
            ->get();

        $assessores = Acessor::
        where('empresa_id', $this->empresa_id)
            ->get();

        $gruposCliente = GrupoCliente::
        where('empresa_id', $this->empresa_id)
            ->get();

        $cfops = $this->getCfopDistintos();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
            ->get();

        $funcionarios2 = Funcionario::
        join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
            ->where('usuarios.empresa_id', $this->empresa_id)
            ->select('funcionarios.nome as nome', 'usuarios.id as id')
            ->get();

        $usuarios = Usuario::
        where('usuarios.empresa_id', $this->empresa_id)
            ->orderBy('nome', 'desc')
            ->get();

        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();

        // echo $funcionarios2;
        // die;

        $pesagem = Pesagem::with(['veiculo', 'tickets.produto']) // Inclui relacionamentos relevantes
        ->where('empresa_id', $this->empresa_id)
            ->get();

        return view('relatorios/index')
            ->with('relatorioJS', true)
            ->with('clientes', $clientes)
            ->with('cfops', $cfops)
            ->with('usuarios', $usuarios)
            ->with('produtosLocacao', $produtosLocacao)
            ->with('naturezas', $naturezas)
            ->with('produtos', $produtos)
            ->with('categorias', $categorias)
            ->with('fornecedores', $fornecedores)
            ->with('listaPrecos', $listaPrecos)
            ->with('marcas', $marcas)
            ->with('subs', $subs)
            ->with('gruposCliente', $gruposCliente)
            ->with('funcionarios2', $funcionarios2)
            ->with('funcionarios', $funcionarios)
            ->with('assessores', $assessores)
            ->with('veiculos', $veiculos)
            ->with('pesagem', $pesagem)
            ->with('title', 'Relatórios')
            ->with('html', '');
    }

    private function getCfopDistintos(){
        $cfops1 = Produto::
        select(\DB::raw('distinct(CFOP_saida_estadual) as cfop'))
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $cfops2 = Produto::
        select(\DB::raw('distinct(CFOP_saida_inter_estadual) as cfop'))
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $cfops = [];

        foreach($cfops1 as $c){
            array_push($cfops, $c->cfop);
        }

        foreach($cfops2 as $c){
            if(!in_array($c->cfop, $cfops)){
                array_push($cfops, $c->cfop);
            }
        }

        return $cfops;
    }

    public function filtroVendas(Request $request)
    {
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem ?: 'desc';
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if ($data_inicial && $data_final) {
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $itensVendaSub = DB::table('item_vendas')
            ->select('venda_id', DB::raw('SUM(quantidade) as quantidade_vendida'))
            ->groupBy('venda_id');

        $vendas = Venda::query()
            ->leftJoinSub($itensVendaSub, 'iv', function ($join) {
                $join->on('iv.venda_id', '=', 'vendas.id');
            })
            ->selectRaw('DATE_FORMAT(vendas.data_registro, "%d-%m-%Y") as data')
            ->selectRaw('SUM((vendas.valor_total - vendas.desconto) + vendas.acrescimo) as total')
            ->selectRaw('SUM(COALESCE(iv.quantidade_vendida, 0)) as quantidade_vendida')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->where('vendas.estado', '!=', 'CANCELADO')
            ->when($data_inicial && $data_final, function ($q) use ($data_inicial, $data_final) {
                return $q->whereBetween('vendas.data_registro', [$data_inicial, $data_final]);
            })
            ->when($filial_id !== null && $filial_id !== '', function ($q) use ($filial_id) {
                $local = ((string)$filial_id === '-1') ? null : $filial_id;

                if (is_null($local)) {
                    return $q->whereNull('vendas.filial_id');
                }

                return $q->where('vendas.filial_id', $local);
            })
            ->groupBy('data');

        if ($ordem === 'data') {
            $vendas->orderByRaw('STR_TO_DATE(data, "%d-%m-%Y") DESC');
        } else {
            $vendas->orderBy('total', $ordem === 'asc' ? 'asc' : 'desc');
        }

        $vendas = $vendas
            ->limit($total_resultados ?? 1000000)
            ->get();

        $itensVendaCaixaSub = DB::table('item_venda_caixas')
            ->select('venda_caixa_id', DB::raw('SUM(quantidade) as quantidade_vendida'))
            ->groupBy('venda_caixa_id');

        $vendasCaixa = VendaCaixa::query()
            ->leftJoinSub($itensVendaCaixaSub, 'ivc', function ($join) {
                $join->on('ivc.venda_caixa_id', '=', 'venda_caixas.id');
            })
            ->selectRaw('DATE_FORMAT(venda_caixas.data_registro, "%d-%m-%Y") as data')
            ->selectRaw('SUM(venda_caixas.valor_total) as total')
            ->selectRaw('SUM(COALESCE(ivc.quantidade_vendida, 0)) as quantidade_vendida')
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->where('venda_caixas.estado', '!=', 'CANCELADO')
            ->when($data_inicial && $data_final, function ($q) use ($data_inicial, $data_final) {
                return $q->whereBetween('venda_caixas.data_registro', [$data_inicial, $data_final]);
            })
            ->when($filial_id !== null && $filial_id !== '', function ($q) use ($filial_id) {
                $local = ((string)$filial_id === '-1') ? null : $filial_id;

                if (is_null($local)) {
                    return $q->whereNull('venda_caixas.filial_id');
                }

                return $q->where('venda_caixas.filial_id', $local);
            })
            ->groupBy('data');

        if ($ordem === 'data') {
            $vendasCaixa->orderByRaw('STR_TO_DATE(data, "%d-%m-%Y") DESC');
        } else {
            $vendasCaixa->orderBy('total', $ordem === 'asc' ? 'asc' : 'desc');
        }

        $vendasCaixa = $vendasCaixa
            ->limit($total_resultados ?? 1000000)
            ->get();

        $arr = $this->uneArrayVendas($vendas, $vendasCaixa);

        if ($total_resultados) {
            $arr = array_slice($arr, 0, $total_resultados);
        }

        usort($arr, function ($a, $b) use ($ordem) {
            if ($ordem === 'asc') {
                return $a['total'] <=> $b['total'];
            } elseif ($ordem === 'desc') {
                return $b['total'] <=> $a['total'];
            }

            $dataA = \DateTime::createFromFormat('d-m-Y', $a['data']);
            $dataB = \DateTime::createFromFormat('d-m-Y', $b['data']);

            if ($dataA == $dataB) {
                return 0;
            }

            return $dataA < $dataB ? 1 : -1;
        });

        if (sizeof($arr) == 0) {
            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/relatorio_venda')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de vendas')
            ->with('vendas', $arr);

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();

        $domPdf->stream("Somatório de vendas.pdf", ["Attachment" => false]);
    }

    public function filtroVendas_err(Request $request){

        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $vendas = Venda
            ::select(\DB::raw('DATE_FORMAT(vendas.data_registro, "%d-%m-%Y") as data, sum(vendas.valor_total-vendas.desconto-vendas.acrescimo) as total, sum(vendas.itens) as quantidade_vendida'))

            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_inicial && $data_final){
                    return $q->whereBetween('vendas.data_registro', [$data_inicial,
                        $data_final]);
                }
            })
            ->when($filial_id, function($q) use ($filial_id){
                $local = $filial_id == -1 ? null : $filial_id;
                return $q->where('vendas.filial_id', $local);
            })
            ->where('vendas.empresa_id', $this->empresa_id)
            ->where('vendas.estado', '!=', 'CANCELADO')
            ->groupBy('data')
            ->orderBy($ordem == 'data' ? 'data' : 'total', $ordem == 'data' ? 'desc' : $ordem)


            ->limit($total_resultados ?? 1000000)
            ->get();

        $vendasCaixa = VendaCaixa
            ::select(\DB::raw('DATE_FORMAT(venda_caixas.data_registro, "%d-%m-%Y") as data, sum(venda_caixas.valor_total) as total, sum(venda_caixas.itens) as quantidade_vendida'))

            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_inicial && $data_final){
                    return $q->whereBetween('venda_caixas.data_registro', [$data_inicial,
                        $data_final]);
                }
            })
            ->when($filial_id, function($q) use ($filial_id){
                $local = $filial_id == -1 ? null : $filial_id;
                return $q->where('venda_caixas.filial_id', $local);
            })
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->where('venda_caixas.estado', '!=', 'CANCELADO')
            ->groupBy('data')
            ->orderBy($ordem == 'data' ? 'data' : 'total', $ordem == 'data' ? 'desc' : $ordem)
            ->limit($total_resultados ?? 1000000)
            ->get();

        $arr = $this->uneArrayVendas($vendas, $vendasCaixa);
        if($total_resultados){
            $arr = array_slice($arr, 0, $total_resultados);
        }

        usort($arr, function($a, $b) use ($ordem){
            if($ordem == 'asc') return $a['total'] > $b['total'] ? 1 : 0;
            else if($ordem == 'desc') return $a['total'] < $b['total'] ? 1 : 0;
            else return $a['data'] < $b['data'] ? 1 : 0;
        });

        if(sizeof($arr) == 0){

            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/relatorio_venda')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')

            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de vendas')

            ->with('vendas', $arr);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();

        $domPdf->stream("Somatório de vendas.pdf", array("Attachment" => false));
    }

    public function filtroVendas2(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;
        $funcionario = $request->funcionario;
        $cliente_id = $request->cliente_id;
        $numero_nfce = $request->numero_nfce;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $vendas = Venda::
        orWhere(function($q) use ($data_inicial, $data_final){
            if($data_inicial && $data_final){
                return $q->whereBetween('vendas.data_registro', [$data_inicial,
                    $data_final]);
            }
        })
            ->where('vendas.empresa_id', $this->empresa_id)
            ->when($cliente_id != 'null', function($q) use ($cliente_id){
                return $q->where('vendas.cliente_id', $cliente_id);
            })
            ->when($filial_id, function($q) use ($filial_id){
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $q->where('vendas.filial_id', $filial_id);
            })
            ->where('vendas.estado', '!=', 'CANCELADO')
            ->limit($total_resultados ?? 1000000);

        if($request->tipo_pagamento){
            $vendas->where('tipo_pagamento', $request->tipo_pagamento);
        }

        if($funcionario != 'null'){
            $funcionario = Funcionario::find($request->funcionario);
            $vendas->where('vendedor_id', $funcionario->usuario_id);
        }

        $vendas = $vendas->with('itens')->get();

        if($numero_nfce){
            $vendas = [];
        }

        $vendasCaixa = VendaCaixa::
        orWhere(function($q) use ($data_inicial, $data_final){
            if($data_inicial && $data_final){
                return $q->whereBetween('venda_caixas.data_registro', [$data_inicial,
                    $data_final]);
            }
        })
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->where('venda_caixas.estado', '!=', 'CANCELADO')
            ->where('venda_caixas.rascunho', 0)
            ->where('venda_caixas.consignado', 0)
            ->when($filial_id, function($q) use ($filial_id){
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $q->where('venda_caixas.filial_id', $filial_id);
            })
            ->when($cliente_id != 'null', function($q) use ($cliente_id){
                return $q->where('venda_caixas.cliente_id', $cliente_id);
            })
            ->when($numero_nfce, function($q) use ($numero_nfce){
                return $q->where('venda_caixas.NFcNumero', $numero_nfce);
            })
            ->limit($total_resultados ?? 1000000);

        if($request->tipo_pagamento){
            $vendasCaixa->where('tipo_pagamento', $request->tipo_pagamento);
        }

        if($funcionario != 'null'){
            $funcionario = Funcionario::find($request->funcionario);
            $vendasCaixa->where('vendedor_id', $funcionario->usuario_id);
        }

        $vendasCaixa = $vendasCaixa->with('itens')->get();

        $arr = $this->uneArrayVendas2($vendas, $vendasCaixa);
        if($total_resultados){
            $arr = array_slice($arr, 0, $total_resultados);
        }

        usort($arr, function($a, $b) use ($ordem){
            return $a['created_at'] > $b['created_at'] ? 1 : 0;
        });

        if(sizeof($arr) == 0){

            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }
        // dd($arr);

        $p = view('relatorios/relatorio_venda2')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de vendas')
            ->with('vendas', $arr);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de vendas.pdf", array("Attachment" => false));
    }

    public function filtroCompras(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }


        $compras = Compra
            ::select(\DB::raw('DATE_FORMAT(compras.created_at, "%d-%m-%Y") as data, sum(compras.valor) as total,
			count(id) as compras_diarias'))
            // ->join('item_compras', 'item_compras.compra_id', '=', 'item_compras.id')
            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('compras.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->when($filial_id, function($q) use ($filial_id){
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $q->where('compras.filial_id', $filial_id);
            })
            ->where('estado', '!=', 'CANCELADO')
            ->where('empresa_id', $this->empresa_id)
            ->groupBy('data')
            // ->orderBy('total', $ordem)

            ->limit($total_resultados ?? 1000000);

        if($ordem == 'data'){
            $compras->orderBy('created_at', 'desc');
        }else{
            $compras->orderBy('total', $ordem);
        }

        $compras = $compras->get();

        if(sizeof($compras) == 0){

            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/relatorio_compra')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de compras')
            ->with('compras', $compras);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de compras.pdf", array("Attachment" => false));
    }

    public function filtroComprasDetalhado(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $numero_nfe = $request->numero_nfe;
        $fornecedor_id = $request->fornecedor_id;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $compras = Compra::
        select('compras.*')
            ->orderBy('compras.fornecedor_id')
            ->where('empresa_id', $this->empresa_id)
            ->when($data_final && $data_final, function($q) use ($data_inicial, $data_final){
                return $q->whereBetween('compras.created_at', [$data_inicial,
                    $data_final]);
            })
            ->when($filial_id, function($q) use ($filial_id){
                $filial_id = $filial_id == -1 ? null : $filial_id;
                return $q->where('compras.filial_id', $filial_id);
            })
            ->when($fornecedor_id != 'null', function($q) use ($fornecedor_id){
                return $q->where('compras.fornecedor_id', $fornecedor_id);
            })
            ->when($numero_nfe, function($q) use ($fornecedor_id){
                return $q->where('compras.nf', 'LIKE', "%$numero_nfe%");
            });

        $compras = $compras->get();

        if(sizeof($compras) == 0){

            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/relatorio_compra_detalhado')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de compras')
            ->with('compras', $compras);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de compras.pdf", array("Attachment" => false));
    }

    public function filtroVendaProdutos(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;

        $categoria_id = $request->categoria_id;
        $marca_id = $request->marca_id;
        $sub_categoria_id = $request->sub_categoria_id;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $itensVenda = ItemVenda
            ::select(\DB::raw('produtos.id as id, produtos.nome as nome, produtos.grade as grade, produtos.str_grade as str_grade, produtos.valor_venda, produtos.valor_compra, sum(item_vendas.quantidade) as total, sum(item_vendas.quantidade * item_vendas.valor) as total_dinheiro, produtos.unidade_venda'))
            ->join('produtos', 'produtos.id', '=', 'item_vendas.produto_id')
            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('item_vendas.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->where('produtos.empresa_id', $this->empresa_id)
            ->groupBy('produtos.id');

        if($categoria_id){
            $itensVenda->where('produtos.categoria_id', $categoria_id);
        }

        if($marca_id){
            $itensVenda->where('produtos.marca_id', $marca_id);
        }

        if($sub_categoria_id){
            $itensVenda->where('produtos.sub_categoria_id', $sub_categoria_id);
        }

        // if($ordem != 'alfa'){
        // 	$itensVenda->orderBy('total', $ordem);
        // }else{
        // 	$itensVenda->orderBy('produtos.nome');
        // }

        // ->limit($total_resultados ?? 1000000)
        $itensVenda = $itensVenda->get();

        $itensVendaCaixa = ItemVendaCaixa
            ::select(\DB::raw('produtos.id as id, produtos.nome as nome, produtos.grade as grade, produtos.str_grade as str_grade, produtos.valor_venda, produtos.valor_compra, sum(item_venda_caixas.quantidade) as total, sum(item_venda_caixas.quantidade * item_venda_caixas.valor) as total_dinheiro, produtos.unidade_venda'))
            ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('item_venda_caixas.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->where('produtos.empresa_id', $this->empresa_id)
            ->groupBy('produtos.id');

        if($categoria_id){
            $itensVendaCaixa->where('produtos.categoria_id', $categoria_id);
        }

        if($marca_id){
            $itensVendaCaixa->where('produtos.marca_id', $marca_id);
        }

        if($sub_categoria_id){
            $itensVendaCaixa->where('produtos.sub_categoria_id', $sub_categoria_id);
        }

        // if($ordem != 'alfa'){
        // 	$itensVendaCaixa->orderBy('total', $ordem);
        // }else{
        // 	$itensVendaCaixa->orderBy('produtos.nome');
        // }

        // ->limit($total_resultados ?? 1000000)
        $itensVendaCaixa = $itensVendaCaixa->get();

        $arr = $this->uneArrayProdutos($itensVenda, $itensVendaCaixa);

        usort($arr, function($a, $b) use ($ordem){
            if($ordem == 'alfa'){
                return $a['nome'] < $b['nome'] ? 1 : -1;
            }else{
                if($ordem == 'asc') return $a['total'] > $b['total'] ? 1 : 0;
                else return $a['total'] < $b['total'] ? 1 : 0;
            }

        });

        // print_r($arr);

        // die;

        if(sizeof($arr) == 0){

            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        if($total_resultados){
            $arr = array_slice($arr, 0, $total_resultados);
        }

        usort($arr, function($a, $b) use ($ordem){
            if($ordem == 'asc') return $a['total'] > $b['total'] ? 1 : 0;
            else return $a['total'] < $b['total'] ? 1 : 0;
        });


        $p = view('relatorios/relatorio_venda_produtos')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de produtos')
            ->with('itens', $arr);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de produtos.pdf", array("Attachment" => false));

    }


    public function filtroVendaClientes(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;
        $cliente_id = $request->cliente_id;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $vendas = Venda
            ::select(\DB::raw('clientes.id as id, clientes.razao_social as nome, count(*) as total, sum(valor_total-desconto+acrescimo) as total_dinheiro'))
            ->join('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('vendas.data_registro', [$data_inicial,
                        $data_final]);
                }
            })
            ->where('vendas.empresa_id', $this->empresa_id)
            ->where('vendas.cliente_id', $cliente_id)
            ->groupBy('clientes.id')
            ->orderBy('total', $ordem)
            ->limit($total_resultados ?? 1000000)
            ->get();

        $vendaCaixa = VendaCaixa
            ::select(\DB::raw('clientes.id as id, clientes.razao_social as nome, count(*) as total, sum(valor_total) as total_dinheiro'))
            ->join('clientes', 'clientes.id', '=', 'venda_caixas.cliente_id')
            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('venda_caixas.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->where('venda_caixas.cliente_id', $cliente_id)
            ->groupBy('clientes.id')
            ->orderBy('total', $ordem)
            ->limit($total_resultados ?? 1000000)
            ->get();
        if(sizeof($vendas) == 0 && sizeof($vendaCaixa) == 0){
            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $temp = [];
        $add = [];
        foreach($vendas as $v){
            array_push($temp, $v);
            array_push($add, $v->nome);
        }

        for($i=0; $i<sizeof($vendaCaixa); $i++){
            try{
                if(in_array($vendaCaixa[$i]->nome, $add)){
                    $indice = $this->getIndice($vendaCaixa[$i]->nome, $temp);

                    $temp[$i]->total_dinheiro += $vendas[$indice]->total_dinheiro;
                    $temp[$i]->total += $vendas[$indice]->total;
                }else{
                    array_push($temp, $vendaCaixa[$i]);
                }
            }catch(\Exception $e){
                echo $e->getMessage();
            }
        }

        $cliente = Cliente::find($cliente_id);
        $p = view('relatorios/relatorio_clientes')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('cliente', $cliente)
            ->with('title', 'Relatório de vendas por cliente(s)')
            ->with('vendas', $temp);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de vendas por cliente(s).pdf", array("Attachment" => false));
    }

    private function getIndice($nome, $arr){
        for($i=0; $i<sizeof($arr); $i++){
            if($arr[$i]->nome == $nome) return $i;
        }
    }

    public function filtroEstoqueMinimo(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;
        $produto_id = $request->produto_id;
        $categoria_id = $request->categoria_id;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
            ->when($produto_id != 'null', function($q) use ($produto_id){
                return $q->where('id', $produto_id);
            })
            ->when($categoria_id, function($q) use ($categoria_id){
                return $q->where('categoria_id', $categoria_id);
            })
            ->get();

        $arrDesfalque = [];
        foreach($produtos as $p){
            if($p->estoque_minimo > 0){
                $estoque = Estoque::where('produto_id', $p->id)->first();
                $temp = null;
                if($estoque == null){
                    $temp = [
                        'id' => $p->id,
                        'nome' => $p->nome . ($p->grade ? " $p->str_grade" : ""),
                        'estoque_minimo' => $p->estoque_minimo,
                        'estoque_atual' => 0,
                        'total_comprar' => $p->estoque_minimo,
                        'valor_compra' => 0
                    ];
                }else{
                    $temp = [
                        'id' => $p->id,
                        'nome' => $p->nome . ($p->grade ? " $p->str_grade" : ""),
                        'estoque_minimo' => $p->estoque_minimo,
                        'estoque_atual' => $estoque->quantidade,
                        'total_comprar' => $p->estoque_minimo - $estoque->quantidade,
                        'valor_compra' => $estoque->valor_compra
                    ];
                }

                array_push($arrDesfalque, $temp);

            }
        }

        if($total_resultados){
            $arrDesfalque = array_slice($arrDesfalque, 0, $total_resultados);
        }

        // print_r($arrDesfalque);

        $p = view('relatorios/relatorio_estoque_minimo')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de Estoque Mínimo')
            ->with('itens', $arrDesfalque);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de estoque minimo.pdf", array("Attachment" => false));
    }

    public function filtroVendaDiaria(Request $request){
        $data = $request->data_inicial;
        $total_resultados = $request->total_resultados;
        $ordem = $request->ordem;

        $numero_nfce = $request->numero_nfce;
        $produto_id = $request->produto_id;

        $data_inicial = null;
        $data_final = null;

        if(strlen($data) == 0){
            session()->flash("mensagem_erro", "Informe o dia para gerar o relatório!");
            return redirect('/relatorios');
        }else{
            $data_inicial = $this->parseDateDay($data);
            $data_final = $this->parseDateDay($data, true);
        }

        $vendas = Venda
            ::select(\DB::raw('vendas.id, DATE_FORMAT(vendas.data_registro, "%d/%m/%Y %H:%i") as data, (valor_total-desconto+acrescimo) as valor_total'))
            ->join('item_vendas', 'item_vendas.venda_id', '=', 'vendas.id')
            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('vendas.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->when($produto_id, function($q) use ($produto_id){
                return $q->where('item_vendas.produto_id', $produto_id);
            })
            ->where('vendas.empresa_id', $this->empresa_id)
            ->groupBy('vendas.id')

            ->limit($total_resultados ?? 1000000)
            ->get();

        if($numero_nfce){
            $vendas = [];
        }

        $vendasCaixa = VendaCaixa
            ::select(\DB::raw('venda_caixas.id, DATE_FORMAT(venda_caixas.data_registro, "%d/%m/%Y %H:%i") as data, (valor_total) as valor_total'))
            ->join('item_venda_caixas', 'item_venda_caixas.venda_caixa_id', '=', 'venda_caixas.id')

            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('venda_caixas.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->when($produto_id, function($q) use ($produto_id){
                return $q->where('item_venda_caixas.produto_id', $produto_id);
            })
            ->when($numero_nfce, function($q) use ($numero_nfce){
                return $q->where('venda_caixas.NFcNumero', $numero_nfce);
            })
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->groupBy('venda_caixas.id')
            ->limit($total_resultados ?? 1000000)
            ->get();


        $arr = $this->uneArrayVendasDay($vendas, $vendasCaixa);
        if($total_resultados){
            $arr = array_slice($arr, 0, $total_resultados);
        }

        // usort($arr, function($a, $b) use ($ordem){
        // 	if($ordem == 'asc') return $a['total'] > $b['total'];
        // 	else if($ordem == 'desc') return $a['total'] < $b['total'];
        // 	else return $a['data'] < $b['data'];
        // });

        if(sizeof($arr) == 0){

            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/relatorio_diario')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')

            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de vendas')
            ->with('vendas', $arr);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de vendas.pdf", array("Attachment" => false));
    }

    public function filtroVendaDiariaPdv(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;

        $vendasCaixa = VendaCaixa
            ::select(\DB::raw('venda_caixas.id, DATE_FORMAT(venda_caixas.created_at, "%d/%m/%Y %H:%i") as data, (valor_total) as valor_total'))
            ->join('item_venda_caixas', 'item_venda_caixas.venda_caixa_id', '=', 'venda_caixas.id')

            ->orWhere(function($q) use ($data_inicial, $data_final){
                if($data_final && $data_final){
                    return $q->whereBetween('venda_caixas.created_at', [$data_inicial,
                        $data_final]);
                }
            })
            ->when(!empty($data_inicial), function ($query) use ($data_inicial) {
                return $query->whereDate('venda_caixas.created_at', '>=', $data_inicial);
            })
            ->when(!empty($data_final), function ($query) use ($data_final) {
                return $query->whereDate('venda_caixas.created_at', '<=', $data_final);
            })
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->groupBy('venda_caixas.id')
            ->limit($total_resultados ?? 1000000)
            ->get();

        if(sizeof($vendasCaixa) == 0){
            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/relatorio_vendas_pdv')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de vendas PDV')
            ->with('vendas', $vendasCaixa);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de vendas PDV.pdf", array("Attachment" => false));
    }

    private function uneArrayVendas2($vendas, $vendasCaixa){
        $adicionados = [];
        $arr = [];

        foreach($vendas as $v){
            $v->tbl = 'pedido';
            array_push($arr, $v);
        }

        foreach($vendasCaixa as $v){
            $v->tbl = 'pdv';
            array_push($arr, $v);

        }
        return $arr;
    }

    private function uneArrayVendas($vendas, $vendasCaixa){
        $adicionados = [];
        $arr = [];

        foreach($vendas as $v){

            $temp = [
                'data' => $v->data,
                'total' => $v->total,
                'quantidade_vendida' => (float)$v->quantidade_vendida
            ];
            array_push($adicionados, $v->data);
            array_push($arr, $temp);

        }

        foreach($vendasCaixa as $v){


            if(!in_array($v->data, $adicionados)){


                $temp = [
                    'data' => $v->data,
                    'total' => $v->total,
                    'quantidade_vendida' => (float)$v->quantidade_vendida
                ];
                array_push($adicionados, $v->data);
                array_push($arr, $temp);
            }else{
                for($aux = 0; $aux < count($arr); $aux++){
                    if($arr[$aux]['data'] == $v->data){
                        $arr[$aux]['total'] += $v->total;
                        $arr[$aux]['quantidade_vendida'] += (float)$v->quantidade_vendida;
                    }
                }
            }

        }
        return $arr;
    }

    private function uneArrayVendasDay($vendas, $vendasCaixa){
        $adicionados = [];
        $arr = [];

        foreach($vendas as $v){

            $temp = [
                'id' => $v->id,
                'data' => $v->data,
                'total' => $v->valor_total,
                'itens' => $v->itens
            ];
            array_push($adicionados, $v->data);
            array_push($arr, $temp);

        }

        foreach($vendasCaixa as $v){

            $temp = [
                'id' => $v->id,
                'data' => $v->data,
                'total' => $v->valor_total,
                'itens' => $v->itens
            ];

            array_push($adicionados, $v->data);
            array_push($arr, $temp);

        }
        return $arr;
    }

    private function uneArrayProdutos($itemVenda, $itemVendasCaixa){
        $adicionados = [];
        $arr = [];

        foreach($itemVenda as $i){

            $temp = [
                'id' => $i->id,
                'nome' => $i->nome,
                'valor_venda' => $i->valor_venda,
                'valor_compra' => $i->valor_compra,
                'total' => $i->total,
                'total_dinheiro' => $i->total_dinheiro,
                'grade' => $i->grade,
                'unidade' => $i->unidade_venda,
                'str_grade' => $i->str_grade,
            ];
            array_push($adicionados, $i->id);
            array_push($arr, $temp);

        }

        foreach($itemVendasCaixa as $i){
            if(!in_array($i->id, $adicionados)){
                $temp = [
                    'id' => $i->id,
                    'nome' => $i->nome,
                    'valor_venda' => $i->valor_venda,
                    'valor_compra' => $i->valor_compra,
                    'total' => $i->total,
                    'total_dinheiro' => $i->total_dinheiro,
                    'grade' => $i->grade,
                    'unidade' => $i->unidade_venda,
                    'str_grade' => $i->str_grade,
                ];
                array_push($adicionados, $i->id);
                array_push($arr, $temp);
            }else{
                for($aux = 0; $aux < count($arr); $aux++){
                    if($arr[$aux]['id'] == $i->id){
                        $arr[$aux]['total'] += $i->total;
                        $arr[$aux]['total_dinheiro'] += $i->total;
                    }
                }
            }
        }

        return $arr;
    }

    private static function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    private static function parseDateDay($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date))) . " 00:00";
        else
            return date('Y-m-d', strtotime(str_replace("/", "-", $date))) . " 23:59";

    }

    public function filtroLucro(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $tipo = $request->tipo;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if($tipo == 'detalhado'){
            if(!$data_inicial){
                session()->flash("mensagem_erro", "Informe a data para gerar o relatório!");
                return redirect('/relatorios');
            }

            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_inicial);

            $vendas = Venda
                ::whereBetween('vendas.created_at', [
                    $data_inicial . " 00:00:00",
                    $data_final . " 23:59:00"
                ])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('vendas.filial_id', $local);
                })
                ->where('empresa_id', $this->empresa_id)
                ->groupBy('created_at')
                ->get();

            $vendasCaixa = VendaCaixa
                ::whereBetween('venda_caixas.created_at', [
                    $data_inicial . " 00:00:00",
                    $data_final . " 23:59:00"
                ])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('venda_caixas.filial_id', $local);
                })
                ->where('empresa_id', $this->empresa_id)
                ->groupBy('created_at')
                ->get();

            $arr = [];
            foreach($vendas as $v){
                $total = $v->valor_total;
                $somaValorCompra = 0;
                foreach($v->itens as $i){
                    //pega valor de compra
                    $vCompra = 0;
                    $vCompra = $i->produto->valor_compra;
                    if(!$vCompra == 0){
                        $estoque = Estoque::ultimoValorCompra($i->produto_id);

                        if($estoque != null){
                            $vCompra = $estoque->valor_compra;
                        }
                    }

                    $somaValorCompra = $i->quantidade * $vCompra;
                }

                $lucro = $total - $somaValorCompra;
                if($somaValorCompra == 0){
                    $somaValorCompra = 1;
                }
                $temp = [
                    'valor_venda' => $total,
                    'valor_compra' => $somaValorCompra,
                    'lucro' => $lucro,
                    'lucro_percentual' =>
                        number_format((($somaValorCompra - $total)/$somaValorCompra*100)*-1, 2),
                    'local' => 'NF-e',
                    'cliente' => $v->cliente->razao_social,
                    'horario' => \Carbon\Carbon::parse($v->created_at)->format('H:i')
                ];
                array_push($arr, $temp);
            }


            foreach($vendasCaixa as $v){
                $total = $v->valor_total;
                $somaValorCompra = 0;
                foreach($v->itens as $i){
                    //pega valor de compra
                    $vCompra = 0;
                    $vCompra = $i->produto->valor_compra;
                    if($vCompra == 0){
                        $estoque = Estoque::ultimoValorCompra($i->produto_id);

                        if($estoque != null){
                            $vCompra = $estoque->valor_compra;
                        }
                    }

                    $somaValorCompra += $i->quantidade * $vCompra;
                }

                // echo "VendaID $v->id | Total: ". $total . " | soma itens: " . $somaValorCompra . "<br>";

                $lucro = $total - $somaValorCompra;

                if($somaValorCompra == 0){
                    $somaValorCompra = 1;
                }

                $temp = [
                    'valor_venda' => $total,
                    'valor_compra' => $somaValorCompra,
                    'lucro' => $lucro,
                    'lucro_percentual' =>
                        number_format((($somaValorCompra - $total)/$somaValorCompra*100)*-1, 2),
                    'local' => 'PDV',
                    'cliente' => $v->cliente ? $v->cliente->razao_social : 'Cliente padrão',
                    'horario' => \Carbon\Carbon::parse($v->created_at)->format('H:i')
                ];
                array_push($arr, $temp);


            }

            if(sizeof($arr) == 0){

                session()->flash("mensagem_erro", "Relatório sem registro!");
                return redirect('/relatorios');
            }

            $p = view('relatorios/lucro_detalhado')
                ->with('data_inicial', $request->data_inicial)
                ->with('title', 'Relatório de lucro')
                ->with('lucros', $arr);

            // return $p;

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->set_paper('letter', 'landscape');
            $domPdf->render();
            $domPdf->stream("Relatório de lucro detalhado.pdf", array("Attachment" => false));

        }else{

            if($data_final && $data_final){
                $data_inicial = $this->parseDate($data_inicial);
                $data_final = $this->parseDate($data_final);
            }
            if(!$data_inicial || !$data_final){
                session()->flash("mensagem_erro", "Informe o periodo corretamente para gerar o relatório!");
                return redirect('/relatorios');
            }

            $vendas = Venda
                ::whereBetween('vendas.created_at', [
                    $data_inicial . " 00:00:00",
                    $data_final . " 23:59:00"
                ])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('vendas.filial_id', $local);
                })
                ->where('empresa_id', $this->empresa_id)
                ->where('estado', '!=', 'CANCELADO')
                ->groupBy('created_at')
                ->get();

            $vendasCaixa = VendaCaixa
                ::whereBetween('venda_caixas.created_at', [
                    $data_inicial . " 00:00:00",
                    $data_final . " 23:59:00"
                ])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('venda_caixas.filial_id', $local);
                })
                ->where('empresa_id', $this->empresa_id)
                ->where('estado', '!=', 'CANCELADO')
                ->groupBy('created_at')
                ->get();

            $tempVenda = [];
            foreach($vendas as $v){
                $total = $v->valor_total;
                $somaValorCompra = 0;
                foreach($v->itens as $i){
                    //pega valor de compra
                    $vCompra = 0;
                    $vCompra = $i->produto->valor_compra;
                    if($vCompra == 0){
                        $estoque = Estoque::ultimoValorCompra($i->produto_id);

                        if($estoque != null){
                            $vCompra = $estoque->valor_compra;
                        }
                    }

                    $somaValorCompra += $i->quantidade * $vCompra;
                }

                $lucro = $total - $somaValorCompra;

                if(!isset($tempVenda[\Carbon\Carbon::parse($v->created_at)->format('d/m/Y')])){
                    $tempVenda[\Carbon\Carbon::parse($v->created_at)->format('d/m/Y')] = $lucro;
                }else{
                    $tempVenda[\Carbon\Carbon::parse($v->created_at)->format('d/m/Y')] += $lucro;
                }

            }

            $tempCaixa = [];
            foreach($vendasCaixa as $v){
                $total = $v->valor_total;
                $somaValorCompra = 0;
                foreach($v->itens as $i){
                    //pega valor de compra
                    $vCompra = 0;
                    $vCompra = $i->produto->valor_compra;
                    if($vCompra == 0){
                        $estoque = Estoque::ultimoValorCompra($i->produto_id);

                        if($estoque != null){
                            $vCompra = $estoque->valor_compra;
                        }
                    }

                    $somaValorCompra += $i->quantidade * $vCompra;
                }

                $lucro = $total - $somaValorCompra;

                if(!isset($tempCaixa[\Carbon\Carbon::parse($v->created_at)->format('d/m/Y')])){
                    $tempCaixa[\Carbon\Carbon::parse($v->created_at)->format('d/m/Y')] = $lucro;
                }else{
                    $tempCaixa[\Carbon\Carbon::parse($v->created_at)->format('d/m/Y')] += $lucro;
                }

            }

            // print_r($tempVenda);
            // print_r($tempCaixa);

            $arr = $this->criarArrayDeDatas($data_inicial, $data_final, $tempVenda, $tempCaixa);


            $p = view('relatorios/lucro')
                ->with('data_inicial', $request->data_inicial)
                ->with('data_final', $request->data_final)
                ->with('title', 'Relatório de lucro sintético')
                ->with('lucros', $arr);

            // return $p;

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("Relatório de lucro.pdf", array("Attachment" => false));
        }
    }

    public function relatorioLucroAnalitico(Request $request){

        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $codigo_venda = $request->codigo_venda;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if(!$data_inicial || !$data_final){
            session()->flash("mensagem_erro", "Informe o periodo corretamente para gerar o relatório!");
            return redirect('/relatorios');
        }

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $vendas = Venda::
        select('vendas.*')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->join('item_vendas', 'item_vendas.venda_id', '=', 'vendas.id')
            ->when($data_final && $data_final, function($q) use ($data_inicial, $data_final){
                return $q->whereBetween('vendas.created_at', [$data_inicial,
                    $data_final]);
            })
            ->when($filial_id, function($q) use ($filial_id){
                $local = $filial_id == -1 ? null : $filial_id;
                return $q->where('vendas.filial_id', $local);
            })
            ->when($codigo_venda, function($q) use ($data_inicial, $data_final){
                return $q->where('vendas.numero_sequencial', $codigo_venda);
            })
            ->groupBy('vendas.id')
            ->get();

        $vendasCaixa = VendaCaixa::
        select('venda_caixas.*')
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->join('item_venda_caixas', 'item_venda_caixas.venda_caixa_id', '=', 'venda_caixas.id')
            ->when($data_final && $data_final, function($q) use ($data_inicial, $data_final){
                return $q->whereBetween('venda_caixas.created_at', [$data_inicial,
                    $data_final]);
            })
            ->when($filial_id, function($q) use ($filial_id){
                $local = $filial_id == -1 ? null : $filial_id;
                return $q->where('venda_caixas.filial_id', $local);
            })
            ->when($codigo_venda, function($q) use ($data_inicial, $data_final){
                return $q->where('venda_caixas.id', $codigo_venda);
            })
            ->groupBy('venda_caixas.id')
            ->get();

        $data = $this->agrupaVendasOrdena($vendas, $vendasCaixa);
        $data = $this->gerarLucroVendas($data);

        $p = view('relatorios/lucro_analitico')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('title', 'Relatório de lucro analítico')
            ->with('data', $data);

        // return $p;
        if($request->excel == 0){
            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4", "landscape");
            $domPdf->render();
            $domPdf->stream("Relatório de lucro.pdf", array("Attachment" => false));
        }else{

            $relatorioEx = new RelatorioLucroAnaliticoExport($data);
            return Excel::download($relatorioEx, 'lucro analítico.xlsx');
        }
    }

    private function agrupaVendasOrdena($vendas, $vendasCaixa){

        $data = $this->agrupaVendas($vendas, $vendasCaixa);

        return $data;
    }

    private function gerarLucroVendas($vendas){
        $temp = [];
        foreach($vendas as $item){
            $v = [
                'data' => $item->created_at,
                'cliente' => $item->cliente ? $item->cliente->razao_social : '--',
                'numero' => $item->tipo == 'VENDA' ? $item->numero_sequencial : $item->id,
                'total' => $item->tipo == 'VENDA' ? ($item->valor_total - $item->desconto + $item->acrescimo) : $item->valor_total,
                'tipo' => $item->tipo
            ];

            $itensPush = [];
            foreach($item->itens as $i){
                $iTemp = [
                    'produto' => $i->produto->nome,
                    'quantidade' => $i->quantidade,
                    'valor' => $i->valor,
                    'custo' => $i->valor_custo,
                    'lucro' => ($i->valor - $i->valor_custo),
                    'lucro_perc' => $i->valor_custo > 0 ? ((($i->valor_custo - $i->valor)/$i->valor_custo)*100)*-1 : 100
                ];
                array_push($itensPush, $iTemp);
            }
            $v['itens'] = $itensPush;
            array_push($temp, $v);
        }

        usort($temp, function($a, $b) {
            return $a['data'] > $b['data'] ? 1 : 0;
        });
        return $temp;
    }

    private function criarArrayDeDatas($inicio, $fim, $tempVenda, $tempCaixa){
        $diferenca = strtotime($fim) - strtotime($inicio);
        $dias = floor($diferenca / (60 * 60 * 24));
        $global = [];
        $dataAtual = $inicio;
        for($aux = 0; $aux < $dias+1; $aux++){
            // echo \Carbon\Carbon::parse($dataAtual)->format('d/m/Y');


            $rs['data'] = $this->parseViewData($dataAtual);
            if(isset($tempCaixa[\Carbon\Carbon::parse($dataAtual)->format('d/m/Y')])){
                $rs['valor_caixa'] = $tempCaixa[\Carbon\Carbon::parse($dataAtual)->format('d/m/Y')];
            }else{
                $rs['valor_caixa'] = 0;
            }
            if(isset($tempVenda[\Carbon\Carbon::parse($dataAtual)->format('d/m/Y')])){
                $rs['valor'] = $tempVenda[\Carbon\Carbon::parse($dataAtual)->format('d/m/Y')];
            }else{
                $rs['valor'] = 0;
            }

            array_push($global, $rs);


            $dataAtual = date('Y-m-d', strtotime($dataAtual. '+1day'));
        }


        return $global;
    }

    private function parseViewData($date){
        return date('d/m/Y', strtotime(str_replace("/", "-", $date)));
    }

    public function estoqueProduto(Request $request){
        $ordem = $request->ordem;
        $total_resultados = $request->total_resultados ?? 1000;

        $produtos = Produto
            ::select(\DB::raw('produtos.id, produtos.referencia, produtos.str_grade, produtos.valor_compra, produtos.nome, produtos.unidade_venda, estoques.quantidade, produtos.valor_venda, produtos.percentual_lucro'))
            ->leftJoin('estoques', 'produtos.id', '=', 'estoques.produto_id')
            ->limit($total_resultados)
            ->where('produtos.empresa_id', $this->empresa_id)
            ->orderBy('produtos.nome');

        if($request->categoria != 'todos'){
            $produtos->where('produtos.categoria_id', $request->categoria);
        }

        if($request->marca != 'todos'){
            $produtos->where('produtos.marca_id', $request->marca);
        }


        if($ordem == 'qtd'){
            // ->orderBy('total', $ordem)
            $produtos = $produtos->orderBy('estoques.quantidade', 'desc');
        }

        $produtos = $produtos->get();

        foreach($produtos as $p){
            $item = ItemCompra::
            where('produto_id', $p->id)
                ->orderBy('id', 'desc')
                ->first();
            if($item != null){
                $p->data_ultima_compra = \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:m');
            }else{
                $p->data_ultima_compra = '--';
            }
        }

        // echo $produtos;
        // die();
        $categoria = 'Todos';
        if($request->categoria != 'todos'){
            $categoria = Categoria::find($request->categoria)->nome;
        }

        $p = view('relatorios/relatorio_estoque')
            ->with('ordem', $ordem == 'asc' ? 'Menos' : 'Mais')
            ->with('categoria', $categoria)
            ->with('title', 'Relatório de estoque')
            ->with('produtos', $produtos);
        // return $p;
        if($request->excel == 0){
            $domPdf = new Dompdf();
            $domPdf->loadHtml($p);

            $domPdf->setPaper("A4", "landscape");
            $domPdf->render();
            $domPdf->stream("Relatório de estoque.pdf", array("Attachment" => false));
        }else{
            $relatorioEx = new RelatorioExport($produtos);
            return Excel::download($relatorioEx, 'estoque de produtos.xlsx');
        }
    }

    public function comissaoVendas2(Request $request){
        $comissoes = ComissaoVenda::
        where('empresa_id', $this->empresa_id)
            ->get();


        foreach($comissoes as $c){
            $tipo = $c->tipo();

            if($tipo == 'Venda'){
                $venda = Venda::
                where('id', $c->venda_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();

                // echo $c;
                echo "Venda código $venda->id alterada comissao<br>";
                if($venda->vendedor_setado){
                    $percentual_comissao = $venda->vendedor_setado->funcionario->percentual_comissao;
                    $vComissao = $this->calcularComissaoVenda($venda, $percentual_comissao);

                    $c->valor = $vComissao;
                    $c->save();
                }

            }
        }
    }

    private function calcularComissaoVenda($venda, $percentual_comissao){
        $valorRetorno = 0;
        foreach($venda->itens as $i){
            if($i->produto->perc_comissao > 0){
                $valorRetorno += (($i->valor*$i->quantidade) * $i->produto->perc_comissao) / 100;

            }
        }
        if($valorRetorno == 0){
            $valorRetorno = (($venda->valor_total-$venda->desconto+$venda->acrescimo) * $percentual_comissao) / 100;
        }
        return $valorRetorno;
    }


    public function comissaoVendas(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $funcionario = $request->funcionario;
        $produto = $request->produto;

        // $comissoes = ComissaoVenda::
        // where('empresa_id', $this->empresa_id)
        // ->get();

        // echo $comissoes;
        // die;

        $comissoes = ComissaoVenda
            ::select(\DB::raw('comissao_vendas.created_at, comissao_vendas.venda_id, comissao_vendas.valor, funcionarios.nome as funcionario, comissao_vendas.tabela'))
            ->where('comissao_vendas.empresa_id', $this->empresa_id)
            ->join('funcionarios', 'funcionarios.id', '=', 'comissao_vendas.funcionario_id');
        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);

            $comissoes->whereBetween('comissao_vendas.created_at', [$data_inicial,
                $data_final]);
        }

        // $c = ComissaoVenda::where('venda_id', $v->id)
        //           ->where('empresa_id', $this->empresa_id)
        //           ->where('tabela', 'venda_caixas')
        //           ->first();

        if($funcionario != 'null'){
            $comissoes = $comissoes->where('funcionario_id', $funcionario);
            $funcionario = Funcionario::find($funcionario)->nome;
        }

        $comissoes = $comissoes->get();
        // echo sizeof($comissoes);
        // die;
        $temp = [];
        foreach($comissoes as $c){

            $c->valor_total_venda = $this->getValorDaVenda($c);
            $c->tipo = $c->tipo();

            $cancelada = false;

            if($c->tipo == 'Venda'){
                $venda = Venda::
                where('id', $c->venda_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();
            }elseif($c->tipo == 'Balcão'){
                $venda = VendaBalcao::
                where('id', $c->venda_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();
            }else{
                $venda = VendaCaixa::
                where('id', $c->venda_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();
            }

            if($venda->estado == 'CANCELADO'){
                $cancelada = true;
            }

            if(!$cancelada){
                if($produto != 'null'){
                    $res = $this->getVenda($c, $produto);
                    if($res){
                        array_push($temp, $c);
                    }
                }else{
                    array_push($temp, $c);
                }
            }
        }

        if($produto != 'null'){
            $produto = Produto::find($produto)->nome;
        }

        usort($temp, function($a, $b){
            return $a['created_at'] > $b['created_at'] ? 1 : 0;
        });

        $comissoes = $temp;

        $p = view('relatorios/relatorio_comissao')
            ->with('funcionario', $funcionario)
            ->with('produto', $produto)
            ->with('title', 'Relatório de comissão')
            ->with('data_inicial', $request->data_inicial)
            ->with('data_final', $request->data_final)
            ->with('comissoes', $comissoes);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de comissão.pdf", array("Attachment" => false));

        // ->join('vendas', 'vendas.id', '=', 'comissao_vendas.venda_id');

    }

    private function getValorDaVenda($comissao){
        $tipo = $comissao->tipo();
        $venda = null;
        $total = 0;
        // echo "$tipo = $comissao <br>";
        if($tipo == 'PDV'){
            $venda = VendaCaixa::
            where('id', $comissao->venda_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            $total = $venda->valor_total;
        }elseif($tipo == 'Balcão'){
            $venda = VendaBalcao::
            where('id', $comissao->venda_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            $total = $venda->valor_total;
        }else{
            $venda = Venda::
            where('id', $comissao->venda_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();

            $total = $venda->valor_total - $venda->desconto + $venda->acrescimo;
        }
        if($venda == null) return 0;
        return $total;
    }

    private function getVenda($comissao, $produto_id){
        $tipo = $comissao->tipo();
        if($tipo == 'PDV'){
            $venda = VendaCaixa::find($comissao->venda_id);
            foreach($venda->itens as $i){
                if($i->produto_id == $produto_id){
                    return true;
                }
            }
            return false;
        }else{

            $venda = Venda::find($comissao->venda_id);
            foreach($venda->itens as $i){
                if($i->produto_id == $produto_id){
                    return true;
                }
            }
            return false;
        }
    }

    public function tiposPagamento(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;

        if(!$data_inicial || !$data_final){
            session()->flash("mensagem_erro", "Informe a data inicial e final!");
            return redirect('/relatorios');
        }

        $vendasPdv = VendaCaixa
            ::whereBetween('created_at', [
                $this->parseDate($data_inicial),
                $this->parseDate($data_final, true)
            ])
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $vendas = Venda
            ::whereBetween('created_at', [
                $this->parseDate($data_inicial),
                $this->parseDate($data_final, true)
            ])
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $vendas = $this->agrupaVendas($vendas, $vendasPdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        $p = view('relatorios/tipos_pagamento')
            ->with('somaTiposPagamento', $somaTiposPagamento)
            ->with('data_inicial', $request->data_inicial)
            ->with('title', 'Relatório tipos de pagamento')
            ->with('data_final', $request->data_final);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório tipos de pagamento.pdf", array("Attachment" => false));
    }

    public function cadastroProdutos(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $status = $request->status;

        if(!$data_inicial || !$data_final){
            session()->flash("mensagem_erro", "Informe a data inicial e final!");
            return redirect('/relatorios');
        }

        $produtos = Produto
            ::whereBetween('created_at', [
                $this->parseDate($data_inicial),
                $this->parseDate($data_final, true)
            ])
            ->where('empresa_id', $this->empresa_id)
            ->where('inativo', $status)
            ->get();

        $p = view('relatorios/cadastro_produtos')
            ->with('produtos', $produtos)
            ->with('data_inicial', $request->data_inicial)
            ->with('title', 'Relatório cadastro de produtos')
            ->with('data_final', $request->data_final);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório cadastro de produtos.pdf", array("Attachment" => false));
    }

    private function agrupaVendas($vendas, $vendasPdv){
        $temp = [];
        foreach($vendas as $v){
            $v->tipo = 'VENDA';
            array_push($temp, $v);
        }

        foreach($vendasPdv as $v){
            $v->tipo = 'PDV';
            array_push($temp, $v);
        }

        return $temp;
    }

    private function preparaTipos(){
        $temp = [];
        foreach(VendaCaixa::tiposPagamento() as $key => $tp){
            $temp[$key] = 0;
        }
        return $temp;
    }

    private function somaTiposPagamento($vendas){
        $tipos = $this->preparaTipos();

        foreach($vendas as $v){

            if(isset($tipos[$v->tipo_pagamento])){
                // $tipos[$v->tipo_pagamento] += $v->valor_total;

                if($v->tipo_pagamento != 99){
                    $tipos[$v->tipo_pagamento] += $v->valor_total;
                }else{
                    if($v->valor_pagamento_1 > 0){
                        $tipos[$v->tipo_pagamento_1] += $v->valor_pagamento_1;
                    }
                    if($v->valor_pagamento_2 > 0){
                        $tipos[$v->tipo_pagamento_2] += $v->valor_pagamento_2;
                    }
                    if($v->valor_pagamento_3 > 0){
                        $tipos[$v->tipo_pagamento_3] += $v->valor_pagamento_3;
                    }
                }
            }
        }
        return $tipos;
    }

    public function vendaDeProdutos(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $ordem = $request->ordem;
        $natureza_id = $request->natureza_id;
        $vendedor = $request->vendedor;

        if(!$data_final || !$data_final){
            session()->flash("mensagem_erro", "Informe a data inicial e final!");
            return redirect('/relatorios');
        }

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $diferenca = strtotime($data_final) - strtotime($data_inicial);
        $dias = floor($diferenca / (60 * 60 * 24));

        $dataAtual = $data_inicial;
        $global = [];
        for($aux = 0; $aux < $dias; $aux++){

            $itens = ItemVenda::
            select(\DB::raw('sum(quantidade*valor) as subtotal, sum(quantidade*valor_custo) as subtotalcusto, sum(quantidade) as soma_quantidade, produto_id, avg(valor) as media, valor, valor_custo'))
                ->whereBetween('item_vendas.created_at',
                    [
                        $dataAtual . " 00:00:00",
                        $dataAtual . " 23:59:59"
                    ]
                )
                ->join('produtos', 'produtos.id', '=', 'item_vendas.produto_id')
                ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
                ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
                ->groupBy('item_vendas.produto_id')
                ->where('produtos.empresa_id', $this->empresa_id);

            if($request->produto_id != 'null'){
                $itens->where('produtos.id', $request->produto_id);
            }
            if($request->categoria != 'todos'){
                $itens->where('categorias.id', $request->categoria);
            }

            if($request->marca != 'todos'){
                $itens->where('produtos.marca_id', $request->marca);
            }

            if($vendedor){
                $itens->where('vendas.vendedor_id', $vendedor);
            }

            if($natureza_id){
                $itens->where('vendas.natureza_id', $natureza_id);
            }

            $itens = $itens->get();


            $itensCaixa = ItemVendaCaixa::
            select(\DB::raw('sum(quantidade*valor) as subtotal, sum(quantidade*valor_custo) as subtotalcusto, sum(quantidade) as soma_quantidade, produto_id, avg(valor) as media, valor, valor_custo'))
                ->whereBetween('item_venda_caixas.created_at',
                    [
                        $dataAtual . " 00:00:00",
                        $dataAtual . " 23:59:59"
                    ]
                )
                ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
                ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
                ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
                ->where('produtos.empresa_id', $this->empresa_id)
                ->where('venda_caixas.rascunho', 0)
                ->where('venda_caixas.consignado', 0)
                ->groupBy('item_venda_caixas.produto_id');

            if($request->produto_id != 'null'){
                $itensCaixa->where('item_venda_caixas.produto_id', $request->produto_id);
            }

            if($request->categoria != 'todos'){
                $itensCaixa->where('categorias.id', $request->categoria);
            }

            if($request->marca != 'todos'){
                $itensCaixa->where('produtos.marca_id', $request->marca);
            }

            if($vendedor){
                $itensCaixa->where('venda_caixas.usuario_id', $vendedor);
            }

            $itensCaixa = $itensCaixa->get();

            $todosItens = $this->uneArrayItens($itens, $itensCaixa, $request->ordem);

            $temp = [
                'data' => $dataAtual,
                'itens' => $todosItens,
            ];
            array_push($global, $temp);
            $dataAtual = date('Y-m-d', strtotime($dataAtual. '+1day'));
        }

        // dd($global);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        $d1 = str_replace("/", "-", $request->data_inicial);
        $d2 = str_replace("/", "-", $request->data_final);
        $p = view('relatorios/venda_por_produtos')
            ->with('itens', $global)
            ->with('config', $config)
            ->with('title', 'Relatório de venda por produtos')
            ->with('data_inicial', $d1)
            ->with('data_final', $d2);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de venda por produtos.pdf", array("Attachment" => false));

    }

    public function vendaDeProdutos2(Request $request){

        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $ordem = $request->ordem;
        $natureza_id = $request->natureza_id;
        $produtos = $request->produtos ?? [];
        $vendedores = $request->vendedores;

        if(!$data_final || !$data_final){
            session()->flash("mensagem_erro", "Informe a data inicial e final!");
            return redirect('/relatorios');
        }

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);
        }

        $diferenca = strtotime($data_final) - strtotime($data_inicial);
        $dias = floor($diferenca / (60 * 60 * 24));

        $dataAtual = $data_inicial;
        $global = [];
        for($aux = 0; $aux < $dias; $aux++){

            $itens = ItemVenda::
            select(\DB::raw('sum(quantidade*valor) as subtotal, sum(quantidade*valor_custo) as subtotalcusto, sum(quantidade) as soma_quantidade, produto_id, avg(valor) as media, valor, valor_custo, funcionarios.nome as nome_vendedor, vendas.id as venda_id'))
                ->whereBetween('item_vendas.created_at',
                    [
                        $dataAtual . " 00:00:00",
                        $dataAtual . " 23:59:59"
                    ]
                )
                ->join('produtos', 'produtos.id', '=', 'item_vendas.produto_id')
                ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
                ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
                ->join('usuarios', 'vendas.vendedor_id', '=', 'usuarios.id')
                ->join('funcionarios', 'funcionarios.usuario_id', '=', 'usuarios.id')

                ->groupBy('item_vendas.produto_id')
                ->whereIn('vendas.vendedor_id', $vendedores)
                ->where('produtos.empresa_id', $this->empresa_id);

            if(sizeof($produtos) > 0){
                $itens->whereIn('produtos.id', $produtos);
            }

            // if($request->categoria != 'todos'){
            // 	$itens->where('categorias.id', $request->categoria);
            // }

            if($natureza_id){
                $itens->where('vendas.natureza_id', $natureza_id);
            }

            $itens = $itens->get();


            $itensCaixa = ItemVendaCaixa::
            select(\DB::raw('sum(quantidade*valor) as subtotal, sum(quantidade*valor_custo) as subtotalcusto, sum(quantidade) as soma_quantidade, produto_id, avg(valor) as media, valor, valor_custo, funcionarios.nome as nome_vendedor, venda_caixas.id as venda_id'))
                ->whereBetween('item_venda_caixas.created_at',
                    [
                        $dataAtual . " 00:00:00",
                        $dataAtual . " 23:59:59"
                    ]
                )
                ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
                ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
                ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
                ->join('usuarios', 'venda_caixas.usuario_id', '=', 'usuarios.id')
                ->join('funcionarios', 'funcionarios.usuario_id', '=', 'usuarios.id')
                ->where('produtos.empresa_id', $this->empresa_id)
                ->where('venda_caixas.rascunho', 0)
                ->whereIn('venda_caixas.usuario_id', $vendedores)
                ->where('venda_caixas.consignado', 0)
                ->groupBy('item_venda_caixas.produto_id');

            if(sizeof($produtos) > 0){
                $itensCaixa->whereIn('item_venda_caixas.produto_id', $produtos);
            }

            // if($request->categoria != 'todos'){
            // 	$itensCaixa->where('categorias.id', $request->categoria);
            // }
            $itensCaixa = $itensCaixa->get();

            $todosItens = $this->uneArrayItens($itens, $itensCaixa, $request->ordem);

            $temp = [
                'data' => $dataAtual,
                'itens' => $todosItens,
            ];
            array_push($global, $temp);
            $dataAtual = date('Y-m-d', strtotime($dataAtual. '+1day'));
        }

        $vendedoresSoma = [];
        $vendedoresTotal = [];
        foreach($vendedores as $i){
            $usuario = \App\Models\Usuario::findOrFail($i);
            $vendedoresSoma[$usuario->funcionario->nome] = 0;
            $vendedoresTotal[$usuario->funcionario->nome] = 0;
        }
        // dd($global);
        foreach($global as $todosItens){
            if(isset($todosItens['itens'])){
                foreach($todosItens['itens'] as $t){
                    if(isset($vendedoresSoma[$t['nome_vendedor']])){
                        $vendedoresSoma[$t['nome_vendedor']] += $t['comissao'];
                        $vendedoresTotal[$t['nome_vendedor']] += $t['subtotal'];
                    }
                }
            }
        }

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        $d1 = str_replace("/", "-", $request->data_inicial);
        $d2 = str_replace("/", "-", $request->data_final);

        $p = view('relatorios/venda_por_produtos_vendedores')
            ->with('itens', $global)
            ->with('config', $config)
            ->with('vendedoresSoma', $vendedoresSoma)
            ->with('vendedoresTotal', $vendedoresTotal)
            ->with('title', 'Relatório de venda por produtos comissão/vendedor')
            ->with('data_inicial', $d1)
            ->with('data_final', $d2);

        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de venda por produtos.pdf", array("Attachment" => false));

    }


    private function uneArrayItens($itens, $itensCaixa, $ordem){
        $data = [];
        $adicionados = [];
        foreach($itens as $i){
            $comissao = ComissaoVenda::where('tabela', 'vendas')
                ->where('venda_id', $i->venda_id)->first();
            $temp = [
                'quantidade' => $i->soma_quantidade,
                'subtotal' => $i->subtotal,
                'subtotalcusto' => $i->subtotalcusto,
                'valor' => $i->valor,
                'valor_custo' => $i->valor_custo,
                'media' => $i->media,
                'produto' => $i->produto,
                'nome_vendedor' => $i->nome_vendedor,
                'comissao' => $comissao != null ? $comissao->valor : 0,
            ];
            array_push($data, $temp);
            // array_push($adicionados, $i->produto->id);
        }

        // print_r($data[0]['produto']);
        foreach($itensCaixa as $i){
            $indiceAdicionado = $this->jaAdicionadoProduto($data, $i->produto->id);
            if($indiceAdicionado == -1){
                $comissao = ComissaoVenda::where('tabela', 'venda_caixas')
                    ->where('venda_id', $i->venda_id)->first();
                $temp = [
                    'quantidade' => $i->soma_quantidade,
                    'subtotal' => $i->subtotal,
                    'subtotalcusto' => $i->subtotalcusto,
                    'valor' => $i->valor,
                    'valor_custo' => $i->valor_custo,
                    'media' => $i->media,
                    'produto' => $i->produto,
                    'nome_vendedor' => $i->nome_vendedor,
                    'comissao' => $comissao != null ? $comissao->valor : 0,
                ];
                array_push($data, $temp);
            }else{
                $data[$indiceAdicionado]['quantidade'] += $i->soma_quantidade;
                $data[$indiceAdicionado]['subtotal'] += $i->subtotal;
                $data[$indiceAdicionado]['media'] = ($data[$indiceAdicionado]['media'] + $i->media) / 2;
            }
        }

        usort($data, function($a, $b) use ($ordem){
            if($ordem == 'asc') return $a['quantidade'] > $b['quantidade'] ? 1 : 0;
            else if($ordem == 'desc') return $a['quantidade'] < $b['quantidade'] ? 1 : 0;
            else return $a['produto']->nome > $b['produto']->nome ? 1 : 0;
        });
        return $data;
    }



    private function jaAdicionadoProduto($array, $produtoId){
        for($i=0; $i<sizeof($array); $i++){
            if($array[$i]['produto']->id == $produtoId){
                return $i;
            }
        }
        return -1;
    }

    public function listaPreco(Request $request){
        $lista = ListaPreco::findOrFail($request->lista_id);

        $d1 = str_replace("/", "-", $request->data);
        $p = view('relatorios/lista_preco')
            ->with('lista', $lista)
            ->with('title', 'Relatório de lista de preço')
            ->with('data', $d1);

        // return $p;

        if($request->excel == 0){
            $domPdf = new Dompdf();
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("Relatório de lista de preço.pdf", array("Attachment" => false));
        }else{
            $relatorioEx = new RelatorioListaPrecoExport($lista->itens);
            return Excel::download($relatorioEx, 'relatorio lista de preço.xlsx');
        }
    }

    public function fiscal(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $cliente_id = $request->cliente_id;
        $estado = $request->estado;
        $tipo = $request->tipo;
        $cfop = $request->cfop;
        $natureza_id = $request->natureza_id;
        $filial_id = isset($request->filial_id) ? $request->filial_id : null;

        if(!$data_inicial || !$data_final){
            session()->flash("mensagem_erro", "Informe um interválo de datas");
            return redirect('/relatorios');
        }

        $nfes = [];
        $nfces = [];
        $ctes = [];
        $mdfes = [];
        $remessas = [];
        $devolucoes = [];

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        if($tipo == 'todos' || $tipo == 'nfe'){
            $nfes = Venda::
            whereBetween('data_emissao', [
                $this->parseDate($request->data_inicial),
                $this->parseDate($request->data_final, true)])
                ->where('NfNumero', '>', 0)
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('vendas.filial_id', $local);
                })
                ->where('vendas.empresa_id', $this->empresa_id);

            if($cliente_id != 'null'){
                $nfes->where('cliente_id', $cliente_id);
            }

            if($natureza_id){
                $nfes->where('natureza_id', $natureza_id);
            }

            if($estado != 'todos'){
                if($estado ==  'aprovados'){
                    $nfes->where('estado', 'APROVADO');
                }else{
                    $nfes->where('estado', 'CANCELADO');
                }
            }

            if($cfop){
                $nfes->join('item_vendas', 'item_vendas.venda_id', '=', 'vendas.id')
                    ->where('item_vendas.cfop', $cfop);
            }
            $nfes = $nfes->get();

            $remessas = RemessaNfe::
            whereBetween('data_emissao', [
                $this->parseDate($request->data_inicial),
                $this->parseDate($request->data_final, true)])
                ->where('numero_nfe', '>', 0)
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('remessa_nves.filial_id', $local);
                })
                ->where('remessa_nves.empresa_id', $this->empresa_id);

            if($cliente_id != 'null'){
                $remessas->where('cliente_id', $cliente_id);
            }

            if($natureza_id){
                $remessas->where('natureza_id', $natureza_id);
            }

            if($estado != 'todos'){
                if($estado ==  'aprovados'){
                    $remessas->where('estado', 'aprovado');
                }else{
                    $remessas->where('estado', 'cancelado');
                }
            }

            if($cfop){
                $remessas->join('item_remessa_nves', 'item_remessa_nves.venda_id', '=', 'remessa_nves.id')
                    ->where('item_remessa_nves.cfop', $cfop);
            }
            $remessas = $remessas->get();

            $devolucoes = Devolucao::
            whereBetween('created_at', [
                $this->parseDate($request->data_inicial),
                $this->parseDate($request->data_final, true)])
                ->where('numero_gerado', '>', 0)
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('devolucaos.filial_id', $local);
                })
                ->where('devolucaos.empresa_id', $this->empresa_id);

            if($natureza_id){
                $devolucoes->where('natureza_id', $natureza_id);
            }

            if($estado != 'todos'){
                if($estado ==  'aprovados'){
                    $devolucoes->where('estado', 1);
                }else{
                    $devolucoes->where('estado', 3);
                }
            }

            if($cfop){
                $devolucoes->join('item_devolucaos', 'item_devolucaos.venda_id', '=', 'devolucaos.id')
                    ->where('item_devolucaos.cfop', $cfop);
            }
            $devolucoes = $devolucoes->get();

        }
        if($tipo == 'todos' || $tipo == 'nfce'){
            $nfces = VendaCaixa::
            select('venda_caixas.*')
                ->whereBetween('venda_caixas.created_at', [
                    $this->parseDate($request->data_inicial),
                    $this->parseDate($request->data_final, true)])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('venda_caixas.filial_id', $local);
                })
                ->where('venda_caixas.empresa_id', $this->empresa_id);

            if($cliente_id != 'null'){
                $nfces->where('cliente_id', $cliente_id);
            }

            if($estado != 'todos'){
                if($estado ==  'aprovados'){
                    $nfces->where('estado', 'APROVADO');
                }else{
                    $nfces->where('estado', 'CANCELADO');
                }
            }

            if($cfop){
                $nfces->join('item_venda_caixas', 'item_venda_caixas.venda_caixa_id', '=', 'venda_caixas.id')
                    ->where('item_venda_caixas.cfop', $cfop);
            }

            if($natureza_id){
                $nfces->join('config_notas', 'config_notas.empresa_id', '=', 'venda_caixas.empresa_id')
                    ->where('config_notas.nat_op_padrao', $natureza_id);
            }
            $nfces = $nfces->get();
        }
        if($tipo == 'todos' || $tipo == 'cte'){
            $ctes = Cte::
            whereBetween('created_at', [
                $this->parseDate($request->data_inicial),
                $this->parseDate($request->data_final, true)])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('filial_id', $local);
                })
                ->where('empresa_id', $this->empresa_id);

            if($cliente_id != 'null'){
                $ctes->where('destinatario_id', $cliente_id);
            }

            if($estado != 'todos'){
                if($estado ==  'aprovados'){
                    $ctes->where('estado', 'APROVADO');
                }else{
                    $ctes->where('estado', 'CANCELADO');
                }
            }

            if($natureza_id){
                $ctes->where('natureza_id', $natureza_id);
            }
            $ctes = $ctes->get();
        }
        if($tipo == 'todos' || $tipo == 'mdfe'){
            $mdfes = Mdfe::
            whereBetween('created_at', [
                $this->parseDate($request->data_inicial),
                $this->parseDate($request->data_final, true)])
                ->when($filial_id, function($q) use ($filial_id){
                    $local = $filial_id == -1 ? null : $filial_id;
                    return $q->where('filial_id', $local);
                })
                ->where('empresa_id', $this->empresa_id);

            if($estado != 'todos'){
                if($estado ==  'aprovados'){
                    $mdfes->where('estado', 'APROVADO');
                }else{
                    $mdfes->where('estado', 'CANCELADO');
                }
            }
            $mdfes = $mdfes->get();
        }

        $data = [];
        foreach($nfes as $n){
            $temp = [
                'valor_total' => $n->valor_total - $n->desconto + $n->acrescimo,
                'data' => \Carbon\Carbon::parse($n->data_emissao)->format('d/m/y H:i'),
                'cliente' => $n->cliente->razao_social,
                'chave' => $n->chave,
                'estado' => $n->estado,
                'numero' => $n->NfNumero,
                'tipo' => 'nfe'
            ];
            array_push($data, $temp);
        }

        foreach($remessas as $n){
            $temp = [
                'valor_total' => $n->valor_total - $n->desconto + $n->acrescimo,
                'data' => \Carbon\Carbon::parse($n->data_emissao)->format('d/m/y H:i'),
                'cliente' => $n->cliente->razao_social,
                'chave' => $n->chave,
                'estado' => strtoupper($n->estado),
                'numero' => $n->numero_nfe,
                'tipo' => 'nfe'
            ];
            array_push($data, $temp);
        }

        foreach($devolucoes as $n){
            $temp = [
                'valor_total' => $n->valor_devolvido,
                'data' => \Carbon\Carbon::parse($n->created_at)->format('d/m/y H:i'),
                'cliente' => $n->fornecedor->razao_social,
                'chave' => $n->chave_gerada,
                'estado' => $n->estado == 1 ? "APROVADO" : "CANCELADO",
                'numero' => $n->numero_gerado,
                'tipo' => 'devolucao'
            ];
            array_push($data, $temp);
        }
        foreach($nfces as $n){
            $temp = [
                'valor_total' => $n->valor_total,
                'data' => \Carbon\Carbon::parse($n->created_at)->format('d/m/y H:i'),
                'cliente' => $n->cliente ? $n->cliente->razao_social : '',
                'chave' => $n->chave,
                'estado' => $n->estado,
                'numero' => $n->NFcNumero,
                'tipo' => 'nfce'
            ];
            array_push($data, $temp);
        }
        foreach($ctes as $n){
            $temp = [
                'valor_total' => $n->valor_receber,
                'data' => \Carbon\Carbon::parse($n->created_at)->format('d/m/y H:i'),
                'cliente' => $n->destinatario->razao_social,
                'chave' => $n->chave,
                'estado' => $n->estado,
                'numero' => $n->cte_numero,
                'tipo' => 'cte'
            ];
            array_push($data, $temp);
        }
        foreach($mdfes as $n){
            $temp = [
                'valor_total' => $n->valor_carga,
                'data' => \Carbon\Carbon::parse($n->created_at)->format('d/m/y H:i'),
                'cliente' => '--',
                'chave' => $n->chave,
                'estado' => $n->estado,
                'numero' => $n->mdfe_numero,
                'tipo' => 'mdfe'
            ];
            array_push($data, $temp);
        }

        $d1 = str_replace("/", "-", $request->data_inicial);
        $d2 = str_replace("/", "-", $request->data_final);

        $cliente = null;
        if($cliente_id != 'null'){
            $cliente = Cliente::findOrFail($cliente_id);
        }

        if(sizeof($data) == 0){
            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/fiscal')
            ->with('data', $data)
            ->with('cliente', $cliente)
            ->with('estado', $estado)
            ->with('tipo', $tipo)
            ->with('d1', $d1)
            ->with('title', 'Relatório Fiscal')
            ->with('d2', $d2);

        // return $p;

        $domPdf = new Dompdf();
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório fiscal.pdf", array("Attachment" => false));
    }

    public function porCfop(Request $request){
        $produtos1 = Produto::
        where('CFOP_saida_estadual', $request->cfop)
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $produtos2 = Produto::
        where('CFOP_saida_inter_estadual', $request->cfop)
            ->where('empresa_id', $this->empresa_id)
            ->get();

        $produtos = [];

        foreach($produtos1 as $p){
            array_push($produtos, $p);
        }

        foreach($produtos2 as $p){
            array_push($produtos, $p);
        }

        $p = view('relatorios/por_cfop')
            ->with('produtos', $produtos)
            ->with('title', 'Relatório CFOP')
            ->with('cfop', $request->cfop);

        // return $p;

        $domPdf = new Dompdf();
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório CFOP.pdf", array("Attachment" => false));

    }

    public function boletos(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $status = $request->status;

        $contas = ContaReceber::
        orderBy('data_vencimento', 'desc')
            ->join('boletos', 'boletos.conta_id', '=', 'conta_recebers.id')
            ->select('conta_recebers.*');

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);

            $contas->whereBetween('conta_recebers.data_vencimento',
                [$data_inicial, $data_final]);
        }

        if($status != ""){
            $contas->where('conta_recebers.status', $status == "recebido" ? 1 : 0);
        }
        $contas = $contas->get();

        $p = view('relatorios/boletos')
            ->with('contas', $contas)
            ->with('data_inicial', $data_inicial)
            ->with('data_final', $data_final)
            ->with('status', $status)
            ->with('title', 'Relatório de Boletos');

        // return $p;

        $domPdf = new Dompdf();
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório Boletos.pdf", array("Attachment" => false));

    }

    public function comissaoAssessor(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $status = $request->status;
        $assessor_id = $request->assessor_id;

        $data = ComissaoAssessor::
        orderBy('created_at', 'desc');

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);

            $data->whereBetween('created_at',
                [$data_inicial, $data_final]);
        }

        if($status != ""){
            $data->where('status', $status == "pago" ? 1 : 0);
        }

        $assessor = null;
        if($assessor_id){
            $data->where('assessor_id', $assessor_id);
            $assessor = Acessor::findOrFail($assessor_id);

        }else{

        }

        $data = $data->get();

        if(sizeof($data) == 0){
            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/comissao_assessor')
            ->with('data', $data)
            ->with('data_inicial', $data_inicial)
            ->with('data_final', $data_final)
            ->with('status', $status)
            ->with('assessor', $assessor)
            ->with('title', 'Relatório de Comissão Assessor');

        // return $p;

        $domPdf = new Dompdf();
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de Comissão Assessor.pdf", array("Attachment" => false));

    }

    public function cte(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $status = $request->status;
        $remetente_id = $request->remetente_id;

        $data = Cte::
        orderBy('created_at', 'desc');

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final, true);

            $data->whereBetween('created_at',
                [$data_inicial, $data_final]);
        }

        if($status != ""){
            $data->where('status', $status == "pago" ? 1 : 0);
        }

        if($remetente_id){
            $data->where('remetente_id', $remetente_id);
        }

        // $data->where('estado', 'APROVADO');

        $data = $data->get();

        if(sizeof($data) == 0){
            session()->flash("mensagem_erro", "Relatório sem registro!");
            return redirect('/relatorios');
        }

        $p = view('relatorios/cte')
            ->with('data', $data)
            ->with('data_inicial', $data_inicial)
            ->with('data_final', $data_final)
            ->with('status', $status)
            ->with('title', 'Relatório de CTe');

        // return $p;

        $domPdf = new Dompdf();
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de Cte.pdf", array("Attachment" => false));

    }

    public function cliente(Request $request){
        $grupo_id = $request->grupo_id;
        $assessor_id = $request->assessor_id;
        $status = $request->status;
        $excel = $request->excel;
        $limite = $request->limite;
        if(!$limite || $limite > 500){
            $limite = 500;
        }
        $data = Cliente::
        where('empresa_id', $request->empresa_id)
            ->when($grupo_id != '', function($q) use ($grupo_id){
                return $q->where('grupo_id', $grupo_id);
            })
            ->when($assessor_id != '', function($q) use ($assessor_id){
                return $q->where('acessor_id', $assessor_id);
            })
            ->when($status != '', function($q) use ($status){
                return $q->where('inativo', $status == 'ativo' ? 0 : 1);
            })

            ->when($excel == 0, function($q) use ($limite){
                return $q->limit($limite);
            })
            ->orderBy('razao_social', 'asc')
            ->get();

        $p = view('relatorios/clientes')
            ->with('data', $data)
            ->with('title', 'Relatório de clientes');

        // return $p;

        if($excel == 0){
            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4", "landscape");
            $domPdf->render();
            $domPdf->stream("Relatório de clientes.pdf", array("Attachment" => false));
        }else{
            $relatorioEx = new ClienteExport($data);
            return Excel::download($relatorioEx, 'clientes.xlsx');
        }
    }

    public function locacao(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $cliente_id = $request->cliente_id;
        $produto_id = $request->produto_id;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }


        $data = Locacao::
        where('locacaos.empresa_id', $request->empresa_id)
            ->select('locacaos.*')
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('locacaos.inicio', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('locacaos.fim', '<=', $data_final);
            })
            ->when($cliente_id, function($q) use ($cliente_id){
                return $q->where('locacaos.cliente_id', $cliente_id);
            })
            ->when($produto_id, function($q) use ($produto_id){
                return $q->join('item_locacaos', 'item_locacaos.locacao_id', '=', 'locacaos.id')
                    ->where('item_locacaos.produto_id', $produto_id);
            })
            ->orderBy('locacaos.id', 'desc')
            ->get();

        $p = view('relatorios/locacao')
            ->with('data', $data)
            ->with('title', 'Relatório de Locaçao');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de locaçao.pdf", array("Attachment" => false));

    }

    public function perca(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $usuario_id = $request->usuario_id;
        $produto_id = $request->produto_id;
        $motivo = $request->motivo;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $data = AlteracaoEstoque::
        where('alteracao_estoques.empresa_id', $request->empresa_id)
            ->select('alteracao_estoques.*')
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('alteracao_estoques.created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('alteracao_estoques.created_at', '<=', $data_final);
            })
            ->when($usuario_id, function($q) use ($usuario_id){
                return $q->where('alteracao_estoques.usuario_id', $usuario_id);
            })
            ->when($motivo, function($q) use ($motivo){
                return $q->where('alteracao_estoques.motivo', $motivo);
            })
            ->when($produto_id, function($q) use ($produto_id){
                return $q->where('alteracao_estoques.produto_id', $produto_id);
            })
            ->where('tipo', 'reducao')
            ->orderBy('alteracao_estoques.id', 'desc')
            ->get();

        $p = view('relatorios/perca')
            ->with('data', $data)
            ->with('title', 'Relatório de Perca');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de locaçao.pdf", array("Attachment" => false));

    }

    public function sangrias(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $usuario_id = $request->usuario_id;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $data = SangriaCaixa::
        where('empresa_id', $request->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('sangria_caixas.created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('sangria_caixas.created_at', '<=', $data_final);
            })
            ->when($usuario_id, function($q) use ($usuario_id){
                return $q->where('usuario_id', $usuario_id);
            })
            ->get();

        $p = view('relatorios/sangrias')
            ->with('data', $data)
            ->with('title', 'Relatório de Sangrias');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de sangrias.pdf", array("Attachment" => false));

    }

    public function taxas(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }
        $taxas = TaxaPagamento::where('empresa_id', $this->empresa_id)->get();
        $tipos = $taxas->pluck('tipo_pagamento')->toArray();

        $vendas = Venda::where('empresa_id', $this->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('created_at', '<=', $data_final);
            })
            ->get();

        $data = [];

        foreach($vendas as $v){
            $bandeira_cartao = $v->bandeira_cartao;
            if(sizeof($v->duplicatas) > 1){
                foreach($v->duplicatas as $ft){
                    $fp = Venda::getTipoPagamentoNFe($ft->tipo_pagamento);

                    if(in_array($fp, $tipos)){
                        $taxa = TaxaPagamento::where('empresa_id', $this->empresa_id)
                            ->where('tipo_pagamento', $fp)
                            ->when($bandeira_cartao != '' && $bandeira_cartao != '99', function($q) use ($bandeira_cartao){
                                return $q->where('bandeira_cartao', $bandeira_cartao);
                            })
                            ->first();

                        if($taxa != null){
                            $item = [
                                'cliente' => $v->cliente ? ($v->cliente->razao_social . " " . $v->cliente->cpf_cnpj) :
                                    'Consumidor final',
                                'total' => $ft->valor_integral,
                                'taxa_perc' => $taxa ? $taxa->taxa : 0,
                                'taxa' => $taxa ? ($ft->valor_integral*($taxa->taxa/100)) : 0,
                                'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                                'tipo_pagamento' => Venda::getTipo($fp),
                                'venda_id' => $v->id,
                                'tipo' => 'PEDIDO'
                            ];
                            array_push($data, $item);
                        }
                    }
                }
            }else{
                if(in_array($v->tipo_pagamento, $tipos)){
                    $total = $v->valor_total-$v->desconto+$v->acrescimo;

                    $taxa = TaxaPagamento::where('empresa_id', $this->empresa_id)
                        ->when($bandeira_cartao != '' && $bandeira_cartao != '99', function($q) use ($bandeira_cartao){
                            return $q->where('bandeira_cartao', $bandeira_cartao);
                        })
                        ->where('tipo_pagamento', $v->tipo_pagamento)->first();

                    if($taxa != null){
                        $item = [
                            'cliente' => $v->cliente->razao_social,
                            'total' => $total,
                            'taxa_perc' => $taxa->taxa,
                            'taxa' => $taxa ? ($total*($taxa->taxa/100)) : 0,
                            'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                            'tipo_pagamento' => Venda::getTipo($v->tipo_pagamento),
                            'venda_id' => $v->id,
                            'tipo' => 'PEDIDO'
                        ];
                        array_push($data, $item);
                    }else{
                        echo $bandeira_cartao;
                        die;
                    }
                }
            }
        }

        $vendasCaixa = VendaCaixa::where('empresa_id', $this->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('created_at', '<=', $data_final);
            })
            ->get();

        foreach($vendasCaixa as $v){
            $bandeira_cartao = $v->bandeira_cartao;
            if(sizeof($v->fatura) > 1){
                foreach($v->fatura as $ft){
                    if(in_array($ft->forma_pagamento, $tipos)){

                        $taxa = TaxaPagamento::where('empresa_id', $this->empresa_id)
                            ->when($bandeira_cartao != '' && $bandeira_cartao != '99', function($q) use ($bandeira_cartao){
                                return $q->where('bandeira_cartao', $bandeira_cartao);
                            })
                            ->where('tipo_pagamento', $ft->forma_pagamento)->first();

                        if($taxa != null){
                            $item = [
                                'cliente' => $v->cliente ? ($v->cliente->razao_social . " " . $v->cliente->cpf_cnpj) :
                                    'Consumidor final',
                                'total' => $ft->valor,
                                'taxa_perc' => $taxa->taxa,
                                'taxa' => $taxa ? ($ft->valor*($taxa->taxa/100)) : 0,
                                'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                                'tipo_pagamento' => Venda::getTipo($ft->forma_pagamento),
                                'venda_id' => $v->id,
                                'tipo' => 'PDV'
                            ];
                            array_push($data, $item);
                        }
                    }
                }
            }else{
                if(in_array($v->tipo_pagamento, $tipos)){

                    $taxa = TaxaPagamento::where('empresa_id', $this->empresa_id)
                        ->when($bandeira_cartao != '' && $bandeira_cartao != '99', function($q) use ($bandeira_cartao){
                            return $q->where('bandeira_cartao', $bandeira_cartao);
                        })
                        ->where('tipo_pagamento', $v->tipo_pagamento)->first();

                    if($taxa != null){
                        $item = [
                            'cliente' => $v->cliente ? ($v->cliente->razao_social . " " . $v->cliente->cpf_cnpj) :
                                'Consumidor final',
                            'total' => $v->valor_total,
                            'taxa_perc' => $taxa->taxa,
                            'taxa' => $taxa ? ($v->valor_total*($taxa->taxa/100)) : 0,
                            'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                            'tipo_pagamento' => Venda::getTipo($v->tipo_pagamento),
                            'venda_id' => $v->id,
                            'tipo' => 'PDV'
                        ];
                        array_push($data, $item);
                    }
                }
            }
        }

        $p = view('relatorios/taxas')
            ->with('data', $data)
            ->with('title', 'Taxas de Pagamento');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Taxas de pagamento.pdf", array("Attachment" => false));
    }

    public function descontos(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $vendas = Venda::where('empresa_id', $this->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('created_at', '<=', $data_final);
            })
            ->where('desconto', '>', 0)
            ->get();

        $data = [];

        foreach($vendas as $v){

            $item = [
                'cliente' => $v->cliente->razao_social . " " . $v->cliente->cpf_cnpj,
                'total' => $v->valor_total,
                'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                'venda_id' => $v->id,
                'desconto' => $v->desconto,
                'tipo' => 'PEDIDO'
            ];
            array_push($data, $item);

        }

        $vendasCaixa = VendaCaixa::where('empresa_id', $this->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('created_at', '<=', $data_final);
            })
            ->where('desconto', '>', 0)
            ->get();


        foreach($vendasCaixa as $v){

            $item = [
                'cliente' => $v->cliente ? ($v->cliente->razao_social . " " . $v->cliente->cpf_cnpj) : 'Consumidor final',
                'total' => $v->valor_total+$v->desconto,
                'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                'venda_id' => $v->id,
                'desconto' => $v->desconto,
                'tipo' => 'PDV'
            ];
            array_push($data, $item);

        }

        usort($data, function($a, $b){
            return $a['data'] > $b['data'] ? 1 : 0;
        });

        $p = view('relatorios/descontos')
            ->with('data', $data)
            ->with('title', 'Relatório de Descontos');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de descontos.pdf", array("Attachment" => false));
    }

    public function acrescimos(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $vendas = Venda::where('empresa_id', $this->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('created_at', '<=', $data_final);
            })
            ->where('acrescimo', '>', 0)
            ->get();

        $data = [];

        foreach($vendas as $v){

            $item = [
                'cliente' => $v->cliente->razao_social . " " . $v->cliente->cpf_cnpj,
                'total' => $v->valor_total+$v->acrescimo,
                'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                'venda_id' => $v->id,
                'acrescimo' => $v->acrescimo,
                'tipo' => 'PEDIDO'
            ];
            array_push($data, $item);

        }

        $vendasCaixa = VendaCaixa::where('empresa_id', $this->empresa_id)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('created_at', '<=', $data_final);
            })
            ->where('acrescimo', '>', 0)
            ->get();

        foreach($vendasCaixa as $v){

            $item = [
                'cliente' => $v->cliente ? ($v->cliente->razao_social . " " . $v->cliente->cpf_cnpj) : 'Consumidor final',
                'total' => $v->valor_total+$v->acrescimo,
                'data' => \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i'),
                'venda_id' => $v->id,
                'acrescimo' => $v->acrescimo,
                'tipo' => 'PDV'
            ];
            array_push($data, $item);

        }

        usort($data, function($a, $b){
            return $a['data'] > $b['data'] ? 1 : 0;
        });

        $p = view('relatorios/acrescimos')
            ->with('data', $data)
            ->with('title', 'Relatório de Acréscimos');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Relatório de Acréscimos.pdf", array("Attachment" => false));
    }

    public function contasRecebidas(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $data = ContaReceber::
        where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('data_recebimento', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('data_recebimento', '<=', $data_final);
            })
            ->get();

        $p = view('relatorios/contas_recebidas')
            ->with('data', $data)
            ->with('title', 'Contas Recebidas');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Contas Recebidas.pdf", array("Attachment" => false));
    }

    public function curva(Request $request){
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $funcionario = $request->funcionario;

        if($data_final && $data_final){
            $data_inicial = $this->parseDate($data_inicial);
            $data_final = $this->parseDate($data_final);
        }

        $itensVenda = ItemVenda::
        where('vendas.empresa_id', $this->empresa_id)
            ->select('item_vendas.*')
            ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('item_vendas.created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('item_vendas.created_at', '<=', $data_final);
            })
            ->when($funcionario != 'null', function($q) use ($funcionario){
                $f = Funcionario::find($funcionario);
                return $q->where('vendedor_id', $f->usuario_id);
            })
            ->get();

        $data = [];
        $dataIds = [];

        foreach($itensVenda as $item){
            if(!in_array($item->produto_id, $dataIds)){
                $p = [
                    'produto_id' => $item->produto_id,
                    'produto_nome' => $item->produto_nome,
                    'quantidade' => (float)$item->quantidade,
                    'valor' => $item->valor,
                    'sub_total' => $item->valor * (float)$item->quantidade,
                    'percentual' => 0
                ];
                array_push($data, $p);
                array_push($dataIds, $item->produto_id);
            }else{
                for($i=0; $i<sizeof($data); $i++){
                    if($data[$i]['produto_id'] == $item->produto_id){
                        $data[$i]['quantidade'] += (float)$item->quantidade;
                        $data[$i]['sub_total'] = (float)$item->quantidade * $data[$i]['valor'];
                    }
                }
            }
        }

        $itensVenda = ItemVendaCaixa::
        where('venda_caixas.empresa_id', $this->empresa_id)
            ->select('item_venda_caixas.*')
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->when($data_inicial != '', function($q) use ($data_inicial){
                return $q->whereDate('item_venda_caixas.created_at', '>=', $data_inicial);
            })
            ->when($data_final != '', function($q) use ($data_final){
                return $q->whereDate('item_venda_caixas.created_at', '<=', $data_final);
            })
            ->when($funcionario != 'null', function($q) use ($funcionario){
                $f = Funcionario::find($funcionario);
                return $q->where('vendedor_id', $f->usuario_id);
            })
            ->get();

        foreach($itensVenda as $item){
            if(!in_array($item->produto_id, $dataIds)){
                $p = [
                    'produto_id' => $item->produto_id,
                    'produto_nome' => $item->produto->nome,
                    'quantidade' => (float)$item->quantidade,
                    'valor' => $item->valor,
                    'sub_total' => $item->valor * (float)$item->quantidade,
                    'percentual' => 0
                ];
                array_push($data, $p);
                array_push($dataIds, $item->produto_id);
            }else{
                for($i=0; $i<sizeof($data); $i++){
                    if($data[$i]['produto_id'] == $item->produto_id){

                        $data[$i]['quantidade'] += (float)$item->quantidade;
                        $data[$i]['sub_total'] = $data[$i]['quantidade'] * $data[$i]['valor'];
                    }
                }
            }
        }

        usort($data, function($a, $b){
            return $a['valor'] < $b['valor'] ? 1 : -1;
        });

        $soma = 0;
        foreach($data as $item){
            $soma += $item['sub_total'];
        }

        for($i=0; $i<sizeof($data); $i++){
            $data[$i]['percentual'] = 100 - (((($data[$i]['sub_total']-$soma)/$soma)*100)*-1);
            $data[$i]['percentual'] =   number_format($data[$i]['percentual'], 3);
        }
        $p = view('relatorios/curva')
            ->with('data', $data)
            ->with('soma', $soma)
            ->with('title', 'Curva ABC');

        // return $p;
        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4", "landscape");
        $domPdf->render();
        $domPdf->stream("Curva ABC.pdf", array("Attachment" => false));
    }

    public function relatorioPesagem(Request $request)
    {
        try {
            $pesagemId = $request->input('pesagem_id');
            $dataInicial = $request->input('data_inicial');
            $dataFinal = $request->input('data_final');
            $veiculoId = $request->input('veiculo_id');
            $status = $request->input('status');

            // Inicializa a consulta base com os relacionamentos necessários
            $query = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id);

            // Filtro por ID da pesagem
            if (!empty($pesagemId)) {
                $ids = explode(',', $pesagemId);
                $query->whereIn('id', $ids);
            }

            // Aplicar outros filtros
            if (!empty($dataInicial)) {
                $query->whereDate('created_at', '>=', $dataInicial);
            }

            if (!empty($dataFinal)) {
                $query->whereDate('created_at', '<=', $dataFinal);
            }

            if (!empty($veiculoId)) {
                $query->where('veiculo_id', $veiculoId);
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            $pesagens = $query->get();

            if ($pesagens->isEmpty()) {
                \Log::info('Nenhuma pesagem encontrada com os critérios fornecidos.', [
                    'empresa_id' => $this->empresa_id,
                    'filtros' => [
                        'pesagemId' => $pesagemId,
                        'dataInicial' => $dataInicial,
                        'dataFinal' => $dataFinal,
                        'veiculoId' => $veiculoId,
                        'status' => $status,
                    ],
                ]);

                session()->flash('mensagem_erro', 'Nenhuma pesagem encontrada com os critérios fornecidos.');
                return redirect('/relatorios');
            }

            $dadosRelatorio = [];
            $pesoTotalGeral = 0;

            foreach ($pesagens as $pesagem) {
                $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets, $produtoId) {
                    $produto = $tickets->first()->produto;
                    $pesoTotalProduto = $tickets->sum('peso');

                    return [
                        'produto' => $produto,
                        'tickets' => $tickets,
                        'peso_total' => $pesoTotalProduto,
                    ];
                });

                $pesoTotalPesagem = $ticketsAgrupados->sum('peso_total');
                $pesoTotalGeral += $pesoTotalPesagem;

                $dadosRelatorio[] = [
                    'pesagem' => $pesagem,
                    'tickets_agrupados' => $ticketsAgrupados,
                    'peso_total_pesagem' => $pesoTotalPesagem,
                ];
            }

            return view('relatorios.pesagem', [
                'dadosRelatorio' => $dadosRelatorio,
                'pesoTotalGeral' => $pesoTotalGeral,
                'data_inicial' => $dataInicial,
                'data_final' => $dataFinal,
                'title' => 'Relatório de Pesagens por Produto',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Erro na consulta do relatório de pesagens', ['exception' => $e->getMessage()]);
            session()->flash('mensagem_erro', 'Erro ao gerar o relatório. Tente novamente mais tarde.');
            return redirect('/relatorios');
        } catch (\Exception $e) {
            \Log::error('Erro inesperado no relatório de pesagens', ['exception' => $e->getMessage()]);
            session()->flash('mensagem_erro', 'Erro inesperado. Entre em contato com o suporte.');
            return redirect('/relatorios');
        }
    }

    public function relatorioPesagem___(Request $request)
    {
        $pesagemId = $request->input('pesagem_id');
        $dataInicial = $request->input('data_inicial');
        $dataFinal = $request->input('data_final');
        $veiculoId = $request->input('veiculo_id');
        $status = $request->input('status');

        try {
            // Inicializa a query com os filtros básicos
            $query = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id);

            // Filtro por ID da pesagem
            if (!empty($pesagemId)) {
                $ids = explode(',', $pesagemId);
                $query->whereIn('id', $ids);
            }

            // Aplicar outros filtros
            if (!empty($dataInicial)) {
                $query->whereDate('created_at', '>=', $dataInicial);
            }

            if (!empty($dataFinal)) {
                $query->whereDate('created_at', '<=', $dataFinal);
            }

            if (!empty($veiculoId)) {
                $query->where('veiculo_id', $veiculoId);
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            // Obtém os resultados da query
            $pesagens = $query->get();

            // Verifica se a coleção está vazia e lança uma exceção
            if ($pesagens->isEmpty()) {
                throw new ModelNotFoundException("Nenhuma pesagem encontrada para os critérios fornecidos.");
            }

        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'pesagem_id' => $pesagemId,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "Nenhuma pesagem encontrada com os critérios fornecidos. Verifique e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Redireciona para a página de relatórios
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('relatorios.index');
        }

        $dadosRelatorio = [];
        $pesoTotalGeral = 0;

        foreach ($pesagens as $pesagem) {
            $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets, $produtoId) {
                $produto = $tickets->first()->produto;
                $pesoTotalProduto = $tickets->sum('peso');

                return [
                    'produto' => $produto,
                    'tickets' => $tickets,
                    'peso_total' => $pesoTotalProduto,
                ];
            });

            $pesoTotalPesagem = $ticketsAgrupados->sum('peso_total');
            $pesoTotalGeral += $pesoTotalPesagem;

            $dadosRelatorio[] = [
                'pesagem' => $pesagem,
                'tickets_agrupados' => $ticketsAgrupados,
                'peso_total_pesagem' => $pesoTotalPesagem,
            ];
        }

        return view('relatorios.pesagem', [
            'dadosRelatorio' => $dadosRelatorio,
            'pesoTotalGeral' => $pesoTotalGeral,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'title' => 'Relatório de Pesagens por Produto',
        ]);
    }

    public function relatorioPesagem__(Request $request)
    {
        $pesagemId = $request->input('pesagem_id');
        $dataInicial = $request->input('data_inicial');
        $dataFinal = $request->input('data_final');
        $veiculoId = $request->input('veiculo_id');
        $status = $request->input('status');

        //$query = Pesagem::with(['tickets.produto', 'veiculo'])
        //    ->where('empresa_id', $this->empresa_id);

        // Busca a pesagem
        try {
            $query = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id);
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $pesagemId,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$pesagemId} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        // Filtro por ID da pesagem
        if (!empty($pesagemId)) {
            $ids = explode(',', $pesagemId);
            $query->whereIn('id', $ids);
        }

        // Aplicar outros filtros
        if (!empty($dataInicial)) {
            $query->whereDate('created_at', '>=', $dataInicial);
        }

        if (!empty($dataFinal)) {
            $query->whereDate('created_at', '<=', $dataFinal);
        }

        if (!empty($veiculoId)) {
            $query->where('veiculo_id', $veiculoId);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $pesagens = $query->get();

        if ($pesagens->isEmpty()) {
            return redirect('/relatorios')->withErrors(['error' => 'Nenhuma pesagem encontrada com os critérios fornecidos.']);
        }

        $dadosRelatorio = [];
        $pesoTotalGeral = 0;

        foreach ($pesagens as $pesagem) {
            $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets, $produtoId) {
                $produto = $tickets->first()->produto;
                $pesoTotalProduto = $tickets->sum('peso');

                return [
                    'produto' => $produto,
                    'tickets' => $tickets,
                    'peso_total' => $pesoTotalProduto,
                ];
            });

            $pesoTotalPesagem = $ticketsAgrupados->sum('peso_total');
            $pesoTotalGeral += $pesoTotalPesagem;

            $dadosRelatorio[] = [
                'pesagem' => $pesagem,
                'tickets_agrupados' => $ticketsAgrupados,
                'peso_total_pesagem' => $pesoTotalPesagem,
            ];
        }

        return view('relatorios.pesagem', [
            'dadosRelatorio' => $dadosRelatorio,
            'pesoTotalGeral' => $pesoTotalGeral,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'title' => 'Relatório de Pesagens por Produto',
        ]);
    }

    public function imprimirRelatorioPesagem(Request $request)
    {
        $pesagemId = $request->input('pesagem_id');
        $dataInicial = $request->input('data_inicial');
        $dataFinal = $request->input('data_final');
        $veiculoId = $request->input('veiculo_id');
        $status = $request->input('status');
        $formato = $request->input('formato', 'a4'); // Default: A4

        //$query = Pesagem::with(['tickets.produto', 'veiculo'])
        //    ->where('empresa_id', $this->empresa_id);

        // Busca a pesagem
        try {
            $query = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($pesagemId); // Tenta buscar o registro
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $pesagemId,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$pesagemId} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        if (!empty($pesagemId)) {
            $ids = explode(',', $pesagemId);
            $query->whereIn('id', $ids);
        }

        if (!empty($dataInicial)) {
            $query->whereDate('created_at', '>=', $dataInicial);
        }

        if (!empty($dataFinal)) {
            $query->whereDate('created_at', '<=', $dataFinal);
        }

        if (!empty($veiculoId)) {
            $query->where('veiculo_id', $veiculoId);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $pesagens = $query->get();

        if ($pesagens->isEmpty()) {
            return redirect('/relatorios')->withErrors(['error' => 'Nenhuma pesagem encontrada com os critérios fornecidos.']);
        }

        // **Carregar dados do emitente**
        $configEmitente = $this->obterConfigEmitentePesagemA4($pesagens->first() ?? null, $this->empresa_id ?? null); // Carregar configuração do emitente
        if (!$configEmitente) {
            return redirect('/relatorios')->withErrors(['error' => 'Configuração do emitente não encontrada.']);
        }

        $dadosRelatorio = [];
        $pesoTotalGeral = 0;

        foreach ($pesagens as $pesagem) {
            $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets, $produtoId) {
                $produto = $tickets->first()->produto;
                $pesoTotalProduto = $tickets->sum('peso');

                return [
                    'produto' => $produto,
                    'tickets' => $tickets,
                    'peso_total' => $pesoTotalProduto,
                ];
            });

            $pesoTotalPesagem = $ticketsAgrupados->sum('peso_total');
            $pesoTotalGeral += $pesoTotalPesagem;

            $dadosRelatorio[] = [
                'pesagem' => $pesagem,
                'tickets_agrupados' => $ticketsAgrupados,
                'peso_total_pesagem' => $pesoTotalPesagem,
            ];
        }

        $title = 'Relatório de Pesagens por Produto';

        $view = $formato === '80col' ? 'relatorios.pesagem_80col' : 'relatorios.pesagem';

        $html = view($view, [
            'dadosRelatorio' => $dadosRelatorio,
            'pesoTotalGeral' => $pesoTotalGeral,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'title' => $title,
            'configEmitente' => $configEmitente, // Passa os dados do emitente para a view
        ])->render();

        $paperSize = $formato === '80col' ? [0, 0, 226.77, 650] : 'A4';
        $orientation = $formato === '80col' ? 'portrait' : 'portrait';

        $dompdf = new \Dompdf\Dompdf(["enable_remote" => true]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paperSize, $orientation);
        $dompdf->render();

        if ($formato !== '80col') {
            $this->adicionarRodapePaginadoPesagemA4($dompdf);
        }

        $fileName = $formato === '80col' ? "relatorio_80col.pdf" : "relatorio_a4.pdf";

        return $dompdf->stream($fileName, ["Attachment" => false]);
    }

    public function imprimirPesagem80mm($id)
    {
        // Busca a pesagem
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id); // Tenta buscar o registro
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        // Cria uma instância do relatório
        $relatorio = new PesagemPrint80($pesagem);

        // Monta o relatório
        $relatorio->monta();

        // Gera o PDF
        $pdf = $relatorio->render();

        // Retorna o PDF com cabeçalhos HTTP corretos
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="relatorio_pesagem.pdf"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function imprimirPesagem80mmSimples($id)
    {
        // Busca a pesagem
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id); // Tenta buscar o registro
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        // Cria uma instância do relatório
        $relatorio = new PesagemPrint80Simples($pesagem);

        // Monta o relatório
        $relatorio->monta();

        // Gera o PDF
        $pdf = $relatorio->render();

        // Retorna o PDF com cabeçalhos HTTP corretos
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="relatorio_pesagem.pdf"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function imprimirPesagemA4($id)
    {
        // Busca a pesagem
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id); // Tenta buscar o registro
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        // Carregar dados do emitente
        $configEmitente = $this->obterConfigEmitentePesagemA4($pesagem, $pesagem->empresa_id ?? $this->empresa_id ?? null);
        if (!$configEmitente) {
            throw new \Exception("Configuração do emitente não encontrada.");
        }

        // Dados necessários para o relatório
        $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets) {
            return [
                'produto' => $tickets->first()->produto,
                'tickets' => $tickets,
                'peso_total' => $tickets->sum('peso'),
            ];
        });

        $dadosRelatorio = [
            'dadosRelatorio' => [
                [
                    'pesagem' => $pesagem,
                    'tickets_agrupados' => $ticketsAgrupados,
                    'peso_total_pesagem' => $ticketsAgrupados->sum('peso_total'),
                ]
            ],
            'pesoTotalGeral' => $pesagem->tickets->sum('peso'),
            'configEmitente' => $configEmitente,
            'title' => 'Relatório de Pesagens por Produto', // Define o título necessário para a view
            'data_inicial' => null, // Opcional para evitar erros na view
            'data_final' => null,  // Opcional para evitar erros na view
        ];

        return $this->renderPesagemA4Pdf($dadosRelatorio);
    }

    public function imprimirPesagem80mmWhats($id, $empresa_id)
    {
        // Busca a pesagem
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $empresa_id)
                ->findOrFail($id); // Tenta buscar o registro
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        // Cria uma instância do relatório
        $relatorio = new PesagemPrint80($pesagem);

        // Monta o relatório
        $relatorio->monta();

        // Gera o PDF
        $pdf = $relatorio->render();

        // Retorna o PDF com cabeçalhos HTTP corretos
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="relatorio_pesagem.pdf"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function imprimirPesagemA4Whats($id, $empresa_id)
    {
        // Busca a pesagem
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $empresa_id)
                ->findOrFail($id); // Tenta buscar o registro
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        // Carregar dados do emitente
        $configEmitente = $this->obterConfigEmitentePesagemA4($pesagem, $pesagem->empresa_id ?? $this->empresa_id ?? null);
        if (!$configEmitente) {
            throw new \Exception("Configuração do emitente não encontrada.");
        }

        // Dados necessários para o relatório
        $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets) {
            return [
                'produto' => $tickets->first()->produto,
                'tickets' => $tickets,
                'peso_total' => $tickets->sum('peso'),
            ];
        });

        $dadosRelatorio = [
            'dadosRelatorio' => [
                [
                    'pesagem' => $pesagem,
                    'tickets_agrupados' => $ticketsAgrupados,
                    'peso_total_pesagem' => $ticketsAgrupados->sum('peso_total'),
                ]
            ],
            'pesoTotalGeral' => $pesagem->tickets->sum('peso'),
            'configEmitente' => $configEmitente,
            'title' => 'Relatório de Pesagens por Produto', // Define o título necessário para a view
            'data_inicial' => null, // Opcional para evitar erros na view
            'data_final' => null,  // Opcional para evitar erros na view
        ];

        return $this->renderPesagemA4Pdf($dadosRelatorio);
    }

    public function relPrnToken($token)
    {
        // Busca a pesagem pelo campo 'token'
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id)
                ->where('token', $token) // Busca pelo campo 'token'
                ->firstOrFail($token); // Lança um erro se não encontrar
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        //$pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
        //    ->where('empresa_id', $this->empresa_id)
        //    ->where('token', $token) // Busca pelo campo 'token'
        //    ->firstOrFail($token); // Lança um erro se não encontrar

        // Cria uma instância do relatório
        $relatorio = new PesagemPrint80($pesagem);

        // Monta o relatório
        $relatorio->monta();

        // Gera o PDF
        $pdf = $relatorio->render();

        // Retorna o PDF com cabeçalhos HTTP corretos
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="relatorio_pesagem.pdf"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function relPrnTokenA4($token)
    {
        // Busca a pesagem pelo campo 'token'
        try {
            $pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
                ->where('empresa_id', $this->empresa_id)
                ->where('token', $token) // Busca pelo campo 'token'
                ->firstOrFail($token); // Lança um erro se não encontrar
        } catch (ModelNotFoundException $e) {
            // Registra o erro no log com informações adicionais
            \Log::error('Erro ao buscar pesagem', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "A pesagem com o ID #{$id} não foi encontrada. Verifique o ID informado e tente novamente.");

            // Registra no log que a mensagem foi salva na sessão
            \Log::debug('Mensagem de erro definida na sessão');

            // Verifica se o redirecionamento para a página anterior é aplicável
            if (request()->headers->get('referer')) {
                // Redireciona o usuário de volta para a página anterior
                return redirect()->back();
            }

            // Redireciona para uma página padrão (por exemplo, lista de pesagens)
            return redirect()->route('pesagens.list');
        }

        //$pesagem = Pesagem::with(['tickets.produto', 'veiculo'])
        //    ->where('empresa_id', $this->empresa_id)
        //    ->where('token', $token) // Busca pelo campo 'token'
        //    ->firstOrFail($token); // Lança um erro se não encontrar

        // Carregar dados do emitente
        $configEmitente = $this->obterConfigEmitentePesagemA4($pesagem, $empresa_id ?? $pesagem->empresa_id ?? null);
        if (!$configEmitente) {
            throw new \Exception("Configuração do emitente não encontrada.");
        }

        // Dados necessários para o relatório
        $ticketsAgrupados = $pesagem->tickets->groupBy('produto.id')->map(function ($tickets) {
            return [
                'produto' => $tickets->first()->produto,
                'tickets' => $tickets,
                'peso_total' => $tickets->sum('peso'),
            ];
        });

        $dadosRelatorio = [
            'dadosRelatorio' => [
                [
                    'pesagem' => $pesagem,
                    'tickets_agrupados' => $ticketsAgrupados,
                    'peso_total_pesagem' => $ticketsAgrupados->sum('peso_total'),
                ]
            ],
            'pesoTotalGeral' => $pesagem->tickets->sum('peso'),
            'configEmitente' => $configEmitente,
            'title' => 'Relatório de Pesagens por Produto', // Define o título necessário para a view
            'data_inicial' => null, // Opcional para evitar erros na view
            'data_final' => null,  // Opcional para evitar erros na view
        ];

        return $this->renderPesagemA4Pdf($dadosRelatorio);
    }

    public function nfeNumeracaoGap(Request $request)
    {
        // Filtros recebidos do formulário
        $dataInicial = $request->get('data_inicial');
        $dataFinal   = $request->get('data_final');
        $serie       = $request->get('serie');
        $tipo        = $request->get('tipo', 'todos'); // nfe, nfce ou todos

        /**
         * ATENÇÃO:
         * Ajuste o nome da tabela e colunas abaixo para o seu layout real.
         *
         * Exemplo assumido:
         *  - Tabela: notas_fiscais
         *  - Colunas: numero, serie, tipo (nfe/nfce), data_emissao
         */
        $query = DB::table('notas_fiscais')
            ->when($dataInicial, function ($q) use ($dataInicial) {
                return $q->whereDate('data_emissao', '>=', $dataInicial);
            })
            ->when($dataFinal, function ($q) use ($dataFinal) {
                return $q->whereDate('data_emissao', '<=', $dataFinal);
            })
            ->when($serie, function ($q) use ($serie) {
                return $q->where('serie', $serie);
            })
            ->when($tipo && $tipo !== 'todos', function ($q) use ($tipo) {
                return $q->where('tipo', $tipo);
            })
            ->orderBy('serie')
            ->orderBy('tipo')
            ->orderBy('numero');

        $notas = $query->get(['numero', 'serie', 'tipo']);

        $gaps = [];

        // Agrupa por tipo+serie para calcular saltos por faixa
        $grouped = $notas->groupBy(function ($n) {
            return $n->tipo . '#' . $n->serie;
        });

        foreach ($grouped as $key => $registros) {
            [$tipoAtual, $serieAtual] = explode('#', $key);

            $numeros = $registros
                ->pluck('numero')
                ->map(function ($n) {
                    return (int)$n;
                })
                ->unique()
                ->sort()
                ->values();

            if ($numeros->isEmpty()) {
                continue;
            }

            $min = $numeros->first();
            $max = $numeros->last();

            $presentes = $numeros->toArray();
            $lookup = [];
            foreach ($presentes as $n) {
                $lookup[$n] = true;
            }

            for ($i = $min; $i <= $max; $i++) {
                if (!isset($lookup[$i])) {
                    $gaps[] = [
                        'tipo'            => $tipoAtual,
                        'serie'           => $serieAtual,
                        'numero_faltante' => $i,
                        'intervalo_inicio'=> $min,
                        'intervalo_fim'   => $max,
                    ];
                }
            }
        }

        return view('relatorios.nfe_numeracao_gaps', [
            'gaps'        => $gaps,
            'dataInicial' => $dataInicial,
            'dataFinal'   => $dataFinal,
            'serie'       => $serie,
            'tipo'        => $tipo,
        ]);
    }

}
