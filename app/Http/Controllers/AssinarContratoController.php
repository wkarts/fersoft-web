<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigNota;
use App\Models\Certificado;
use App\Models\Contrato;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\EmpresaContrato;
use setasign\Fpdi\TcpdfFpdi;
use Fpdf\Fpdf;
use NFePHP\Common\Certificate;
use Dompdf\Dompdf;

class AssinarContratoController extends Controller
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
		$contrato = Contrato::first();
		$empresa = Empresa::find($this->empresa_id);

		$texto = __preparaTexto($contrato->texto, $empresa);

		return view('contrato/mostrar')
		->with('texto', $texto)
		->with('title', 'Contrato');
	}

	public function assinar(Request $request){

		if(!$request->aceito){
			session()->flash("mensagem_erro", "Aceite os termos!");
			return redirect()->back();
		}

		$config = ConfigNota::where('empresa_id', $this->empresa_id)
		->first();

		$empresa = Empresa::find($this->empresa_id);

		if($config == null){
			session()->flash("mensagem_erro", "Configure o emitente!");
			return redirect()->back();
		}

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$contrato = Contrato::first();

		if($certificado == null && $contrato->usar_certificado){
			session()->flash("mensagem_erro", "Configure o certificado!");
			return redirect()->back();
		}

		$arrSuper = explode(',', env("USERMASTER"));
		$userSuper = $arrSuper[0];

		$userSuper = Usuario::where('login', $userSuper)->first();

		if($userSuper){
			$configSuper = ConfigNota::where('empresa_id', $userSuper->empresa_id)->first();
			$empresaSuper = $userSuper->empresa;
			$certificadoSuper = Certificado::
			where('empresa_id', $configSuper->empresa_id)
			->first();
		}
		$this->gerarContrato($this->empresa_id);

		try{

			$cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);
			
			if($contrato->usar_certificado){
				$cert = Certificate::readPfx($certificado->arquivo, $certificado->senha);
				$publicKey = $cert->publicKey;

				// return view('land_page', compact('publicKey'));
				$pdf = new TcpdfFpdi();

				$info = array(
					'Name' => $empresa->nome,
					'Date' => date("Y.m.d H:i:s"),
					'Reason' => 'Assinatura de contrato',
					'ContactInfo' => $empresa->telefone,
				);
				if($empresaSuper){
					$certSuper = Certificate::readPfx($certificadoSuper->arquivo, $certificadoSuper->senha);

					$infoSuper = array(
						'Name' => $empresaSuper->nome,
						'Date' => date("Y.m.d H:i:s"),
						'Reason' => 'Assinatura de contrato',
						'ContactInfo' => $empresaSuper->telefone,
					);
				}

				$pageCount = $pdf->setSourceFile(public_path('contratos/'.$cnpj.'.pdf'));

				for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
					$pdf->setSignature($cert->__toString(), $cert->privateKey, $certificado->senha, '', 2, $info);
					$pdf->SetFont('helvetica', '', 11);
					$pdf->AddPage();

					if($certificadoSuper){
						$pdf->setSignature($certSuper->__toString(), $certSuper->privateKey, $certificadoSuper->senha, '', 2, $infoSuper);
						$publicKeySuper = $certSuper->publicKey;
					}

					if($pageNo == $pageCount){
						$len = strlen($publicKey->commonName);
						$name1 = substr($publicKey->commonName, 0, 25);
						$name2 = substr($publicKey->commonName, 25, $len);

						$pdf->SetFont('helvetica', '', 8);
						$pdf->Text(30, 255, $name1);
						$pdf->Text(30, 260, $name2);

						// $pdf->SetFont('helvetica', '', 12);
						// $pdf->Text(10, 260, $publicKey->commonName);
						$pdf->SetFont('helvetica', '', 6);
						$pdf->Text(30, 265,"Assinado de forma digital por:");
						$pdf->Text(30, 269, $publicKey->commonName);
						$pdf->Text(30, 274, "Dados: " . date('d/m/y H:i:s'));

						if($certificadoSuper){
							$len = strlen($publicKeySuper->commonName);
							$name1 = substr($publicKeySuper->commonName, 0, 25);
							$name2 = substr($publicKeySuper->commonName, 25, $len);

							$pdf->SetFont('helvetica', '', 8);
							$pdf->Text(130, 255, $name1);
							$pdf->Text(130, 260, $name2);

							$pdf->SetFont('helvetica', '', 6);
							$pdf->Text(130, 265,"Assinado de forma digital por:");
							$pdf->Text(130, 269, $publicKeySuper->commonName);
							$pdf->Text(130, 274, "Dados: " . date('d/m/y H:i:s'));
						}

					}

					$tplId = $pdf->importPage($pageNo);
					// $pdf->setSignatureAppearance(180, 60, 15, 15);
					// $pdf->addEmptySignatureAppearance(180, 80, 15, 15);
					$pdf->useTemplate($tplId, 0, 0);
				}

				$pdf->Output(public_path('contratos/'.$cnpj.'.pdf'), 'F');

				$file = file_get_contents(public_path('contratos/'.$cnpj.'.pdf'));
				// return response($file)
				// ->header('Content-Type', 'application/pdf');

				$empresa = Empresa::find($this->empresa_id);
				$contrato = $empresa->contrato;
				$contrato->status = 1;
				$contrato->save();
				session()->flash("mensagem_sucesso", "Contrato assinado!");
				return redirect('/graficos');
			}else{

				$pdf = new TcpdfFpdi();

				$info = array(
					'Name' => $empresa->nome,
					'Date' => date("Y.m.d H:i:s"),
					'Reason' => 'Assinatura de contrato',
					'ContactInfo' => $empresa->telefone,
				);

				$pdf->setSourceFile(public_path('contratos/'.$cnpj.'.pdf'));

				$pdf->SetFont('helvetica', '', 12);
				$pdf->AddPage();

				$pdf->Text(10, 255, "Contrato assinado $cnpj");
				$pdf->Text(10, 265, "Data da assinatura: " . date('d/m/y H:i:s'));
				$tplId = $pdf->importPage(1);

				// $pdf->setSignatureAppearance(180, 60, 15, 15);
				// $pdf->addEmptySignatureAppearance(180, 80, 15, 15);
				$pdf->useTemplate($tplId, 0, 0);
				
				$pdf->Output(public_path('contratos/'.$cnpj.'.pdf'), 'F');
				$empresa = Empresa::find($this->empresa_id);
				$contrato = $empresa->contrato;
				$contrato->status = 1;
				$contrato->save();
				session()->flash("mensagem_sucesso", "Contrato assinado!");
				return redirect('/graficos');
			}

		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
			$this->gerarContrato($this->empresa_id);
			return redirect()->back();
		}

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
			return "ok";
		}catch(\Exception $e){
			echo $e->getMessage();
		}

	}
}
