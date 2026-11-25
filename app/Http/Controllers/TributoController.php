<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tributacao;
use App\Models\Produto;
use Illuminate\Support\Facades\Schema;

class TributoController extends Controller
{
    protected $empresa_id = null;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id ?? session('user_logged')['empresa'] ?? null;

            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(Request $request){
        $empresaId = $request->empresa_id ?? $this->empresa_id;
        $regimes = Tributacao::regimes();
        $tributo = Tributacao::where('empresa_id', $empresaId)->first();

        return view('tributos/index')
            ->with('tributo', $tributo)
            ->with('regimes', $regimes)
            ->with('title', 'Configurar Tributação');
    }

    public function save(Request $request){
        $this->_validate($request);

        $empresaId = $request->empresa_id ?? $this->empresa_id;

        $csll      = $request->csll !== null && $request->csll !== '' ? __replace($request->csll) : 0;
        $ir        = $request->ir !== null && $request->ir !== '' ? __replace($request->ir) : 0;
        $aliqIbsUf = $request->aliq_ibs_uf !== null && $request->aliq_ibs_uf !== '' ? __replace($request->aliq_ibs_uf) : 0.10;
        $aliqCbs   = $request->aliq_cbs !== null && $request->aliq_cbs !== '' ? __replace($request->aliq_cbs) : 0.90;

        $trib = Tributacao::where('empresa_id', $empresaId)->first();

        if($request->id == 0 || !$trib){
            $dadosCreate = [
                'icms'                     => __replace($request->icms),
                'pis'                      => __replace($request->pis),
                'cofins'                   => __replace($request->cofins),
                'ipi'                      => __replace($request->ipi),
                'csll'                     => $csll,
                'ir'                       => $ir,
                'aliq_ibs_uf'              => $aliqIbsUf,
                'aliq_cbs'                 => $aliqCbs,
                'perc_ap_cred'             => $request->perc_ap_cred ? __replace($request->perc_ap_cred) : 0,
                'regime'                   => $request->regime,
                'exclusao_icms_pis_cofins' => $request->exclusao_icms_pis_cofins ?? 0,
                'ncm_padrao'               => $request->ncm_padrao ?? '',
                'link_nfse'                => $request->link_nfse ?? '',
                'empresa_id'               => $empresaId
            ];

            if ($request->has('aliq_ibs_mun') && Schema::hasColumn('tributacaos', 'aliq_ibs_mun')) {
                $dadosCreate['aliq_ibs_mun'] = $request->aliq_ibs_mun !== '' ? __replace($request->aliq_ibs_mun) : 0;
            }
            if ($request->has('cst_ibs_cbs') && Schema::hasColumn('tributacaos', 'cst_ibs_cbs')) {
                $dadosCreate['cst_ibs_cbs'] = $request->cst_ibs_cbs ?? '000';
            }
            if ($request->has('class_trib_ibs_cbs') && Schema::hasColumn('tributacaos', 'class_trib_ibs_cbs')) {
                $dadosCreate['class_trib_ibs_cbs'] = $request->class_trib_ibs_cbs ?? '000001';
            }

            $result = Tributacao::create($dadosCreate);
        }else{
            if($trib->regime != $request->regime){
                $this->alteraProdutos($request->regime);
            }

            $trib->icms                     = __replace($request->icms);
            $trib->pis                      = __replace($request->pis);
            $trib->cofins                   = __replace($request->cofins);
            $trib->ipi                      = __replace($request->ipi);
            $trib->csll                     = $csll;
            $trib->ir                       = $ir;
            $trib->aliq_ibs_uf              = $aliqIbsUf;
            $trib->aliq_cbs                 = $aliqCbs;
            $trib->perc_ap_cred             = $request->perc_ap_cred ? __replace($request->perc_ap_cred) : 0;
            $trib->regime                   = $request->regime;
            $trib->exclusao_icms_pis_cofins = $request->exclusao_icms_pis_cofins ?? 0;
            $trib->ncm_padrao               = $request->ncm_padrao ?? '';
            $trib->link_nfse                = $request->link_nfse ?? '';

            if ($request->has('aliq_ibs_mun') && Schema::hasColumn('tributacaos', 'aliq_ibs_mun')) {
                $trib->aliq_ibs_mun = $request->aliq_ibs_mun !== '' ? __replace($request->aliq_ibs_mun) : 0;
            }
            if ($request->has('cst_ibs_cbs') && Schema::hasColumn('tributacaos', 'cst_ibs_cbs')) {
                $trib->cst_ibs_cbs = $request->cst_ibs_cbs ?? '000';
            }
            if ($request->has('class_trib_ibs_cbs') && Schema::hasColumn('tributacaos', 'class_trib_ibs_cbs')) {
                $trib->class_trib_ibs_cbs = $request->class_trib_ibs_cbs ?? '000001';
            }

            $result = $trib->save();
        }

        if($result){
            session()->flash("mensagem_sucesso", "Configurado com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao configurar!');
        }

        return redirect('/tributos');
    }

    private function alteraProdutos($regime){
        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();
        if($regime == 1){
            foreach($produtos as $p){
                if($p->CST_CSOSN == '102'){
                    $p->CST_CSOSN = '00';
                }

                if($p->CST_CSOSN == '500'){
                    $p->CST_CSOSN = '60';
                }
                $p->save();
            }
        }else{
            foreach($produtos as $p){
                if($p->CST_CSOSN == '00'){
                    $p->CST_CSOSN = '102';
                }

                if($p->CST_CSOSN == '60'){
                    $p->CST_CSOSN = '500';
                }
                $p->save();
            }
        }
    }

    private function _validate(Request $request){
        $rules = [
            'icms' => 'required',
            'pis' => 'required',
            'cofins' => 'required',
            'ipi' => 'required'
        ];

        $messages = [
            'icms.required' => 'O campo ICMS é obrigatório.',
            'pis.required' => 'O campo PIS é obrigatório.',
            'cofins.required' => 'O campo COFINS é obrigatório.',
            'ipi.required' => 'O campo IPI é obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }
}
