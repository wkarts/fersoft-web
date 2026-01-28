<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Cliente;
use App\Models\ClienteDelivery;
use App\Models\Produto;
use App\Models\ItemVendaCaixa;
use App\Models\ItemVenda;
use App\Models\PedidoDelivery;
use App\Models\Venda;
use App\Models\VendaCaixa;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\Usuario;
use App\Models\Aviso;
use App\Models\AvisoAcesso;
use App\Models\Orcamento;
use App\Models\PlanoEmpresa;
use App\Models\RemessaNfe;
use App\Models\ConfigNota;

class HomeController extends Controller
{
    protected $empresa_id = null;
    protected $acesso_financeiro = false;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(session('user_contador')){
                return redirect('/contador');
            }

            if(!$value){
                return redirect("/login");
            }else{
                $usuario = Usuario::find($value['id']);
                $permissao = json_decode($usuario->permissao);
                // print_r($permissao);
                if(in_array("/contasPagar", $permissao) || in_array("/contasReceber", $permissao)){
                    $this->acesso_financeiro = true;
                }
            }
            return $next($request);
        });
    }

    private function setMaskDoc($doc){
        if(strlen($doc) == 14){
            $str = substr($doc, 0,2). ".";
            $str .= substr($doc, 2,3). ".";
            $str .= substr($doc, 5,3). "/";
            $str .= substr($doc, 8,4). "-";
            $str .= substr($doc, 12,2);

            return $str;
        }else{
            $str = substr($doc, 0,3). ".";
            $str .= substr($doc, 3,3). ".";
            $str .= substr($doc, 6,3). "-";
            $str .= substr($doc, 9,2);
            return $str;
        }
    }

    public function contasPagar(Request $request)
    {
        // 1) Normaliza filial: request > local_padrão (session) > null (matriz)
        $filial = null;
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Define início em 7 dias atrás e percorre 15 dias
        $inicio = Carbon::today()->subDays(7);
        $retorno = [];

        for ($i = 0; $i < 15; $i++) {
            $dataVenc = $inicio->copy()->addDays($i);

            // 3) Soma as contas a pagar para esse dia
            $total = ContaPagar::whereDate('data_vencimento', $dataVenc->toDateString())
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->where('status', false)
                ->sum('valor_integral');

            $retorno[] = [
                'data'  => $dataVenc->format('d/m'),
                'total' => number_format($total, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function contasPagar_02072025_bkp(Request $request){
        $data_final = date('Y-m-d', strtotime('+7 day'));
        $data_inicial = date('Y-m-d', strtotime('-7 day'));
        $filial_id = $request->filial_id;

        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux < 15; $aux++){
            $total = ContaPagar::
            whereDate('data_vencimento', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->where('status', 0)
                ->sum('valor_integral');

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function contasReceber(Request $request)
    {
        // 1) Normaliza filial: request > local_padrão (session) > null (matriz)
        $filial = null;
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Define janela de -7 até +7 dias (15 dias no total)
        $inicio = Carbon::today()->subDays(7);
        $retorno = [];

        for ($i = 0; $i < 15; $i++) {
            $dataVencto = $inicio->copy()->addDays($i);

            // 3) Soma as contas a receber para essa data
            $total = ContaReceber::whereDate('data_vencimento', $dataVencto->toDateString())
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->where('status', false)
                ->sum('valor_integral');

            $retorno[] = [
                'data'  => $dataVencto->format('d/m'),
                'total' => number_format($total, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function contasReceber_02072025_bkp(Request $request){
        $data_final = date('Y-m-d', strtotime('+7 day'));
        $data_inicial = date('Y-m-d', strtotime('-7 day'));
        $filial_id = $request->filial_id;
        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux < 15; $aux++){
            $total = ContaReceber::
            whereDate('data_vencimento', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->where('status', 0)
                ->sum('valor_integral');

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function vendasPdv(Request $request)
    {
        // 1) Normaliza filial: primeiro tenta do request, depois do local_padrao da sessão, senão null (matriz)
        $filial = null;
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Define período: de 15 dias atrás até hoje (16 pontos: de zero a 15)
        $inicio = Carbon::today()->subDays(15);
        $retorno = [];

        for ($i = 0; $i <= 15; $i++) {
            $dia = $inicio->copy()->addDays($i);

            // 3) Soma as vendas no PDV para a data
            $total = VendaCaixa::whereDate('created_at', $dia->toDateString())
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->sum('valor_total');

            $retorno[] = [
                'data'  => $dia->format('d/m'),
                'total' => number_format($total, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function vendasPdv_02072025_bkp(Request $request){
        $data_final = date('Y-m-d');
        $data_inicial = date('Y-m-d', strtotime('-15 day'));
        $filial_id = $request->filial_id;
        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux <= 15; $aux++){
            $total = VendaCaixa::
            whereDate('created_at', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->sum('valor_total');

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function vendasPedido(Request $request)
    {
        // 1) Normaliza filial: request > local_padrao da sessão > null (matriz)
        $filial = null;
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Período: de 15 dias atrás até hoje
        $inicio  = Carbon::today()->subDays(15);
        $retorno = [];

        for ($i = 0; $i <= 15; $i++) {
            $dia = $inicio->copy()->addDays($i);

            // 3) Soma os pedidos de venda para o dia
            $total = Venda::whereDate('created_at', $dia->toDateString())
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->sum('valor_total');

            $retorno[] = [
                'data'  => $dia->format('d/m'),
                'total' => number_format($total, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function vendasPedido_02072025_bkp(Request $request){
        $data_final = date('Y-m-d');
        $data_inicial = date('Y-m-d', strtotime('-15 day'));
        $filial_id = $request->filial_id;
        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux <= 15; $aux++){
            $total = Venda::
            whereDate('created_at', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->sum('valor_total');

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function orcamentos(Request $request)
    {
        // 1) Normaliza filial: request > local_padrao da sessão > null (matriz)
        $filial = null;
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Ponto de partida: 15 dias atrás até hoje
        $inicio  = Carbon::today()->subDays(15);
        $retorno = [];

        for ($i = 0; $i <= 15; $i++) {
            $dia = $inicio->copy()->addDays($i);

            // 3) Total de orçamentos no dia
            $total = Orcamento::whereDate('created_at', $dia->toDateString())
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->sum('valor_total');

            $retorno[] = [
                'data'  => $dia->format('d/m'),
                'total' => number_format($total, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function orcamentos_02072025_bkp(Request $request){
        $data_final = date('Y-m-d');
        $data_inicial = date('Y-m-d', strtotime('-15 day'));
        $filial_id = $request->filial_id;
        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux <= 15; $aux++){
            $total = Orcamento::
            whereDate('created_at', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->sum('valor_total');

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function produtos(Request $request)
    {
        // 1) Determina filial_id do request ou, se não vier, do local_padrao na sessão
        $filialIdRaw = $request->has('filial_id')
            ? $request->filial_id
            : session('user_logged.local_padrao');

        // 2) Normaliza: tudo que intval < 1 é Matriz
        $isMatrix = (intval($filialIdRaw) < 1);
        $filialId = $isMatrix
            ? null
            : (string) intval($filialIdRaw);

        // 3) Nome simbólico para Matriz
        $MATRIZ = 'MATRIZ';

        // 4) Intervalo dos últimos 6 meses + mês atual
        $mesInicial = (int) date('m', strtotime('-6 months'));
        $retorno    = [];

        for ($i = 0; $i <= 6; $i++) {
            $mes = $mesInicial + $i;
            if ($mes > 12) {
                $mes -= 12;
            }

            // Busca produtos criados naquele mês
            $produtos = Produto::where('empresa_id', $this->empresa_id)
                ->whereMonth('created_at', sprintf('%02d', $mes))
                ->get();

            $total = 0;

            foreach ($produtos as $p) {
                // Decodifica JSON de locais (com fallback para aspas simples)
                $raw     = $p->locais;
                $decoded = @json_decode($raw, true);
                if (! is_array($decoded)) {
                    $decoded = json_decode(str_replace("'", '"', $raw), true) ?: [];
                }

                // Normaliza IDs e flag de Matriz
                $idsUnicos = [];
                $hasMatrix = false;
                foreach ($decoded as $loc) {
                    // qualquer valor cujo intval < 1 → Matriz
                    if (intval($loc) < 1) {
                        $hasMatrix = true;
                    } else {
                        $idsUnicos[] = (string) intval($loc);
                    }
                }

                // Se tiver flag Matriz ou nenhum filial listado, garante o sentinel
                if ($hasMatrix || empty($idsUnicos)) {
                    $idsUnicos[] = $MATRIZ;
                }
                $idsUnicos = array_unique($idsUnicos);

                // Conta se pertence ao local selecionado
                if ($isMatrix) {
                    if (in_array($MATRIZ, $idsUnicos, true)) {
                        $total++;
                    }
                } else {
                    if (in_array($filialId, $idsUnicos, true)) {
                        $total++;
                    }
                }
            }

            $retorno[] = [
                'data'  => $this->getMes($mes),
                'total' => $total,
            ];
        }

        return response()->json($retorno, 200);
    }

    public function produtos_ok(Request $request)
    {
        // 1) Determina filial_id do request ou, se não vier, do local_padrao na sessão
        $filialIdRaw = $request->has('filial_id')
            ? $request->filial_id
            : session('user_logged.local_padrao');

        // 2) Normaliza: '-1', '' ou null são Matriz
        $isMatrix = ($filialIdRaw === null || $filialIdRaw === '' || $filialIdRaw === '-1');
        $filialId = $isMatrix
            ? null
            : (string) intval($filialIdRaw);

        // 3) Nome simbólico para Matriz
        $MATRIZ = 'MATRIZ';

        // 4) Intervalo dos últimos 6 meses + mês atual
        $mesInicial = (int) date('m', strtotime('-6 months'));

        $retorno = [];

        for ($i = 0; $i <= 6; $i++) {
            $mes = $mesInicial + $i;
            if ($mes > 12) {
                $mes -= 12;
            }

            // Busca produtos criados naquele mês
            $produtos = Produto::query()
                ->where('empresa_id', $this->empresa_id)
                ->whereMonth('created_at', sprintf('%02d', $mes))
                ->get();

            $total = 0;

            foreach ($produtos as $p) {
                // Decodifica JSON de locais (com fallback para aspas simples)
                $raw     = $p->locais;
                $decoded = @json_decode($raw, true);
                if (! is_array($decoded)) {
                    $decoded = json_decode(str_replace("'", '"', $raw), true) ?: [];
                }

                // Normaliza IDs e flag de Matriz
                $idsUnicos = [];
                $hasMatrix = false;
                foreach ($decoded as $loc) {
                    if ($loc === null || $loc === '-1' || $loc === '') {
                        $hasMatrix = true;
                    } else {
                        $idsUnicos[] = (string) intval($loc);
                    }
                }
                // Se só Matriz ou array vazio, adiciona sentinel
                if ($hasMatrix || empty($idsUnicos)) {
                    $idsUnicos[] = $MATRIZ;
                }
                $idsUnicos = array_unique($idsUnicos);

                // Conta somente se pertence ao local desejado
                if ($isMatrix) {
                    if (in_array($MATRIZ, $idsUnicos, true)) {
                        $total++;
                    }
                } else {
                    if (in_array($filialId, $idsUnicos, true)) {
                        $total++;
                    }
                }
            }

            $retorno[] = [
                'data'  => $this->getMes($mes),
                'total' => $total, // já inteiro, sem casas decimais
            ];
        }

        return response()->json($retorno, 200);
    }

    public function produtos_02072025_bkp(Request $request){
        $mes_atual = date('m');
        $mes_inicial = date('m', strtotime('-6 months'));
        $filial_id = $request->filial_id;
        // if($filial_id == -1){
        //     $filial_id = null;
        // }
        $retorno = [];
        $mes = (int)$mes_inicial;

        for($aux = 0; $aux <= 6; $aux++){
            if($mes > 12){
                $mes = 1;
            }
            $data = Produto::
            whereMonth('created_at', ($mes < 10) ? "0".$mes : $mes)
                ->where('empresa_id', $this->empresa_id)
                ->get();

            $total = 0;
            foreach($data as $p){
                $l = json_decode($p->locais);
                if(is_array($l)){
                    if(in_array($filial_id, $l)){
                        $total++;
                    }
                }
            }

            $temp = [
                'data' => $this->getMes($mes),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
            $mes++;
        }
        return response()->json($retorno, 200);
    }

    private function getMes($p){
        $meses = [
            'Jan',
            'Fev',
            'Mar',
            'Abr',
            'Mai',
            'Jun',
            'Jul',
            'Ago',
            'Set',
            'Out',
            'Nov',
            'Dez',
        ];
        return $meses[$p-1];
    }

    public function emissaoNfe(Request $request)
    {
        // 1) Decide a filial: request > session.local_padrao > null
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            $filial = ((int)$padrao > 0) ? (int)$padrao : null;
        }

        // 2) Começa de 15 dias atrás até hoje
        $inicio    = Carbon::today()->subDays(15);
        $retorno   = [];

        for ($i = 0; $i <= 15; $i++) {
            $dia = $inicio->copy()->addDays($i)->toDateString();

            // 3) Soma de NF emitidas em Vendas
            $totalVenda = Venda::whereDate('data_emissao', $dia)
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->where('NfNumero', '>', 0)
                ->sum('valor_total');

            // 4) Soma de NF emitidas em RemessaNfe
            $totalRemessa = RemessaNfe::whereDate('data_emissao', $dia)
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->where('numero_nfe', '>', 0)
                ->sum('valor_total');

            $retorno[] = [
                'data'  => Carbon::parse($dia)->format('d/m'),
                'total' => number_format($totalVenda + $totalRemessa, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function emissaoNfe_02072025_bkp(Request $request){
        $data_final = date('Y-m-d');
        $data_inicial = date('Y-m-d', strtotime('-15 day'));
        $filial_id = $request->filial_id;
        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux <= 15; $aux++){
            $totalVenda = Venda::
            whereDate('data_emissao', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->where('NfNumero', '>', 0)
                ->sum('valor_total');

            $totalRemessa = RemessaNfe::
            whereDate('data_emissao', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->where('numero_nfe', '>', 0)
                ->sum('valor_total');

            $total = $totalVenda + $totalRemessa;

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function emissaoNfce(Request $request)
    {
        // 1) Determina a filial: request.filial_id > 0 ? request : session.local_padrao > 0 ? session : null
        if ($request->filled('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            $filial = ((int)$padrao > 0) ? (int)$padrao : null;
        }

        // 2) Cria o ponto inicial (hoje - 15 dias)
        $inicio  = Carbon::today()->subDays(15);
        $retorno = [];

        // 3) Percorre de 0 até 15 dias a partir de $inicio
        for ($i = 0; $i <= 15; $i++) {
            $data = $inicio->copy()->addDays($i)->toDateString();

            // 4) Conta total de NFC-e emitidas no PDV (VendaCaixa.NFcNumero > 0)
            $total = VendaCaixa::whereDate('created_at', $data)
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                ->where('NFcNumero', '>', 0)
                ->sum('valor_total');

            // 5) Formata e adiciona ao array
            $retorno[] = [
                'data'  => Carbon::parse($data)->format('d/m'),
                'total' => number_format($total, 2, '.', '')
            ];
        }

        return response()->json($retorno, 200);
    }

    public function emissaoNfce_02072025_bkp(Request $request){
        $data_final = date('Y-m-d');
        $data_inicial = date('Y-m-d', strtotime('-15 day'));
        $filial_id = $request->filial_id;
        if($filial_id == -1){
            $filial_id = null;
        }
        $retorno = [];

        $dateAux = $data_inicial;
        for($aux = 0; $aux <= 15; $aux++){
            $total = VendaCaixa::
            whereDate('created_at', date('Y-m-d', strtotime("+".($aux)." days",strtotime($data_inicial))))
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->where('NFcNumero', '>', 0)
                ->sum('valor_total');

            $d = date($data_inicial, strtotime(($aux).' day'));
            $d = \Carbon\Carbon::parse($d)->format('d/m');
            $temp = [
                'data' => date('d/m', strtotime("+".($aux)." days",strtotime($data_inicial))),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($retorno, $temp);
        }
        return response()->json($retorno, 200);
    }

    public function index()
    {
        // $this->setMaskDoc('09520985980');

        $dataFinal2 = $dataFinal = date('d/m/Y');
        $dataInicial2 = $dataInicial = date('d/m/Y', strtotime('-6 day'));
        $totalDeClientes = Cliente::where('empresa_id', $this->empresa_id)->count();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $graficosDash = [];

        if($config != null){
            $graficosDash = $config->graficos_dash ? json_decode($config->graficos_dash) : [];
        }
        return view('default/grafico')
            ->with('graficoHomeJs', true)
            ->with('totalDeClientes', $totalDeClientes)
            ->with('graficosDash', $graficosDash)
            ->with('dataInicial', $dataInicial)
            ->with('dataFinal', $dataFinal)
            ->with('dataInicial2', $dataInicial2)
            ->with('dataFinal2', $dataFinal2)
            ->with('title', 'Bem Vindo');
    }

    public function countProdutos(Request $request)
    {
        // 1) Pega filial_id do request ou, se não vier, do local_padrao na sessão
        $filialIdRaw = $request->has('filial_id')
            ? $request->filial_id
            : session('user_logged.local_padrao');

        // 2) Normaliza: tudo que, convertido em int, for < 1 é Matriz
        $isMatrix = (intval($filialIdRaw) < 1);
        $filialId = $isMatrix
            ? null
            : (string) intval($filialIdRaw);

        $total = 0;

        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();

        foreach ($produtos as $p) {
            // Decodifica JSON de locais (tenta consertar aspas simples)
            $raw     = $p->locais;
            $decoded = @json_decode($raw, true);
            if (! is_array($decoded)) {
                $decoded = json_decode(str_replace("'", '"', $raw), true) ?: [];
            }

            // 3) Normaliza cada loc: int<1 → Matriz; int>=1 → filial
            $idsUnicos = [];
            $hasMatrix = false;
            foreach ($decoded as $loc) {
                // se intval < 1, marca Matriz
                if (intval($loc) < 1) {
                    $hasMatrix = true;
                } else {
                    $idsUnicos[] = (string) intval($loc);
                }
            }

            // 4) Se veio só matriz ou lista vazia, garante a flag "MATRIZ"
            if ($hasMatrix || empty($idsUnicos)) {
                $idsUnicos[] = 'MATRIZ';
            }
            $idsUnicos = array_unique($idsUnicos);

            // 5) Conta se pertence ao local escolhido
            if ($isMatrix) {
                if (in_array('MATRIZ', $idsUnicos, true)) {
                    $total++;
                }
            } else {
                if (in_array($filialId, $idsUnicos, true)) {
                    $total++;
                }
            }
        }

        return response()->json($total, 200);
    }

    public function countProdutos___(Request $request)
    {
        // 1) Pega filial_id do request ou do padrão na sessão
        $filialIdRaw = $request->has('filial_id')
            ? $request->filial_id
            : session('user_logged.local_padrao');

        // 2) Normaliza: '-1', '' ou null são Matriz
        $isMatrix = ($filialIdRaw === null || $filialIdRaw === '' || $filialIdRaw === '-1');
        $filialId  = $isMatrix ? null : (string) intval($filialIdRaw);

        $total = 0;

        // 3) Recupera todos os produtos da empresa
        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();

        foreach ($produtos as $p) {
            // 4) Decodifica JSON de locais, tenta consertar se inválido
            $raw     = $p->locais;
            $decoded = @json_decode($raw, true);
            if (! is_array($decoded)) {
                $decoded = json_decode(str_replace("'", '"', $raw), true) ?: [];
            }

            // 5) Normaliza IDs únicos + flag de Matriz
            $idsUnicos = [];
            $hasMatrix = false;

            foreach ($decoded as $loc) {
                if ($loc === null || $loc === '-1' || $loc === '') {
                    $hasMatrix = true;
                } else {
                    $idsUnicos[] = (string) intval($loc);
                }
            }

            if ($hasMatrix || empty($idsUnicos)) {
                $idsUnicos[] = 'MATRIZ';
            }
            $idsUnicos = array_unique($idsUnicos);

            // 6) Verifica e conta se pertence
            if ($isMatrix) {
                if (in_array('MATRIZ', $idsUnicos, true)) {
                    $total++;
                }
            } else {
                if (in_array($filialId, $idsUnicos, true)) {
                    $total++;
                }
            }
        }

        return response()->json($total, 200);
    }

    public function countProdutos_ok(Request $request)
    {
        // 1) Pega filial_id do request ou, se não vier, do local_padrao na sessão
        $filialIdRaw = $request->has('filial_id')
            ? $request->filial_id
            : session('user_logged.local_padrao');

        // 2) Normaliza: '-1', '' ou null são Matriz
        $isMatrix = ($filialIdRaw === null || $filialIdRaw === '' || $filialIdRaw === '-1');
        $filialId  = $isMatrix ? null : (string) intval($filialIdRaw);

        $total = 0;

        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();

        foreach ($produtos as $p) {
            // Decodifica JSON de locais
            $raw     = $p->locais;
            $decoded = @json_decode($raw, true);
            if (!is_array($decoded)) {
                // tenta consertar JSON com aspas simples
                $decoded = json_decode(str_replace("'", '"', $raw), true) ?: [];
            }

            // Normaliza os IDs únicos
            $idsUnicos = [];
            $hasMatrix = false;
            foreach ($decoded as $loc) {
                if ($loc === null || $loc === '-1' || $loc === '') {
                    $hasMatrix = true;
                } else {
                    $idsUnicos[] = (string) intval($loc);
                }
            }
            // Se não houver locais ou tiver sentinel, inclui 'MATRIZ'
            if ($hasMatrix || empty($idsUnicos)) {
                $idsUnicos[] = 'MATRIZ';
            }
            $idsUnicos = array_unique($idsUnicos);

            // Verifica pertencimento e conta
            if ($isMatrix) {
                if (in_array('MATRIZ', $idsUnicos, true)) {
                    $total++;
                }
            } else {
                if (in_array($filialId, $idsUnicos, true)) {
                    $total++;
                }
            }
        }

        return response()->json($total, 200);
    }

    private function totalizacao(){
        $totalDeProdutos = Produto::
        where('empresa_id', $this->empresa_id)
            // ->groupBy('referencia_grade')
            ->count();

        $totalDeClientes = Cliente::where('empresa_id', $this->empresa_id)->count();

        return [
            'totalDeClientes' => $totalDeClientes,
            'totalDeProdutos' => $totalDeProdutos,
            'totalDeVendas' => $this->totalDeVendasHoje(),
            'totalDePedidos' => $this->totalDePedidosDeDeliveryHoje(),
            'totalDeContaReceber' => $this->totalDeContaReceberHoje(),
            'totalDeContaPagar' => $this->totalDeContaPagarHoje(),
        ];
    }

    private function totalDeVendasHoje()
    {
        // 1) Normaliza filial padrão da sessão
        $padrao = Session::get('user_logged.local_padrao', null);
        if ((int) $padrao <= 0) {
            $padrao = null; // Matriz
        }

        // 2) Soma Vendas
        $vendas = Venda::selectRaw('SUM(valor_total) as total')
            ->whereBetween('created_at', [
                date('Y-m-d') . ' 00:00:00',
                date('Y-m-d') . ' 23:59:59',
            ])
            ->where('empresa_id', $this->empresa_id)
            // 3) Filtra pela filial, se não for Matriz
            ->when($padrao !== null, function ($q) use ($padrao) {
                $q->where('filial_id', $padrao);
            })
            ->first();

        // 4) Soma VendaCaixas
        $vendaCaixas = VendaCaixa::selectRaw('SUM(valor_total) as total')
            ->whereBetween('created_at', [
                date('Y-m-d') . ' 00:00:00',
                date('Y-m-d') . ' 23:59:59',
            ])
            ->where('empresa_id', $this->empresa_id)
            ->when($padrao !== null, function ($q) use ($padrao) {
                $q->where('filial_id', $padrao);
            })
            ->first();

        // 5) Retorna o total combinado
        return ($vendas->total ?? 0) + ($vendaCaixas->total ?? 0);
    }
    private function totalDeVendasHoje_02072025_bkp_2(Request $request)
    {
        // 1) Normaliza filial_id (sentinel matriz → null)
        $raw = $request->filial_id;
        if ($raw === null || $raw === '' || intval($raw) < 1) {
            $filialId = null;
        } else {
            $filialId = intval($raw);
        }

        // 2) Período de hoje
        $start = Carbon::today()->startOfDay();
        $end   = Carbon::today()->endOfDay();

        // 3) Soma vendas “normais”
        $vendas = DB::table('vendas')
            ->selectRaw('COALESCE(SUM(valor_total), 0) as total')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('created_at', [$start, $end])
            ->when(is_null($filialId), function($q) {
                // Matriz = filial_id IS NULL
                $q->whereNull('filial_id');
            }, function($q) use ($filialId) {
                // Caso contrário, filial específica
                $q->where('filial_id', $filialId);
            })
            ->first();

        // 4) Soma vendas de caixa (PDV)
        $vendaCaixas = DB::table('venda_caixas')
            ->selectRaw('COALESCE(SUM(valor_total), 0) as total')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('created_at', [$start, $end])
            ->when(is_null($filialId), function($q) {
                $q->whereNull('filial_id');
            }, function($q) use ($filialId) {
                $q->where('filial_id', $filialId);
            })
            ->first();

        // 5) Retorna o total combinado
        return ($vendas->total ?? 0) + ($vendaCaixas->total ?? 0);
    }

    private function totalDeVendasHoje_02072025_bkp(){
        $vendas = Venda::
        select(\DB::raw('sum(valor_total) as total'))
            ->whereBetween('created_at', [
                date('Y-m-d') . " 00:00:00",
                date('Y-m-d') . " 23:59:59"
            ])
            ->where('empresa_id', $this->empresa_id)
            ->first();

        $vendaCaixas = VendaCaixa::
        select(\DB::raw('sum(valor_total) as total'))
            ->whereBetween('created_at', [
                date('Y-m-d') . " 00:00:00",
                date('Y-m-d') . " 23:59:59"
            ])
            ->where('empresa_id', $this->empresa_id)
            ->first();

        return $vendas->total + $vendaCaixas->total;

    }

    private function totalDePedidosDeDeliveryHoje()
    {
        // 1) Captura filial padrão da sessão (se existir e maior que zero)
        $padrao = session('user_logged.local_padrao', null);
        $filial  = ((int)$padrao > 0) ? (int)$padrao : null;

        // 2) Define o intervalo para "hoje" (inclusive inicio e fim de dia)
        $hoje       = Carbon::today();
        $amanha     = $hoje->copy()->addDay();
        $dataInicio = $hoje->toDateString();
        $dataFim    = $amanha->toDateString();

        // 3) Consulta contando pedidos de delivery
        $pedidos = PedidoDelivery::query()
            ->selectRaw('count(*) as linhas')
            ->whereBetween('data_registro', [$dataInicio, $dataFim])
            ->where('empresa_id', $this->empresa_id)
            ->when($filial !== null, function ($q) use ($filial) {
                $q->where('filial_id', $filial);
            })
            ->first();

        return (int)$pedidos->linhas;
    }

    private function totalDePedidosDeDeliveryHoje_02072025_bkp(){
        $pedidos = PedidoDelivery::
        select(\DB::raw('count(*) as linhas'))
            ->whereBetween('data_registro', [date("Y-m-d"),
                date('Y-m-d', strtotime('+1 day'))])
            ->first();
        return $pedidos->linhas;
    }

    private function totalDeContaReceberHoje()
    {
        // 1) Captura filial padrão da sessão (null = matriz)
        $padrao = session('user_logged.local_padrao', null);
        $filial  = ((int)$padrao > 0) ? (int)$padrao : null;

        // 2) Define intervalo “hoje”
        $hoje   = Carbon::today()->toDateString();
        $amanha = Carbon::tomorrow()->toDateString();

        // 3) Monta consulta
        $contas = ContaReceber::query()
            ->selectRaw('sum(valor_integral) as total')
            ->whereBetween('data_vencimento', [$hoje, $amanha])
            ->where('status', false)
            ->where('empresa_id', $this->empresa_id)
            ->when($filial !== null, function ($q) use ($filial) {
                $q->where('filial_id', $filial);
            })
            ->first();

        // 4) Se usuário não tem acesso financeiro, retorna zero
        if ($this->acesso_financeiro == 0) {
            return 0;
        }

        // 5) Retorna total (ou zero, se nulo)
        return $contas->total ?? 0;
    }

    private function totalDeContaReceberHoje_02072025_bkp(){
        $contas = ContaReceber::
        select(\DB::raw('sum(valor_integral) as total'))
            ->whereBetween('data_vencimento', [date("Y-m-d"),
                date('Y-m-d', strtotime('+1 day'))])
            ->where('status', false)
            ->where('empresa_id', $this->empresa_id)
            ->first();
        if($this->acesso_financeiro == 0) return 0;
        return $contas->total ?? 0;
    }

    private function totalDeContaPagarHoje()
    {
        // 1) Captura filial padrão da sessão (null = matriz)
        $padrao = session('user_logged.local_padrao', null);
        $filial  = ((int)$padrao > 0) ? (int)$padrao : null;

        // 2) Define intervalo “hoje”
        $hoje   = Carbon::today()->toDateString();
        $amanha = Carbon::tomorrow()->toDateString();

        // 3) Monta consulta
        $contas = ContaPagar::query()
            ->selectRaw('sum(valor_integral) as total')
            ->whereBetween('data_vencimento', [$hoje, $amanha])
            ->where('status', false)
            ->where('empresa_id', $this->empresa_id)
            ->when($filial !== null, function ($q) use ($filial) {
                $q->where('filial_id', $filial);
            })
            ->first();

        // 4) Se usuário não tem acesso financeiro, retorna zero
        if ($this->acesso_financeiro == 0) {
            return 0;
        }

        // 5) Retorna total (ou zero, se nulo)
        return $contas->total ?? 0;
    }

    private function totalDeContaPagarHoje_02072025_bkp(){
        $contas = ContaPagar::
        select(\DB::raw('sum(valor_integral) as total'))
            ->whereBetween('data_vencimento', [date("Y-m-d"),
                date('Y-m-d', strtotime('+1 day'))])
            ->where('status', false)
            ->where('empresa_id', $this->empresa_id)
            ->first();
        if($this->acesso_financeiro == 0) return 0;
        return $contas->total ?? 0;
    }

    public function faturamentoDosUltimosSeteDias(Request $request)
    {
        // 1) Normaliza filial: prioriza request, depois local_padrão, senão null
        $filial = null;
        if ($request->has('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        $arrayVendas = [];

        // 2) Para cada um dos últimos 7 dias
        for ($offset = 0; $offset > -7; $offset--) {
            $start = date('Y-m-d', strtotime("$offset day")) . ' 00:00:00';
            $end   = date('Y-m-d', strtotime(($offset + 1) . ' day')) . ' 00:00:00';

            // Vendas “normais”
            $vendas = Venda::selectRaw('SUM(valor_total) as total')
                ->whereBetween('data_registro', [$start, $end])
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, function ($q) use ($filial) {
                    $q->where('filial_id', $filial);
                })
                ->first();

            // Vendas no caixa
            $vendaCaixas = VendaCaixa::selectRaw('SUM(valor_total) as total')
                ->whereBetween('data_registro', [$start, $end])
                ->where('empresa_id', $this->empresa_id)
                ->when($filial !== null, function ($q) use ($filial) {
                    $q->where('filial_id', $filial);
                })
                ->first();

            // Soma dos dois (cast para float, caso venha string)
            $total = (float) $vendas->total + (float) $vendaCaixas->total;

            $arrayVendas[] = [
                'data'  => date('d/m', strtotime("$offset day")),
                'total' => number_format($total, 2, '.', ''),
            ];
        }

        // Se não tem acesso financeiro, retorna array vazio
        if ($this->acesso_financeiro == 0) {
            return response()->json([], 200);
        }

        // Inverte para ordem cronológica e retorna
        return response()->json(array_reverse($arrayVendas), 200);
    }

    public function faturamentoDosUltimosSeteDias_02072025_bkp(Request $request){

        $arrayVendas = [];
        $filial_id = $request->filial_id;
        $filial_id = $filial_id == -1 ? null : $filial_id;
        for($aux = 0; $aux > -7; $aux--){
            $vendas = Venda::
            select(\DB::raw('sum(valor_total) as total'))
                ->whereBetween('data_registro',
                    [
                        date('Y-m-d', strtotime($aux.' day')),
                        date('Y-m-d', strtotime(($aux+1).' day'))
                    ]
                )
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->first();


            $vendaCaixas = VendaCaixa::
            select(\DB::raw('sum(valor_total) as total'))
                ->whereBetween('data_registro',
                    [
                        date('Y-m-d', strtotime($aux.' day')),
                        date('Y-m-d', strtotime(($aux+1).' day'))
                    ]
                )
                ->where('empresa_id', $this->empresa_id)
                ->where('filial_id', $filial_id)
                ->first();

            $total = (float)str_replace(",", ".", $vendas->total) + (float)str_replace(",", ".", $vendaCaixas->total);
            $temp = [
                'data' => date('d/m', strtotime(($aux).' day')),
                'total' => number_format($total, 2, ".", "")
            ];
            array_push($arrayVendas, $temp);
        }
        if($this->acesso_financeiro == 0){
            return response()->json(array_reverse([]));
        }
        return response()->json(array_reverse($arrayVendas));

    }

    public function produtosFiltrado(Request $request)
    {
        // 1) Data inicial/final
        $dataInicial = \Carbon\Carbon::parse(str_replace('/', '-', $request->data_inicial))
            ->format('Y-m-d');
        $dataFinal = \Carbon\Carbon::parse(str_replace('/', '-', $request->data_final))
            ->format('Y-m-d');

        // ───────────────────────────────────────────────────────────────────────
        // 2) Filial: vem do request ou do padrão na sessão
        $rawFilial    = $request->has('filial_id')
            ? $request->filial_id
            : session('user_logged.local_padrao');
        // tudo com intval < 1 é Matriz
        $isMatrix     = (intval($rawFilial) < 1);
        $filialId     = $isMatrix
            ? null
            : (string) intval($rawFilial);
        // ───────────────────────────────────────────────────────────────────────

        // 3) Busca PDV
        $pdv = ItemVendaCaixa::selectRaw(
            'produtos.nome as nome, sum(item_venda_caixas.quantidade) as qtd, item_venda_caixas.produto_id'
        )
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->join('produtos',       'produtos.id',       '=', 'item_venda_caixas.produto_id')
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->whereDate('item_venda_caixas.created_at', '>=', $dataInicial)
            ->whereDate('item_venda_caixas.created_at', '<=', $dataFinal)
            // só filtra por filial se não for Matriz
            ->when(! $isMatrix, function($q) use($filialId) {
                $q->where('venda_caixas.filial_id', $filialId);
            })
            ->groupBy('item_venda_caixas.produto_id')
            ->orderBy('qtd', 'desc')
            ->limit(15)
            ->get();

        // 4) Busca Vendas
        $vendas = ItemVenda::selectRaw(
            'produtos.nome as nome, sum(item_vendas.quantidade) as qtd, item_vendas.produto_id'
        )
            ->join('vendas',   'vendas.id',   '=', 'item_vendas.venda_id')
            ->join('produtos', 'produtos.id', '=', 'item_vendas.produto_id')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->whereDate('item_vendas.created_at', '>=', $dataInicial)
            ->whereDate('item_vendas.created_at', '<=', $dataFinal)
            // só filtra por filial se não for Matriz
            ->when(! $isMatrix, function($q) use($filialId) {
                $q->where('vendas.filial_id', $filialId);
            })
            ->groupBy('item_vendas.produto_id')
            ->orderBy('qtd', 'desc')
            ->limit(15)
            ->get();

        // 5) Merge mantendo ordem e evitando duplicados
        $merged = collect();
        foreach ([$vendas, $pdv] as $list) {
            foreach ($list as $item) {
                if (! $merged->pluck('produto_id')->contains($item->produto_id)) {
                    $merged->push($item);
                }
            }
        }

        // 6) Filtra pelos "locais" de cada produto
        $result = [];
        foreach ($merged as $item) {
            $produto = Produto::find($item->produto_id);
            if (! $produto) continue;

            // Decodifica JSON de locais (com fallback para aspas simples)
            $rawLocais = $produto->locais;
            $arr       = @json_decode($rawLocais, true);
            if (! is_array($arr)) {
                $arr = json_decode(str_replace("'", '"', $rawLocais), true) ?: [];
            }

            // Normaliza IDs únicos + flag de Matriz
            $ids       = [];
            $hasMatrix = false;
            foreach ($arr as $loc) {
                if (intval($loc) < 1) {
                    $hasMatrix = true;
                } else {
                    $ids[] = (string) intval($loc);
                }
            }
            if ($hasMatrix || empty($ids)) {
                $ids[] = 'MATRIZ';
            }
            $ids = array_unique($ids);

            // Conta só se pertence ao local selecionado
            if (
                ($isMatrix    && in_array('MATRIZ', $ids, true)) ||
                (! $isMatrix && in_array($filialId, $ids, true))
            ) {
                $result[] = [
                    'data'  => $item->nome,
                    'total' => $item->qtd,
                ];
            }
        }

        // 7) Retorna os 15 itens mesclados e filtrados
        return response()->json(array_reverse($result), 200);
    }

    public function produtosFiltrado_ok(Request $request)
    {
        // 1) Data inicial/final
        $dataInicial = \Carbon\Carbon::parse(str_replace('/', '-', $request->data_inicial))
            ->format('Y-m-d');
        $dataFinal = \Carbon\Carbon::parse(str_replace('/', '-', $request->data_final))
            ->format('Y-m-d');

        // 2) Normaliza filial_id (>=1 → filial; -1, "", null → Matriz)
        $raw = $request->filial_id;
        $isMatrix = ($raw === null || $raw === '' || intval($raw) < 1);
        $filialId = $isMatrix
            ? null
            : (string) intval($raw);

        // 3) Busca PDV
        $pdv = ItemVendaCaixa::selectRaw(
            'produtos.nome as nome, sum(item_venda_caixas.quantidade) as qtd, item_venda_caixas.produto_id'
        )
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->join('produtos',       'produtos.id',       '=', 'item_venda_caixas.produto_id')
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->whereDate('item_venda_caixas.created_at', '>=', $dataInicial)
            ->whereDate('item_venda_caixas.created_at', '<=', $dataFinal)
            ->when(!$isMatrix && $filialId !== null, function($q) use($filialId) {
                $q->where('venda_caixas.filial_id', $filialId);
            })
            ->groupBy('item_venda_caixas.produto_id')
            ->orderBy('qtd', 'desc')
            ->limit(15)
            ->get();

        // 4) Busca Vendas
        $vendas = ItemVenda::selectRaw(
            'produtos.nome as nome, sum(item_vendas.quantidade) as qtd, item_vendas.produto_id'
        )
            ->join('vendas',   'vendas.id',   '=', 'item_vendas.venda_id')
            ->join('produtos', 'produtos.id', '=', 'item_vendas.produto_id')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->whereDate('item_vendas.created_at', '>=', $dataInicial)
            ->whereDate('item_vendas.created_at', '<=', $dataFinal)
            ->when(!$isMatrix && $filialId !== null, function($q) use($filialId) {
                $q->where('vendas.filial_id', $filialId);
            })
            ->groupBy('item_vendas.produto_id')
            ->orderBy('qtd', 'desc')
            ->limit(15)
            ->get();

        // 5) Merge mantendo ordem e evitando duplicados
        $merged = collect();
        foreach ([$vendas, $pdv] as $list) {
            foreach ($list as $item) {
                if (! $merged->pluck('produto_id')->contains($item->produto_id)) {
                    $merged->push($item);
                }
            }
        }

        // 6) Filtra por “locais” do próprio produto
        $result = [];
        foreach ($merged as $item) {
            $produto = Produto::find($item->produto_id);
            if (! $produto) {
                continue;
            }

            // Decodifica e corrige JSON de locais
            $rawLocais = $produto->locais;
            $arr = @json_decode($rawLocais, true);
            if (! is_array($arr)) {
                $arr = json_decode(str_replace("'", '"', $rawLocais), true) ?: [];
            }

            // Normaliza em IDs únicos e flag de matriz
            $ids = [];
            $hasMatrix = false;
            foreach ($arr as $loc) {
                if ($loc === null || $loc === '-1' || $loc === '') {
                    $hasMatrix = true;
                } else {
                    $ids[] = (string) intval($loc);
                }
            }
            if ($hasMatrix || empty($ids)) {
                $ids[] = 'MATRIZ';
            }
            $ids = array_unique($ids);

            // Verifica pertencimento
            if (
                ($isMatrix    && in_array('MATRIZ', $ids, true)) ||
                (! $isMatrix && in_array($filialId, $ids, true))
            ) {
                $result[] = [
                    'data'  => $item->nome,
                    'total' => $item->qtd,
                ];
            }
        }

        // 7) Retorna no mesmo formato (array_reverse se precisar)
        return response()->json(array_reverse($result), 200);
    }

    public function produtosFiltrado_02072025_bkp(Request $request){

        $dataInicial = \Carbon\Carbon::parse(str_replace("/", "-", $request->data_inicial))->format('Y-m-d');
        $dataFinal = \Carbon\Carbon::parse(str_replace("/", "-", $request->data_final))->format('Y-m-d');
        $data = [];

        $filial_id = ($request->filial_id && $request->filial_id >= 1) ? $request->filial_id : null;

        $itensVendaCaixa = ItemVendaCaixa::
        selectRaw('produtos.nome as nome, sum(quantidade) as qtd, item_venda_caixas.produto_id as produto_id')
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
            ->where('venda_caixas.empresa_id', $this->empresa_id)
            ->groupBy('produto_id')
            ->orderBy('qtd')
            ->whereDate('item_venda_caixas.created_at', '>=', $dataInicial)
            ->whereDate('item_venda_caixas.created_at', '<=', $dataFinal)
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('venda_caixas.filial_id', $filial_id);
            })
            ->limit(15)
            ->get();

        $itensVenda = ItemVenda::
        selectRaw('produtos.nome as nome, sum(quantidade) as qtd, item_vendas.produto_id as produto_id')
            ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
            ->join('produtos', 'produtos.id', '=', 'item_vendas.produto_id')
            ->where('vendas.empresa_id', $this->empresa_id)
            ->groupBy('produto_id')
            ->orderBy('qtd')
            ->whereDate('item_vendas.created_at', '>=', $dataInicial)
            ->whereDate('item_vendas.created_at', '<=', $dataFinal)
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('vendas.filial_id', $filial_id);
            })
            ->limit(15)
            ->get();

        $ids = [];
        foreach($itensVenda as $i){
            if(!in_array($i->produto_id, $ids)){
                array_push($ids, $i->produto_id);
                array_push($data, [
                    'data' => $i->nome,
                    'total' => $i->qtd
                ]);
            }
        }

        foreach($itensVendaCaixa as $i){
            if(!in_array($i->produto_id, $ids)){
                array_push($ids, $i->produto_id);
                array_push($data, [
                    'data' => $i->nome,
                    'total' => $i->qtd
                ]);
            }
        }

        return response()->json(array_reverse($data));

    }

    public function faturamentoFiltrado(Request $request)
    {
        // 1) Normaliza filial: prioriza request, depois local_padrão, senão null (matriz)
        $filial = null;
        if ($request->has('filial_id') && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Converte datas iniciais/finais
        $dataInicial = Carbon::createFromFormat('d/m/Y', $request->data_inicial)->startOfDay();
        $dataFinal   = Carbon::createFromFormat('d/m/Y', $request->data_final)->endOfDay();

        $dias = $dataInicial->diffInDays($dataFinal) + 1;
        $arrayVendas = [];

        if ($dias > 30) {
            // --- agrupamento por MÊS ---
            // vamos usar um mapa para acumular por mês
            $map = [];
            for ($dt = $dataInicial->copy(); $dt->lte($dataFinal); $dt->addDay()) {
                $key = $dt->format('m/Y');

                // buscamos o total daquele DIA (vai somar depois no mês)
                $v1 = Venda::selectRaw('SUM(valor_total) as total')
                    ->whereBetween('data_registro', [
                        $dt->toDateString() . ' 00:00:00',
                        $dt->toDateString() . ' 23:59:59',
                    ])
                    ->where('empresa_id', $this->empresa_id)
                    ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                    ->value('total') ?: 0;

                $v2 = VendaCaixa::selectRaw('SUM(valor_total) as total')
                    ->whereBetween('data_registro', [
                        $dt->toDateString() . ' 00:00:00',
                        $dt->toDateString() . ' 23:59:59',
                    ])
                    ->where('empresa_id', $this->empresa_id)
                    ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                    ->value('total') ?: 0;

                $map[$key] = ($map[$key] ?? 0) + ((float)$v1 + (float)$v2);
            }

            // monta o array final
            foreach ($map as $mes => $total) {
                $arrayVendas[] = [
                    'data'  => $mes,
                    'total' => number_format($total, 2, '.', '')
                ];
            }

        } else {
            // --- agrupamento por DIA ---
            for ($dt = $dataInicial->copy(); $dt->lte($dataFinal); $dt->addDay()) {
                $v1 = Venda::selectRaw('SUM(valor_total) as total')
                    ->whereBetween('data_registro', [
                        $dt->toDateString() . ' 00:00:00',
                        $dt->toDateString() . ' 23:59:59',
                    ])
                    ->where('empresa_id', $this->empresa_id)
                    ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                    ->value('total') ?: 0;

                $v2 = VendaCaixa::selectRaw('SUM(valor_total) as total')
                    ->whereBetween('data_registro', [
                        $dt->toDateString() . ' 00:00:00',
                        $dt->toDateString() . ' 23:59:59',
                    ])
                    ->where('empresa_id', $this->empresa_id)
                    ->when($filial !== null, fn($q) => $q->where('filial_id', $filial))
                    ->value('total') ?: 0;

                $arrayVendas[] = [
                    'data'  => $dt->format('d/m'),
                    'total' => number_format((float)$v1 + (float)$v2, 2, '.', '')
                ];
            }
        }

        // se não tem acesso financeiro, não mostra nada
        if ($this->acesso_financeiro == 0) {
            return response()->json([], 200);
        }

        // já está em ordem crescente (do início ao fim do período)
        return response()->json($arrayVendas, 200);
    }

    public function faturamentoFiltrado_02072025_bkp(Request $request){

        $dataInicial = strtotime(str_replace("/", "-", $request->data_inicial));
        $dataFinal = strtotime(str_replace("/", "-", $request->data_final));

        $diferenca = ($dataFinal - $dataInicial)/86400; //86400 segundos do dia

        $arrayVendas = [];
        $filial_id = $request->filial_id;
        $filial_id = $filial_id == -1 ? null : $filial_id;

        if($diferenca+1 > 30){ //filtrar por mes

            $total = 0;
            for($aux = 0; $aux > (($diferenca+1)*-1); $aux--){
                $vendas = Venda::
                select(\DB::raw('sum(valor_total) as total'))
                    ->whereBetween('data_registro',
                        [
                            date('Y-m-d', strtotime($aux.' day')),
                            date('Y-m-d', strtotime(($aux+1).' day'))
                        ]
                    )
                    ->where('filial_id', $filial_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();


                $vendaCaixas = VendaCaixa::
                select(\DB::raw('sum(valor_total) as total'))
                    ->whereBetween('data_registro',
                        [
                            date('Y-m-d', strtotime($aux.' day')),
                            date('Y-m-d', strtotime(($aux+1).' day'))
                        ]
                    )
                    ->where('filial_id', $filial_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();

                if($this->confereMesNoArray($arrayVendas, date('m/Y', strtotime(($aux).' day')))){
                    $cont = 0;
                    foreach($arrayVendas as $arr){
                        if($arr['data'] == date('m/Y', strtotime(($aux).' day'))){
                            $arrayVendas[$cont]['total'] += $vendas->total + $vendaCaixas->total;

                        }
                        $cont++;
                    }
                }else{
                    $temp = [
                        'data' => date('m/Y', strtotime(($aux).' day')),
                        'total' => number_format($vendas->total + $vendaCaixas->total, 2, '.', '')
                    ];
                    array_push($arrayVendas, $temp);
                }

            }

        }else{ //filtro por dia
            for($aux = 0; $aux > (($diferenca+1)*-1); $aux--){
                $vendas = Venda::
                select(\DB::raw('sum(valor_total) as total'))
                    ->whereBetween('data_registro',
                        [
                            date('Y-m-d', strtotime($aux.' day')),
                            date('Y-m-d', strtotime(($aux+1).' day'))
                        ]
                    )
                    ->where('filial_id', $filial_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();


                $vendaCaixas = VendaCaixa::
                select(\DB::raw('sum(valor_total) as total'))
                    ->whereBetween('data_registro',
                        [
                            date('Y-m-d', strtotime($aux.' day')),
                            date('Y-m-d', strtotime(($aux+1).' day'))
                        ]
                    )
                    ->where('filial_id', $filial_id)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();
                $temp = [
                    'data' => date('d/m', strtotime(($aux).' day')),
                    'total' => number_format(($vendas->total + $vendaCaixas->total), 2, '.', '')
                ];
                array_push($arrayVendas, $temp);
            }
        }
        if($this->acesso_financeiro == 0){
            return response()->json(array_reverse([]));
        }
        return response()->json(array_reverse($arrayVendas));

    }

    private function confereMesNoArray($arr, $mes){
        foreach($arr as $a){
            if($a['data'] == $mes) return true;
        }
        return false;
    }

    private function totalDeVendasDias($dias, $filial_id)
    {
        // 1) Normaliza filial do parâmetro ou pega o padrão da sessão
        $filial = $filial_id;
        if (! $filial || (int) $filial <= 0) {
            $filial = Session::get('user_logged.local_padrao', null);
        }
        // valores inválidos viram null (Matriz)
        if ((int) $filial <= 0) {
            $filial = null;
        }

        // 2) Monta o callback para o filtro de filial
        $filialFilter = function ($query) use ($filial) {
            if ($filial === null) {
                return $query->whereNull('filial_id');
            }
            return $query->where('filial_id', $filial);
        };

        // 3) Consulta em vendas
        $vendas = Venda::selectRaw('SUM(valor_total) as total')
            ->when($dias == 1, function ($q) {
                $q->whereDate('created_at', date('Y-m-d'));
            })
            ->when($dias == 7, function ($q) {
                // última semana completa
                $sem = date('W') - 1;
                $q->whereRaw("WEEK(created_at) = {$sem}");
            })
            ->when($dias == 30, function ($q) {
                $q->whereMonth('created_at', date('m'));
            })
            ->where('empresa_id', $this->empresa_id)
            ->tap($filialFilter)
            ->first();

        // 4) Consulta em venda_caixas
        $vendaCaixas = VendaCaixa::selectRaw('SUM(valor_total) as total')
            ->when($dias == 1, function ($q) {
                $q->whereDate('created_at', date('Y-m-d'));
            })
            ->when($dias == 7, function ($q) {
                $sem = date('W') - 1;
                $q->whereRaw("WEEK(created_at) = {$sem}");
            })
            ->when($dias == 30, function ($q) {
                $q->whereMonth('created_at', date('m'));
            })
            ->where('empresa_id', $this->empresa_id)
            ->tap($filialFilter)
            ->first();

        // 5) Formata a soma (se for null, considera zero)
        $totalVendas      = $vendas->total      ?? 0;
        $totalVendaCaixas = $vendaCaixas->total ?? 0;
        $soma             = $totalVendas + $totalVendaCaixas;

        return number_format($soma, 2, ',', '.');
    }

    private function totalDeVendasDias_02072025_bkp($dias, $filial_id){
        $vendas = Venda::
        select(\DB::raw('sum(valor_total) as total'))
            // ->whereBetween('created_at', [
            //     date('Y-m-d', strtotime("-$dias days")),
            //     date('Y-m-d', strtotime('+1 day'))
            // ])
            ->when($dias == 1, function ($query) {
                return $query->whereDate('created_at', date('Y-m-d'));
            })
            ->when($dias == 7, function ($query) {
                return $query->whereRaw('WEEK(created_at) = ' . (date('W')-1));
            })
            ->when($dias == 30, function ($query) {
                return $query->whereMonth('created_at', date('m'));
            })
            ->where('filial_id', $filial_id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        $vendaCaixas = VendaCaixa::
        select(\DB::raw('sum(valor_total) as total'))
            // ->whereBetween('created_at', [
            //     date('Y-m-d', strtotime("-$dias days")),
            //     date('Y-m-d', strtotime('+1 day'))
            // ])
            ->when($dias == 1, function ($query) {
                return $query->whereDate('created_at', date('Y-m-d'));
            })
            ->when($dias == 7, function ($query) {
                return $query->whereRaw('WEEK(created_at) = ' . (date('W')-1));
            })
            ->when($dias == 30, function ($query) {
                return $query->whereMonth('created_at', date('m'));
            })
            ->where('filial_id', $filial_id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        return number_format($vendas->total + $vendaCaixas->total, 2, ',', '.');

    }

    private function totalDePedidosDeDeliveryDias($dias, $filial_id)
    {
        // 1) Normaliza filial do parâmetro ou pega o padrão da sessão
        $filial = $filial_id;
        if (! $filial || (int) $filial <= 0) {
            $filial = Session::get('user_logged.local_padrao', null);
        }
        // valores inválidos viram null (Matriz)
        if ((int) $filial <= 0) {
            $filial = null;
        }

        // 2) Calcula intervalo de datas (começa $dias atrás até hoje)
        $dataInicio = date('Y-m-d', strtotime("-{$dias} days"));
        // incluímos o dia de hoje inteiro
        $dataFim    = date('Y-m-d', strtotime('+1 day'));

        // 3) Monta a query
        $query = PedidoDelivery::selectRaw('COUNT(*) as linhas')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_registro', [$dataInicio, $dataFim]);

        // 4) Aplica filtro de filial / Matriz
        if ($filial === null) {
            $query->whereNull('filial_id');
        } else {
            $query->where('filial_id', $filial);
        }

        // 5) Executa e retorna
        $pedidos = $query->first();

        return (int) ($pedidos->linhas ?? 0);
    }

    private function totalDePedidosDeDeliveryDias_02072025_bkp($dias, $filial_id){
        $pedidos = PedidoDelivery::
        select(\DB::raw('count(*) as linhas'))
            ->whereBetween('data_registro', [
                    date('Y-m-d', strtotime("-$dias days")),
                    date('Y-m-d', strtotime('+1 day'))]
            )
            ->first();
        return $pedidos->linhas;
    }

    private function totalDeContaReceberDias($dias, $filial_id)
    {
        // 1) Se o usuário não tem acesso financeiro, já retorna 0
        if ($this->acesso_financeiro == 0) {
            return 0;
        }

        // 2) Normaliza filial do parâmetro ou pega o padrão da sessão
        $filial = $filial_id;
        if (! $filial || (int) $filial <= 0) {
            $filial = session('user_logged.local_padrao', null);
        }
        if ((int) $filial <= 0) {
            $filial = null; // Matriz
        }

        // 3) Datas: de hoje até hoje + $dias
        $inicio = date('Y-m-d');
        $fim    = date('Y-m-d', strtotime("+{$dias} days"));

        // 4) Monta a query
        $contas = ContaReceber::selectRaw('SUM(valor_integral) as total')
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->where('empresa_id', $this->empresa_id)
            ->when(
                is_null($filial),
                fn($q) => $q->whereNull('filial_id'),
                fn($q) => $q->where('filial_id', $filial)
            )
            ->where('status', false)
            ->first();

        // 5) Formata e retorna
        $total = $contas->total ?? 0;
        return number_format($total, 2, ',', '.');
    }

    private function totalDeContaReceberDias_02072025_bkp($dias, $filial_id){
        $contas = ContaReceber::
        select(\DB::raw('sum(valor_integral) as total'))
            ->whereBetween('data_vencimento', [
                date('Y-m-d'),
                date('Y-m-d', strtotime("+$dias days"))
            ])
            ->where('filial_id', $filial_id)
            ->where('status', false)
            ->where('empresa_id', $this->empresa_id)
            ->first();
        if($this->acesso_financeiro == 0) return 0;
        return $contas->total ? number_format($contas->total, 2, ',', '.') : 0;
    }

    private function totalDeContaPagarDias($dias, $filial_id)
    {
        // 1) Se o usuário não tem acesso financeiro, retorna 0 imediatamente
        if ($this->acesso_financeiro == 0) {
            return 0;
        }

        // 2) Normaliza filial: se veio <= 0 ou null, pega o local_padrão da sessão
        $filial = $filial_id;
        if (! $filial || (int) $filial <= 0) {
            $filial = session('user_logged.local_padrao', null);
        }
        if ((int) $filial <= 0) {
            $filial = null; // interpreta como Matriz
        }

        // 3) Intervalo de datas com horário
        $inicio = date('Y-m-d') . ' 00:00:00';
        $fim    = date('Y-m-d', strtotime("+{$dias} days")) . ' 23:59:59';

        // 4) Monta a query, sempre filtrando empresa_id + filial (ou null)
        $contas = ContaPagar::selectRaw('SUM(valor_integral) AS total')
            ->whereBetween('data_vencimento', [$inicio, $fim])
            ->where('empresa_id', $this->empresa_id)
            ->when(
                is_null($filial),
                fn($q) => $q->whereNull('filial_id'),
                fn($q) => $q->where('filial_id', $filial)
            )
            ->where('status', false)
            ->first();

        // 5) Formata e retorna
        $total = $contas->total ?? 0;
        return number_format($total, 2, ',', '.');
    }

    private function totalDeContaPagarDias_02072025_bkp($dias, $filial_id){
        if($filial_id == -1){
            $filial_id = null;
        }
        $contas = ContaPagar::
        select(\DB::raw('sum(valor_integral) as total'))
            ->whereBetween('data_vencimento', [
                date('Y-m-d') . " 00:00:00",
                date('Y-m-d', strtotime("+$dias days")). " 23:59:00"
            ])
            ->where('filial_id', $filial_id)
            ->where('status', false)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if($this->acesso_financeiro == 0){
            return 0;
        }
        return $contas->total ? number_format($contas->total, 2, ',', '.') : 0;
    }

    public function boxConsulta(Request $request)
    {
        $dias = $request->dias;

        // 1) Normaliza filial: se veio >0, usa; senão tenta o local_padrão; senão null (matriz)
        $filial = null;
        if (isset($request->filial_id) && (int)$request->filial_id > 0) {
            $filial = (int)$request->filial_id;
        } else {
            $padrao = session('user_logged.local_padrao', null);
            if (isset($padrao) && (int)$padrao > 0) {
                $filial = (int)$padrao;
            }
        }

        // 2) Monta o array de retorno chamando cada método com o filial normalizado
        $data = [
            'totalDeVendas'        => $this->totalDeVendasDias($dias, $filial),
            'totalDePedidos'       => $this->totalDePedidosDeDeliveryDias($dias, $filial),
            'totalDeContaReceber'  => $this->totalDeContaReceberDias($dias, $filial),
            'totalDeContaPagar'    => $this->totalDeContaPagarDias($dias, $filial),
        ];

        return response()->json($data, 200);
    }

    public function boxConsulta_02072025_bkp(Request $request){
        $dias = $request->dias;
        $filial_id = $request->filial_id;
        $filial_id = $filial_id == -1 ? null : $filial_id;
        $data = [
            'totalDeVendas' => $this->totalDeVendasDias($dias, $filial_id),
            'totalDePedidos' => $this->totalDePedidosDeDeliveryDias($dias, $filial_id),
            'totalDeContaReceber' => $this->totalDeContaReceberDias($dias, $filial_id),
            'totalDeContaPagar' => $this->totalDeContaPagarDias($dias, $filial_id)
        ];

        return response()->json($data, 200);
    }


    public function all(){
        $avisos = Aviso::limit(10)
            ->where('status', 1)
            ->get();

        $temp = [];
        foreach($avisos as $a){
            $avisoAcesso = AvisoAcesso
                ::where('empresa_id', $this->empresa_id)
                ->where('aviso_id', $a->id)
                ->exists();

            if(!$avisoAcesso){
                array_push($temp, $a);
            }
        }
        $avisos = $temp;

        $view = view('alertas/linhas', compact('avisos'))->render();

        $data = [
            'view' => $view,
            'size' => sizeof($avisos)
        ];
        return response()->json($data, 200);
    }

    public function avisoView($id){

        $temp = AvisoAcesso::
        where('empresa_id', $this->empresa_id)
            ->where('aviso_id', $id)
            ->exists();

        if(!$temp){
            AvisoAcesso::create([
                'empresa_id' => $this->empresa_id,
                'aviso_id' => $id
            ]);
        }
        $item = Aviso::findOrFail($id);
        return view('alertas/view')
            ->with('title', 'Alerta')
            ->with('item', $item);
    }

    public function getPlan(){
        $plan = PlanoEmpresa::
        where('empresa_id', $this->empresa_id)
            ->with('plano')
            ->first();

        $plan->expira = \Carbon\Carbon::parse($plan->expiracao)->format('d/m/Y');
        return response()->json($plan, 200);
    }

}
