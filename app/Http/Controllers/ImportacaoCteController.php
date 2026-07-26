<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cte;
use App\Models\ContaReceber;
use App\Models\Cliente;
use App\Models\Cidade;
use App\Models\CategoriaConta;
use App\Models\NaturezaOperacao;
use App\Models\Veiculo;
use Illuminate\Support\Facades\DB;

class ImportacaoCteController extends BaseController
{
    public function __construct()
    {
        $this->model = Cte::class;
        $this->redirectPage = '/importarCte';
        parent::__construct();
    }

    protected function rules(): array 
    { 
        return [
            'xmls' => 'required',
            'categoria_id' => 'required',
            'prazo' => 'required'
        ]; 
    }

    protected function messages(): array 
    { 
        return [
            'xmls.required' => 'Selecione os arquivos XML.',
            'categoria_id.required' => 'A categoria é obrigatória.',
            'prazo.required' => 'O prazo é obrigatório.'
        ]; 
    }

    public function index(Request $request = null)
    {
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'LIKE', 'receber%')
            ->orderBy('nome', 'asc')
            ->get();

        if ($categorias->isEmpty()) {
            $categorias = CategoriaConta::where('tipo', 'LIKE', 'receber%')->get();
        }

        return view('cte.importar', [
            'categorias' => $categorias, 
            'title' => 'Importação de CT-e'
        ]);
    }
public function importarLote(Request $request)
    {
        $request->validate([
            'xmls' => ['required', 'array', 'min:1', 'max:100'],
            'xmls.*' => ['required', 'file', 'mimes:xml,text/xml', 'max:5120'],
            'categoria_id' => ['required', 'integer'],
            'prazo' => ['required', 'integer', 'min:0', 'max:3650'],
            'filial_id' => ['nullable'],
        ]);

        $empresaId = (int) $this->empresa_id;
        $filialId = (int) $request->input('filial_id', 0);
        $filialId = $filialId > 0 ? $filialId : null;
        if ($filialId !== null && !DB::table('filials')->where('empresa_id', $empresaId)->where('id', $filialId)->exists()) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'A filial informada não pertence à empresa atual.');
        }
        $categoria = CategoriaConta::query()->where('empresa_id', $empresaId)->whereKey((int)$request->categoria_id)->first();
        if (!$categoria) return redirect()->back()->withInput()->with('mensagem_erro', 'Categoria financeira inválida.');

        $sucessos = 0; $duplicados = 0; $erros = [];
        foreach ((array)$request->file('xmls') as $arquivo) {
            $numeroCte = null;
            try {
                $conteudo = file_get_contents($arquivo->getRealPath());
                $anterior = libxml_use_internal_errors(true);
                $xml = simplexml_load_string($conteudo, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
                libxml_clear_errors(); libxml_use_internal_errors($anterior);
                if (!$xml) throw new \RuntimeException('XML inválido.');

                $nodes = $xml->xpath('//*[local-name()="infCte"]');
                if (!$nodes) throw new \RuntimeException('O arquivo não contém um CT-e autorizado.');
                $infCte = $nodes[0];
                $numeroCte = trim((string)$infCte->ide->nCT);
                $chave = preg_replace('/\D+/', '', str_replace('CTe','',(string)$infCte['Id']));
                if ($numeroCte === '' || strlen($chave) !== 44) throw new \RuntimeException('Número ou chave do CT-e inválido.');
                $dataEmissao = \Carbon\Carbon::parse((string)($infCte->ide->dhEmi ?? now()))->format('Y-m-d');

                $duplicadoCte = Cte::query()->where('empresa_id',$empresaId)->where(function($q) use($chave,$numeroCte){$q->where('chave',$chave)->orWhere('cte_numero',$numeroCte);})
                    ->where(function($q) use($filialId){$filialId===null?$q->whereNull('filial_id'):$q->where('filial_id',$filialId);})->exists();
                $duplicadoConta = ContaReceber::query()->where('empresa_id',$empresaId)->where('numero_nota_fiscal',$numeroCte)->where('nf_modelo','57')
                    ->where(function($q) use($filialId){$filialId===null?$q->whereNull('filial_id'):$q->where('filial_id',$filialId);})->exists();
                if ($duplicadoCte || $duplicadoConta) { $duplicados++; continue; }

                DB::transaction(function() use($infCte,$numeroCte,$chave,$dataEmissao,$empresaId,$filialId,$categoria,$request,$conteudo){
                    $naturezaDescricao=trim((string)$infCte->ide->natOp);
                    $natureza=NaturezaOperacao::query()->where('empresa_id',$empresaId)
                        ->when($filialId!==null,fn($q)=>$q->where(function($q2)use($filialId){$q2->where('filial_id',$filialId)->orWhereNull('filial_id');}))
                        ->where('natureza','like','%'.$naturezaDescricao.'%')->first()
                        ?? NaturezaOperacao::query()->where('empresa_id',$empresaId)->first();
                    if(!$natureza) throw new \RuntimeException('Cadastre uma natureza de operação antes da importação.');
                    $veiculo=Veiculo::query()->where('empresa_id',$empresaId)
                        ->when($filialId!==null,fn($q)=>$q->where(function($q2)use($filialId){$q2->where('filial_id',$filialId)->orWhereNull('filial_id');}))
                        ->first();
                    if(!$veiculo) throw new \RuntimeException('Cadastre um veículo antes da importação.');
                    $cidadeId=function($codigo){return Cidade::query()->where('codigo',(string)$codigo)->value('id') ?: 1;};
                    $remetente=$this->buscarOuCriarParticipante($infCte->rem,$empresaId);
                    $destinatario=$this->buscarOuCriarParticipante($infCte->dest,$empresaId);
                    $tomador=$this->identificarTomador($infCte,$empresaId);
                    Cte::create([
                        'empresa_id'=>$empresaId,'filial_id'=>$filialId,'usuario_id'=>$this->usuario_id,
                        'remetente_id'=>$remetente->id,'destinatario_id'=>$destinatario->id,'natureza_id'=>$natureza->id,'veiculo_id'=>$veiculo->id,
                        'municipio_envio'=>$cidadeId($infCte->ide->cMunEnv),'municipio_inicio'=>$cidadeId($infCte->ide->cMunIni),'municipio_fim'=>$cidadeId($infCte->ide->cMunFim),'municipio_tomador'=>$cidadeId($infCte->ide->cMunEnv),
                        'chave'=>$chave,'cte_numero'=>$numeroCte,'data_emissao'=>$dataEmissao,'chave_nfe'=>(string)($infCte->infCTeNorm->infDoc->infNFe->chave ?? ''),
                        'valor_transporte'=>(float)$infCte->vPrest->vTPrest,'valor_receber'=>(float)$infCte->vPrest->vRec,'valor_carga'=>(float)($infCte->infCTeNorm->infCarga->vCarga ?? 0),
                        'produto_predominante'=>(string)($infCte->infCTeNorm->infCarga->proPred ?? 'DIVERSOS'),'data_previsata_entrega'=>\Carbon\Carbon::parse($dataEmissao)->addDay()->format('Y-m-d'),
                        'tomador'=>(int)($infCte->ide->toma3->toma ?? 3),'estado'=>'APROVADO','path_xml'=>$chave.'.xml','retira'=>(int)($infCte->ide->retira ?? 0),'modal'=>(string)($infCte->ide->modal ?? '01'),
                        'tpDoc'=>'00','observacao'=>'','sequencia_cce'=>0,'detalhes_retira'=>'','descOutros'=>'','nDoc'=>0,'vDocFisc'=>0,'globalizado'=>0,
                    ]);
                    ContaReceber::create([
                        'empresa_id'=>$empresaId,'filial_id'=>$filialId,'usuario_id'=>$this->usuario_id,'cliente_id'=>$tomador->id,'categoria_id'=>$categoria->id,
                        'nf_modelo'=>'57','nf_chave'=>$chave,'nf_data_emissao'=>$dataEmissao,'numero_nota_fiscal'=>$numeroCte,'valor_integral'=>(float)$infCte->vPrest->vRec,
                        'data_vencimento'=>\Carbon\Carbon::parse($dataEmissao)->addDays((int)$request->prazo)->format('Y-m-d'),'data_emissao'=>$dataEmissao,'status'=>false,'referencia'=>'CT-e '.$numeroCte,
                    ]);
                    $pasta=public_path('xml_cte'); if(!is_dir($pasta)&&!@mkdir($pasta,0755,true)&&!is_dir($pasta)) throw new \RuntimeException('Não foi possível criar a pasta de XML de CT-e.');
                    if(@file_put_contents($pasta.DIRECTORY_SEPARATOR.$chave.'.xml',$conteudo,LOCK_EX)===false) throw new \RuntimeException('Não foi possível salvar o XML do CT-e.');
                },3);
                $sucessos++;
            } catch(\Throwable $e) {
                \Log::error('Falha ao importar CT-e em lote',['empresa_id'=>$empresaId,'cte'=>$numeroCte,'arquivo'=>$arquivo->getClientOriginalName(),'erro'=>$e->getMessage()]);
                $erros[]=$arquivo->getClientOriginalName().': '.$e->getMessage();
            }
        }
        $mensagem="Importados: {$sucessos} | Duplicados: {$duplicados} | Erros: ".count($erros);
        $redirect=redirect($this->redirectPage)->with('mensagem_sucesso',$mensagem);
        if($erros) $redirect->with('mensagem_erro',implode(' | ',array_slice($erros,0,5)));
        return $redirect;
    }

    private function buscarOuCriarParticipante($node, $empresa_id)
    {
        $cnpj = (string)($node->CNPJ ?? $node->CPF);
        $cliente = Cliente::where('cpf_cnpj', $cnpj)->where('empresa_id', $empresa_id)->first();
        if (!$cliente) {
            $cliente = Cliente::create([
                'empresa_id' => $empresa_id,
                'razao_social' => (string)$node->xNome,
                'cpf_cnpj' => $cnpj,
                'cidade_id' => 1 
            ]);
        }
        return $cliente;
    }

    private function identificarTomador($infCte, $empresa_id)
    {
        $tomaPos = (int)($infCte->ide->toma3->toma ?? 3);
        $tags = ['rem', 'exped', 'receb', 'dest'];
        $tagBusca = $tags[$tomaPos] ?? 'dest';
        return $this->buscarOuCriarParticipante($infCte->$tagBusca, $empresa_id);
    }
}