<?php

/*
|-------------------------------------------------------------------------- 
| Compatibilidade SPED-DA / tc-lib-barcode 1.x
|--------------------------------------------------------------------------
|
| O nfephp-org/sped-da v1.x ainda depende oficialmente de
| tecnickcom/tc-lib-barcode ^1. A partir da tc-lib-barcode 1.18.5 a própria
| biblioteca passou a emitir E_USER_DEPRECATED no construtor, oferecendo
| este sinalizador oficial para aplicações que ainda dependem da série 1.x.
|
| Sem esse sinalizador, o handler de erros do SPED-DA transforma o aviso em
| exception e interrompe a geração de DANFE/DACTE/DAMDFE.
|
| Remover quando o nfephp-org/sped-da adotar oficialmente tc-lib-barcode ^2.
|
*/
if (!defined('TCLIB_BARCODE_SILENCE_DEPRECATION')) {
    define('TCLIB_BARCODE_SILENCE_DEPRECATION', true);
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
