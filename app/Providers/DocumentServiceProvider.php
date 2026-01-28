<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CustomDanfe;
use App\Services\CustomDacte;
//use App\Services\CustomDamdfe;
//use App\Services\CustomDabpe;
//use App\Services\CustomDanfce;
//use App\Services\CustomNfeEvento;
//use App\Services\CustomMdfeEvento;
//use App\Services\CustomCteEvento;

use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\MDFe\Damdfe;
use NFePHP\DA\BPe\Dabpe;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Daevento as DaeventoNFe;
use NFePHP\DA\MDFe\Daevento as DaeventoMDFe;
use NFePHP\DA\CTe\Daevento as DaeventoCTe;

class DocumentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Danfe::class, CustomDanfe::class);
        $this->app->bind(Dacte::class, CustomDacte::class);
        //$this->app->bind(Damdfe::class, CustomDamdfe::class);
        //$this->app->bind(Dabpe::class, CustomDabpe::class);
        //$this->app->bind(Danfce::class, CustomDanfce::class);
        //$this->app->bind(DaeventoNFe::class, CustomNfeEvento::class);
        //$this->app->bind(DaeventoMDFe::class, CustomMdfeEvento::class);
        //$this->app->bind(DaeventoCTe::class, CustomCteEvento::class);
    }

    public function boot()
    {
        $config = config('danfe');
        CustomDanfe::setRodapeConfig($config);
        CustomDacte::setRodapeConfig($config);
        //CustomDamdfe::setRodapeConfig($config);
        //CustomDabpe::setRodapeConfig($config);
        //CustomDanfce::setRodapeConfig($config);
        //CustomNfeEvento::setRodapeConfig($config);
        //CustomMdfeEvento::setRodapeConfig($config);
        //CustomCteEvento::setRodapeConfig($config);
    }
}
