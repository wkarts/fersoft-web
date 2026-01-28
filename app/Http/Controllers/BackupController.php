<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupController extends Controller
{
    // public function index(Request $request){

    //     $tables = $this->getTables($request->empresa_id);
    //     // echo "Total de tabelas: " . sizeof($tables);
    //     $return = "";
    //     foreach($tables as $table => $sql){

    //         try{
    //             $struture = DB::getSchemaBuilder()->getColumnListing($table);

    //             $fields = Schema::getColumnListing($table);

    //             $createSql = $this->createTableSql($table);

    //             $return .= "\n\n" . $createSql . ";\n\n";

    //             if($sql){
    //                 $result = DB::select($sql);

    //                 foreach($result as $r){
    //                     $array = json_decode(json_encode($r), true);
    //                     $return .= "INSERT INTO $table VALUES";
    //                     $return .= "(";
    //                     for ($i=0; $i < sizeof($fields); $i++) { 

    //                         $return .= $this->retiraAcentos($array[$fields[$i]], $fields[$i]);

    //                         $return .= ($i < sizeof($fields)-1 ? ', ' : '');
    //                     }
    //                     $return .= ");";

    //                 }
    //             }

    //         }catch(\Exception $e){
    //             echo $e->getMessage();
    //         }

    //     }
    //     // die;
    //     $myfile = fopen("backup.sql", "w") or die("Unable to open file!");
    //     fwrite($myfile, $return);
    //     fclose($myfile);

    //     return response()->download("backup.sql");
    //     echo $return;
    // }

    // private function retiraAcentos($value, $field){
    //     if(in_array($field, $this->isColumnNull())){
    //         return 1;
    //     }
    //     return "'".str_replace("'", "", $value)."'";
    // }

    // private function getTables($empresa_id){
    //     // $tables = DB::select("show tables");
    //     $data = [
    //         'empresas' => "SELECT * FROM empresas WHERE id = $empresa_id",
    //         'usuarios' => "SELECT * FROM usuarios WHERE empresa_id = $empresa_id",
    //         'abertura_caixas' => "SELECT * FROM abertura_caixas WHERE empresa_id = $empresa_id",
    //         'cidades' => "SELECT * FROM cidades",
    //         'clientes' => "SELECT * FROM clientes WHERE empresa_id = $empresa_id",
    //         'categorias' => "SELECT * FROM categorias WHERE empresa_id = $empresa_id",
    //         'fornecedors' => "SELECT * FROM fornecedors WHERE empresa_id = $empresa_id",
    //         'natureza_operacaos' => "SELECT * FROM natureza_operacaos WHERE empresa_id = $empresa_id",
    //         'vendas' => "SELECT * FROM vendas WHERE empresa_id = $empresa_id",
    //         'item_vendas' => "SELECT * FROM item_vendas INNER JOIN vendas on vendas.id = item_vendas.venda_id WHERE empresa_id = $empresa_id",
    //     ];

    //     return $data;
    // }

    // private function createTableSql($table){
    //     $createSql = "SHOW CREATE TABLE $table;";

    //     $createSql = DB::select($createSql);
    //     if($createSql){
    //         foreach($createSql as $cr){
    //             foreach($cr as $key => $c){
    //                 if($key != 'Table') return $c;
    //             }
    //         }
    //     }
    // }

    // private function isColumnNull(){
    //     return [
    //         'cidade_cobranca_id'
    //     ];
    // }

    public function index(){
        $appName = env("APP_NAME");
        $dir = public_path() . "/../storage/app/$appName";
        $files = array_diff(scandir($dir), array('.', '..', '.DS_Store'));
        foreach($files as $file){
            unlink("$dir/$file");
        }

        \Artisan::call('backup:run');
        
        $files = array_diff(scandir($dir), array('.', '..', '.DS_Store'));
        $file = null;
        foreach($files as $f){
            $file = $f; 
        }

        if(file_exists("$dir/$file")){
            return response()->download("$dir/$file");
        }
    }

    public function sql(){

        if(env("APP_ENV") == "demo"){
            session()->flash("mensagem_erro", "Esta funcionalidade não esta disponivel em modo de demonstração!");
            return redirect('/empresas');
        }
        if(!is_dir(public_path('bkp'))){
            mkdir(public_path('bkp'), 0777, true);
        }
        \Spatie\DbDumper\Databases\MySql::create()
        ->setDbName(env("DB_DATABASE"))
        ->setUserName(env("DB_USERNAME"))
        ->setPassword(env("DB_PASSWORD"))
        ->dumpToFile(public_path('bkp/').'bd.sql');

        if(file_exists(public_path('bkp/') . "bd.sql")){
            $zip = new \ZipArchive();
            $zip_file = public_path('zips/') . 'sql.zip';

            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFile(public_path('bkp/') . "bd.sql");
            $zip->close();

            return response()->download(public_path('zips/') . "sql.zip");
        }else{
            echo "arquivo não encontrado!";
        }
    }

}

