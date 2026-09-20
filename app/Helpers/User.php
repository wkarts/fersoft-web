<?php
use Illuminate\Support\Facades\DB;
use App\Models\EscritorioContabil;
use App\Models\RecordLog;
use App\Models\ConfigNota;
use App\Models\Filial;
use App\Models\Usuario;
use App\Models\ErroLog;
use App\Models\SuperAdminAlerta;
use App\Models\Redirect;
use App\Models\ConfigSystem;
use App\Http\Controllers\EvoApiInstanceController;
use App\Helpers\UserOtpHelper;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

function is_adm(){
    $usr = session('user_logged');
    return $usr['adm'];
}

function get_id_user(){
    $usr = session('user_logged');
    return $usr['id'];
}

function __replace($valor){
    return str_replace(",", ".", $valor);
}

function moeda($valor){
    return number_format($valor, 2, ',', '');
}
// function moeda_format($valor){
// 	return number_format($valor, 2, ',', '.');
// }

function __date($data, $time = true){
    if($time){
        return \Carbon\Carbon::parse($data)->format('d/m/Y H:i');
    }else{
        return \Carbon\Carbon::parse($data)->format('d/m/Y');
    }
}

//Wallace em 20/11/2024
function valida_objeto($objeto) {
    $usr = session('user_logged');

    // Caso original: valida um array simples
    if (is_array($objeto) && isset($objeto['empresa_id']) && $objeto['empresa_id'] == $usr['empresa']) {
        return true;
    }

    // Caso 1: valida objeto individual
    if (is_object($objeto) && isset($objeto->empresa_id) && $objeto->empresa_id == $usr['empresa']) {
        return true;
    }

    // Caso 2: valida coleções do Eloquent
    if ($objeto instanceof \Illuminate\Support\Collection) {
        foreach ($objeto as $item) {
            if (!isset($item->empresa_id) || $item->empresa_id != $usr['empresa']) {
                return false; // Se qualquer item não for válido, retorna false
            }
        }
        return true; // Todos os itens são válidos
    }

    return false; // Se não atender a nenhum dos casos, retorna falso
}


//Wallace em 20/11/2024
/*
function valida_objeto($objeto){
	$usr = session('user_logged');
	if(isset($objeto['empresa_id']) && $objeto['empresa_id'] == $usr['empresa']){
		return true;
	}else{
		return false;
	}
}
*/

function tabelasArmazenamento(){
    // indice nome da tabela, valor em kb
    return [
        'clientes' => 5,
        'produtos' => 8,
        'fornecedors' => 4,
        'vendas' => 4,
        'venda_caixas' => 4,
        'transportadoras' => 4,
        'orcamentos' => 4,
        'categorias' => 4,
    ];
}

function isSuper($login){
    $arrSuper = explode(',', env("USERMASTER"));

    if(in_array($login, $arrSuper)){
        return true;
    }
    return false;
}

function getSuper(){
    $arrSuper = explode(',', env("USERMASTER"));

    return $arrSuper[0];
}

function importaXmlSieg($file, $empresa_id){
    $escritorio = EscritorioContabil::
    where('empresa_id', $empresa_id)
        ->first();

    if($escritorio != null && $escritorio->token_sieg != ""){
        $url = "https://api.sieg.com/aws/api-xml.ashx";

        $curl = curl_init();

        $headers = [];

        $data = $file;
        curl_setopt($curl, CURLOPT_URL, $url . "?apikey=".$escritorio->token_sieg."&email=".$escritorio->email);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $xml = json_decode(curl_exec($curl));
        if(isset($xml->Message)){
            if($xml->Message == 'Importado com sucesso'){
                return $xml->Message;
            }
        }
        return false;
    }else{
        return false;
    }
}

function __saveLog($record){
    RecordLog::create($record);
}

function __saveError($error, $empresa_id){
    ErroLog::create([
        'arquivo' => $error->getFile(),
        'linha' => $error->getLine(),
        'erro' => $error->getMessage(),
        'empresa_id' => $empresa_id
    ]);

    __saveAlertSuper('Erro no sistema', $error->getMessage(), $empresa_id);
}

function __saveAlertSuper($tipo, $mensagem, $empresa_id){
    SuperAdminAlerta::create([
        'tipo' => $tipo,
        'mensagem' => $mensagem,
        'empresa_id' => $empresa_id
    ]);
}

function __saveRedirect($empresa_id, $rota, $local){
    $redirect = Redirect::where('empresa_id', $empresa_id)
        ->where('local', $local)->first();
    if($redirect == null){
        Redirect::create([
            'empresa_id' => $empresa_id,
            'rota' => $rota,
            'local' => $local
        ]);
    }else{
        $redirect->local = $local;
        $redirect->rota = $rota;
        $redirect->save();
    }
}

function __getRedirect($empresa_id, $local){
    $redirect = Redirect::where('empresa_id', $empresa_id)
        ->where('local', $local)->first();
    if($redirect != null){
        return $redirect->rota;
    }
    return "";
}


function valor_por_extenso($valor = 0, $maiusculas = false) {

    $singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
    $plural = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões",
        "quatrilhões");

    $c = array("", "cem", "duzentos", "trezentos", "quatrocentos",
        "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
    $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta",
        "sessenta", "setenta", "oitenta", "noventa");
    $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze",
        "dezesseis", "dezesete", "dezoito", "dezenove");
    $u = array("", "um", "dois", "três", "quatro", "cinco", "seis",
        "sete", "oito", "nove");

    $z = 0;
    $rt = "";

    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);
    for($i=0;$i<count($inteiro);$i++)
        for($ii=strlen($inteiro[$i]);$ii<3;$ii++)
            $inteiro[$i] = "0".$inteiro[$i];

    $fim = count($inteiro) - ($inteiro[count($inteiro)-1] > 0 ? 1 : 2);
    for ($i=0;$i<count($inteiro);$i++) {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

        $r = $rc.(($rc && ($rd || $ru)) ? " e " : "").$rd.(($rd &&
                $ru) ? " e " : "").$ru;
        $t = count($inteiro)-1-$i;
        $r .= $r ? " ".($valor > 1 ? $plural[$t] : $singular[$t]) : "";
        if ($valor == "000")$z++; elseif ($z > 0) $z--;
        if (($t==1) && ($z>0) && ($inteiro[0] > 0)) $r .= (($z>1) ? " de " : "").$plural[$t];
        if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) &&
                ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
    }

    if(!$maiusculas){
        return($rt ? $rt : "zero");
    } else {

        if ($rt) $rt=ereg_replace(" E "," e ",ucwords($rt));
        return (($rt) ? ($rt) : "Zero");
    }

}

function __locaisAtivosUsuario($usuario){
    $locais = $usuario->locais != 'null' && $usuario->locais != '' ? json_decode($usuario->locais) : [];
    $locaisRetorno = [];
    $locaisRetorno['-1'] = 'Matriz';

    foreach($locais as $l){
        if($l != -1){
            $f = Filial::where('status', 1)->where('id', $l)->first();
            if($f != null){
                $locaisRetorno[$f->id] = $f->descricao;
            }
        }
    }
    return $locaisRetorno;

}

function __getLocaisUsarioLogado(){
    $usr = Usuario::find(get_id_user());
    $locais = [];
    $loc = $usr->locais != null ? json_decode($usr->locais) : [];
    return $loc;
}

function getLocaisUsarioLogado(){
    $usr = Usuario::find(get_id_user());
    $locais = [];
    $loc = $usr->locais != null && $usr->locais != 'null' ? json_decode($usr->locais) : [];
    if(sizeof($loc) > 0){
        foreach($loc as $l){
            $f = Filial::find($l);
            if($l == '-1'){
                $locais['-1'] = 'Matriz';
            }else{
                if($f != null){
                    $locais[$f->id] = $f->descricao;
                }
            }
        }

    }
    return $locais;

}

function __locaisAtivos(){
    $usr = session('user_logged');

    $locais = getLocaisUsarioLogado();
    if(sizeof($locais) > 0){
        return $locais;
    }
    // $config = ConfigNota::
    // where('empresa_id', $usr['empresa'])
    // ->first();
    $filiais = Filial::
    where('empresa_id', $usr['empresa'])
        ->where('status', 1)
        ->get();

    $locais['-1'] = 'Matriz';

    // foreach($filiais as $f){
    // 	$locais[$f->id] = $f->descricao;
    // }
    return $locais;
}

function __locaisAtivosAll(){
    $usr = session('user_logged');

    $filiais = Filial::
    where('empresa_id', $usr['empresa'])
        ->where('status', 1)
        ->get();

    $locais['-1'] = 'Matriz';

    foreach($filiais as $f){

        $locais[$f->id] = $f->descricao;
    }
    return $locais;
}

function __get_local_padrao(){
    $usr = Usuario::find(get_id_user());
    // if($usr->local_padrao == -1) return NULL;
    return $usr->local_padrao;
}

function __user_all_locations(){
    if(sizeof(__locaisAtivosAll()) == sizeof(__locaisAtivos()))
        return true;
    else
        return false;
}

function __view_locais_select_home($lbl = "Local", $filial_id = null){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){

        $local_padrao = __get_local_padrao();

        $html = '<div class="form-group col-12 col-lg-2">';
        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div><div class="input-group">';
        $html .= '<select id="filial_id" name="filial_id" class="form-control custom-select">';
        if(__user_all_locations()){
            $html .= '<option value="">--</option>';
        }
        foreach($locais as $key => $l){
            $html .= '<option '. ($local_padrao == $key ? 'selected' : '') .' value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div></div>';
    }else{
        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='hidden' id='filial_id' name='filial_id' value='$v' />";
        }
    }

    return $html;
}

function __view_locais_select_relatorios(){
    $locais = __locaisAtivos();

    if (sizeof($locais) > 1) {
        $html  = '<div class="form-group col-12 col-md-6">';
        $html .= '<label class="col-form-label">Local</label>';
        $html .= '<select name="filial_id" class="form-control custom-select w-100">';
        $html .= '<option value="">--</option>';
        foreach ($locais as $key => $l) {
            $html .= '<option value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div>';
    } else {
        // quando só há um local, use hidden ou string vazia
        $v = array_key_first($locais);
        if ($v == -1) {
            $html = '';
        } else {
            $html = "<input type='hidden' name='filial_id' value='$v' />";
        }
    }

    return $html;
}

function __view_locais_select_filtro($lbl = "Local", $filial_id = null){
    $locais = __locaisAtivos();
    if(sizeof($locais) > 1){
        if($filial_id == null){

            $url = request()->fullUrl();
            if (!str_contains($url, 'filtro')) {
                $filial_id = __get_local_padrao();
            }
        }

        $html = '<div class="form-group col-12 col-lg-2">';
        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div><div class="input-group">';
        $html .= '<select id="locais" name="filial_id" class="form-control custom-select">';
        $html .= '<option value="">--</option>';
        foreach($locais as $key => $l){
            $html .= '<option '.($filial_id == $key ? 'selected' : '').' value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div></div>';
    }else{

        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='hidden' name='filial_id' value='$v' />";
        }
    }

    return $html;
}

function __view_locais_select_filtro_xml($filial_id = null){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){

        if($filial_id == null){
            $filial_id = __get_local_padrao();
        }

        $html = '<div class="form-group col-12 col-lg-2">';
        $html .= '<label class="col-form-label">Local</label>';
        $html .= '<div><div class="input-group">';
        $html .= '<select id="locais" name="filial_id" class="form-control custom-select">';
        foreach($locais as $key => $l){
            $html .= '<option '.($filial_id == $key ? 'selected' : '').' value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div></div>';
    }else{
        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='hidden' name='filial_id' value='$v' />";
        }
    }

    return $html;
}

function __view_locais_select($lbl = "Local"){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){
        $local_padrao = __get_local_padrao();
        $html = '<div class="form-group col-lg-2 col-sm-6">';

        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div>';
        $html .= '<select name="filial_id" id="filial_id" class="form-control custom-select" required>';
        $html .= '<option value="">--</option>';
        foreach($locais as $key => $l){
            $html .= '<option '. ($local_padrao == $key ? 'selected' : '') .' value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);

        $html = "<input type='hidden' id='filial_id' name='filial_id' value='$v' />";
    }

    return $html;
}

function __view_locais_select_pdv($lbl = "Local"){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){

        $html = '<div class="form-group col-lg-12 col-sm-6">';

        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div>';
        $html .= '<select name="filial_id" id="filial_id" class="form-control custom-select" required>';
        $html .= '<option value="">--</option>';
        foreach($locais as $key => $l){
            $html .= '<option value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);

        $html = "<input type='hidden' id='filial_id' name='filial_id' value='$v' />";
    }

    return $html;
}

function __view_locais_select_transfencia($lbl, $variavel){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){

        $html = '<div class="form-group col-lg-2 col-sm-6">';

        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div>';
        $html .= '<select name="'.$variavel.'" id="'.$variavel.'" class="form-control custom-select" required>';
        $html .= '<option value="">--</option>';
        foreach($locais as $key => $l){
            $html .= '<option value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);

        $html = "<input type='hidden' id='filial_id' name='filial_id' value='$v' />";
    }

    return $html;
}

function __view_locais_select_edit($lbl, $local_id){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){
        if(!$local_id){
            $local_id = -1;
        }
        $html = '<div class="form-group col-lg-2 col-sm-6">';

        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div>';
        $html .= '<select name="filial_id" id="filial_id" class="form-control custom-select" required>';
        $html .= '<option value="">--</option>';
        foreach($locais as $key => $l){
            $html .= '<option '. ($key == $local_id ? 'selected' : '') .' value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);

        $html = "<input type='hidden' id='filial_id' name='filial_id' value='$v' />";
    }

    return $html;
}

function __view_locais($lbl = "Locais de acesso"){
    $locais = __locaisAtivos();

    if(sizeof($locais) > 1){

        $html = '<div class="form-group validated col-sm-10 col-lg-3">';
        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div class="">';
        $html .= '<select id="locais" name="local[]" required class="form-control select2-custom" multiple>';
        foreach($locais as $key => $l){
            $html .= '<option value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='hidden' name='local[]' value='$v' />";
        }
    }

    return $html;
}


function __view_locais_user($lbl = "Locais de acesso"){
    $locais = __locaisAtivosAll();

    if(sizeof($locais) > 1){

        $html = '<div class="form-group validated col-sm-10 col-lg-3">';
        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div class="">';
        $html .= '<select id="locais" name="local[]" required class="form-control select2-custom" multiple>';
        foreach($locais as $key => $l){
            $html .= '<option value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='hidden' name='local[]' value='$v' />";
        }
    }

    return $html;
}

function __view_locais_user_edit($locais_ativos, $lbl = "Locais de acesso"){
    $locais = __locaisAtivosAll();

    $locais_ativos = $locais_ativos != null && $locais_ativos != 'null' ? json_decode($locais_ativos) : [];
    if(sizeof($locais) > 1){
        $html = '<div class="form-group validated col-sm-10 col-lg-3">';
        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div class="">';
        $html .= '<select id="locais" name="local[]" required class="form-control select2-custom" multiple>';
        foreach($locais as $key => $l){
            $html .= '<option '. (in_array($key, $locais_ativos) ? ' selected ' : '') .' value="'.$key.'">'.$l.'</option>';
        }
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='' name='local[]' value='$v' />";
        }
    }

    return $html;
}

function __view_locais_edit($locais_ativos, $lbl = "Locais de acesso"){
    $locais = __locaisAtivos();
    if(sizeof($locais) > 1){
        $locais_ativos = $locais_ativos == 'null' ? [] : json_decode($locais_ativos);

        $html = '<div class="form-group validated col-sm-10 col-lg-3">';
        $html .= '<label class="col-form-label">'.$lbl.'</label>';
        $html .= '<div class="">';
        $html .= '<select id="locais" name="local[]" required class="form-control select2-custom" multiple>';
        foreach($locais as $key => $l){
    $html .= '<option '. (in_array($key, (array)$locais_ativos) ? ' selected ' : '') .' value="'.$key.'">'.$l.'</option>';
}
        $html .= '</select></div></div>';
    }else{
        $v = array_key_first($locais);
        if($v == -1){
            $html = "";
        }else{
            $html = "<input type='hidden' name='local[]' value='$v' />";
        }
    }

    return $html;
}



function __get_locais($locais_ativos){
    // $locais_ativos = $locais_ativos ? json_decode($locais_ativos) : [];
    $locais_ativos = $locais_ativos != null && $locais_ativos != 'null' ? json_decode($locais_ativos) : [];

    $html = "";
    foreach($locais_ativos as $l){
        $f = Filial::find($l);
        if($l == '-1'){
            $html .= "Matriz | ";
        }else{
            if($f != null){
                $html .= "$f->descricao | ";
            }
        }

    }

    $html = substr($html, 0, strlen($html)-2);
    return $html;
}

function empresaComFilial(){
    $usr = session('user_logged');

    $filiais = Filial::
    where('empresa_id', $usr['empresa'])
        ->where('status', 1)
        ->exists();
    return $filiais;
}

function __preparaTexto($texto, $empresa){
    $texto = str_replace("{{nome}}", $empresa->nome, $texto);
    $texto = str_replace("{{rua}}", $empresa->rua, $texto);
    $texto = str_replace("{{numero}}", $empresa->numero, $texto);
    $texto = str_replace("{{bairro}}", $empresa->bairro, $texto);
    $texto = str_replace("{{nome_fantasia}}", $empresa->nome_fantasia, $texto);
    $texto = str_replace("{{email}}", $empresa->email, $texto);
    $texto = str_replace("{{cidade}}", $empresa->cidade, $texto);
    $texto = str_replace("{{cnpj}}", $empresa->cnpj, $texto);
    $texto = str_replace("{{data}}", date("d/m/Y H:i"), $texto);

    $mes =  ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $texto = str_replace("{{d}}", date("d"), $texto);
    $texto = str_replace("{{m}}", $mes[(int)date("m")], $texto);
    $texto = str_replace("{{a}}", date("Y"), $texto);
    $texto = str_replace("{{mes}}", date("m"), $texto);

    if($empresa->planoEmpresa){
        $vPlano = $empresa->planoEmpresa->valor;
        if($vPlano == 0){
            $vPlano = $empresa->planoEmpresa->plano->valor;
        }
        $valorPlano = "";
        $valorPlano = "R$ " . moeda($vPlano);
        $nomePlano = $empresa->planoEmpresa->plano->nome;
        $texto = str_replace("{{valor_plano}}", $valorPlano, $texto);
        $texto = str_replace("{{nome_plano}}", $nomePlano, $texto);

        $configSystem = ConfigSystem::first();
        if($configSystem && $configSystem->valor_base_contrato > 0){

            $vb = $vPlano/$configSystem->valor_base_contrato*100;
            $texto = str_replace("{{valor_base}}", moeda($vb), $texto);
        }
    }

    $texto = str_replace("{{uf}}", $empresa->uf, $texto);
    $texto = str_replace("{{cep}}", $empresa->cep, $texto);
    $texto = str_replace("{{representante_legal}}", $empresa->representante_legal, $texto);
    $texto = str_replace("{{cpf_representante_legal}}", $empresa->cpf_representante_legal, $texto);

    return $texto;
}
function get_filial_padrao() {
    return session('user_logged.local_padrao') ?? session('user_logged')['local_padrao'] ?? null;
}

function __view_locais_select_menu_superior_no_log() {
    $locais = __locaisAtivos();

    if (sizeof($locais) > 1) {
        $local_padrao = __get_local_padrao();

        $html = '<div class="input-group" style="width: 220px;">';

        // SELECT dropdown com ID único
        $html .= '<select id="filial_id_top" name="filial_id" class="form-control custom-select">';
        foreach ($locais as $key => $l) {
            $selected = ($local_padrao == $key) ? 'selected' : '';
            $html .= "<option value=\"$key\" $selected>$l</option>";
        }
        $html .= '</select>';

        // Botão acoplado com ID exclusivo
        $html .= '<div class="input-group-append">';
        $html .= '<button id="set-location-top" class="btn btn-success" title="Salvar filial padrão">';
        $html .= '<i class="la la-save"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>'; // fim input-group

        // Script de ação
        $html .= <<<HTML
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            var path = window.location.origin + '/';

            // Evita bind duplicado
            $('#set-location-top').off('click').on('click', function(e) {
                e.preventDefault();
                var filial_id = $('#filial_id_top').val();

                $.get(path + 'usuarios/set-location', { filial_id: filial_id })
                    .done(function(success) {
                        swal("Sucesso", "Local definido como padrão!", "success");

                        if (typeof faturamentoDosUltimosSeteDias !== "function") {
                            let script = document.createElement("script");
                            script.src = path + "js/grafico_home.js";
                            script.onload = function () {
                                console.log("grafico_home.js carregado dinamicamente.");
                                setTimeout(() => {
                                    $('#seteDias').trigger('click');
                                }, 100);
                            };
                            document.body.appendChild(script);
                        } else {
                            $('#seteDias').trigger('click');
                        }
                    })
                    .fail(function(err) {
                        console.error("Erro ao definir filial:", err);
                        swal("Opss", "Algo deu errado!", "error");
                    });
            });
        });
        </script>
HTML;

        return $html;
    } else {
        $v = array_key_first($locais);
        return $v == -1 ? "" : "<input type='hidden' id='filial_id_top' name='filial_id' value='$v' />";
    }
}

function __view_locais_select_menu_superior() {

    $locais = __locaisAtivos();

    if (sizeof($locais) > 1) {
        $local_padrao = __get_local_padrao();

        $html = '<div class="input-group" style="width: 300px">';

        // SELECT dropdown com ID único
        $html .= '<select id="filial_id_top" name="filial_id" class="form-control custom-select">';
        foreach ($locais as $key => $l) {
            $selected = ($local_padrao == $key) ? 'selected' : '';
            $html .= "<option value=\"$key\" $selected>$l</option>";
        }
        $html .= '</select>';

        // Botão acoplado com ID exclusivo
        $html .= '<div class="input-group-append">';
        $html .= '<button id="set-location-top" class="btn btn-success" title="Salvar filial padrão">';
        $html .= '<i class="la la-save"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>'; // fim input-group

        // Script de ação
        $html .= <<<HTML
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            var path = window.location.origin + '/';

            // Evita bind duplicado
            $('#set-location-top').off('click').on('click', function(e) {
                e.preventDefault();
                var filial_id = $('#filial_id_top').val();

                $.get(path + 'usuarios/set-location', { filial_id: filial_id })
                    .done(function(success) {
                        swal("Sucesso", "Local definido como padrão!", "success");

                        // 🔹 Adicionado reload após sucesso
                        setTimeout(function () {
                            location.reload();
                        }, 1000);

                        if (typeof faturamentoDosUltimosSeteDias !== "function") {
                            let script = document.createElement("script");
                            script.src = path + "js/grafico_home.js";
                            script.onload = function () {
                                console.log("grafico_home.js carregado dinamicamente.");
                                setTimeout(() => {
                                    $('#seteDias').trigger('click');
                                }, 100);
                            };
                            document.body.appendChild(script);
                        } else {
                            $('#seteDias').trigger('click');
                        }
                    })
                    .fail(function(err) {
                        console.error("Erro ao definir filial:", err);
                        swal("Opss", "Algo deu errado!", "error");
                    });
            });
        });
        </script>
HTML;
        /*
                // 🔹 Log automático (somente quando exibe o seletor)
                try {
                    $empresaId = session('empresa_id') ?? null;
                    $usuarioId = session('user_logged')['id'] ?? null;
                    $filialId = session('user_logged')['loca_padrao'] ?? null;
                    $logService = new \App\Services\LogService($empresaId, $usuarioId, $filialId);
                    $logService->registrar('update', 'SelectorFilialSuperior', [
                        'registro_id' => null,
                        'dados_antes' => null,
                        'dados_depois' => [
                            'empresa_id' => $empresaId,
                            'usuario_id' => $usuarioId,
                            'locais_disponiveis' => $locais,
                            'local_padrao' => $local_padrao,
                        ],
                    ]);
                } catch (\Exception $e) {
                    \Log::warning("Falha ao registrar log no __view_locais_select_menu_superior: " . $e->getMessage());
                }
        */
        return $html;
    } else {
        $v = array_key_first($locais);
        return $v == -1 ? "" : "<input type='hidden' id='filial_id' name='filial_id' value='$v' />";
    }
}

if (! function_exists('__view_connect_api_whatsapp_modal_button')) {
    /**
     * Gera botão + modais de envio de WhatsApp via Connect|API (texto + arquivos)
     * sem precisar passar instância por parâmetro,
     * garantindo ordem de z-index correta.
     *
     * @return string
     */
    function __view_connect_api_whatsapp_modal_button(): string
    {
        $csrf = csrf_token();
        // título do modal vindo do .env (padrão: "Enviar WhatsApp")
        $modalHintButton = env('CONNECT_API_WHATSAPP_MODAL_HINTBUTTON', 'Enviar WhatsApp');
        $modalTitle = env('CONNECT_API_WHATSAPP_MODAL_TITLE', 'Enviar WhatsApp');
        // texto do botão de envio vindo do .env (padrão: "Enviar")
        $sendText   = env('CONNECT_API_WHATSAPP_SEND_BUTTON_TEXT', 'Enviar');

        // caminho absoluto para evitar que rotas aninhadas tentem resolver /vendas/js/axios.min.js
        $axiosUrl = asset('js/axios.min.js');

        $modalHintFooter= env('CONNECT_API_WHATSAPP_MODAL_HINTFOOTER', 'Este recurso permite o envio imediato de mensagens de texto e arquivos via WhatsApp diretamente da plataforma. Caso não esteja disponível (funcional), entre em contato com o suporte para habilitá-lo.');

        return <<<HTML
<style>


  /* Modal WhatsApp: acima do backdrop */
  #modalWhatsAppGlobal {
    z-index: 1100 !important;
  }

  /* Modais de confirmação e mensagem: acima do WhatsApp */
  #modalConfirmacaoGlobal,
  #modalMensagemGlobal {
    z-index: 1200 !important;
  }
</style>

<!-- Botão que dispara o modal -->
<button id="btn-whatsapp-global" class="btn btn-primary btn-sm btn-custom" title="{$modalHintButton}" style="width:28px; height:28px; padding:4px;">
  <i class="fa fa-whatsapp"></i>
</button>

<!-- Modal de Confirmação -->
<div class="modal fade" id="modalConfirmacaoGlobal" tabindex="-1" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-hover-dark text-white">
        <h5 id="modalConfirmacaoGlobalLabel" class="modal-title">Confirmação</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div id="modalConfirmacaoGlobalMensagem" class="modal-body">
        Deseja realmente continuar?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
        <button type="button" id="btnConfirmarAcaoGlobal" class="btn btn-primary">Sim</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Mensagem -->
<div class="modal fade" id="modalMensagemGlobal" tabindex="-1" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 id="modalMensagemGlobalLabel" class="modal-title">Mensagem</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div id="modalMensagemGlobalTexto" class="modal-body">
        Mensagem exibida aqui.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Envio WhatsApp -->
<div class="modal fade" id="modalWhatsAppGlobal" tabindex="-1" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <!-- faixa azul no topo via bg-primary -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">{$modalTitle}</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Telefone (DDI DDD Número)</label>
            <input type="tel" id="wh_number_global" class="form-control" placeholder="5511999999999">
          </div>
          <div class="form-group col-md-6">
            <label>Legenda (opcional)</label>
            <input type="text" id="wh_caption_global" class="form-control" placeholder="Legenda para os arquivos">
          </div>
        </div>
        <div class="form-group">
          <label>Mensagem de Texto (opcional)</label>
          <textarea id="wh_text_msg_global" class="form-control" rows="3" placeholder="Digite sua mensagem..."></textarea>
        </div>
        <div class="form-group">
          <label>Selecione Arquivos (opcional)</label>
          <input type="file" id="wh_files_input_global" class="form-control-file" multiple>
          <small class="form-text text-muted">Você pode selecionar várias imagens, PDF etc.</small>
          <ul id="wh_file_list_global" class="list-unstyled mt-2"></ul>
        </div>
      </div>
      <div class="modal-footer">
        <div class="mr-auto text-left small text-muted" style="max-width: 60%;">
            <span class="mr-auto">{$modalHintFooter}</span>
        </div>
        <button class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        <!-- botão de envio com texto dinâmico -->
        <button id="wh_send_btn_global" class="btn btn-primary">{$sendText}</button>
      </div>
    </div>
  </div>
</div>

<script src="{$axiosUrl}"></script>
<script>
document.addEventListener("DOMContentLoaded", function(){
  let _whFilesData = [];

  // 1) Abre modal WhatsApp
  document.getElementById('btn-whatsapp-global').addEventListener('click', function(){
    _whFilesData = [];
    document.getElementById('wh_number_global').value   = '';
    document.getElementById('wh_caption_global').value  = '';
    document.getElementById('wh_text_msg_global').value = '';
    document.getElementById('wh_file_list_global').innerHTML = '';
    $('#modalWhatsAppGlobal').modal('show');
  });

  // 2) Lê múltiplos arquivos
  document.getElementById('wh_files_input_global').addEventListener('change', function(){
    _whFilesData = [];
    const ul = document.getElementById('wh_file_list_global');
    ul.innerHTML = '';
    Array.from(this.files).forEach(f => {
      const li = document.createElement('li');
      li.textContent = f.name;
      ul.append(li);
      const reader = new FileReader();
      reader.onload = () => _whFilesData.push({ name:f.name, data:reader.result });
      reader.readAsDataURL(f);
    });
  });

  // 3) Botão Enviar
  document.getElementById('wh_send_btn_global').addEventListener('click', function(){
    const number  = document.getElementById('wh_number_global').value.trim();
    if (!number) {
      showMessageGlobal('Erro','<p>Informe o número de destino.</p>','danger');
      return;
    }
    const text    = document.getElementById('wh_text_msg_global').value.trim();
    const caption = document.getElementById('wh_caption_global').value.trim();
    const files   = _whFilesData;

    confirmActionGlobal(
      'Confirmar envio',
      `<p>Enviar mensagem + arquivos para <strong>\${number}</strong>?</p>`,
      () => doSendGlobal(number, text, caption, files)
    );
  });

  function doSendGlobal(number, text, caption, files) {
    const btn = $('#wh_send_btn_global').prop('disabled', true).text('Enviando…');
    axios.post('/connect-api/send-whatsapp-button', {
      number, mode:'mixed', text, caption, files
    }, {
      headers:{ 'X-CSRF-TOKEN':'{$csrf}' }
    })
    .then(() => {
      showMessageGlobal('Sucesso','<p>Enviado com sucesso!</p>','success');
      $('#modalWhatsAppGlobal').modal('hide');
    })
    .catch(async err => {
      let msg = 'Erro no envio';
      try { const j = err.response.data; msg = j.error||j.message||msg; } catch{}
      showMessageGlobal('Erro',`<p>\${msg}</p>`,'danger');
    })
    .finally(() => {
      btn.prop('disabled', false).text('{$sendText}');
    });
  }

  // reset automático ao fechar
  $('#modalWhatsAppGlobal').on('hidden.bs.modal', function(){
    $('#wh_send_btn_global').prop('disabled', false).text('{$sendText}');
  });

  // Mostra o modal de mensagem
  function showMessageGlobal(title, html, type) {
    $('#modalConfirmacaoGlobal').modal('hide');
    $('#modalMensagemGlobalLabel').text(title);
    $('#modalMensagemGlobalTexto').html(html);
    $('#modalMensagemGlobal .modal-header')
      .removeClass('bg-success bg-warning bg-danger')
      .addClass(type==='success'?'bg-success':'bg-danger')
      .find('.modal-title').addClass('text-white');
    $('#modalMensagemGlobal').modal('show');
  }

  // Mostra o modal de confirmação
  function confirmActionGlobal(title, html, onConfirm) {
    $('#modalMensagemGlobal').modal('hide');
    $('#modalConfirmacaoGlobalLabel').text(title);
    $('#modalConfirmacaoGlobalMensagem').html(html);
    $('#modalConfirmacaoGlobal').modal('show');
    $('#btnConfirmarAcaoGlobal').off('click').on('click', function(){
      $('#modalConfirmacaoGlobal').modal('hide');
      onConfirm();
    });
  }
});
</script>
HTML;
    }
}

if (! function_exists('__view_datahora_clock')) {
    function __view_datahora_clock(): string
    {
        // já mostramos a hora “zeroth” com Carbon, formatada no timezone do servidor
        $inicial = \Carbon\Carbon::now()->format('d/m/Y H:i:s');
        return <<<HTML
<div class="topbar-item">
  <div class="btn btn-icon w-auto btn-clean d-flex align-items-center btn-lg px-2">
    <span class="kt-header__topbar-welcome kt-hidden-mobile">Data/Hora: </span>
    <span style="margin-left:3px;" class="kt-header__topbar-username kt-hidden-mobile">
      <span id="dataHoraClock" style="font-weight:bold;" class="text-info text-left lbl-custom">{$inicial}</span>
    </span>
  </div>
</div>

<script>
(function(){
  const el = document.getElementById('dataHoraClock');
  const pad = n => String(n).padStart(2,'0');

  function formatar(d) {
    return pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear()
         + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  }

  function atualizar() {
    // usa o seu getServerDate(), que já corrige o fuso do servidor
    const agora = getServerDate();
    el.textContent = formatar(agora);
  }

  // iniciar e disparar a cada segundo
  document.addEventListener('DOMContentLoaded', () => {
    setInterval(atualizar, 1000);
  });
})();
</script>
HTML;
    }
}

if (! function_exists('__view_otp_modal')) {
    function __view_otp_modal($showModal = false): string
    {
        $showModal = $showModal ? 'true' : 'false';
        return <<<HTML
<div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="otpModalLabel">Autenticação de Dois Fatores</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>Informe o código de segurança (6 dígitos):</p>
        <div id="otp-error" class="alert alert-danger d-none"></div>
        <input type="text" id="otp-code" class="form-control" maxlength="6" autocomplete="one-time-code" inputmode="numeric" placeholder="000000">
      </div>
      <div class="modal-footer">
        <button type="button" id="btn-otp-validate" class="btn btn-primary">Validar</button>
      </div>
    </div>
  </div>
</div>
<script>
$(function(){
  var showOtpModal = $showModal;
  if (showOtpModal === 'true') {
    setTimeout(function(){
      $('#otpModal').modal('show');
    }, 200);
  }
  var \$loginForm = $('form').filter(function(){
    return $(this).attr('action') && $(this).attr('action').indexOf('/login/request') !== -1;
  }).first();

  $('#otpModal').on('shown.bs.modal', function(){
    $('#otp-error').addClass('d-none').text('');
    $('#otp-code').val('').focus();
  }).on('hidden.bs.modal', function(){
    $('#otp-error').addClass('d-none').text('');
    $('#otp-code').val('');
  });

  $('#btn-otp-validate').on('click', function(e){
    e.preventDefault();
    var code = $('#otp-code').val().trim();
    var \$error = $('#otp-error');
    \$error.addClass('d-none').text('');
    if (!/^\d{6}$/.test(code)) {
      \$error.removeClass('d-none').text('Código inválido, informe 6 dígitos.');
      return;
    }
    $('#btn-otp-validate').prop('disabled', true);

    // Só valida o OTP, não faz login!
    \$.ajax({
      url: '/otp/verify-login',
      method: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        otp_code: code
      },
      success: function(resp) {
        if (resp.valid) {
          // Se válido, insere no form e submete para o fluxo normal do login!
          $('<input>')
            .attr({ type: 'hidden', name: 'otp_code', value: code })
            .appendTo(\$loginForm);
          $('#otpModal').modal('hide');
          \$loginForm.submit();
        } else {
          \$error.removeClass('d-none').text('Código OTP inválido.');
        }
      },
      error: function(xhr) {
        let msg = 'Erro ao validar código.';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          msg = xhr.responseJSON.error;
        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
          let first = Object.values(xhr.responseJSON.errors)[0];
          msg = Array.isArray(first) ? first[0] : first;
        }
        \$error.removeClass('d-none').text(msg);
      },
      complete: function(){
        $('#btn-otp-validate').prop('disabled', false);
      }
    });
  });

  $('#otp-code').on('keypress', function(e){
    if(e.which == 13) $('#btn-otp-validate').click();
  });
});
</script>
HTML;
    }
}
// Methods to New Dashboard/Panel/Graphics/MultiTenant
if (!function_exists('__empresa_id_logada')) {
    function __empresa_id_logada()
    {
        $usr = session('user_logged');
        return $usr['empresa'] ?? $usr['empresa_id'] ?? null;
    }
}

if (!function_exists('__usuario_locais_ids_logado')) {
    function __usuario_locais_ids_logado(): array
    {
        $usr = \App\Models\Usuario::find(get_id_user());
        if (!$usr) {
            return [-1];
        }

        $locais = $usr->locais != null && $usr->locais != 'null'
            ? json_decode($usr->locais, true)
            : [];

        if (!is_array($locais) || count($locais) === 0) {
            return [];
        }

        return array_map(function ($item) {
            return is_numeric($item) ? (int)$item : $item;
        }, $locais);
    }
}

if (!function_exists('__usuario_pode_ver_todos_locais')) {
    function __usuario_pode_ver_todos_locais(): bool
    {
        return __user_all_locations();
    }
}

if (!function_exists('__filial_solicitada_valida_para_usuario')) {
    function __filial_solicitada_valida_para_usuario($filialId): bool
    {
        if ($filialId === null || $filialId === '' || $filialId === 'todos') {
            return true;
        }

        if (__usuario_pode_ver_todos_locais()) {
            return true;
        }

        $locaisPermitidos = __usuario_locais_ids_logado();

        if ($filialId === 'matriz') {
            return in_array(-1, $locaisPermitidos, true) || in_array('-1', $locaisPermitidos, true);
        }

        return in_array((int)$filialId, $locaisPermitidos, true) || in_array((string)$filialId, $locaisPermitidos, true);
    }
}

if (!function_exists('__normaliza_filial_banco')) {
    function __normaliza_filial_banco($filialId)
    {
        if ($filialId === '-1' || $filialId === -1 || $filialId === 'matriz') {
            return null;
        }

        if ($filialId === '' || $filialId === 'todos') {
            return null;
        }

        return $filialId !== null ? (int)$filialId : null;
    }
}

if (!function_exists('__aplicar_filtro_empresa_filial')) {
    function __aplicar_filtro_empresa_filial($query, string $tabela = '', string $colunaFilial = 'filial_id')
    {
        $empresaId = __empresa_id_logada();
        $filialSelecionada = request()->get('filial_id', 'todos');

        $colEmpresa = $tabela ? "{$tabela}.empresa_id" : 'empresa_id';
        $colFilial = $tabela ? "{$tabela}.{$colunaFilial}" : $colunaFilial;

        $query->where($colEmpresa, $empresaId);

        /*
        |--------------------------------------------------------------------------
        | Restringe pelo escopo do usuário quando ele NÃO pode ver tudo
        |--------------------------------------------------------------------------
        */
        if (!__usuario_pode_ver_todos_locais()) {
            $locaisPermitidos = __usuario_locais_ids_logado();

            $temMatriz = in_array(-1, $locaisPermitidos, true) || in_array('-1', $locaisPermitidos, true);
            $filiaisPermitidas = array_values(array_filter($locaisPermitidos, function ($item) {
                return (string)$item !== '-1';
            }));

            $query->where(function ($q) use ($colFilial, $temMatriz, $filiaisPermitidas) {
                if ($temMatriz) {
                    $q->orWhereNull($colFilial)
                        ->orWhere($colFilial, 0);
                }

                if (count($filiaisPermitidas) > 0) {
                    $q->orWhereIn($colFilial, $filiaisPermitidas);
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Aplica o filtro escolhido na tela
        |--------------------------------------------------------------------------
        */
        if ($filialSelecionada === 'todos' || $filialSelecionada === null || $filialSelecionada === '') {
            return $query;
        }

        if (!__filial_solicitada_valida_para_usuario($filialSelecionada)) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        if ($filialSelecionada === 'matriz') {
            $query->where(function ($q) use ($colFilial) {
                $q->whereNull($colFilial)
                    ->orWhere($colFilial, 0);
            });

            return $query;
        }

        $query->where($colFilial, (int)$filialSelecionada);

        return $query;
    }
}
