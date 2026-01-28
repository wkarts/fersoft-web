<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contrato;
use App\Models\Empresa;
use App\Models\EmpresaContrato;
use Dompdf\Dompdf;

class ContratoController extends Controller
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

		$contrato = Contrato::first();

		return view('contrato/register')
		->with('contratoJs', true)
		->with('contract', $contrato)
		->with('title', 'Contrato');
	}

	public function save(Request $request){
		$request->merge([ 
			'usar_certificado' => $request->input('usar_certificado') ? true : false,
			'accessos_forcar_assinar' => $request->accessos_forcar_assinar ?? 0,
		]);

		Contrato::create($request->all());

		session()->flash("mensagem_sucesso", "Contrato salvo!!");
		return redirect()->back();
	}

	public function update(Request $request){
		$contrato = Contrato::first();

		$contrato->texto = $request->texto;
		$contrato->usar_certificado = $request->input('usar_certificado') ? true : false ;
		$contrato->accessos_forcar_assinar = $request->accessos_forcar_assinar;
		$contrato->save();

		session()->flash("mensagem_sucesso", "Contrato alterado!!");
		return redirect()->back();
	}

	public function impressao(){
		$contrato = Contrato::first();
		$texto = $contrato->texto;

		$domPdf = new Dompdf(["enable_remote" => true]);

		$usr = session('user_logged');
		$empresa = Empresa::findOrFail($usr['empresa']);

		$texto = __preparaTexto($contrato->texto, $empresa);

		$domPdf->loadHtml($texto);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4");
		$domPdf->render();
		$domPdf->stream("contrato_modelo.pdf", array("Attachment" => false));
	}

	public function gerarContrato($empresa_id){
		try{
			$contrato = Contrato::first();

			if($contrato == null){
				session()->flash("mensagem_erro", "Cadastre o contrato!!");
				return redirect('/contrato');
			}
			$empresa = Empresa::find($empresa_id);

			$texto = __preparaTexto($contrato->texto, $empresa);

			$domPdf = new Dompdf(["enable_remote" => true]);
			$domPdf->loadHtml($texto);

			$pdf = ob_get_clean();

			$domPdf->setPaper("A4");
			$domPdf->render();
		// $domPdf->stream("contrato_modelo.pdf");
			$output = $domPdf->output();

			$cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);

			if(!is_dir(public_path('contratos'))){
				mkdir(public_path('contratos'), 0777, true);
			}
			file_put_contents(public_path('contratos/'.$cnpj.'.pdf'), $output);

			EmpresaContrato::where('empresa_id', $empresa->id)->delete();

			EmpresaContrato::create(
				[
					'empresa_id' => $empresa->id, 
					'status' => 0,
					'cpf_cnpj' => $empresa->cnpj
				]
			);

			session()->flash("mensagem_sucesso", "Contrato criado!");
			return redirect()->back();
		}catch(\Exception $e){
			echo $e->getMessage();
		}

	}


	public function download($empresa_id){
		$empresa = Empresa::find($empresa_id);

		$cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);

		return response()->download(public_path('contratos/'.$cnpj.'.pdf'));

	}

	public function imprimir($empresa_id){
		$empresa = Empresa::find($empresa_id);

		$cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);

		$pdf = file_get_contents(public_path('contratos/').$cnpj.'.pdf');
		if($pdf){
			header("Content-Disposition: ; filename=Contrato.pdf");
			return response($pdf)
			->header('Content-Type', 'application/pdf');
		}else{
			session()->flash("mensagem_erro", "Contrato não encontrado!");
			return redirect()->back();
		}

	}
}
