<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmpresaLogada;
use App\Models\Empresa;

class EmpresasLogadaCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empresas_logada:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $empresas = Empresa::
        orderBy('id', 'desc')
        ->where('status', 1)
        ->get();

        $total = 0;
        $minutos = env("MINUTOS_ONLINE");

        foreach($empresas as $e){
            $ult = $e->ultimoLogin2($e->id);
            if($ult != null){
                $strValidade = strtotime($ult->updated_at);
                $strHoje = strtotime(date('Y-m-d H:i:s'));
                $dif = $strHoje - $strValidade;
                $dif = $dif/60;

                if((int) $dif <= $minutos && $e->usuarios[0]->login != env("USERMASTER")){
                    $total++;
                }
            }
        }

        EmpresaLogada::create(['total' => $total]);
    }
}
