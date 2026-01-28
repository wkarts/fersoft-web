<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IBPT;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\ItemIBTE;
use App\Models\ProdutoIbpt;
use App\Services\IbptService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class IbptController extends Controller
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

        set_time_limit(300); // Aumenta o tempo limite para evitar timeout
	}

	public function index(){
		$ibtes = IBPT::all();
		return view('ibpt/list')
		->with('ibtes', $ibtes)
		->with('title', 'IBPT');
	}

    public function new(){
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $uf_tenante = $config ? strtolower($config->UF) : '';

        $todos = IBPT::estados();
        $estados = [];

        foreach($todos as $uf){
            $res = IBPT::where('uf', $uf)->first();
            if($res == null){
                array_push($estados, $uf);
            }
        }

        return view('ibpt/new')
            ->with('estados', $estados)
            ->with('uf_tenante', $uf_tenante)
            ->with('title', 'IBPT');
    }

    public function new_(){
		$todos = IBPT::estados();
		$estados = [];
		foreach($todos as $uf){
			$res = IBPT::where('uf', $uf)->first();
			if($res == null){
				array_push($estados, $uf);
			}
		}

		return view('ibpt/new')
		->with('estados', $estados)
		->with('title', 'IBPT');
	}

    public function refresh($id)
    {
        try {
            $ibpt = IBPT::find($id);

            if (!$ibpt) {
                Log::error("Erro no refresh: IBPT ID {$id} não encontrado.");
                return redirect('/ibpt')->with('mensagem_erro', 'Registro não encontrado!');
            }

            // Se IBPT existir, pega a UF correta
            $uf_tenante = !empty($ibpt->uf) ? strtolower($ibpt->uf) : '';

            Log::info("Executando refresh para IBPT ID: {$id}, UF: {$uf_tenante}");

            return view('ibpt/new', [
                'ibpt' => $ibpt,
                'uf_tenante' => $uf_tenante,
                'title' => 'IBPT',
            ]);
        } catch (\Exception $e) {
            Log::error("Erro crítico no refresh: " . $e->getMessage());
            return redirect('/ibpt')->with('mensagem_erro', 'Erro ao carregar o registro.');
        }
    }

    public function refresh_($id){
		$ibpt = IBPT::find($id);

		return view('ibpt/new')
		->with('ibpt', $ibpt)
		->with('title', 'IBPT');
	}

	public function importar(Request $request){
		if ($request->hasFile('file')){
			$file = $request->file;
			$handle = fopen($file, "r");
			$row = 0;
			$linhas = [];

			if($request->ibpt_id == 0){
				$result = IBPT::create(
					[
						'uf' => $request->uf,
						'versao' => $request->versao,
					]
				);
			}else{
				$result = IBPT::find($request->ibpt_id);
				$result->versao = $request->versao;
				$result->save();
				ItemIBTE::where('ibte_id', $request->ibpt_id)->delete();
			}

			while ($line = fgetcsv($handle, 1000, ";")) {
				if ($row++ == 0) {
					continue;
				}

				$data = [
					'ibte_id' => $result->id,
					'codigo' => $line[0],
					'descricao' => $line[3],
					'nacional_federal' => $line[4],
					'importado_federal' => $line[5],
					'estadual' => $line[6],
					'municipal' => $line[7]
				];
				ItemIBTE::create($data);

			}
			if($request->ibpt_id > 0){
				session()->flash('mensagem_sucesso', 'Importação atualizada para '.$request->uf);
			}else{
				session()->flash('mensagem_sucesso', 'Importação concluída para '.$request->uf);
			}
			return redirect("/ibpt");


		}else{
			if($request->ibpt_id > 0){
				$result = IBPT::find($request->ibpt_id);
				$result->versao = $request->versao;
				session()->flash('mensagem_sucesso', 'Versão atualizada!');
				$result->save();
			}else{
				session()->flash('mensagem_erro', 'Arquivo inválido!');
			}
			return redirect("/ibpt");
		}
	}

	public function ver($id){
		$ibpt = IBPT::find($id);
		$itens = ItemIBTE::where('ibte_id', $id)->paginate(100);
		return view('ibpt/ver')
		->with('ibpt', $ibpt)
		->with('itens', $itens)
		->with('links', true)
		->with('title', 'IBPT');
	}

	public function atualizaIbpt(){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$produtos = Produto::where('empresa_id', $this->empresa_id)
		->get();

		$ibptService = new IbptService($config->token_ibpt, preg_replace('/[^0-9]/', '', $config->cnpj));
		$produtosAtualizados = 0;

		foreach($produtos as $p){
			if($p->NCM){
				$data = [
					'ncm' => preg_replace('/[^0-9]/', '', $p->NCM),
					'uf' => $config->UF,
					'extarif' => 0,
					'descricao' => $p->nome,
					'unidadeMedida' => $p->unidade_venda,
					'valor' => number_format(0, $config->casas_decimais),
					'gtin' => $p->codBarras,
					'codigoInterno' => 0
				];

				$resp = $ibptService->consulta($data);
				if(isset($resp->httpcode)){
					if($resp->httpcode == 403){
						session()->flash('mensagem_erro', $resp->response);
						return redirect("/produtos");
					}
				}
				try{
					if($p->ibpt){
						$ibpt = $p->ibpt;
						$ibpt->codigo = $resp->Codigo;
						$ibpt->uf = $resp->UF;
						$ibpt->descricao = $resp->Descricao;
						$ibpt->nacional = $resp->Nacional;
						$ibpt->estadual = $resp->Estadual;
						$ibpt->importado = $resp->Importado;
						$ibpt->municipal = $resp->Municipal;
						$ibpt->vigencia_inicio = $resp->VigenciaInicio;
						$ibpt->vigencia_fim = $resp->VigenciaFim;
						$ibpt->chave = $resp->Chave;
						$ibpt->versao = $resp->Versao;
						$ibpt->fonte = $resp->Fonte;
						$ibpt->save();
					}else{
						$dataIbpt = [
							'produto_id' => $p->id,
							'codigo' => $resp->Codigo,
							'uf' => $resp->UF,
							'descricao' => $resp->Descricao,
							'nacional' => $resp->Nacional,
							'estadual' => $resp->Estadual,
							'importado' => $resp->Importado,
							'municipal' => $resp->Municipal,
							'vigencia_inicio' => $resp->VigenciaInicio,
							'vigencia_fim' => $resp->VigenciaFim,
							'chave' => $resp->Chave,
							'versao' => $resp->Versao,
							'fonte' => $resp->Fonte
						];

						ProdutoIbpt::create($dataIbpt);
					}
					$produtosAtualizados++;
				}catch(\Exception $e){
				// echo $e->getMessage();
				// echo "<pre>";
				// print_r($resp);
				// echo "</pre>";

				}
			}
		}

		session()->flash('mensagem_sucesso', 'Produtos atualizados');
		return redirect("/produtos");

	}

	public function atualizaApi(){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$produtos = Produto::where('empresa_id', $this->empresa_id)
		->get();

		$ibptService = new IbptService($config->token_ibpt, preg_replace('/[^0-9]/', '', $config->cnpj));

		$produtosAtualizados = 0;
		foreach($produtos as $p){
			$data = [
				'ncm' => preg_replace('/[^0-9]/', '', $p->NCM),
				'uf' => $config->UF,
				'extarif' => 0,
				'descricao' => $p->nome,
				'unidadeMedida' => $p->unidade_venda,
				'valor' => number_format(0, $config->casas_decimais),
				'gtin' => $p->codBarras,
				'codigoInterno' => 0
			];

			$resp = $ibptService->consulta($data);

			if(isset($resp->httpcode)){
				if($resp->httpcode == 403){
					return response()->json($resp->response, 401);
				}
			}

			try{
				if($p->ibpt){
					$ibpt = $p->ibpt;
					$ibpt->codigo = $resp->Codigo;
					$ibpt->uf = $resp->UF;
					$ibpt->descricao = $resp->Descricao;
					$ibpt->nacional = $resp->Nacional;
					$ibpt->estadual = $resp->Estadual;
					$ibpt->importado = $resp->Importado;
					$ibpt->municipal = $resp->Municipal;
					$ibpt->vigencia_inicio = $resp->VigenciaInicio;
					$ibpt->vigencia_fim = $resp->VigenciaFim;
					$ibpt->chave = $resp->Chave;
					$ibpt->versao = $resp->Versao;
					$ibpt->fonte = $resp->Fonte;
					$ibpt->save();
				}else{
					$dataIbpt = [
						'produto_id' => $p->id,
						'codigo' => $resp->Codigo,
						'uf' => $resp->UF,
						'descricao' => $resp->Descricao,
						'nacional' => $resp->Nacional,
						'estadual' => $resp->Estadual,
						'importado' => $resp->Importado,
						'municipal' => $resp->Municipal,
						'vigencia_inicio' => $resp->VigenciaInicio,
						'vigencia_fim' => $resp->VigenciaFim,
						'chave' => $resp->Chave,
						'versao' => $resp->Versao,
						'fonte' => $resp->Fonte
					];

					ProdutoIbpt::create($dataIbpt);
				}


				$produtosAtualizados++;
			}catch(\Exception $e){
				// echo $e->getMessage();
				// die;

			}
		}
		if($produtosAtualizados == 0){
			return response()->json("Finalizado", 200);
		}
		return response()->json("Produtos atualizados.", 200);
	}

    public function importarAutomatico(Request $request)
    {
        try {
            if (!$request->has('uf') || empty($request->uf)) {
                Log::error("UF não informada na importação automática.");
                return response()->json(['error' => "UF não informada para a importação."], 400);
            }

            $uf = strtolower($request->uf);
            Log::info("Iniciando importação para a UF: {$uf}");

            $ibptService = new IbptService();
            $filePath = $ibptService->baixarCsvPorUf($uf);

            if (!$filePath) {
                Log::error("Erro ao baixar o CSV para a UF: {$uf}");
                return response()->json(['error' => "Erro ao baixar a tabela IBPT para {$uf}"], 500);
            }

            $dados = $ibptService->lerCsv($filePath);
            if (empty($dados)) {
                Log::error("Erro ao processar CSV. O arquivo pode estar corrompido ou vazio.");
                return response()->json(['error' => "Erro ao processar o CSV de {$uf}"], 500);
            }

            // Criar ou atualizar o IBPT
            $ibpt = IBPT::updateOrCreate(['uf' => strtoupper($uf)], [
                'versao' => $dados[0]['versao'] ?? 'Desconhecida',
            ]);

            session(['import_progress' => 0]);

            $chunkSize = 500;
            $chunks = array_chunk($dados, $chunkSize);
            $totalChunks = count($chunks);

            foreach ($chunks as $index => $chunk) {
                foreach ($chunk as $linha) {
                    ItemIBTE::updateOrCreate(
                        ['ibte_id' => $ibpt->id, 'codigo' => $linha['codigo']],
                        [
                            'descricao' => $linha['descricao'],
                            'nacional_federal' => $linha['nacional_federal'],
                            'importado_federal' => $linha['importado_federal'],
                            'estadual' => $linha['estadual'],
                            'municipal' => $linha['municipal'],
                            'vigencia_inicio' => $linha['vigencia_inicio'],
                            'vigencia_fim' => $linha['vigencia_fim'],
                            'chave' => $linha['chave'],
                            'versao' => $linha['versao'],
                            'fonte' => $linha['fonte'],
                        ]
                    );
                }
                session(['import_progress' => round(($index + 1) / $totalChunks * 100)]);
            }

            // Excluir CSV após a importação
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            Log::info("Importação concluída para a UF {$uf}, versão: {$ibpt->versao}");

            return response()->json([
                'redirect' => url('/ibpt'),
                'versao' => $ibpt->versao
            ]);

        } catch (\Exception $e) {
            Log::error("Erro crítico na importação: " . $e->getMessage());
            return response()->json(['error' => "Erro na importação: " . $e->getMessage()], 500);
        }
    }

    public function getProgress()
    {
        $progress = session('import_progress', 0);
        Log::info("Consulta de progresso: {$progress}% concluído.");
        return response()->json(['progress' => $progress]);
    }



}
