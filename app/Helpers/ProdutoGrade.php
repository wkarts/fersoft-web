<?php

namespace App\Helpers;

use App\Models\Estoque;
use App\Models\Produto;
use Illuminate\Support\Str;
use App\Models\AlteracaoEstoque;
use App\Models\ProdutoEcommerce;
use App\Models\ImagemProdutoEcommerce;
use App\Helpers\StockMove;
use App\Models\ConfigNota;
use App\Services\IbptService;
use App\Models\ProdutoIbpt;
use App\Models\TributacaoUf;

class ProdutoGrade {
	
	public function salvar($request, $nomeImagem, $randUpdate = null){

		if($randUpdate == null){
			$rand = Str::random(20);
		}else{
			$rand = $randUpdate;
		}

		$combinacoes = json_decode($request->combinacoes);

		if(!$combinacoes) return "erro";

		$locais = json_encode($request->local);
		if($request->local == null){
			$locais = "[-1]";
		}
		$request->merge([ 'locais' => $locais ]);

		foreach($combinacoes as $key => $comb){
			$request->merge([ 'valor_venda' => str_replace(",", ".", $comb->valor)]);
			$request->merge([ 'codBarras' => $comb->cod_barras ? str_replace(",", ".", $comb->cod_barras) : 'SEM GTIN']);
			$request->merge([ 'referencia_grade' => $rand]);
			$request->merge([ 'grade' => true ]);
			$request->merge([ 'referencia' => $request->referencia ?? '' ]);
			$request->merge([ 'imagem' => $nomeImagem ]);
			$request->merge([ 'str_grade' => $comb->titulo ]);
			$request->merge([ 'CEST' => $request->CEST ?? '']);
			$request->merge([ 'unidade_tributavel' => $request->unidade_tributavel != '' ? 
				$request->unidade_tributavel : '']);
			$request->merge([ 'quantidade_tributavel' => $request->quantidade_tributavel != '' ? __replace($request->quantidade_tributavel) : '']);
			$request->merge([ 'renavam' => $request->renavam ?? '']);
			$request->merge([ 'placa' => $request->placa ?? '']);
			$request->merge([ 'chassi' => $request->chassi ?? '']);
			$request->merge([ 'combustivel' => $request->combustivel ?? '']);
			$request->merge([ 'ano_modelo' => $request->ano_modelo ?? '']);
			$request->merge([ 'cor_veiculo' => $request->cor_veiculo ?? '']);
			$request->merge([ 'CST_CSOSN_EXP' => $request->input('CST_CSOSN_EXP') ?? '']);
			$request->merge([ 'cBenef' => $request->cBenef ? $request->cBenef : '']);

			try{
				$produto = Produto::create($request->all());
				$this->criarLog($produto, $request->empresa_id);
				$this->saveIbpt($produto, $request->empresa_id);
				$this->inserePercentualPorEstado($produto, $request->empresa_id);

				if($request->ecommerce){
					$this->salvarProdutoEcommerce($request, $produto, $nomeImagem, str_replace(",", ".", $comb->valor));
				}
				$estoque = __replace($comb->quantidade);

				if($estoque > 0){
					$data = [
						'produto_id' => $produto->id,
						'usuario_id' => get_id_user(),
						'quantidade' => $estoque,
						'tipo' => 'incremento',
						'observacao' => '',
						'empresa_id' => $request->empresa_id
					];
					AlteracaoEstoque::create($data);
					$stockMove = new StockMove();
					if($locais == "[-1]"){
						$result = $stockMove->pluStock($produto->id, 
							$estoque, str_replace(",", ".", $produto->valor_venda));
					}
				}
			}catch(\Exception $e){
				echo $e->getMessage();
				die;
				return $e->getMessage();
			}
		}
		return "ok";
	}

	private function inserePercentualPorEstado($produto, $empresa_id){
		$tribucoesCadastradas = TributacaoUf::
		select('tributacao_ufs.uf')
		->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
		->where('empresa_id', $empresa_id)
		->distinct()
		->get();

		foreach($tribucoesCadastradas as $t){
			$ex = TributacaoUf::
			where('produto_id', $produto->id)
			->where('uf', $t->uf)
			->exists();

			if(!$ex){
				$temp = TributacaoUf::
				where('uf', $t->uf)
				->first();

				$res = TributacaoUf::create([
					'produto_id' => $produto->id,
					'uf' => $t->uf,
					'percentual_icms' => $temp->percentual_icms
				]);

			}
		}

	}

	private function criarLog($objeto, $empresa_id){
		if(isset(session('user_logged')['log_id'])){

			$record = [
				'tipo' => 'criar',
				'usuario_log_id' => session('user_logged')['log_id'],
				'tabela' => 'produtos',
				'registro_id' => $objeto->id,
				'empresa_id' => $empresa_id
			];
			__saveLog($record);
		}
	}

	public function update($request, $nomeImagem, $randUpdate = null){

		if($randUpdate == null){
			$rand = Str::random(20);
		}else{
			$rand = $randUpdate;
		}

		$combinacoes = json_decode($request->combinacoes);
		if(!$combinacoes) return "erro";

		foreach($combinacoes as $key => $comb){
			if($key > 0 && $randUpdate != null){
				$request->merge([ 'valor_venda' => str_replace(",", ".", $comb->valor)]);
				$request->merge([ 'codBarras' => $comb->cod_barras ? str_replace(",", ".", $comb->cod_barras) : 'SEM GTIN']);
				$request->merge([ 'referencia_grade' => $rand]);
				$request->merge([ 'grade' => true ]);
				$request->merge([ 'referencia' => $request->referencia ?? '' ]);
				$request->merge([ 'imagem' => $nomeImagem ]);
				$request->merge([ 'imagem' => $nomeImagem ]);
				$request->merge([ 'str_grade' => $comb->titulo ]);
				$request->merge([ 'CEST' => $request->CEST ?? '']);
				$request->merge([ 'unidade_tributavel' => $request->unidade_tributavel != '' ? 
					$request->unidade_tributavel : '']);
				$request->merge([ 'quantidade_tributavel' => $request->quantidade_tributavel != '' ? __replace($request->quantidade_tributavel) : '']);
				$request->merge([ 'renavam' => $request->renavam ?? '']);
				$request->merge([ 'placa' => $request->placa ?? '']);
				$request->merge([ 'chassi' => $request->chassi ?? '']);
				$request->merge([ 'combustivel' => $request->combustivel ?? '']);
				$request->merge([ 'ano_modelo' => $request->ano_modelo ?? '']);
				$request->merge([ 'cor_veiculo' => $request->cor_veiculo ?? '']);
				$request->merge([ 'CST_CSOSN_EXP' => $request->input('CST_CSOSN_EXP') ?? '']);
				$request->merge([ 'cBenef' => $request->cBenef ? $request->cBenef : '']);
				$request->merge([ 'tipo_dimensao' => $request->tipo_dimensao ? $request->tipo_dimensao : '']);
				$request->merge([ 'lote' => $request->lote ?? '']);
				$request->merge([ 'vencimento' => $request->vencimento ?? '']);

				try{

					$produto = Produto::create($request->all());
					if($request->ecommerce){
						$this->salvarProdutoEcommerce($request, $produto, $nomeImagem);
					}
					$estoque = __replace($comb->quantidade);

					if($estoque > 0){
						$data = [
							'produto_id' => $produto->id,
							'usuario_id' => get_id_user(),
							'quantidade' => $estoque,
							'tipo' => 'incremento',
							'observacao' => '',
							'empresa_id' => $request->empresa_id
						];
						AlteracaoEstoque::create($data);
						$stockMove = new StockMove();
						$result = $stockMove->pluStock($produto->id, 
							$estoque, str_replace(",", ".", $produto->valor_venda));
					}
				}catch(\Exception $e){
					echo $e->getMessage();
					die;
					return $e->getMessage();
				}
			}
		}
		return "ok";
	}

	private function salvarProdutoEcommerce($request, $produto, $nomeImagem, $valor){
        // $this->_validateEcommerce($request);

		$produtoEcommerce = [
			'produto_id' => $produto->id,
			'categoria_id' => $request->categoria_ecommerce_id ? $request->categoria_ecommerce_id : $categoriaFirst->id,
			'empresa_id' => $request->empresa_id,
			'descricao' => $request->descricao  ?? '',
			'controlar_estoque' => $request->input('controlar_estoque') ? true : false,
			'status' => $request->input('status') ? true : false ,
			'valor' => __replace($valor),
			'destaque' => $request->input('destaque') ? true : false
		];

		$result = ProdutoEcommerce::create($produtoEcommerce);
		$produtoEcommerce = ProdutoEcommerce::find($result->id);
		if($result){
			$this->salveImagemProdutoEcommerce($nomeImagem, $produtoEcommerce);
		}

	}

	private function salveImagemProdutoEcommerce($nomeImagem, $produtoEcommerce){

		if($nomeImagem != ""){

			$extensao = substr($nomeImagem, strlen($nomeImagem)-4, strlen($nomeImagem));
			$novoNome = Str::random(20) . $extensao;
			copy(public_path('imgs_produtos/').$nomeImagem, public_path('ecommerce/produtos/').$novoNome);
            // $upload = $file->move(public_path('ecommerce/produtos'), $nomeImagem);

			ImagemProdutoEcommerce::create(
				[
					'produto_id' => $produtoEcommerce->id, 
					'img' => $novoNome
				]
			);

		}else{

		}
	}


	private function saveIbpt($produto, $empresa_id){
		$config = ConfigNota::
		where('empresa_id', $empresa_id)
		->first();

		if($config->token_ibpt != ""){
			$ibptService = new IbptService($config->token_ibpt, preg_replace('/[^0-9]/', '', $config->cnpj));
			$data = [
				'ncm' => preg_replace('/[^0-9]/', '', $produto->NCM),
				'uf' => $config->UF,
				'extarif' => 0,
				'descricao' => $produto->nome,
				'unidadeMedida' => $produto->unidade_venda,
				'valor' => number_format(0, $config->casas_decimais),
				'gtin' => $produto->codBarras,
				'codigoInterno' => 0
			];
			$resp = $ibptService->consulta($data);
			if(!isset($resp->httpcode)){
				$dataIbpt = [
					'produto_id' => $produto->id,
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
		}
	}

	public function salvarDoEcommerce($request, $nomeImagem, $randUpdate = null, $arr = null){

		if($randUpdate == null){
			$rand = Str::random(20);
		}else{
			$rand = $randUpdate;
		}

		$combinacoes = json_decode($request->combinacoes);

		if(!$combinacoes) return "erro";

		foreach($combinacoes as $key => $comb){
			$arr[ 'valor_venda' ] = str_replace(",", ".", $comb->valor);
			$arr[ 'codBarras'] = $comb->cod_barras ? str_replace(",", ".", $comb->cod_barras) : 'SEM GTIN';
			$arr[ 'referencia_grade' ] = $rand;
			$arr[ 'grade' ] = true ;
			$arr[ 'referencia' ] = $request->referencia ?? '';
			$arr[ 'imagem' ] = $nomeImagem;
			$arr[ 'str_grade' ] = $comb->titulo;
			$arr[ 'CEST' ] = $request->CEST ?? '';
			$arr[ 'unidade_tributavel' ] = $request->unidade_tributavel != '' ? 
			$request->unidade_tributavel : '';
			$arr[ 'quantidade_tributavel' ] = $request->quantidade_tributavel != '' ? __replace($request->quantidade_tributavel) : '';
			$arr[ 'renavam' ] = $request->renavam ?? '';
			$arr[ 'placa' ] = $request->placa ?? '';
			$arr[ 'chassi' ] = $request->chassi ?? '';
			$arr[ 'combustivel' ] = $request->combustivel ?? '';
			$arr[ 'ano_modelo' ] = $request->ano_modelo ?? '';
			$arr[ 'cor_veiculo' ] = $request->cor_veiculo ?? '';
			$arr[ 'CST_CSOSN_EXP' ] = $request->input('CST_CSOSN_EXP') ?? '';
			$arr[ 'cBenef' ] = $request->cBenef ? $request->cBenef : '';

			try{
				$produto = Produto::create($arr);
				$this->criarLog($produto, $request->empresa_id);
				$this->saveIbpt($produto, $request->empresa_id);

				if($arr['ecommerce']){
					$this->salvarProdutoEcommerce($request, $produto, $nomeImagem, str_replace(",", ".", $comb->valor));
				}
				$estoque = __replace($comb->quantidade);

				if($estoque > 0){
					$data = [
						'produto_id' => $produto->id,
						'usuario_id' => get_id_user(),
						'quantidade' => $estoque,
						'tipo' => 'incremento',
						'observacao' => '',
						'empresa_id' => $request->empresa_id
					];
					AlteracaoEstoque::create($data);
					$stockMove = new StockMove();
					$result = $stockMove->pluStock($produto->id, 
						$estoque, str_replace(",", ".", $produto->valor_venda));
				}
			}catch(\Exception $e){
				echo $e->getMessage();
				die;
				return $e->getMessage();
			}
		}
		return "ok";
	}

}