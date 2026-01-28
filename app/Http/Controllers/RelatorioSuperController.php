<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plano;
use App\Models\Empresa;
use App\Models\Venda;
use App\Models\VendaCaixa;
use App\Models\Contador;
use App\Models\PlanoEmpresa;
use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\RecordLog;
use App\Models\UsuarioAcesso;
use App\Models\Representante;
use Dompdf\Dompdf;
use NFePHP\Common\Certificate;

class RelatorioSuperController extends Controller
{
	public function __construct(){
		$this->middleware(function ($request, $next) {
			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}

			if(!$value['super']){
				return redirect('/graficos');
			}
			return $next($request);
		});
	}

	public function index(){

		$planos = Plano::all();
		$contadores = Contador::all();
		$empresas = Empresa::all();
		$representantes = Representante::all();
		return view('relatorios_super/index')
		->with('planos', $planos)
		->with('empresas', $empresas)
		->with('representantes', $representantes)
		->with('contadores', $contadores)
		->with('title', 'Relatórios');
	}

	public function empresas(Request $request){
		$empresa = $request->empresa;
		$status = $request->status;
		$plano = $request->plano;

		// if($empresa == "null"){
		// 	session()->flash('mensagem_erro', 'Selecione uma empresa');
		// 	return redirect()->back();
		// }

		$empresas = Empresa::
		select('empresas.*');

		if($empresa != 'null'){
			$empresas->where('empresas.id', $empresa);
		}

		if($plano != 'null'){
			$empresas->join('plano_empresas', 'plano_empresas.empresa_id', '=', 
				'empresas.id');
			$empresas->where('plano_empresas.plano_id', $plano);
		}

		$empresas = $empresas->get();

		if($status != 'TODOS'){
			$temp = [];
			foreach($empresas as $e){
				if($e->status() == $request->status){
					array_push($temp, $e);
				}
				if($request->status == 2){
					if(!$e->planoEmpresa){
						array_push($temp, $e);	
					}
				}
			}
			$empresas = $temp;
		}

		$p = view('relatorios_super/relatorio_empresas')
		->with('empresa', $empresa)
		->with('plano', $plano)
		->with('title', 'Relatório de empresas')
		->with('empresas', $empresas)
		->with('status', $status);

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4", "landscape");
		$domPdf->render();
		$domPdf->stream("Relatório de empresas.pdf", array("Attachment" => false));
	}

	public function planosVencer(Request $request){
		$data_inicial = $request->data_inicial;
		$data_final = $request->data_final;

		$dtInicial = $this->parseDate($data_inicial);
		$dtFinal = $this->parseDate($data_final);

		if(!$data_inicial || !$data_final){
			session()->flash('mensagem_erro', 'Informe o período');
			return redirect()->back();
		}

		$data = PlanoEmpresa::
		whereDate('expiracao', '>=', $dtInicial)
		->whereDate('expiracao', '<=', $dtFinal)
		->get();

		$p = view('relatorios_super/planos_vencer')
		->with('data_inicial', $data_inicial)
		->with('data_final', $data_final)
		->with('data', $data)
		->with('title', 'Relatório de planos à vencer');

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4", "landscape");
		$domPdf->render();
		$domPdf->stream("Relatório de planos à vencer.pdf", array("Attachment" => false));
	}

	public function certificados(Request $request){
		$data_inicial = $request->data_inicial;
		$data_final = $request->data_final;
		$status = $request->status;
		$status_empresa = $request->status_empresa;
		$cpf_cnpj = $request->cpf_cnpj;

		$dataHoje = date('Y-m-d');
		$empresas = Empresa::
		when($cpf_cnpj != '', function ($query) use ($cpf_cnpj) {
			return $query->where('cnpj', $cpf_cnpj);
		})
		->get();

		$temp = [];

		$dtInicial = $this->parseDate($data_inicial);
		$dtFinal = $this->parseDate($data_final);
		foreach($empresas as $e){
			if($e->certificado){
				try{
					$infoCertificado = Certificate::readPfx($e->certificado->arquivo, $e->certificado->senha);
					$publicKey = $infoCertificado->publicKey;

					$e->vencimento = $publicKey->validTo->format('Y-m-d');
					$e->vencido = strtotime($dataHoje) > strtotime($e->vencimento);

					if($data_inicial && $data_final){
						if((strtotime($e->vencimento) > strtotime($dtInicial)) && (strtotime($e->vencimento) < strtotime($dtFinal))){
							array_push($temp, $e);
						}
					}
					else if($status != 'TODOS'){
						if($status == 1 && $e->vencido){
							array_push($temp, $e);
						}elseif($status == 2 && !$e->vencido){
							array_push($temp, $e);
						}
					}else{
						array_push($temp, $e);
					}

					usort($temp, function($a, $b){
						return strtotime($a->vencimento) > strtotime($b->vencimento) ? 1 : 0;
					});
				}catch(\Exception $e){
					
				}
			}	
		}

		if($status_empresa != 'TODOS'){
			$empresas = $temp;
			$temp = [];
			foreach($empresas as $e){
				if($status_empresa == $e->status()){
					array_push($temp, $e);
				}
			}
		}
		// die;

		$p = view('relatorios_super/relatorio_certificados')
		->with('data_inicial', $data_inicial)
		->with('data_final', $data_final)
		->with('empresas', $temp)
		->with('title', 'Relatório de certificados')
		->with('status', $status);

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4", "landscape");
		$domPdf->render();
		$domPdf->stream("Relatório de certificados.pdf", array("Attachment" => false));

	}

	private static function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	public function extrtoCliente(Request $request){

		if($request->empresa == "null"){
			session()->flash('mensagem_erro', 'Selecione uma empresa');
			return redirect()->back();
		}

		$empresa = Empresa::find($request->empresa);

		$acessos = $this->totalizaAcessos($request, $empresa);
		$totalNfe = $this->totalizaNFe($request);
		$totalNfce = $this->totalizaNFCe($request);
		$totalCte = $this->totalizaCTe($request);
		$totalMdfe = $this->totalizaMDFe($request);

		$totalVendas = $this->totalizaVendas($request);
		$totalizaVendasCaixa = $this->totalizaVendasCaixa($request);

		$p = view('relatorios_super/extrato_cliente')
		->with('empresa', $empresa)
		->with('acessos', $acessos)
		->with('totalNfe', $totalNfe)
		->with('totalVendas', $totalVendas)
		->with('totalizaVendasCaixa', $totalizaVendasCaixa)
		->with('totalNfce', $totalNfce)
		->with('totalCte', $totalCte)
		->with('totalMdfe', $totalMdfe)
		->with('data_inicial', $request->data_inicial)
		->with('data_final', $request->data_final)
		->with('title', 'Relatório de extrato de cliente');

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4");
		$domPdf->render();
		$domPdf->stream("Relatório de extrato de cliente.pdf", array("Attachment" => false));
	}

	public function historicoAcessos(Request $request){
		$empresas = Empresa::
		orderBy('id', 'desc')
		->get();
		$data = [];
		foreach($empresas as $e){
			$request->empresa = $e->id;
			$acessos = $this->totalizaAcessos($request, $e);
			$totalNfe = $this->totalizaNFe($request);
			$totalNfce = $this->totalizaNFCe($request);
			$totalBruto = $this->totalizaVendasBruta($request);

			$item = [
				'empresa' => $e->nome,
				'acessos' => $acessos,
				'nfes' => $totalNfe,
				'nfces' => $totalNfce,
				'bruto' => $totalBruto,
				'data_cadastro' => \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i'),
				'plano_nome' => $e->planoEmpresa ? $e->planoEmpresa->plano->nome : '--',
				'plano_valor' => $e->planoEmpresa ? $e->planoEmpresa->valor : 0
			];

			if($acessos > 0)
				array_push($data, $item);
		}

		usort($data, function($a, $b){
			return $a['acessos'] < $b['acessos'] ? 1 : 0;
		});

		$p = view('relatorios_super/extrato_acessos')
		->with('data', $data)
		->with('data_inicial', $request->data_inicial)
		->with('data_final', $request->data_final)
		->with('title', 'Relatório histórico de acessos');

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4", "landscape");
		$domPdf->render();
		$domPdf->stream("Relatório de extrato de cliente.pdf", array("Attachment" => false));
	}

	private function totalizaVendasBruta($request){
		$vendas = Venda::
		where('empresa_id', $request->empresa)
		->select(\DB::raw('SUM(valor_total) as total'));

		if($request->data_inicial && $request->data_final){

			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}
		$vendas = $vendas->first();

		$soma = $vendas->total ?? 0;

		$vendas = VendaCaixa::
		where('empresa_id', $request->empresa)
		->select(\DB::raw('SUM(valor_total) as total'));

		if($request->data_inicial && $request->data_final){

			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}
		$vendas = $vendas->first();

		$soma += $vendas->total ?? 0;

		return $soma;
	}

	private function totalizaNFe($request){
		$vendas = Venda::
		where('empresa_id', $request->empresa)
		->where('estado', 'APROVADO')
		->where('NfNumero', '>', 0);

		if($request->data_inicial && $request->data_final){

			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		return $vendas->count();
	}

	private function totalizaVendas($request){
		$vendas = Venda::
		where('empresa_id', $request->empresa)
		->where('estado', '!=', 'CANCELADO');

		if($request->data_inicial && $request->data_final){

			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		return $vendas->count();
	}

	private function totalizaVendasCaixa($request){
		$vendas = VendaCaixa::
		where('empresa_id', $request->empresa)
		->where('estado', '!=', 'CANCELADO');

		if($request->data_inicial && $request->data_final){

			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		return $vendas->count();
	}

	private function totalizaNFCe($request){
		$vendas = VendaCaixa::
		where('empresa_id', $request->empresa)
		->where('estado', 'APROVADO')
		->where('NFcNumero', '>', 0);

		if($request->data_inicial && $request->data_final){
			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		return $vendas->count();
	}

	private function totalizaCTe($request){
		$vendas = Cte::
		where('empresa_id', $request->empresa)
		->where('cte_numero', '>', 0);

		if($request->data_inicial && $request->data_final){
			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		return $vendas->count();
	}

	private function totalizaMDFe($request){
		$vendas = Mdfe::
		where('empresa_id', $request->empresa)
		->where('mdfe_numero', '>', 0);

		if($request->data_inicial && $request->data_final){
			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$vendas->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		return $vendas->count();
	}

	private function totalizaAcessos($request, $empresa){
		$usuarios = $empresa->usuarios;

		$cont = 0;
		foreach($usuarios as $u){

			if($request->data_inicial && $request->data_final){
				$dataInicial = $this->parseDate($request->data_inicial);
				$dataFinal = $this->parseDate($request->data_final, true);

				$acessos = UsuarioAcesso::
				where('usuario_id', $u->id)
				->whereBetween('created_at', [
					$dataInicial, 
					$dataFinal
				])->count();
				if($acessos) $cont += $acessos;
			}else{
				$cont += sizeof($u->acessos);
			}
		}
		return $cont;
	}

	public function empresasContador(Request $request){
		$contador = Contador::findOrFail($request->contador_id);

		$empresas = Empresa::
		where('contador_id', $request->contador_id)
		->get();
		$dataHoje = date('Y-m-d');
		
		foreach($empresas as $e){
			if($e->certificado){
				$infoCertificado = Certificate::readPfx($e->certificado->arquivo, $e->certificado->senha);
				$publicKey = $infoCertificado->publicKey;
				
				$e->vencimento = $publicKey->validTo->format('Y-m-d');
				$e->vencido = strtotime($dataHoje) > strtotime($e->vencimento);
			}
		}

		$p = view('relatorios_super/empresas_contador')
		->with('contador', $contador)
		->with('empresas', $empresas)
		->with('title', 'Relatório de empresas contador ' . $contador->razao_social);

		// return $p;
		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4");
		$domPdf->render();
		$domPdf->stream("Relatório de empresas contador.pdf", array("Attachment" => false));
	}

	public function log(Request $request){
		$empresa = $request->empresa;
		$user_logado = $request->user_logado;
		if($empresa == "null"){
			session()->flash('mensagem_erro', 'Selecione uma empresa');
			return redirect()->back();
		}

		$data = RecordLog::where('empresa_id', $empresa);

		if($request->data_inicial && $request->data_final){
			$dataInicial = $this->parseDate($request->data_inicial);
			$dataFinal = $this->parseDate($request->data_final, true);
			$data->whereBetween('created_at', [
				$dataInicial, 
				$dataFinal
			]);
		}

		if($user_logado){
			$data->where('usuario_log_id', $user_logado);
		}

		$data = $data->orderBy('id', $request->ordem)->get();

		if(sizeof($data) == 0){
			session()->flash('mensagem_erro', 'Relatório sem registros');
			return redirect()->back();
		}

		$empresa = Empresa::find($request->empresa);
		$p = view('relatorios_super/logs')
		->with('data', $data)
		->with('data_inicial', $request->data_inicial)
		->with('data_final', $request->data_final)
		->with('empresa', $empresa)
		->with('title', 'Relatório de logs');

		// return $p;
		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4", "landscape");
		$domPdf->render();
		$domPdf->stream("Relatório de logs.pdf", array("Attachment" => false));

	}
}
