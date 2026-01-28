<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe;

class DanfeServiceProvider extends ServiceProvider
{
    /**
     * Registra serviços e substitui a implementação original de Danfe.
     */
    public function register()
    {
        // Substituir o binding para usar a classe customizada
        $this->app->bind(Danfe::class, CustomDanfe::class);
    }

    /**
     * Realiza configurações durante o boot.
     */
    public function boot()
    {
        // Carregar configurações personalizadas para o rodapé da DANFE
        CustomDanfe::setRodapeConfig(config('danfe'));
    }
}
