<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Orcamento;
use App\Models\ItemOrcamento;
use App\Models\ConfigNota;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Tributacao;
use App\Models\Transportadora;
use App\Models\Cliente;
use App\Models\ItemVenda;
use App\Models\Frete;
use App\Models\AberturaCaixa;
use App\Models\ContaBancaria;
use App\Models\FormaPagamento;
use App\Models\Usuario;
use App\Models\ListaPreco;
use App\Models\EmailConfig;
use App\Models\ContaReceber;
use App\Models\Venda;
use App\Models\NaturezaOperacao;
use App\Models\FaturaOrcamento;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Services\NFService;
//use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe as Danfe;
use Mail;
use App\Models\CategoriaConta;
use App\Helpers\StockMove;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\DB;

class OrcamentoController extends Controller
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

	public function numeroSequencial(){
		$verify = Orcamento::where('empresa_id', $this->empresa_id)
		->where('numero_sequencial', 0)
		->first();

		if($verify){
			$os = Orcamento::where('empresa_id', $this->empresa_id)
			->get();

			$n = 1;
			foreach($os as $v){
				$v->numero_sequencial = $n;
				$n++;
				$v->save();
			}
		}
	}

	public function index(){
		$this->numeroSequencial();

		$permissaoAcesso = __getLocaisUsarioLogado();
		$orcamentos = Orcamento::
		where('empresa_id', $this->empresa_id)
		->select('orcamentos.*')
		->orderBy('id', 'desc')
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('filial_id', $value);
				}
			}
		})
		->paginate(30);

		$menos30 = $this->menos30Dias();
		$date = date('d/m/Y');

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		return view("orcamentos/list")
		->with('orcamentos', $orcamentos)
		->with('orcamentoJs', true)
		->with('links', true)
		->with('dataInicial', $menos30)
		->with('dataFinal', $date)
		->with('config', $config)
		->with('title', "Lista de Orçamenos");
	}

	private function menos30Dias(){
		return date('d/m/Y', strtotime("-30 days",strtotime(str_replace("/", "-",
			date('Y-m-d')))));
	}

	private function publicFileExists($relativePath){
		if($relativePath == null || trim($relativePath) == ''){
			return false;
		}
		$fullPath = public_path(ltrim($relativePath, '/'));
		return file_exists($fullPath) && is_file($fullPath) && is_readable($fullPath);
	}

	private function prepararAssetsOrcamento($orcamento, $config){
		if($config != null){
			$logoRelativo = 'logos/' . ($config->logo ?? '');
			if(!$this->publicFileExists($logoRelativo)){
				$config->logo = '';
			}
		}

		if($orcamento != null){
			foreach($orcamento->itens as $item){
				$imagem = $item->produto->imagem ?? '';
				if(!$this->publicFileExists('imgs_produtos/' . $imagem)) {
					$item->produto->imagem = '';
				}
			}
		}
		return [$orcamento, $config];
	}

	public function salvar(Request $request){
		try{
			$result = DB::transaction(function () use ($request) {
				$venda = $request->venda;
				$valorFrete = str_replace(",", ".", $venda['valorFrete'] ?? 0);
				$vol = $venda['volume'];

				if($vol['pesoL']){
					$pesoLiquido = str_replace(",", ".", $vol['pesoL']);
				}else{
					$pesoLiquido = 0;
				}

				if($vol['pesoB']){
					$pesoBruto = str_replace(",", ".", $vol['pesoB']);
				}else{
					$pesoBruto = 0;
				}

				if($vol['qtdVol']){
					$qtdVol = str_replace(",", ".", $vol['qtdVol']);
				}else{
					$qtdVol = 0;
				}

				$totalVenda = str_replace(",", ".", $venda['total']);

				$desconto = 0;
				if($venda['desconto']){
					$desconto = str_replace(",", ".", $venda['desconto']);
				}

				$acrescimo = 0;
				if($venda['acrescimo']){
					$acrescimo = str_replace(",", ".", $venda['acrescimo']);
				}

				$frete = null;
				if($venda['frete'] != '9'){
					$frete = Frete::create([
						'placa' => $venda['placaVeiculo'] ?? '',
						'valor' => $valorFrete ?? 0,
						'tipo' => (int)$venda['frete'],
						'qtdVolumes' => $qtdVol?? 0,
						'uf' => $venda['ufPlaca'] ?? '',
						'numeracaoVolumes' => $vol['numeracaoVol'] ?? '0',
						'especie' => $vol['especie'] ?? '*',
						'peso_liquido' => $pesoLiquido ?? 0,
						'peso_bruto' => $pesoBruto ?? 0
					]);
				}

				$dt = date("Y-m-d");

				$numero_sequencial = 0;
				$last = Orcamento::where('empresa_id', $this->empresa_id)
				->orderBy('id', 'desc')
				->first();

				$numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;

				$result = Orcamento::create([
					'cliente_id' => $venda['cliente'],
					'transportadora_id' => $venda['transportadora'],
					'forma_pagamento' => $venda['formaPagamento'],
					'tipo_pagamento' => $venda['tipoPagamento'],
					'usuario_id' => get_id_user(),
					'valor_total' => $totalVenda,
					'numero_sequencial' => $numero_sequencial,
					'data_entrega' => $venda['data_entrega'] != '' ? $this->parseDate($venda['data_entrega']) : null,
					'data_retroativa' => $venda['data_retroativa'] != '' ? $this->parseDate($venda['data_retroativa']) : null,
					'desconto' => $desconto,
					'acrescimo' => $acrescimo,
					'frete_id' => $frete != null ? $frete->id : null,
					'natureza_id' => $venda['naturezaOp'],
					'observacao' => $this->sanitizeString($venda['observacao']) ?? '',
					'estado' => 'NOVO',
					'email_enviado' => 0,
					'validade' => date( "Y-m-d", strtotime( "$dt +7 day" )),
					'venda_id' => 0,
					'vendedor_id' => $venda['vendedor_id'] ?? null,
					'empresa_id' => $this->empresa_id,
					'filial_id' => $venda['filial_id'] != -1 ? $venda['filial_id'] : null
				]);

				$itens = $venda['itens'];
				foreach ($itens as $i) {
					ItemOrcamento::create([
						'orcamento_id' => $result->id,
						'produto_id' => (int) $i['codigo'],
						'quantidade' => (float) str_replace(",", ".", $i['quantidade']),
						'valor' => (float) str_replace(",", ".", $i['valor']),
						'altura' => $i['altura'],
						'largura' =>  $i['largura'],
						'profundidade' => $i['profundidade'],
						'esquerda' =>  $i['esquerda'],
						'direita' =>  $i['direita'],
						'superior' =>  $i['superior'],
						'inferior' =>  $i['inferior']
					]);
				}

				$this->criarLog($result);
				if($venda['formaPagamento'] != 'a_vista' && $venda['formaPagamento'] != 'conta_crediario'){
					$fatura = $venda['fatura'];

					foreach ($fatura as $f) {
						$valorParcela = str_replace(",", ".", $f['valor']);

						$resultFatura = FaturaOrcamento::create([
							'orcamento_id' => $result->id,
							'vencimento' => $this->parseDate($f['data']),
							'valor' => $valorParcela,
							'tipo_pagamento' => $f['tipo'],
							'empresa_id' => $this->empresa_id
						]);
					}
				}else{
					$resultFatura = FaturaOrcamento::create([
						'orcamento_id' => $result->id,
						'vencimento' => date('Y-m-d'),
						'valor' => $totalVenda - $desconto,
						'tipo_pagamento' => Venda::getTipo($venda['tipoPagamento']),
						'empresa_id' => $this->empresa_id
					]);
				}


				return $result;
			});
            return response()->json($result, 200);
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            return response()->json($e->getMessage(), 400);
        }
    }

    private function criarLog($objeto, $tipo = 'criar'){
        if(isset(session('user_logged')['log_id'])){
            $record = [
                'tipo' => $tipo,
                'usuario_log_id' => session('user_logged')['log_id'],
                'tabela' => 'orcamentos',
                'registro_id' => $objeto->id,
                'empresa_id' => $this->empresa_id
            ];
            __saveLog($record);
        }
    }

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    function sanitizeString($str){
        return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
            utf8_decode(html_entity_decode($str)),
            utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
            'AAAAEEIOOOUUCNaaaaeeiooouucn')));
    }

    public function detalhar($id){
        $orcamento = Orcamento::
        where('id', $id)
        ->first();

        if(valida_objeto($orcamento)){

            $naturezas = NaturezaOperacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $menos30 = $this->menos30Dias();
            $date = date('d/m/Y');

            $d1 = strtotime(date('Y-m-d'));
            $d2 = strtotime($orcamento->validade);

            $distancia = $d2 - $d1;

            $diasParaVencimento = $distancia/86400;

            $simulacaoPagamento = $this->simulacaoPagamento($orcamento->valor_total);

            $clientes = Cliente::where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

            return view("orcamentos/detalhe")
            ->with('orcamento', $orcamento)
            ->with('naturezas', $naturezas)
            ->with('simulacaoPagamento', $simulacaoPagamento)
            ->with('clientes', $clientes)
            ->with('diasParaVencimento', $diasParaVencimento)
            ->with('orcamentoJs', true)
            ->with('title', "Detalhe do Orçamento $id");
        }else{
            return redirect('/403');
        }
    }

    private function simulacaoPagamento($total){
        $soma = 0;
        $tempArr = [];
        $valorP = number_format($total/12,2);
        for($i = 1; $i <= 12; $i++){
            $t = [
                'parcelas' => $i,
                'valor' => number_format($total/$i,2)
            ];

            array_push($tempArr, $t);
        }

        return $tempArr;
    }

    public function imprimir($id){

        $orcamento = Orcamento::find($id);
        if(valida_objeto($orcamento)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($orcamento->filial_id != null){
                $config = $orcamento->filial;
            }

            [$orcamento, $config] = $this->prepararAssetsOrcamento($orcamento, $config);

            $p = view('orcamentos/print')
            ->with('orcamento', $orcamento)
            ->with('config', $config);

                // return $p;
            $options = new Options();
            $options->set('isRemoteEnabled', TRUE);
            $domPdf = new Dompdf($options);

            $domPdf->loadHtml($p);


            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("orcamento.pdf", ["Attachment" => false]);
                // $domPdf->stream("orcamento.pdf");

        }else{
            return redirect('/403');
        }

    }

    public function imprimirCompleto($id){
        $orcamento = Orcamento::find($id);
        if(valida_objeto($orcamento)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $p = view('orcamentos/print_completo')
            ->with('orcamento', $orcamento)
            ->with('config', $config);


            $options = new Options();
            $options->set('isRemoteEnabled', TRUE);
            $domPdf = new Dompdf($options);

            $contxt = stream_context_create([
                'ssl' => [
                    'verify_peer' => FALSE,
                    'verify_peer_name' => FALSE,
                    'allow_self_signed'=> TRUE
                ]
            ]);
            $domPdf->setHttpContext($contxt);

            $domPdf->loadHtml($p);

            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("orcamento.pdf");
        }else{
            return redirect('/403');
        }

    }

    public function rederizarDanfe($id){
        $orcamento = Orcamento::find($id);

        if(valida_objeto($orcamento)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $cnpj = str_replace(".", "", $config->cnpj);
            $cnpj = str_replace("/", "", $cnpj);
            $cnpj = str_replace("-", "", $cnpj);
            $cnpj = str_replace(" ", "", $cnpj);

            $nfe_service = new NFService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb" => (int)$config->ambiente,
                "razaosocial" => $config->razao_social,
                "siglaUF" => $config->UF,
                "cnpj" => $cnpj,
                "schemes" => "PL_009_V4",
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id
            ]);
            $nfe = $nfe_service->simularOrcamento($orcamento);
            if(!isset($nfe['erros_xml'])){
                $xml = $nfe['xml'];

                $logo = null;
                if($config->logo && $this->publicFileExists('logos/' . $config->logo)){
                    $logo = 'data://text/plain;base64,' . base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
                }

                try {
                    $danfe = new Danfe($xml);
                        // $id = $danfe->monta();
                    $pdf = $danfe->render($logo);
                    header('Content-Type: application/pdf');
                        // echo $pdf;
                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');
                } catch (InvalidArgumentException $e) {
                    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
                }

            }else{
                foreach($nfe['erros_xml'] as $e){
                    echo $e;
                }
            }
        }else{
            return redirect('/403');
        }

    }

    public function enviarEmail(Request $request){
        $email = $request->email;
        $id = $request->id;

        $orcamento = Orcamento::find($id);
        if(valida_objeto($orcamento)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($email == ''){

                session()->flash("mensagem_sucesso", "Informe um email!");
                return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
            }

            if($orcamento->filial_id != null){
                $config = $orcamento->filial;
            }

            [$orcamento, $config] = $this->prepararAssetsOrcamento($orcamento, $config);

            $p = view('orcamentos/print')
            ->with('config', $config)
            ->with('orcamento', $orcamento);
            // return $p;

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();

            $public = env('SERVIDOR_WEB') ? 'public/' : '';

            file_put_contents($public.'orcamento/ORCAMENTO_'.$id.'.pdf', $domPdf->output());

            $value = session('user_logged');

            if($config->usar_email_proprio){
                $send = $this->enviaEmailPHPMailer($orcamento, $email, $config);
                if(!isset($send['erro'])){
                    session()->flash("mensagem_sucesso", "Email enviado!");
                }else{
                    session()->flash("mensagem_erro", "Erro ao enviar email: " . $send['erro']);
                }
                return redirect()->back();

            }else{
                try{
                    Mail::send('mail.orcamento_send', ['emissao' => $orcamento->created_at,
                        'valor' => $orcamento->valor_total, 'usuario' => $value['nome'], 'config' => $config], function($m) use ($orcamento, $email, $pdf){

                            $public = env('SERVIDOR_WEB') ? 'public/' : '';
                            $nomeEmpresa = env('MAIL_NAME');
                            $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                            $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                            $emailEnvio = env('MAIL_USERNAME');

                            $m->from($emailEnvio, $nomeEmpresa);
                            $m->subject('Envio de Oçamento ' . $orcamento->id);
                            $m->attach($public.'orcamento/ORCAMENTO_'.$orcamento->id.'.pdf');
                            $m->to($email);
                            return response()->json("ok", 200);

                        });
                    if(isset($request->redirect)) {

                        session()->flash("mensagem_sucesso", "Email enviado!");
                        return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
                    }
                }catch(\Exception $e){
                    return response()->json($e->getMessage(), 401);
                }
            }
        }else{
            return redirect('/403');
        }

    }

    private function enviaEmailPHPMailer($orcamento, $email, $config){
        $emailConfig = EmailConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($emailConfig == null){
            return [
                'erro' => 'Primeiramente configure seu email'
            ];
        }

        $public = env('SERVIDOR_WEB') ? 'public/' : '';

        $value = session('user_logged');

        $mail = new PHPMailer(true);

        try {
            if($emailConfig->smtp_debug){
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $mail->isSMTP();
            $mail->Host = $emailConfig->host;
            $mail->SMTPAuth = $emailConfig->smtp_auth;
            $mail->Username = $emailConfig->email;
            $mail->Password = $emailConfig->senha;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $emailConfig->porta;

            $mail->setFrom($emailConfig->email, $emailConfig->nome);
            $mail->addAddress($email);

            $mail->addAttachment($public.'orcamento/ORCAMENTO_'.$orcamento->id.'.pdf');

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';

            $mail->Subject = "Envio de Pedido #$orcamento->id";
            $body = view('mail.orcamento_send', ['emissao' => $orcamento->created_at,
                'valor' => $orcamento->valor_total, 'usuario' => $value['nome'], 'config' => $config]);
            $mail->Body = $body;
            $mail->send();
            return [
                'sucesso' => true
            ];
        } catch (Exception $e) {
            return [
                'erro' => $mail->ErrorInfo
            ];
                // echo "Message could; not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    public function deleteItem($id){
        $item = ItemOrcamento::find($id);

        if(valida_objeto($item->orcamento)){
            $orcamento = $item->orcamento;
            $item->delete();

            session()->flash("mensagem_sucesso", "Item removido!");

            $this->atualizarTotal($orcamento);
            return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
        }else{
            return redirect('/403');
        }
    }

    public function addItem(Request $request){
        $orcamento = Orcamento::find($request->orcamento_id);
        if(valida_objeto($orcamento)){
            $produto = $request->produto;
            $produto = Produto::find($produto);

            $item = ItemOrcamento::create(
                [
                    'orcamento_id' => $orcamento->id,
                    'produto_id' => $produto->id,
                    'quantidade' => (float) str_replace(",", ".", $request->quantidade),
                    'valor' => (float) str_replace(",", ".", $request->valor)
                ]
            );

            session()->flash("mensagem_sucesso", "Item adicionado!");

            $this->atualizarTotal($orcamento);
            return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
        }else{
            return redirect('/403');
        }
    }

private function atualizarTotal($orcamento){
	$orcamento = Orcamento::find($orcamento->id);

	$soma = 0;
	foreach($orcamento->itens as $i){
		$soma += $i->quantidade * $i->valor;
	}

	$orcamento->valor_total = $soma;
	$orcamento->save();
	$this->deleteParcelas($orcamento);
}

public function setValidade(Request $request){
	$orcamento = Orcamento::find($request->orcamento_id);

	$orcamento->validade = \Carbon\Carbon::parse(str_replace("/", "-",
		$request->validade))->format('Y-m-d');
	$orcamento->observacao = $request->observacao ?? '';

	session()->flash("mensagem_sucesso", "Data de validade alterada!");
	$orcamento->save();
	return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);

}

private function deleteParcelas($orcamento){
	foreach($orcamento->duplicatas as $dp){
		$dp->delete();
	}
}

public function addPag(Request $request){
	$orcamento = Orcamento::find($request->orcamento_id);
	if(valida_objeto($orcamento)){
		$valor = __replace($request->valor);

		if(!$valor){

			session()->flash("mensagem_erro", "Informe um valor para parcela!");

			return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
		}

		if((float)number_format($orcamento->valor_total, 2, '.', '') < ($orcamento->somaParcelas() + $valor)){

			session()->flash("mensagem_erro", "Soma de parcelas ultrapassou o valor de produtos!");

			return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
		}

		$vencimento = \Carbon\Carbon::parse(str_replace("/", "-", $request->data))->format('Y-m-d');

		$strtotimeData = strtotime($vencimento);
		$strtotimeHoje = strtotime(date('Y-m-d'));

		$dif = $strtotimeData - $strtotimeHoje;

		if($dif < 0){

			session()->flash("mensagem_erro", "Data deve ser posterior ou igual a de hoje!");

			return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
		}

		if($orcamento->validaFatura($vencimento) == false){

			session()->flash("mensagem_erro", "Data de fatura deve seguir ordem crescente!");

			return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
		}

		$orcamento->forma_pagamento = 'personalizado';
		$orcamento->save();

		$vencimento = \Carbon\Carbon::parse(str_replace("/", "-", $request->data))->format('Y-m-d');
		$fatura = FaturaOrcamento::create([
			'valor' => $valor,
			'vencimento' => $vencimento,
			'orcamento_id' => $orcamento->id,
			'empresa_id' => $this->empresa_id
		]);

		session()->flash("mensagem_sucesso", "Parcela adicionada!");

		return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
	}else{
		return redirect('/403');
	}
}

public function deleteParcela($id){
	$parcela = FaturaOrcamento::find($id);
	if(valida_objeto($parcela->orcamento)){
		$orcamento = $parcela->orcamento;
		$parcela->delete();

		session()->flash("mensagem_sucesso", "Parcela removida!");

		$this->atualizarTotal($orcamento);
		return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
	}else{
		return redirect('/403');
	}
}

private function validaEstoque2($orcamento){

	$msg = "";
	foreach ($orcamento->itens as $i) {
		if($i->produto->gerenciar_estoque == 1){
			$quantidadeDisponivel = $i->produto->estoquePorLocal($orcamento->filial_id);
			if($i->quantidade > $quantidadeDisponivel){
				$msg .= $i->produto->nome . " com quantidade insuficiente | ";
			}
		}
	}
	$msg = substr($msg, 0, strlen($msg)-2);
	return $msg;
}

public function gerarVenda($id){
	$orcamento = Orcamento::findOrFail($id);

	$validaEstoque = $this->validaEstoque2($orcamento);
	if($validaEstoque != ""){
		session()->flash("mensagem_erro", $validaEstoque);
		return redirect()->back();
	}

	$frete = null;

	$result = Venda::create([
		'cliente_id' => $orcamento->cliente_id,
		'transportadora_id' => NULL,
		'forma_pagamento' => $orcamento->forma_pagamento,
		'tipo_pagamento' => $orcamento->tipo_pagamento,
		'data_entrega' => $orcamento->data_entrega,
		'data_retroativa' => $orcamento->data_retroativa,
		'usuario_id' => get_id_user(),
		'valor_total' => $orcamento->valor_total,
		'desconto' => $orcamento->desconto,
		'acrescimo' => $orcamento->acrescimo,
		'frete_id' => $frete != null ? $frete->id : null,
		'NfNumero' => 0,
		'natureza_id' => NaturezaOperacao::
		where('empresa_id', $this->empresa_id)
		->first()->id,
		'path_xml' => '',
		'chave' => '',
		'sequencia_cce' => 0,
		'observacao' => $orcamento->observacao,
		'filial_id' => $orcamento->filial_id,
		'estado' => 'DISPONIVEL',
		'empresa_id' => $this->empresa_id
	]);

	$stockMove = new StockMove();
	foreach ($orcamento->itens as $i) {
		ItemVenda::create([
			'venda_id' => $result->id,
			'produto_id' => $i->produto_id,
			'quantidade' => $i->quantidade,
			'valor' => $i->valor,
			'altura' => $i->altura,
			'largura' =>  $i->largura,
			'profundidade' => $i->profundidade,
			'esquerda' =>  $i->esquerda,
			'direita' =>  $i->direita,
			'superior' =>  $i->superior,
			'inferior' =>  $i->inferior,
			'valor_custo' => $i->produto->valor_compra
		]);
			// $stockMove->downStock(
			// 	$i->produto_id, $i->quantidade, $orcamento->filial_id);

		$prod = Produto
		::where('id', $i->produto_id)
		->first();

		if(!empty($prod->receita)){
				//baixa por receita
			$receita = $prod->receita;
			foreach($receita->itens as $rec){
				$stockMove->downStock(
					$rec->produto_id,
					$i->quantidade *
					($rec->quantidade/$receita->rendimento),
					$orcamento->filial_id
				);
			}
		}else{
			$stockMove->downStock($i->produto_id, $i->quantidade, $orcamento->filial_id);
		}
	}

	foreach ($orcamento->duplicatas as $key => $f) {

		$resultFatura = ContaReceber::create([
			'venda_id' => $result->id,
			'data_vencimento' => $f->vencimento,
			'data_recebimento' => $f->vencimento,
			'tipo_pagamento' => $f->tipo_pagamento,
			'valor_integral' => $f->valor,
			'valor_recebido' => 0,
			'status' => false,
			'referencia' => "Parcela, ".($key+1).", da Venda " . $result->id,
			'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->first()->id,
			'empresa_id' => $this->empresa_id,
            'filial_id' => $result->filial_id,

            'nf_modelo'       => '55',
            'nf_numero'       => $result->NfNumero ?? 0,
            'nf_data_emissao' => $result->data_emissao ?? $result->created_at ?? null,
            'nf_chave'        => $result->chave ?? null,
		]);
	}

	$orcamento->estado = 'APROVADO';
	$orcamento->venda_id = $result->id;
	$orcamento->save();

	session()->flash("mensagem_sucesso", "Venda gerada!");
	return redirect('/vendas');
}

public function filtro(Request $request){
	$dataInicial = $request->data_inicial;
	$dataFinal = $request->data_final;
	$cliente = $request->cliente;
	$estado = $request->estado;
	$filial_id = $request->filial_id;
	$orcamentos = null;

	$permissaoAcesso = __getLocaisUsarioLogado();

	$orcamentos = Orcamento::
	where('orcamentos.empresa_id', $this->empresa_id)
	->where(function($query) use ($permissaoAcesso){
		if($permissaoAcesso != null){
			foreach ($permissaoAcesso as $value) {
				if($value == -1){
					$value = null;
				}
				$query->orWhere('orcamentos.filial_id', $value);
			}
		}
	})
	->orderBy('orcamentos.id', 'desc')
	->select('orcamentos.*');

	if(isset($dataInicial) && isset($dataFinal)){
		$orcamentos->whereBetween('orcamentos.created_at', [
			$this->parseDate($dataInicial),
			$this->parseDate($dataFinal, true)
		]);
	}

	if(isset($cliente)){
		$orcamentos->join('clientes', 'clientes.id' , '=', 'orcamentos.cliente_id')
		->where('clientes.'.$request->tipo_pesquisa, 'LIKE', "%$cliente%");
	}

	if($estado != "TODOS"){
		$orcamentos->where('estado', $estado);
	}

	if($filial_id){
		if($filial_id == -1){
			$orcamentos->where('filial_id', null);
		}else{
			$orcamentos->where('filial_id', $filial_id);
		}
	}
	$orcamentos = $orcamentos->get();

		// if(isset($cliente) && isset($dataInicial) && isset($dataFinal)){
		// 	$orcamentos = Orcamento::filtroDataCliente(
		// 		$cliente,
		// 		$this->parseDate($dataInicial),
		// 		$this->parseDate($dataFinal, true),
		// 		$estado,
		// 		$request->tipo_pesquisa
		// 	);
		// }else if(isset($dataInicial) && isset($dataFinal)){
		// 	$orcamentos = Orcamento::filtroData(
		// 		$this->parseDate($dataInicial),
		// 		$this->parseDate($dataFinal, true),
		// 		$estado
		// 	);
		// }else if(isset($cliente)){
		// 	$orcamentos = Orcamento::filtroCliente(
		// 		$cliente,
		// 		$estado,
		// 		$request->tipo_pesquisa
		// 	);

		// }else{
		// 	$orcamentos = Orcamento::filtroEstado(
		// 		$estado
		// 	);
		// }

	if($request->imprimir == 0){

		$menos30 = $this->menos30Dias();
		$date = date('d/m/Y');

		return view("orcamentos/list")
		->with('orcamentos', $orcamentos)
		->with('orcamentoJs', true)
		->with('cliente', $cliente)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('filial_id', $filial_id)
		->with('tipoPesquisa', $request->tipo_pesquisa)
		->with('estado', $estado)
		->with('imprimir', true)
		->with('title', "Lista de Orçamenos");
	}else{

		$p = view('relatorios/orcamentos')
		->with('data_inicial', $dataInicial)
		->with('data_final', $dataFinal)
		->with('orcamentos', $orcamentos);

			// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4");
		$domPdf->render();
		$domPdf->stream("Orçamentos.pdf");
	}
}

public function reprovar($id){
	$orcamento = Orcamento::find($id);
	if(valida_objeto($orcamento)){
		$orcamento->estado = 'REPROVADO';
		$orcamento->save();

		session()->flash("mensagem_erro", "Orçamento reprovado!");

		return redirect('/orcamentoVenda/detalhar/' . $orcamento->id);
	}else{
		return redirect('/403');
	}
}

public function delete($id){
	$orcamento = Orcamento::find($id);

	$this->criarLog($orcamento, 'deletar');

	if(valida_objeto($orcamento)){
		$orcamento->delete();

		session()->flash("mensagem_sucesso", "Orçamento removido!");
		return redirect('/orcamentoVenda');
	}else{
		return redirect('/403');
	}
}

public function relatorioItens($dataInicial, $dataFinal){

	$dI = $dataInicial;
	$dF = $dataFinal;
	$dataInicial = $this->parseDate($dataInicial);
	$dataFinal = $this->parseDate($dataFinal, true);

	$orcamentos = Orcamento::
	whereBetween('created_at', [$dataInicial,
		$dataFinal])
	->where('estado', 'NOVO')
	->get();

	$itens = [];
	foreach($orcamentos as $o){
		foreach($o->itens as $i){
				// echo $i;
			$temp = [
				'codigo' => $i->produto->id,
				'produto' => $i->produto->nome,
				'quantidade' => $i->quantidade
			];
			$dp = $this->itemNaoInserido($temp, $itens);

			if(!$dp){
				array_push($itens, $temp);
			}else{
				for($aux = 0; $aux < sizeof($itens); $aux++){
					if($itens[$aux]['codigo'] == $temp['codigo']){
						$itens[$aux]['quantidade'] += $i->quantidade;
					}
				}
			}

		}
	}

	$p = view('relatorios/relatorio_compra_orcamento')
	->with('data_inicial', $dI)
	->with('data_final', $dF)
	->with('itens', $itens);

		// return $p;

	$domPdf = new Dompdf(["enable_remote" => true]);
	$domPdf->loadHtml($p);

	$pdf = ob_get_clean();

	$domPdf->setPaper("A4");
	$domPdf->render();
	$domPdf->stream("Relatório de compra orçamento.pdf");
}

private function itemNaoInserido($item, $itens){
	foreach($itens as $i){
		if($i['codigo'] == $item['codigo']) return true;
	}
	return false;
}

public function gerarPagamentos(Request $request){
	$qtdParcelas = $request->qtd_parcelas;
	$intervalo = $request->intervalo;
	$id = $request->orcamento_id;

	$orcamento = Orcamento::find($id);
	$total = $orcamento->valor_total;
	$soma = 0;

	foreach($orcamento->duplicatas as $dp){
		$dp->delete();
	}

	$vp = number_format($total/$qtdParcelas, 2);
	$data = date('Y-m-d');
	for($i=0; $i < $qtdParcelas; $i++){
		$valor = 0;
		if($i<$qtdParcelas-1){
			$valor = $vp;
			$soma += $vp;
		}else{
			$valor = number_format($total-$soma, 2);
		}

		$data = $this->calculaData($data, $intervalo);

		$fatura = FaturaOrcamento::create([
			'valor' => $valor,
			'vencimento' => $data,
			'orcamento_id' => $id,
			'empresa_id' => $this->empresa_id
		]);
	}

	return redirect()->back();

}

private function calculaData($data, $intervalo){
	return date('Y-m-d', strtotime("+$intervalo day",strtotime(str_replace("/", "-", $data))));
}

public function consultar_cliente($id){
	$orcamento = orcamento::
	where('id', $id)
	->where('empresa_id', $this->empresa_id)
	->first();
	echo json_encode($orcamento->cliente);
}

public function alterarCliente(Request $request){
	$orcamento = Orcamento::find($request->orcamento_id);
	$orcamento->cliente_id = $request->cliente_id;
	$orcamento->save();
	session()->flash("mensagem_sucesso", "Novo cliente definido!");
	return redirect()->back();
}

public function edit($id){
	$orcamento = Orcamento::
	where('id', $id)
	->with('itens')
	->with('cliente')
	->with('duplicatas')
	->first();

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();
	if($config == null){
		return redirect('configNF');
	}
	$lastNF = Venda::lastNF();

	$naturezas = NaturezaOperacao::
	where('empresa_id', $this->empresa_id)
	->get();

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();

	$categorias = Categoria::
	where('empresa_id', $this->empresa_id)
	->get();

	$tributacao = Tributacao::
	where('empresa_id', $this->empresa_id)
	->first();

	$clientes = Cliente::
	where('empresa_id', $this->empresa_id)
	->where('inativo', false)
	->with('cidade')
	->get();

	$tiposPagamento = Venda::tiposPagamento();

	if(count($naturezas) == 0 || $config == null || count($categorias) == 0 || $tributacao == null || count($clientes) == 0){

		$p = view("vendas/alerta")
		->with('categorias', count($categorias))
		->with('clientes', count($clientes))
		->with('naturezas', $naturezas)
		->with('produtos', 0)
		->with('config', $config)
		->with('tributacao', $tributacao)
		->with('title', "Validação para Emitir");
		return $p;

	}else{

		$transportadoras = Transportadora::
		where('empresa_id', $this->empresa_id)
		->get();

		foreach($clientes as $c){
			$c->cidade;
		}

		$abertura = $this->verificaAberturaCaixa();
		if($abertura == -1 && env("CAIXA_PARA_NFE") == 1){
			session()->flash("mensagem_erro", "Abra o caixa para vender!");
			return redirect('/caixa');
		}

		$contaPadrao = ContaBancaria::
		where('empresa_id', $this->empresa_id)
		->where('padrao', true)
		->first();

		$unidadesDeMedida = Produto::unidadesMedida();

		$tributacao = Tributacao::
		where('empresa_id', $this->empresa_id)
		->first();
		$anps = Produto::lista_ANP();

		if($tributacao->regime == 1){
			$listaCSTCSOSN = Produto::listaCST();
		}else{
			$listaCSTCSOSN = Produto::listaCSOSN();
		}
		$listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
		$listaCST_IPI = Produto::listaCST_IPI();

		$natureza = Produto::
		firstNatureza($this->empresa_id);

		$formasPagamento = FormaPagamento::
		where('empresa_id', $this->empresa_id)
		->where('status', true)
		->get();

		$usuario = Usuario::find(get_id_user());

		$usuarios = Usuario::where('empresa_id', $this->empresa_id)
		->where('ativo', 1)
		->orderBy('nome', 'asc')
		->get();

		$vendedores = [];
		foreach($usuarios as $u){
			if($u->funcionario){
				array_push($vendedores, $u);
			}
		}

			// foreach($orcamento->duplicatas as $dp){
			// 	echo $dp;
			// }
			// die;

		return view("orcamentos/edit")
		->with('naturezas', $naturezas)
		->with('formasPagamento', $formasPagamento)
		->with('config', $config)
		->with('usuario', $usuario)
		->with('vendedores', $vendedores)
		->with('listaCSTCSOSN', $listaCSTCSOSN)
		->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
		->with('listaCST_IPI', $listaCST_IPI)
		->with('natureza', $natureza)
		->with('contaPadrao', $contaPadrao)
		->with('clientes', $clientes)
		->with('categorias', $categorias)
		->with('anps', $anps)
		->with('orcamento', $orcamento)
		->with('unidadesDeMedida', $unidadesDeMedida)
		->with('tributacao', $tributacao)
		->with('transportadoras', $transportadoras)
		->with('tiposPagamento', $tiposPagamento)
		->with('lastNF', $lastNF)
		->with('listaPreco', ListaPreco::where('empresa_id', $this->empresa_id)->get())
		->with('title', "Editar Orçamento");

	}
}

private function verificaAberturaCaixa(){

	$ab = AberturaCaixa::where('ultima_venda_nfce', 0)
	->where('empresa_id', $this->empresa_id)
	->where('status', 0)
	->orderBy('id', 'desc')->first();

	$ab2 = AberturaCaixa::where('ultima_venda_nfe', 0)
	->where('empresa_id', $this->empresa_id)
	->where('status', 0)
	->orderBy('id', 'desc')->first();

	if($ab != null && $ab2 == null){
		return $ab->valor;
	}else if($ab == null && $ab2 != null){
		$ab2->valor;
	}else if($ab != null && $ab2 != null){
		if(strtotime($ab->created_at) > strtotime($ab2->created_at)){
			$ab->valor;
		}else{
			$ab2->valor;
		}
	}else{
		return -1;
	}

	if($ab != null) return $ab->valor;
	else return -1;
}

public function update(Request $request){
	try{
		$result = DB::transaction(function () use ($request) {
			$venda = $request->venda;
			$valorFrete = str_replace(",", ".", $venda['valorFrete'] ?? 0);
			$vol = $venda['volume'];

			if($vol['pesoL']){
				$pesoLiquido = str_replace(",", ".", $vol['pesoL']);
			}else{
				$pesoLiquido = 0;
			}

			if($vol['pesoB']){
				$pesoBruto = str_replace(",", ".", $vol['pesoB']);
			}else{
				$pesoBruto = 0;
			}

			if($vol['qtdVol']){
				$qtdVol = str_replace(",", ".", $vol['qtdVol']);
			}else{
				$qtdVol = 0;
			}

			$totalVenda = str_replace(",", ".", $venda['total']);

			$desconto = 0;
			if($venda['desconto']){
				$desconto = str_replace(",", ".", $venda['desconto']);
			}

			$acrescimo = 0;
			if($venda['acrescimo']){
				$acrescimo = str_replace(",", ".", $venda['acrescimo']);
			}

			$frete = null;
			if($venda['frete'] != '9'){
				$frete = Frete::create([
					'placa' => $venda['placaVeiculo'] ?? '',
					'valor' => $valorFrete ?? 0,
					'tipo' => (int)$venda['frete'],
					'qtdVolumes' => $qtdVol?? 0,
					'uf' => $venda['ufPlaca'] ?? '',
					'numeracaoVolumes' => $vol['numeracaoVol'] ?? '0',
					'especie' => $vol['especie'] ?? '*',
					'peso_liquido' => $pesoLiquido ?? 0,
					'peso_bruto' => $pesoBruto ?? 0
				]);
			}

			$dt = date("Y-m-d");
			$result = Orcamento::where('id', $venda['orcamento_id'])->first();

			$result->transportadora_id = $venda['transportadora'];
			$result->cliente_id = $venda['cliente'];
			$result->forma_pagamento = $venda['formaPagamento'];
			$result->tipo_pagamento = $venda['tipoPagamento'];
			$result->valor_total = $totalVenda;

			$result->data_entrega = $venda['data_entrega'] != '' ? $this->parseDate($venda['data_entrega']) : null;
			$result->data_retroativa =  $venda['data_retroativa'] != '' ? $this->parseDate($venda['data_retroativa']) : null;
			$result->desconto = $desconto;
			$result->acrescimo = $acrescimo;
			$result->frete_id = $frete != null ? $frete->id : null;
			$result->natureza_id = $venda['naturezaOp'];
			$result->observacao = $this->sanitizeString($venda['observacao']) ?? '';
			$result->vendedor_id = $venda['vendedor_id'] ?? null;
			$result->filial_id = $venda['filial_id'] != -1 ? $venda['filial_id'] : null;
			$result->save();

			$result->itens()->delete();
			$itens = $venda['itens'];
			foreach ($itens as $i) {
				ItemOrcamento::create([
					'orcamento_id' => $result->id,
					'produto_id' => (int) $i['codigo'],
					'quantidade' => (float) str_replace(",", ".", $i['quantidade']),
					'valor' => (float) str_replace(",", ".", $i['valor']),
					'altura' => $i['altura'],
					'largura' =>  $i['largura'],
					'profundidade' => $i['profundidade'],
					'esquerda' =>  $i['esquerda'],
					'direita' =>  $i['direita'],
					'superior' =>  $i['superior'],
					'inferior' =>  $i['inferior']
				]);
			}

			$result->duplicatas()->delete();

			$this->criarLog($result);
			if($venda['formaPagamento'] != 'a_vista' && $venda['formaPagamento'] != 'conta_crediario'){
				$fatura = $venda['fatura'];

				foreach ($fatura as $f) {
					$valorParcela = str_replace(",", ".", $f['valor']);

					$resultFatura = FaturaOrcamento::create([
						'orcamento_id' => $result->id,
						'vencimento' => $this->parseDate($f['data']),
						'valor' => $valorParcela,
						'tipo_pagamento' => $f['tipo'],
						'empresa_id' => $this->empresa_id
					]);
				}
			}else{
				$resultFatura = FaturaOrcamento::create([
					'orcamento_id' => $result->id,
					'vencimento' => date('Y-m-d'),
					'valor' => $totalVenda - $desconto,
					'tipo_pagamento' => Venda::getTipo($venda['tipoPagamento']),
					'empresa_id' => $this->empresa_id
				]);
			}
			if($venda['gerarVenda'] == 1){
				$this->criarVenda($venda, $result->id);
			}
		});

		echo json_encode($result);
	}catch(\Exception $e){
		__saveError($e, $this->empresa_id);
		return response()->json($e->getMessage(), 400);
	}

}

private function criarVenda($venda, $orcamentoId){
	$orcamento = Orcamento::find($orcamentoId);
	$frete = null;

	$result = Venda::create([
		'cliente_id' => $orcamento->cliente_id,
		'transportadora_id' => NULL,
		'forma_pagamento' => $orcamento->forma_pagamento,
		'tipo_pagamento' => $orcamento->tipo_pagamento,
		'data_entrega' => $orcamento->data_entrega,
		'data_retroativa' => $orcamento->data_retroativa,
		'usuario_id' => get_id_user(),
		'valor_total' => $orcamento->valor_total,
		'desconto' => $orcamento->desconto,
		'acrescimo' => $orcamento->acrescimo,
		'frete_id' => $frete != null ? $frete->id : null,
		'NfNumero' => 0,
		'natureza_id' => $venda['naturezaOp'],
		'path_xml' => '',
		'chave' => '',
		'sequencia_cce' => 0,
		'observacao' => $orcamento->observacao,
		'filial_id' => $orcamento->filial_id,
		'estado' => 'DISPONIVEL',
		'empresa_id' => $this->empresa_id
	]);

	$stockMove = new StockMove();
	foreach ($orcamento->itens as $i) {
		ItemVenda::create([
			'venda_id' => $result->id,
			'produto_id' => $i->produto_id,
			'quantidade' => $i->quantidade,
			'valor' => $i->valor,
			'altura' => $i->altura,
			'largura' =>  $i->largura,
			'profundidade' => $i->profundidade,
			'esquerda' =>  $i->esquerda,
			'direita' =>  $i->direita,
			'superior' =>  $i->superior,
			'inferior' =>  $i->inferior,
			'valor_custo' => $i->produto->valor_compra
		]);
			// $stockMove->downStock(
			// 	$i->produto_id, $i->quantidade, $orcamento->filial_id);

		$prod = Produto
		::where('id', $i->produto_id)
		->first();

		if(!empty($prod->receita)){
				//baixa por receita
			$receita = $prod->receita;
			foreach($receita->itens as $rec){
				$stockMove->downStock(
					$rec->produto_id,
					$i->quantidade *
					($rec->quantidade/$receita->rendimento),
					$orcamento->filial_id
				);
			}
		}else{
			$stockMove->downStock(
				$i->produto_id, $i->quantidade, $orcamento->filial_id);
		}
	}

	foreach ($orcamento->duplicatas as $key => $f) {

		$resultFatura = ContaReceber::create([
			'venda_id' => $result->id,
			'data_vencimento' => $f->vencimento,
			'data_recebimento' => $f->vencimento,
			'tipo_pagamento' => $f->tipo_pagamento,
			'valor_integral' => $f->valor,
			'valor_recebido' => 0,
			'status' => false,
			'referencia' => "Parcela, ".($key+1).", da Venda " . $result->id,
			'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->first()->id,
			'empresa_id' => $this->empresa_id,
            'filial_id' => $result->filial_id,

            'nf_modelo'       => '55',
            'nf_numero'       => $result->NfNumero ?? 0,
            'nf_data_emissao' => $result->data_emissao ?? $result->created_at ?? null,
            'nf_chave'        => $result->chave ?? null,
		]);
	}

	$orcamento->estado = 'APROVADO';
	$orcamento->venda_id = $result->id;
	$orcamento->save();
}

public function create(){
	$cotacao = null;
	if(isset(request()->cotacao_id)){
		$cotacao = Cotacao::with('itens')->findOrFail(request()->cotacao_id);
	}
	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();
	if($config == null){
		return redirect('configNF');
	}
	$lastNF = Venda::lastNF();

	$naturezas = NaturezaOperacao::
	where('empresa_id', $this->empresa_id)
	->get();

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();

	$categorias = Categoria::
	where('empresa_id', $this->empresa_id)
	->get();

	$tributacao = Tributacao::
	where('empresa_id', $this->empresa_id)
	->first();

	$clientes = Cliente::
	where('empresa_id', $this->empresa_id)
	->where('inativo', 0)
	->get();

	$tiposPagamento = Venda::tiposPagamento();

	if(count($naturezas) == 0 || $config == null || count($categorias) == 0 || $tributacao == null || count($clientes) == 0){

		$p = view("vendas/alerta")
		->with('categorias', count($categorias))
		->with('clientes', count($clientes))
		->with('naturezas', $naturezas)
		->with('produtos', 0)
		->with('config', $config)
		->with('tributacao', $tributacao)
		->with('title', "Validação para Emitir");
		return $p;

	}else{

		$transportadoras = Transportadora::
		where('empresa_id', $this->empresa_id)
		->get();

		foreach($clientes as $c){
			$c->cidade;
		}

		$abertura = $this->verificaAberturaCaixa();
		if($abertura == -1 && env("CAIXA_PARA_NFE") == 1){
			session()->flash("mensagem_erro", "Abra o caixa para vender!");
			return redirect('/caixa');
		}

		$contaPadrao = ContaBancaria::
		where('empresa_id', $this->empresa_id)
		->where('padrao', true)
		->first();

		$unidadesDeMedida = Produto::unidadesMedida();

		$tributacao = Tributacao::
		where('empresa_id', $this->empresa_id)
		->first();
		$anps = Produto::lista_ANP();

		if($tributacao->regime == 1){
			$listaCSTCSOSN = Produto::listaCST();
		}else{
			$listaCSTCSOSN = Produto::listaCSOSN();
		}
		$listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
		$listaCST_IPI = Produto::listaCST_IPI();

		$natureza = Produto::
		firstNatureza($this->empresa_id);

		$formasPagamento = FormaPagamento::
		where('empresa_id', $this->empresa_id)
		->where('status', true)
		->get();

		$usuario = Usuario::find(get_id_user());

		$usuarios = Usuario::where('empresa_id', $this->empresa_id)
		->where('ativo', 1)
		->orderBy('nome', 'asc')
		->get();

		$vendedores = [];
		foreach($usuarios as $u){
			if($u->funcionario){
				array_push($vendedores, $u);
			}
		}

		$p = view("orcamentos/register")
		->with('naturezas', $naturezas)
		->with('formasPagamento', $formasPagamento)
		->with('config', $config)
		->with('usuario', $usuario)
		->with('vendedores', $vendedores)
		->with('listaCSTCSOSN', $listaCSTCSOSN)
		->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
		->with('listaCST_IPI', $listaCST_IPI)
		->with('natureza', $natureza)
		->with('contaPadrao', $contaPadrao)
		->with('clientes', $clientes)
		->with('categorias', $categorias)
		->with('anps', $anps)
		->with('cotacao', $cotacao)
		->with('unidadesDeMedida', $unidadesDeMedida)
		->with('tributacao', $tributacao)
		->with('transportadoras', $transportadoras)
		->with('tiposPagamento', $tiposPagamento)
		->with('lastNF', $lastNF)
		->with('listaPreco', ListaPreco::where('empresa_id', $this->empresa_id)->get())
		->with('title', "Novo Orçamento");

		return $p;
	}
}

public function validaEstoque(Request $request){
	$itens = $request->itens;
	$orcamento = Orcamento::findOrFail($request->orcamento_id);
	$msg = "";

	foreach ($itens as $i) {
		$i = (object)$i;
		$produto = Produto::findOrFail($i->codigo);

		if($produto->gerenciar_estoque == 1){
			$quantidadeDisponivel = $produto->estoquePorLocal($orcamento->filial_id);
			if($i->quantidade > $quantidadeDisponivel){
				$msg .= $produto->nome . " com quantidade insuficiente | ";
			}
		}
	}
	$msg = substr($msg, 0, strlen($msg)-2);

	return response()->json($msg, 200);
}

}
