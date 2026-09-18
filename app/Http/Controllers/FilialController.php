<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Filial;
use App\Models\Cidade;
use App\Models\ConfigNota;
use App\Models\NaturezaOperacao;
use NFePHP\Common\Certificate;

class FilialController extends Controller
{
    use \App\Traits\GeocodeTrait;

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

    public function index(Request $request){

        $data = Filial::
        where('empresa_id', $this->empresa_id)
            ->get();

        return view('filial.index')
            ->with('title', 'Filiais')
            ->with('data', $data);

    }

    public function create(){

        $cidades = Cidade::all();
        $naturezas = NaturezaOperacao::where('empresa_id', $this->empresa_id)->get();
        $planoContas = \App\Models\PlanoContasContabil::where('empresa_id', $this->empresa_id)->get();
        $infoCertificado = null;

        return view('filial.create', ['title' => 'Nova Localização'],
            compact('cidades', 'naturezas', 'infoCertificado', 'planoContas'))
            ->with('testeJs', true);

    }

    public function edit($id){
        $config = Filial::findOrFail($id);
        if(valida_objeto($config)){

            $planoContas = \App\Models\PlanoContasContabil::where('empresa_id', $this->empresa_id)->get();
            $cidades = Cidade::all();
            $naturezas = NaturezaOperacao::where('empresa_id', $this->empresa_id)->get();
            $infoCertificado = null;

            if($config->arquivo_certificado != null){
                $infoCertificado = $this->getInfoCertificado($config);
            }

            return view('filial.create', ['title' => 'Editar Localização'],
                compact('cidades', 'naturezas', 'config', 'infoCertificado', 'planoContas'))
                ->with('testeJs', true);

        } else {
            return redirect('/403');
        }
    }

    private function getInfoCertificado($config){

        try{

            $infoCertificado = Certificate::readPfx($config->arquivo_certificado, $config->senha_certificado);
            $publicKey = $infoCertificado->publicKey;
            $inicio =  $publicKey->validFrom->format('Y-m-d H:i:s');
            $expiracao =  $publicKey->validTo->format('Y-m-d H:i:s');

            return [
                'serial' => $publicKey->serialNumber,
                'inicio' => \Carbon\Carbon::parse($inicio)->format('d-m-Y H:i'),
                'expiracao' => \Carbon\Carbon::parse($expiracao)->format('d-m-Y H:i'),
                'id' => $publicKey->commonName
            ];

        }catch(\Exception $e){
            return null;
        }

    }

    public function store(Request $request){

        $this->_validate($request);

        $logo_name = "";

        if($request->hasFile('file')){
            $file = $request->file('file');
            $extensao = $file->getClientOriginalExtension();
            $rand = rand(0, 999999);
            $logo_name = md5($file->getClientOriginalName()).$rand.".".$extensao;
            $upload = $file->move(public_path('logos'), $logo_name);
        }

        $certificado = null;
        if($request->hasFile('certificado')){
            $file = $request->file('certificado');
            $certificado = safe_file_get_contents($file);
        }

        $cidade = Cidade::find($request->cidade);
        $codMun = $cidade->codigo;
        $uf = $cidade->uf;
        $cUF = ConfigNota::getCodUF($uf);
        $municipio = $cidade->nome;

        $enderecoBusca = $request->logradouro . ', ' . $request->numero . ', ' . $request->bairro . ', ' . $municipio . ' - ' . $uf;
        $coords = $this->buscarCoordenadas($enderecoBusca);

        $request->merge([
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'numero_serie_cte' => $request->numero_serie_cte ?? 0,
            'numero_serie_mdfe' => $request->numero_serie_mdfe ?? 0,
            'ultimo_numero_cte' => $request->ultimo_numero_cte ?? 0,
            'ultimo_numero_mdfe' => $request->ultimo_numero_mdfe ?? 0,
            'email' => $request->email ?? '',
            'municipio' => strtoupper($municipio),
            'codMun' => $codMun,
            'codPais' => '1058',
            'UF' => $uf,
            'cUF' => $cUF,
            'inscricao_municipal' => $request->inscricao_municipal ?? '',
            'complemento' => $request->complemento ?? '',
            'aut_xml' => $request->aut_xml ?? '',
            'senha_certificado' => $request->senha_certificado ?? '',
            'arquivo_certificado' => $certificado,
            'logo' => $logo_name,
            'permitir_estoque_negativo' => $request->has('permitir_estoque_negativo') ? 1 : 0,
        ]);

        try{
            Filial::create($request->all());
            session()->flash("mensagem_sucesso", "Localização criada com sucesso!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }

        return redirect()->route('filial.index');

    }

    public function update(Request $request, $id){
        $this->_validate($request);
        $item = Filial::findOrFail($id);

        $logo_name = "";
        if($request->hasFile('file')){

            if($item->logo != ''){
                if(file_exists(public_path('logos/').$item->logo)){
                    unlink(public_path('logos/').$item->logo);
                }
            }
            $file = $request->file('file');

            $extensao = $file->getClientOriginalExtension();
            $rand = rand(0, 999999);
            $logo_name = md5($file->getClientOriginalName()).$rand.".".$extensao;
            $upload = $file->move(public_path('logos'), $logo_name);
        }

        $certificado = null;
        if($request->hasFile('certificado')){
            $file = $request->file('certificado');
            $certificado = safe_file_get_contents($file);
            $request->merge([
                'arquivo_certificado' => $certificado
            ]);
        }

        $cidade = Cidade::find($request->cidade);
        $codMun = $cidade->codigo;
        $uf = $cidade->uf;
        $cUF = ConfigNota::getCodUF($uf);
        $municipio = $cidade->nome;

        $enderecoBusca = $request->logradouro . ', ' . $request->numero . ', ' . $request->bairro . ', ' . $municipio . ' - ' . $uf;
        $coords = $this->buscarCoordenadas($enderecoBusca, $municipio, $uf); // Passando os novos parâmetros

        $request->merge([
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'numero_serie_cte' => $request->numero_serie_cte ?? 0,
            'numero_serie_mdfe' => $request->numero_serie_mdfe ?? 0,
            'ultimo_numero_cte' => $request->ultimo_numero_cte ?? 0,
            'ultimo_numero_mdfe' => $request->ultimo_numero_mdfe ?? 0,
            'email' => $request->email ?? '',
            'municipio' => strtoupper($municipio),
            'codMun' => $codMun,
            'codPais' => '1058',
            'UF' => $uf,
            'cUF' => $cUF,
            'complemento' => $request->complemento ?? '',
            'inscricao_municipal' => $request->inscricao_municipal ?? '',
            'aut_xml' => $request->aut_xml ?? '',
            'senha_certificado' => $request->senha_certificado ?? '',
            'logo' => $logo_name,
            'permitir_estoque_negativo' => $request->has('permitir_estoque_negativo') ? 1 : 0,
        ]);

        try{
            $item->fill($request->all())->save();
            session()->flash("mensagem_sucesso", "Localização atualizada com sucesso!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }

        return redirect()->route('filial.index');

    }

    private function _validate(Request $request){
        $rules = [
            'descricao' => 'required|max:60',
            'razao_social' => 'required|max:60',
            'nome_fantasia' => 'required|max:60',
            'cnpj' => 'required',
            'ie' => 'required',
            'logradouro' => 'required|max:80',
            'numero' => 'required|max:10',
            'bairro' => 'required|max:50',
            'fone' => 'required|max:20',
            'email' => 'max:60',
            'cep' => 'required',
            // 'municipio' => 'required',
            // 'codMun' => 'required',
            // 'uf' => 'required|max:2|min:2',
            'ultimo_numero_nfe' => 'required',
            'ultimo_numero_nfce' => 'required',
            // 'ultimo_numero_cte' => 'required',
            // 'ultimo_numero_mdfe' => 'required',
            'numero_serie_nfe' => 'required|max:3',
            'numero_serie_nfce' => 'required|max:3',
            // 'numero_serie_cte' => 'required|max:3',
            // 'numero_serie_mdfe' => 'required|max:3',
            'csc' => 'required',
            'csc_id' => 'required',
            'file' => 'max:2000',
        ];

        $messages = [
            'descricao.required' => 'O campo descrição nome é obrigatório.',
            'descricao.max' => '60 caracteres maximos permitidos.',
            'razao_social.required' => 'O Razão social nome é obrigatório.',
            'razao_social.max' => '60 caracteres maximos permitidos.',
            'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
            'nome_fantasia.max' => '60 caracteres maximos permitidos.',
            'cnpj.required' => 'O campo CNPJ é obrigatório.',
            'logradouro.required' => 'O campo Logradouro é obrigatório.',
            'ie.required' => 'O campo Inscrição Estadual é obrigatório.',
            'logradouro.max' => '80 caracteres maximos permitidos.',
            'numero.required' => 'O campo Numero é obrigatório.',
            'cep.required' => 'O campo CEP é obrigatório.',
            'municipio.required' => 'O campo Municipio é obrigatório.',
            'numero.max' => '10 caracteres maximos permitidos.',
            'bairro.required' => 'O campo Bairro é obrigatório.',
            'bairro.max' => '50 caracteres maximos permitidos.',
            'fone.required' => 'O campo Telefone é obrigatório.',
            'fone.max' => '20 caracteres maximos permitidos.',

            'uf.required' => 'O campo UF é obrigatório.',
            'uf.max' => 'UF inválida.',
            'uf.min' => 'UF inválida.',

            'pais.required' => 'O campo Pais é obrigatório.',
            'codPais.required' => 'O campo Código do Pais é obrigatório.',
            'codMun.required' => 'O campo Código do Municipio é obrigatório.',
            'rntrc.max' => '12 caracteres maximos permitidos.',
            'ultimo_numero_nfe.required' => 'Campo obrigatório.',
            'ultimo_numero_nfe.required' => 'Campo obrigatório.',
            'ultimo_numero_nfce.required' => 'Campo obrigatório.',
            'ultimo_numero_cte.required' => 'Campo obrigatório.',
            'ultimo_numero_mdfe.required' => 'Campo obrigatório.',
            'numero_serie_nfe.required' => 'Campo obrigatório.',
            'numero_serie_nfe.max' => 'Maximo de 3 Digitos.',
            'numero_serie_nfce.required' => 'Campo obrigatório.',
            'numero_serie_nfce.max' => 'Maximo de 3 Digitos.',
            'numero_serie_cte.required' => 'Campo obrigatório.',
            'numero_serie_cte.max' => 'Maximo de 3 Digitos.',
            'numero_serie_mdfe.required' => 'Campo obrigatório.',
            'numero_serie_mdfe.max' => 'Maximo de 3 Digitos.',
            'csc.required' => 'O CSC é obrigatório.',
            'csc_id.required' => 'O CSCID é obrigatório.',
            'file.max' => 'Upload de até 2000KB.',
            'email.required' => 'Campo obrigatório.',
            'email.max' => 'Máximo de 60caracteres.',
            'email.email' => 'Email inválido.',
        ];

        $this->validate($request, $rules, $messages);
    }

    public function removeLogo($id){
        $config = Filial::findOrFail($id);

        if($config->logo != ''){
            if(file_exists(public_path('logos/').$config->logo)){
                unlink(public_path('logos/').$config->logo);
            }
        }
        $config->logo = '';
        $config->save();
        session()->flash("mensagem_sucesso", "Logo removida!");
        return redirect()->back();
    }

    public function destroy($id){
        try{

            $config = Filial::findOrFail($id);

            if($config->logo != ''){
                if(file_exists(public_path('logos/').$config->logo)){
                    unlink(public_path('logos/').$config->logo);
                }
            }
            $config->delete();

            session()->flash("mensagem_sucesso", "Localização removida!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->route('filial.index');
    }

}
