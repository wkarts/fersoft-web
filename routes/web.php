<?php

Route::get('/cadastro', 'UserController@cadastro');
Route::post('/cadastro', 'UserController@salvarEmpresa');
Route::get('/plano', 'UserController@plano');
Route::get('/novoparceiro', 'UserController@novoparceiro');

Route::post('/recuperarSenha', 'UserController@recuperarSenha');

// Cadastro público de parceiro
Route::group(['prefix' => 'cadastro-parceiro'], function(){
    Route::get('/{nome_empresa}', 'PessoaPreCadastroController@createPublico');
    Route::post('/salvar', 'PessoaPreCadastroController@storePublico');
});

// PORTAL DO CLIENTE
// O NOVO não fornece middleware próprio do portal; não utilizar a sessão interna do ERP como substituta.
Route::group(['prefix' => 'portal-cliente'], function(){
    Route::get('/{empresa_id}/login', 'PortalClienteController@login');
    Route::post('/{empresa_id}/autenticar', 'PortalClienteController@autenticar');
    Route::get('/logout', 'PortalClienteController@logout');
    Route::get('/{slug}/esqueci-senha', 'PortalClienteController@esqueciSenha');
    Route::post('/{slug}/recuperar-senha', 'PortalClienteController@recuperarSenha');

    // Painel e Solicitação: a sessão do cliente deve ser validada no controller.
    Route::get('/painel', 'PortalClienteController@painel');
    Route::post('/coleta/salvar', 'PortalClienteController@storeColeta');
    Route::get('/sair', 'PortalClienteController@logout');
});

// 📋 Rota Pública do Checklist do Motorista (Acesso via WhatsApp)
// O controller deve validar a autorização do acesso externo; o ID sozinho não demonstra essa autorização.
Route::group(['prefix' => 'checklist/veiculo'], function(){
    Route::get('/{id}', 'ChecklistMovimentacaoController@showForm');
    Route::post('/{id}/salvar', 'ChecklistMovimentacaoController@store');
});

// Webhooks de comunicação entram exclusivamente por /api/webhooks/connect-api.
// O sistema de Ponto é acionado pelo dispatcher de automações após persistência/idempotência.

Route::group(['prefix' => '/ajax'], function(){
    Route::get('/', 'AjaxController@index');
});

Route::get('/clear-all', function(){
    \Artisan::call('cache:clear');
    \Artisan::call('config:clear');
    \Artisan::call('view:clear');
    // system('composer dump-autoload');
});

Route::get('/', function(){
    return redirect('/login');
});

Route::get('/run-migrate', function(){
    \Artisan::call('migrate', [
        '--force' => true,
    ]);
});
/*
Route::group(['prefix' => '/sync'], function () {
    Route::post('/data', 'SyncDataController@sync');
});
*/
Route::group(['prefix' => '/sync'], function () {
    Route::get('/data', 'SyncDataController@sync');     // para testes ou listagem
    Route::post('/data', 'SyncDataController@sync');    // para criação
    Route::put('/data', 'SyncDataController@sync');     // para atualização
    Route::delete('/data', 'SyncDataController@sync');  // para remoção
    Route::patch('/data', 'SyncDataController@sync');   // se quiser aceitar PATCH também
    Route::options('/data', 'SyncDataController@sync'); // para CORS ou negociações
    Route::get('/doc', 'SyncDataController@docSync'); // para CORS ou negociações
});

//Route::post('/sync/data', 'SyncDataController@sync');

//Route::group(['prefix' => '/getTicket'], function(){
//    Route::get('/withToken/relPrn80mm/{token}', 'PublicRelatorioController@gerarRelatorio80mm');
//    Route::get('/withToken/relPrnA4/{token}', 'PublicRelatorioController@gerarRelatorioA4');
//});

Route::group(['prefix' => '/getTicket', 'middleware' => 'throttle:10,1'], function () {
    Route::get('/withToken/relPrn80mm/{token}', 'PublicRelatorioController@gerarRelatorio80mm');
});

/* Desabilitado por Wallace em 16022026
Route::get('/teste', 'TesteController@index');

Route::group(['prefix' => '/appUpdate'], function(){
	Route::get('/', 'AppUpdateController@index');
	Route::get('/new', 'AppUpdateController@index');
	Route::get('/download', 'AppUpdateController@download');
	Route::get('/update', 'AppUpdateController@update');
	Route::get('/sql', 'AppUpdateController@sql');
	Route::post('/sql', 'AppUpdateController@sqlStore');
	Route::post('/run-sql', 'AppUpdateController@runSql');
});
*/
Route::group(['prefix' => 'login'],function(){
    Route::get('/', 'UserController@newAccess')->name('login');
    Route::get('/logoff', 'UserController@logoff');
    // Route::post('/request', 'UserController@request')->middleware('verificaPesquisa');
    Route::post('/request', 'UserController@request')->middleware('acessoUsuario');
});

Route::prefix('otp')->group(function () {
    Route::post('/verify-login', 'OtpController@verifyLoginAjax');
    // Outras rotas OTP no futuro...
});

Route::get('/response/{code}', 'CotacaoResponseController@response');
Route::get('/finish', 'CotacaoResponseController@finish')->name('catacao.finish');
Route::post('/save', 'CotacaoResponseController@save')->name('catacao.save');

Route::get('/error', function(){
    return view('sempermissao')->with('title', 'Acesso Bloqueado');
});

Route::group(['prefix' => 'migrador'], function(){
    Route::get('/{empresa_id}', 'MigradorController@index');
    Route::post('/', 'MigradorController@save');
});

Route::group(['prefix' => 'online', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'EmpresaController@online');
});

Route::group(['prefix' => 'errosLog', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'ErroLogController@index');
    Route::get('/filtro', 'ErroLogController@filtro');
    Route::delete('/{id}/destroy', 'ErroLogController@destroy')->name('errosLog.destroy');
});

Route::group(['prefix' => 'ticketsSuper', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'TicketSuperController@index');
    Route::get('/view/{id}', 'TicketSuperController@view');
    Route::get('/filtro', 'TicketSuperController@filtro');
    Route::get('/finalizar/{id}', 'TicketSuperController@finalizar');
    Route::post('/save', 'TicketSuperController@save');
    Route::post('/novaMensagem', 'TicketSuperController@novaMensagem');
    Route::post('/finalizar', 'TicketSuperController@finalizarPost');
});

Route::group(['prefix' => 'config', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'ConfigController@index');
    Route::get('/remove-cor', 'ConfigController@removeCor');
    Route::post('/save', 'ConfigController@save');
});

Route::group(['prefix' => 'videos', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'VideoController@index');
    Route::get('/delete/{id}', 'VideoController@delete');
    Route::post('/store', 'VideoController@store');
});

Route::group(['prefix' => 'relatorioSuper', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'RelatorioSuperController@index');
    Route::get('/empresas', 'RelatorioSuperController@empresas');
    Route::get('/certificados', 'RelatorioSuperController@certificados');
    Route::get('/extrtoCliente', 'RelatorioSuperController@extrtoCliente');
    Route::get('/empresasContador', 'RelatorioSuperController@empresasContador');
    Route::get('/historicoAcessos', 'RelatorioSuperController@historicoAcessos');
    Route::get('/log', 'RelatorioSuperController@log');
    Route::get('/planosVencer', 'RelatorioSuperController@planosVencer');
});

Route::group(['prefix' => '/assinarContrato', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'AssinarContratoController@index');
    Route::post('/', 'AssinarContratoController@assinar');
});

Route::group(['prefix' => '/super-admin'], function(){
    Route::get('/alertas', 'SuperAdminController@alertas');
    Route::get('/altera-status/{id}', 'SuperAdminController@alteraStatus');
    Route::get('/altera-todos', 'SuperAdminController@alteraTodos');
});

Route::group(['prefix' => '/etiquetas', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'EtiquetaController@index');
    Route::get('/new', 'EtiquetaController@new');
    Route::get('/edit/{id}', 'EtiquetaController@edit');
    Route::get('/delete/{id}', 'EtiquetaController@delete');
    Route::post('/save', 'EtiquetaController@save');
    Route::post('/update', 'EtiquetaController@update');

});

Route::group(['prefix' => '/payment', 'middleware' => 'verificaEmpresa'], function(){
    Route::get('/', 'PaymentController@index');
    Route::post('/setPlano', 'PaymentController@setPlano');

    Route::get('/finish', 'PaymentController@finish');
    Route::get('/{code}', 'PaymentController@detalhesPagamento');
    Route::post('/paymentCard', 'PaymentController@paymentCard');
    Route::post('/paymentBoleto', 'PaymentController@paymentBoleto');
    Route::post('/paymentPix', 'PaymentController@paymentPix');

    Route::get('/consulta/{code}', 'PaymentController@consultaPagamento');
});

Route::middleware(['verificaEmpresa', 'validaAcesso', 'verificaContratoAssinado', 'limiteArmazenamento'])->group(function () {
    Route::get('/app-versions', 'AppVersionController@index');
    Route::get('/app-versions/{appVersion}/pdf/{type?}', 'AppVersionController@downloadPdf')->where('type', 'current|cumulative');
    Route::get('/app-versions/{appVersion}/html/{type?}', 'AppVersionController@html')->where('type', 'current|cumulative');
    // Route::get('/backup', 'BackupController@index');
    Route::get('/backupSql', 'BackupController@sql');

    Route::resource('nfse-config', 'NfseConfigController');
    Route::resource('caixa-empresa', 'CaixaEmpresaController');
    Route::get('/nfse-config-remove-logo', 'NfseConfigController@removeLogo')->name('nfse-config.remove-logo');
    Route::get('/nfse-config-certificado', 'NfseConfigController@certificado')->name('nfse-config.certificado');
    Route::get('/nfse-new-token', 'NfseConfigController@newToken')->name('nfse-config.new-token');
    Route::post('/nfse-config-upload-certificado', 'NfseConfigController@uploadCertificado')->name('nfse-config.upload-certificado');

    Route::group(['prefix' => '/nfse'], function(){
        Route::get('/', 'NfseController@index');
        Route::get('/filtro', 'NfseController@filtro');
        Route::get('/create', 'NfseController@create');
        Route::get('/clone/{id}', 'NfseController@clone');
        Route::get('/edit/{id}', 'NfseController@edit');
        Route::get('/delete/{id}', 'NfseController@delete');
        Route::get('/teste', 'NfseController@teste');
        Route::get('/baixarXml/{id}', 'NfseController@baixarXml');
        Route::get('/imprimir/{id}', 'NfseController@imprimir');
        Route::post('/store', 'NfseController@store');
        Route::post('/storeAjax', 'NfseController@storeAjax');
        Route::put('/update/{id}', 'NfseController@update');

        Route::post('/enviar', 'NfseController@enviar');
        Route::post('/consultar', 'NfseController@consultar');
        Route::post('/cancelar', 'NfseController@cancelar');
        Route::get('/enviarXml', 'NfseController@enviarXml');

        Route::post('/enviar-integra-notas', 'NfseIntegraNotasController@enviar');
        Route::post('/consultar-integra-notas', 'NfseIntegraNotasController@consultar');
        Route::post('/cancelar-integra-notas', 'NfseIntegraNotasController@cancelar');
        Route::get('/preview-xml/{id}', 'NfseIntegraNotasController@previewXml');

    });

    Route::group(['prefix' => '/boleto'], function(){
        Route::get('/gerar/{conta_receber_id}', 'BoletoController@gerar');
        Route::post('/gerarStore', 'BoletoController@gerarStore');
        Route::post('/gerarStoreMulti', 'BoletoController@gerarStoreMulti');
        Route::get('/imprimir/{id}', 'BoletoController@imprimir');
        Route::get('/gerarMultiplos/{contas}', 'BoletoController@gerarMultiplos');

        Route::get('/gerarRemessa/{boleto_id}', 'BoletoController@gerarRemessa');

    });

    Route::resource('sintegra', 'SintegraController');
    Route::resource('motoristas', 'MotoristaController');
    Route::resource('contas-empresa', 'ContaEmpresaController');
    Route::resource('taxas-pagamento', 'TaxaPagamentoController');
    Route::resource('config-catraca', 'ConfigCatracaController');

    Route::group(['prefix' => 'contador'], function(){
        Route::get('/', 'Contador\\ContadorController@index');
        Route::post('/set-empresa', 'Contador\\ContadorController@setEmpresa');
        Route::get('/clientes', 'Contador\\ContadorController@clientes');
        Route::get('/fornecedores', 'Contador\\ContadorController@fornecedores');
        Route::get('/produtos', 'Contador\\ContadorController@produtos');
        Route::get('/vendas', 'Contador\\ContadorController@vendas');
        Route::get('/venda-download-xml/{id}', 'Contador\\ContadorController@downloadXmlNfe');

        Route::get('/pdv', 'Contador\\ContadorController@pdv');
        Route::get('/pdv-download-xml/{id}', 'Contador\\ContadorController@downloadXmlPdv');

        Route::get('/empresas', 'Contador\\ContadorController@empresas');
        Route::get('/empresa-detalhe/{id}', 'Contador\\ContadorController@empresaDetalhe');
        Route::get('/download-certificado/{id}', 'Contador\\ContadorController@downloadCertificado');
        Route::get('/download-xml-nfe', 'Contador\\ContadorController@downloadFiltroXmlNfe');
        Route::get('/download-xml-nfce', 'Contador\\ContadorController@downloadFiltroXmlNfce');

    });

    Route::resource('sped', 'SpedController');
    Route::resource('sped-config', 'SpedConfigController');
    Route::get('/sped-regras-1400', [App\Http\Controllers\SpedRegra1400Controller::class, 'index'])->name('sped_regras.index');
    Route::post('/sped-regras-1400', [App\Http\Controllers\SpedRegra1400Controller::class, 'store'])->name('sped_regras.store');
    Route::delete('/sped-regras-1400/{id}', [App\Http\Controllers\SpedRegra1400Controller::class, 'destroy'])->name('sped_regras.destroy');

    Route::prefix('sped-nfce-consolidado')->group(function () {
        Route::get('/list',   'SpedNfceConsolidadoController@list')->name('sped.consolidado.list');
        Route::post('/save',  'SpedNfceConsolidadoController@save')->name('sped.consolidado.save');
        Route::get('/download/{id}', 'SpedNfceConsolidadoController@download')->name('sped.consolidado.download');
    });

    Route::group(['prefix' => 'telasPedido'], function(){
        Route::get('/', 'TelaPedidoController@index');
        Route::get('/new', 'TelaPedidoController@new');
        Route::post('/save', 'TelaPedidoController@save');
        Route::post('/update', 'TelaPedidoController@update');
        Route::get('/edit/{id}', 'TelaPedidoController@edit');
        Route::get('/delete/{id}', 'TelaPedidoController@delete');
    });

    Route::group(['prefix' => 'controleCozinha'],function(){
        Route::get('/', 'CozinhaController@index');
        Route::get('/controle/{tela?}', 'CozinhaController@index');
        Route::get('/selecionar', 'CozinhaController@selecionar');
        Route::get('/buscar', 'CozinhaController@buscar');
        Route::get('/concluido', 'CozinhaController@concluido');
        Route::get('/apiPedidos', 'CozinhaController@apiPedidosPendentes');
        Route::get('/concluir/{tipo}/{id}', 'CozinhaController@concluirPedidoCozinha');
        Route::get('/api/pedidos-pendentes', 'CozinhaController@pedidosPendentes');
        Route::get('/pedido/status/{id}/{status}', 'CozinhaController@atualizarStatus');
    });

    Route::group(['prefix' => 'remessasBoleto'], function(){
        Route::get('/', 'RemessaController@index');
        Route::get('/boletosSemRemessa', 'RemessaController@boletosSemRemessa');
        Route::get('/gerarRemessaMulti/{boletos}', 'RemessaController@gerarRemessaMulti');
        Route::get('/ver/{id}', 'RemessaController@ver');
        Route::get('/delete/{id}', 'RemessaController@delete');
        Route::get('/download/{id}', 'RemessaController@download');
    });

    Route::group(['prefix' => 'retorno-boleto'], function(){
        Route::get('/', 'RetornoBoletoController@index');
    });

    Route::group(['prefix' => '/financeiro'], function(){
        Route::get('/', 'FinanceiroController@index');
        Route::get('/filtro', 'FinanceiroController@filtro');
        Route::get('/novoPagamento', 'FinanceiroController@novoPagamento');
        Route::get('/pay/{id}', 'FinanceiroController@pay');
        Route::post('/pay', 'FinanceiroController@payStore');
        Route::get('/detalhes/{id}', 'FinanceiroController@detalhes');
        Route::get('/verificaPagamentos', 'FinanceiroController@verificaPagamentos');

        Route::get('/removerPlano/{id}', 'FinanceiroController@removerPlano');
        Route::get('/indeterminado', 'FinanceiroController@indeterminado');
        Route::get('/indeterminado_filtro', 'FinanceiroController@indeterminadoFiltro');
        Route::get('/indeterminado_delete/{id}', 'FinanceiroController@indeterminadoDelete');
        Route::post('/indeterminado_save', 'FinanceiroController@indeterminadoSave');

        Route::group(['prefix' => 'conciliacao'], function(){
            Route::get('/', 'ConciliacaoController@list')->name('financeiro.conciliacao.index');
            Route::get('/list', 'ConciliacaoController@list')->name('financeiro.conciliacao.list');
            Route::post('/importar', 'ConciliacaoController@importar')->name('financeiro.conciliacao.importar');
            Route::post('/conciliar', 'ConciliacaoController@conciliar')->name('financeiro.conciliacao.conciliar');
            Route::post('/criarEConciliar', 'ConciliacaoController@criarEConciliar')->name('financeiro.conciliacao.criarEConciliar');
            Route::post('/transferir', 'ConciliacaoController@transferir')->name('financeiro.conciliacao.transferir');
            Route::post('/limpar-pendentes', 'ConciliacaoController@limparPendentes')->name('financeiro.conciliacao.limparPendentes');
            Route::post('/arquivar', 'ConciliacaoController@arquivar')->name('financeiro.conciliacao.arquivar');
            Route::post('/processar-automaticos', 'ConciliacaoController@processarAutomaticos')->name('financeiro.conciliacao.processarAutomaticos');
            Route::post('/processar-lote', 'ConciliacaoController@processarLote')->name('financeiro.conciliacao.processarLote');
            Route::get('/sugestoes', 'ConciliacaoController@getSugestoes')->name('conciliacao.sugestoes');
        });


        Route::group(['prefix' => 'custo-veiculo'], function(){
            Route::get('/', 'CustoVeiculoController@index')->name('custo.veiculo.index');
            Route::get('/excel', 'CustoVeiculoController@exportarExcel')->name('custo.veiculo.excel');
            Route::get('/pdf', 'CustoVeiculoController@gerarPdf')->name('custo.veiculo.pdf');
        });
    });

    Route::group(['prefix' => '/contadores'], function(){
        Route::get('/', 'ContadorController@index');
        Route::get('/new', 'ContadorController@new');
        Route::get('/filtro', 'ContadorController@filtro');
        Route::get('/filtroEmpresa', 'ContadorController@filtroEmpresa');
        Route::get('/editar/{id}', 'ContadorController@edit');
        Route::post('/save', 'ContadorController@save');
        Route::post('/update', 'ContadorController@update');
        Route::get('/delete/{id}', 'ContadorController@delete');
        Route::get('/empresas/{id}', 'ContadorController@empresas');
        Route::get('/delete-empresa/{id}', 'ContadorController@deleteEmpresa');

        Route::post('/quickSave', 'ContadorController@quickSave');
        Route::post('/set-empresa', 'ContadorController@setEmpresa');

    });

    Route::group(['prefix' => '/ibpt'], function(){
        Route::get('/', 'IbptController@index');
        Route::get('/new', 'IbptController@new');
        Route::post('/new', 'IbptController@importar');
        //Route::get('/refresh/{id}', 'IbptController@refresh');
        Route::get('/refresh/{id}', 'IbptController@refresh');
        Route::get('/ver/{id}', 'IbptController@ver');
        Route::get('/atualizaApi', 'IbptController@atualizaApi');
        Route::post('/importar', 'IbptController@importarAutomatico');
        Route::get('/progress', 'IbptController@getProgress');
    });

    Route::resource('plano-contas', 'PlanoContaController');
    Route::get('/plano-contas-issue', 'PlanoContaController@issue')->name('plano-contas.issue');

    Route::resource('contigencia', 'ContigenciaController');
    Route::resource('cancelamento', 'CancelamentoController');
    Route::get('/cancelamento-download-contrato', 'CancelamentoController@downloadContrato')->name('cancelamento.download');

    Route::resource('difal', 'DifalController');
    Route::get('/contigencia-desative/{id}', 'ContigenciaController@desactive')->name('contigencia.desactive');

    Route::resource('eventosFuncionario', 'EventoFuncionarioController');
    Route::resource('funcionarioEventos', 'FuncionarioAdicionarEventoController');
    Route::resource('apuracaoMensal', 'ApuracaoSalarioController');
    Route::resource('filial', 'FilialController');
    Route::get('/filial-remove-logo/{id}', 'FilialController@removeLogo')->name('filial.remove-logo');

    Route::get('/apuracaoMensal/getEventos/{funcionario_id}', 'ApuracaoSalarioController@getEventos');
    Route::get('/apuracaoMensal/contaPagar/{apuracao_id}', 'ApuracaoSalarioController@contaPagar');
    Route::put('/apuracaoMensalSetConta/{id}', 'ApuracaoSalarioController@setConta')->name('apuracaoMensal.setConta');


    Route::group(['prefix' => '/ifood'], function(){
        Route::get('/config', 'Ifood\\ConfigController@index');
        Route::get('/userCode', 'Ifood\\ConfigController@userCode');
        Route::get('/getToken', 'Ifood\\ConfigController@getToken');
        Route::post('/configSave', 'Ifood\\ConfigController@configSave');

        Route::get('/catalogos', 'Ifood\\CatalogoController@index');
        Route::get('/setCatalogo/{id}', 'Ifood\\CatalogoController@setCatalogo');

        Route::get('/products', 'Ifood\\ProductController@index');
        Route::get('/refreshProduct/{message?}', 'Ifood\\ProductController@refreshProduct');
        Route::get('/productsFilter', 'Ifood\\ProductController@productsFilter');
        Route::get('/productsCreate', 'Ifood\\ProductController@productsCreate');
        Route::get('/productsEdit/{id}', 'Ifood\\ProductController@productsEdit');
        Route::get('/productsDestroy/{id}', 'Ifood\\ProductController@destroy');
        Route::post('/storeProduct', 'Ifood\\ProductController@store')->name('product-ifood.store');
        Route::put('{id}/updateProduct', 'Ifood\\ProductController@update')->name('product-ifood.update');

        Route::get('/pedidos', 'Ifood\\OrderController@index');
        Route::get('/pedidosFilter', 'Ifood\\OrderController@filter');
        Route::get('/newOrders', 'Ifood\\OrderController@getNewOrders')->name('ifood.new-orders');
        Route::get('/pedidosDetail/{id}', 'Ifood\\OrderController@detail');
        Route::get('/printOrder/{id}', 'Ifood\\OrderController@print');

        Route::get('/getNewOrdersAsync', 'Ifood\\OrderController@getNewOrdersAsync');
        Route::post('/readOrder', 'Ifood\\OrderController@readOrder');
        Route::post('/cancel', 'Ifood\\OrderController@cancelOrder');
        Route::get('/dispatch/{id}', 'Ifood\\OrderController@dispatch');
        Route::get('/requestDriver/{id}', 'Ifood\\OrderController@requestDriver');
        Route::get('/pdv/{id}', 'Ifood\\OrderController@pdv');
        Route::get('/imprimirPedido/{id}', 'Ifood\\OrderController@imprimirPedido');
        Route::get('/loja', 'Ifood\\LojaController@index');
        Route::post('/interrupcao', 'Ifood\\LojaController@interrupcao');
        Route::get('/deleteInterruption/{id}', 'Ifood\\LojaController@deleteInterruption');

    });

    Route::group(['prefix' => '/pesquisa'], function(){
        Route::get('/', 'PesquisaController@index');
        Route::get('/imprimir/{id}', 'PesquisaController@imprimir');
        Route::get('/create', 'PesquisaController@create');
        Route::get('/edit/{id}', 'PesquisaController@edit');
        Route::get('/find/{id}', 'PesquisaController@find');
        Route::get('/list/{id}', 'PesquisaController@list');
        Route::get('/salvarNota', 'PesquisaController@salvarNota');
        Route::post('store', 'PesquisaController@store');
        Route::put('update/{id}', 'PesquisaController@update');
        Route::delete('/{id}/destroy', 'PesquisaController@destroy')->name('pesquisa.destroy');
    });

    Route::group(['prefix' => '/alertas'], function(){
        Route::get('/', 'AlertaController@index');
        Route::get('/create', 'AlertaController@create');
        Route::get('/edit/{id}', 'AlertaController@edit');

        Route::get('/all', 'HomeController@all');
        Route::get('/view/{id}', 'HomeController@avisoView');
        Route::get('/list/{id}', 'AlertaController@list');
        Route::post('store', 'AlertaController@store');
        Route::put('update/{id}', 'AlertaController@update');
        Route::delete('/{id}/destroy', 'AlertaController@destroy')->name('alerta.destroy');
    });

    Route::group(['prefix' => '/contrato'], function(){
        Route::get('/', 'ContratoController@index');
        Route::get('/impressao', 'ContratoController@impressao');
        Route::post('/save', 'ContratoController@save');
        Route::post('/update', 'ContratoController@update');
        Route::get('/gerarContrato/{empresa_id}', 'ContratoController@gerarContrato');
        Route::get('/download/{empresa_id}', 'ContratoController@download');
        Route::get('/imprimir/{empresa_id}', 'ContratoController@imprimir');
    });

    Route::group(['prefix' => '/empresas'], function(){
        Route::get('/plano-contas', 'EmpresaController@planoContas');
        Route::get('/', 'EmpresaController@index');
        Route::get('/desativadas', 'EmpresaController@desativadas');
        Route::get('/ajuste', 'EmpresaController@ajuste');
        Route::get('/autocomplete', 'EmpresaController@autocomplete');
        // Route::get('/setpdv', 'EmpresaController@setpdv');
        Route::get('/alteraDebug', 'EmpresaController@alteraDebug');
        Route::get('/nova', 'EmpresaController@nova');
        Route::get('/verDelete/{id}', 'EmpresaController@verDelete');
        Route::get('/delete/{id}', 'EmpresaController@delete');
        Route::post('/save', 'EmpresaController@save');
        Route::get('/detalhes/{id}', 'EmpresaController@detalhes');
        Route::get('/alterarSenha/{id}', 'EmpresaController@alterarSenha');
        Route::post('/alterarSenha', 'EmpresaController@alterarSenhaPost');
        Route::post('/update', 'EmpresaController@update');
        Route::get('/filtro', 'EmpresaController@filtro');
        Route::get('/setarPlano/{id}', 'EmpresaController@setarPlano');
        Route::post('/setarPlano', 'EmpresaController@setarPlanoPost');
        Route::post('/relatorio', 'EmpresaController@relatorio');
        Route::get('/download/{id}', 'EmpresaController@download');
        Route::get('/download_file/{file_name}', 'EmpresaController@download_file');
        Route::get('/alterarStatus/{id}', 'EmpresaController@alterarStatus');
        Route::get('/mensagemBloqueio/{id}', 'EmpresaController@mensagemBloqueio');
        Route::post('/salvarMensagemBloqueio', 'EmpresaController@salvarMensagemBloqueio');
        Route::get('/cancelarBloqueio/{id}', 'EmpresaController@cancelarBloqueio');

        Route::get('/arquivosXml/{empresa_id}', 'EmpresaController@arquivosXml');
        Route::get('/filtroXml', 'EmpresaController@filtroXml');

        Route::get('/downloadXml/{empresa_id}', 'EmpresaController@downloadXml');
        Route::get('/downloadNfce/{empresa_id}', 'EmpresaController@downloadNfce');
        Route::get('/downloadCte/{empresa_id}', 'EmpresaController@downloadCte');
        Route::get('/downloadMdfe/{empresa_id}', 'EmpresaController@downloadMdfe');
        Route::get('/downloadEntrada/{empresa_id}', 'EmpresaController@downloadEntrada');
        Route::get('/downloadDevolucao/{empresa_id}', 'EmpresaController@downloadDevolucao');

        Route::get('/configEmitente/{empresa_id}', 'EmpresaController@configEmitente');
        Route::post('/saveConfig', 'EmpresaController@saveConfig');
        Route::get('/deleteCertificado/{empresa_id}', 'EmpresaController@deleteCertificado');
        Route::get('/uploadCertificado/{empresa_id}', 'EmpresaController@uploadCertificado');
        Route::post('/saveCertificado', 'EmpresaController@saveCertificado');
        Route::get('/removeLogo/{empresa_id}', 'EmpresaController@removeLogo');
        Route::get('/removeSenha/{empresa_id}', 'EmpresaController@removeSenha');
        Route::get('/login/{empresa_id}', 'EmpresaController@login');
        Route::get('/acessosDiarios', 'EmpresaController@acessosDiarios');
        Route::get('/buscar-empresas', 'EmpresaController@buscarEmpresas');

    });

    Route::get('/cancelamento-super', 'EmpresaController@cancelamentos');
    Route::get('/cancelamento-super-delete/{id}', 'EmpresaController@cancelamentoDelete');
    Route::get('/cache-clear', 'EmpresaController@cacheClear');

    Route::group(['prefix' => '/representantes'], function(){
        Route::get('/', 'RepresentanteController@index');
        Route::get('/novo', 'RepresentanteController@novo');
        Route::post('/save', 'RepresentanteController@save');
        Route::get('/detalhes/{id}', 'RepresentanteController@detalhes');
        Route::post('/update', 'RepresentanteController@update');
        Route::post('/saveEmpresa', 'RepresentanteController@saveEmpresa');
        Route::get('/delete/{id}', 'RepresentanteController@delete');
        Route::get('/empresas/{id}', 'RepresentanteController@empresas');
        Route::get('/deleteAttr/{id}', 'RepresentanteController@deleteAttr');
        Route::get('/alterarSenha/{id}', 'RepresentanteController@alterarSenha');
        Route::post('/alterarSenha', 'RepresentanteController@alterarSenhaPost');
        Route::get('/filtro', 'RepresentanteController@filtro');
        Route::get('/financeiro/{id}', 'RepresentanteController@financeiro');
        Route::get('/filtroFinanceiro', 'RepresentanteController@filtroFinanceiro');
        Route::get('/pagarComissao/{id}', 'RepresentanteController@pagarComissao');
    });

    Route::resource('rep-parceiro', 'RepContadorController');

    Route::group(['prefix' => '/rep'], function(){
        Route::get('/', 'RepController@index');
        Route::get('/detalhes/{id}', 'RepController@detalhes')->middleware('validaRepresentante');
        Route::post('/update', 'RepController@update');
        Route::get('/alterarSenha/{id}', 'RepController@alterarSenha')->middleware('validaRepresentante');
        Route::post('/alterarSenha', 'RepController@alterarSenhaPost');
        Route::get('/filtro', 'RepController@filtro');
        Route::get('/financeiro/{id}', 'RepController@financeiro');
        Route::post('/salvarPagamento', 'RepController@salvarPagamento');
        Route::get('/verPagamentos/{id}', 'RepController@verPagamentos');

        Route::get('/novaEmpresa', 'RepController@novaEmpresa');
        Route::post('/saveEmpresa', 'RepController@saveEmpresa');

        Route::get('/setarPlano/{empresa_id}', 'RepController@setarPlano')->middleware('validaRepresentante');
        Route::post('/setarPlano', 'RepController@setarPlanoPost');

        Route::get('/arquivosXml/{empresa_id}', 'RepController@arquivosXml')->middleware('validaRepresentante');
        Route::get('/filtroXml', 'RepController@filtroXml');

        Route::get('/download/{empresa_id}', 'RepController@download');

        Route::get('/downloadXml/{empresa_id}', 'RepController@downloadXml');
        Route::get('/downloadNfce/{empresa_id}', 'RepController@downloadNfce');
        Route::get('/downloadCte/{empresa_id}', 'RepController@downloadCte');
        Route::get('/downloadMdfe/{empresa_id}', 'RepController@downloadMdfe');
        Route::get('/downloadEntrada/{empresa_id}', 'RepController@downloadEntrada');
        Route::get('/downloadDevolucao/{empresa_id}', 'RepController@downloadDevolucao');

        Route::get('/configEmitente/{empresa_id}', 'RepController@configEmitente');
        Route::post('/saveConfig', 'RepController@saveConfig');
        Route::get('/deleteCertificado/{empresa_id}', 'RepController@deleteCertificado');
        Route::get('/uploadCertificado/{empresa_id}', 'RepController@uploadCertificado');
        Route::post('/saveCertificado', 'RepController@saveCertificado');
        Route::get('/removeLogo/{empresa_id}', 'RepController@removeLogo');
        Route::get('/removeSenha/{empresa_id}', 'RepController@removeSenha');
        Route::post('/novo-contador', 'RepController@storeContador');

    });

    Route::group(['prefix' => '/planos'], function(){
        Route::get('/', 'PlanoController@index');
        Route::get('/new', 'PlanoController@new');
        Route::post('/save', 'PlanoController@save');
        Route::post('/update', 'PlanoController@update');

        Route::get('/editar/{id}', 'PlanoController@editar');
        Route::get('/delete/{id}', 'PlanoController@delete');
    });

    Route::group(['prefix' => '/planosPendentes'], function(){
        Route::get('/', 'PlanoRepresentanteController@index');
        Route::get('/ativar/{id}', 'PlanoRepresentanteController@ativar');
        Route::get('/delete/{id}', 'PlanoRepresentanteController@delete');
    });

    Route::group(['prefix' => 'perfilAcesso'],function(){
        Route::get('/', 'PerfilAcessoController@index');
        Route::get('/new', 'PerfilAcessoController@new');
        Route::get('/edit/{id}', 'PerfilAcessoController@edit');
        Route::get('/delete/{id}', 'PerfilAcessoController@delete');

        Route::post('/save', 'PerfilAcessoController@save');
        Route::post('/update', 'PerfilAcessoController@update');
    });

    Route::group(['prefix' => 'dre'], function(){
        Route::get('/', 'DreController@index');
        Route::get('/list', 'DreController@list');
        Route::get('/ver/{id}', 'DreController@ver');
        Route::get('/deleteLancamento/{id}', 'DreController@deleteLancamento');
        Route::get('/imprimir/{id}', 'DreController@imprimir');
        Route::post('/save', 'DreController@save');
        Route::post('/novolancamento', 'DreController@novolancamento');
        Route::post('/updatelancamento', 'DreController@updatelancamento');

        Route::get('/delete/{id}', 'DreController@delete');
    });


    Route::get('/rotaEntrega/{id}', 'DeliveryController@rotaEntrega');

    Route::group(['prefix' => '/pagseguro'], function(){
        Route::get('/getSessao', 'PagSeguroController@getSessao');
        Route::post('/efetuaPagamento', 'PagSeguroController@efetuaPagamento');
        Route::get('/consultaJS', 'PagSeguroController@consultaJS');
        Route::get('/getFuncionamento', 'PagSeguroController@getFuncionamento');
    });

    Route::group(['prefix' => '/agendamentos'], function(){
        Route::get('/', 'AgendamentoController@index');
        Route::get('/all', 'AgendamentoController@all');
        Route::get('/filtro', 'AgendamentoController@filtro');
        Route::post('/saveCliente', 'AgendamentoController@saveCliente');
        Route::post('/save', 'AgendamentoController@save');
        Route::get('/detalhes/{id}', 'AgendamentoController@detalhes');
        Route::get('/delete/{id}', 'AgendamentoController@delete');
        Route::get('/alterarStatus/{id}', 'AgendamentoController@alterarStatus');
        Route::get('/irParaFrenteCaixa/{id}', 'AgendamentoController@irParaFrenteCaixa');

        Route::get('/comissao', 'AgendamentoController@comissao');
        Route::get('/filtrarComissao', 'AgendamentoController@filtrarComissao');

        Route::get('/servicos', 'AgendamentoController@servicos');
        Route::get('/filtrarServicos', 'AgendamentoController@filtrarServicos');

        // Rota correta para conversão em Ordem de Serviço
        Route::get('/gerarOs/{id}', 'AgendamentoController@gerarOs');
        Route::post('/cancelar/{id}', 'AgendamentoController@cancelar');
    });

    Route::group(['prefix' => '/eventos', 'middleware' => ['validaEvento']], function(){
        Route::get('/', 'EventoController@index');
        Route::get('/pesquisa', 'EventoController@pesquisa');
        Route::get('/novo', 'EventoController@novo');
        Route::post('/save', 'EventoController@save')->middleware('limiteEvento');
        Route::post('/update', 'EventoController@update');
        Route::get('/edit/{id}', 'EventoController@edit');
        Route::get('/delete/{id}', 'EventoController@delete');
        Route::get('/funcionarios/{id}', 'EventoController@funcionarios');
        Route::post('/saveFuncionario', 'EventoController@saveFuncionario');
        Route::get('/removeFuncionario/{id}', 'EventoController@removeFuncionario');

        Route::get('/atividades/{id}', 'EventoController@atividades');
        Route::get('/filtroAtividade', 'EventoController@filtroAtividade');
        Route::get('/novaAtividade/{id}', 'EventoController@novaAtividade');
        Route::post('/salvarAtividade', 'EventoController@salvarAtividade');

        Route::get('/finalizarAtividade/{id}', 'EventoController@finalizarAtividade');
        Route::post('/finalizarAtividade', 'EventoController@finalizarAtividadeSave');

        Route::get('/movimentacao', 'EventoController@movimentacao');
        Route::get('/movimentacaoFiltro', 'EventoController@movimentacaoFiltro');
        Route::post('/relatorioAtividadeFiltro', 'EventoController@relatorioAtividadeFiltro');
        Route::get('/relatorioAtividade', 'EventoController@relatorioAtividade');
        Route::get('/imprimirComprovante/{id}', 'EventoController@imprimirComprovante');
        Route::get('/registros/{id}', 'EventoController@registros');

    });

    // Contratos de engenharia, locação e serviços
    Route::group(['prefix' => 'contratos'], function(){
        Route::get('/', 'ContratoEngenhariaController@list')->name('contratos.index');
        Route::get('/list', 'ContratoEngenhariaController@list');
        Route::post('/list', 'ContratoEngenhariaController@filtro');
        Route::get('/new', 'ContratoEngenhariaController@register')->name('contratos.create');
        Route::get('/detalhes/{id}', 'ContratoEngenhariaController@detalhes')->name('contratos.detalhes');
        Route::get('/edit/{id}', 'ContratoEngenhariaController@edit')->name('contratos.edit');
        Route::post('/save', 'ContratoEngenhariaController@save')->name('contratos.store');
        Route::post('/update/{id}', 'ContratoEngenhariaController@update')->name('contratos.update');
        Route::get('/delete/{id}', 'ContratoEngenhariaController@delete')->name('contratos.destroy');

        Route::post('/alocar-funcionario/{id}', 'ContratoEngenhariaController@alocarFuncionario')->name('contratos.alocar-funcionario');
        Route::get('/desalocar-funcionario/{alocacao_id}', 'ContratoEngenhariaController@desalocarFuncionario')->name('contratos.desalocar-funcionario');
        Route::get('/dashboard-dre', 'ContratoEngenhariaController@dashboardDre')->name('contratos.dashboard-dre');
        Route::get('/dashboard-dre/excel', 'ContratoEngenhariaController@exportarExcelDashboard')->name('contratos.dashboard-dre.excel');
    });

    Route::group(['prefix' => 'contratos/medicoes', 'middleware' => 'verificaEmpresa'], function(){
        Route::get('/create/novo', 'ContratoEngMedicaoController@create')->name('contratos.medicoes.create.avulso');
        Route::get('/create/{contrato_id?}', 'ContratoEngMedicaoController@create')->name('contratos.medicoes.create');
        Route::post('/store', 'ContratoEngMedicaoController@store')->name('contratos.medicoes.store.avulso');
        Route::post('/store/{contrato_id?}', 'ContratoEngMedicaoController@store')->name('contratos.medicoes.store');
        Route::get('/edit/{id}', 'ContratoEngMedicaoController@edit')->name('contratos.medicoes.edit');
        Route::post('/update/{id}', 'ContratoEngMedicaoController@update')->name('contratos.medicoes.update');
        Route::get('/imprimir/{id}', 'ContratoEngMedicaoController@imprimir')->name('contratos.medicoes.imprimir');
        Route::get('/pdf/{id}', 'ContratoEngMedicaoController@gerarPdf')->name('contratos.medicoes.pdf');
        Route::get('/enviar-whatsapp/{id}', 'ContratoEngMedicaoController@enviarWhatsapp')->name('contratos.medicoes.whatsapp');
        Route::get('/enviar-email/{id}', 'ContratoEngMedicaoController@enviarEmail')->name('contratos.medicoes.email');
        Route::post('/mudar-status/{id}', 'ContratoEngMedicaoController@mudarStatus')->name('contratos.medicoes.status');
        Route::get('/cancelar/{id}', 'ContratoEngMedicaoController@cancelar')->name('contratos.medicoes.cancelar');


        Route::get('/delete/{id}', 'ContratoEngMedicaoController@destroy')->name('contratos.medicoes.destroy');
        Route::get('/{contrato_id?}', 'ContratoEngMedicaoController@index')
            ->where('contrato_id', '[0-9]+')
            ->name('contratos.medicoes.index');
    });

    // NFS-e de medições: endpoints históricos consumidos pela view existente.
    Route::group(['middleware' => 'verificaEmpresa'], function(){
        Route::get('/nfse/emitir/{id}', 'NfseNacionalController@emitir')->name('nfse.emitir');
        Route::get('/nfse/xml/{id}', 'NfseNacionalController@downloadXml')->name('nfse.xml');
        Route::get('/nfse/imprimir-medicao/{id}', 'NfseNacionalController@imprimir')->name('nfse.medicao.imprimir');
        Route::get('/nfse/consultar/{id}', 'NfseNacionalController@consultar')->name('nfse.consultar');
    });

    // MTR 3.0 - SINIR / IEMA
    Route::group(['prefix' => 'mtr/unidades', 'middleware' => 'verificaEmpresa'], function(){
        Route::get('/', 'MtrUnidadeController@index')->name('mtr.unidades.index');
        Route::get('/create', 'MtrUnidadeController@create')->name('mtr.unidades.create');
        Route::post('/store', 'MtrUnidadeController@store')->name('mtr.unidades.store');
        Route::get('/edit/{id}', 'MtrUnidadeController@edit')->name('mtr.unidades.edit');
        Route::match(['post', 'put'], '/update/{id}', 'MtrUnidadeController@update')->name('mtr.unidades.update');
        Route::get('/delete/{id}', 'MtrUnidadeController@destroy')->name('mtr.unidades.destroy');
        Route::post('/testar-conexao', 'MtrUnidadeController@testarConexao')->name('mtr.unidades.testarConexao');
    });

    Route::group(['prefix' => 'mtr/depara-residuos', 'middleware' => 'verificaEmpresa'], function(){
        Route::get('/', 'MtrDeparaController@index')->name('mtr.depara.index');
        Route::get('/create', 'MtrDeparaController@create')->name('mtr.depara.create');
        Route::post('/store', 'MtrDeparaController@store')->name('mtr.depara.store');
        Route::get('/edit/{id}', 'MtrDeparaController@edit')->name('mtr.depara.edit');
        Route::match(['post', 'put'], '/update/{id}', 'MtrDeparaController@update')->name('mtr.depara.update');
        Route::get('/delete/{id}', 'MtrDeparaController@destroy')->name('mtr.depara.destroy');
    });

    Route::group(['prefix' => 'mtr/emissao', 'middleware' => 'verificaEmpresa'], function(){
        Route::get('/', 'MtrController@index')->name('mtr.emissao.index');
        Route::get('/create/avulso', 'MtrController@createAvulso')->name('mtr.emissao.create.avulso');
        Route::get('/create/nfe/{venda_id}', 'MtrController@createNfe')->name('mtr.emissao.create.nfe');
        Route::get('/create/pesagem/{pesagem_id}', 'MtrController@createPesagem')->name('mtr.emissao.create.pesagem');
        Route::post('/store', 'MtrController@salvarRascunho')->name('mtr.emissao.store');
        Route::get('/edit/{id}', 'MtrController@edit')->name('mtr.emissao.edit');
        Route::match(['post', 'put'], '/update/{id}', 'MtrController@update')->name('mtr.emissao.update');
        Route::match(['get', 'delete'], '/delete/{id}', 'MtrController@destroy')->name('mtr.emissao.destroy');
        Route::post('/transmitir/{id}', 'MtrController@transmitir')->name('mtr.emissao.transmitir');
        Route::post('/cancelar/{id}', 'MtrController@cancelar')->name('mtr.emissao.cancelar');
        Route::get('/consultar/{id}', 'MtrController@consultarStatus')->name('mtr.emissao.consultar');
        Route::post('/whatsapp', 'MtrController@enviarWhatsAppMtr')->name('mtr.emissao.whatsapp');
        Route::get('/download-pdf/{numero_mtr}', 'MtrController@downloadPdf')->name('mtr.emissao.pdf');
        Route::get('/download-cdf/{numero_mtr}', 'MtrController@downloadCdf')->name('mtr.emissao.cdf');

    });

    Route::group(['prefix' => 'mtr', 'middleware' => 'verificaEmpresa'], function(){
        Route::get('/recepcao', 'MtrController@recepcaoIndex')->name('mtr.recepcao.index');
        Route::post('/receber/barra', 'MtrController@receberPorCodigoBarras')->name('mtr.recepcao.barra');
        Route::post('/receber/provisorio', 'MtrController@receberPorProvisorio')->name('mtr.recepcao.provisorio');
        Route::get('/detalhes/{numero}', 'MtrController@detalhesMtrExterno')->name('mtr.recepcao.detalhes');
        Route::post('/receber-completo', 'MtrController@salvarRecebimentoMtr')->name('mtr.recepcao.salvar');
    });

    Route::group(['prefix' => '/locacao'], function(){
        Route::get('/', 'LocacaoController@index');
        Route::get('/pesquisa', 'LocacaoController@pesquisa');
        Route::get('/relatorio', 'LocacaoController@relatorio');
        Route::get('/novo', 'LocacaoController@novo');
        Route::get('/edit/{id}', 'LocacaoController@edit');
        Route::post('/salvar', 'LocacaoController@salvar');

        Route::get('/itens/{id}', 'LocacaoController@itens');
        Route::get('/delete/{id}', 'LocacaoController@delete');
        Route::get('/validaEstoque/{produto_id}/{locacao_id}', 'LocacaoController@validaEstoque');
        Route::post('/salvarItem', 'LocacaoController@salvarItem');
        Route::post('/saveObs', 'LocacaoController@saveObs');
        Route::get('/deleteItem/{id}', 'LocacaoController@deleteItem');
        Route::get('/alterarStatus/{id}', 'LocacaoController@alterarStatus');
        Route::get('/comprovante/{id}', 'LocacaoController@comprovante');

        // ROTAS LOGÍSTICAS VIA POST (PARA RECEBER MOTORISTA/VEÍCULO DO MODAL)
        Route::match(['get', 'post'], '/marcarComoEntregue/{id}', 'LocacaoController@marcarComoEntregue');
        Route::match(['get', 'post'], '/solicitarRetirada/{id}', 'LocacaoController@solicitarRetirada');
        Route::match(['get', 'post'], '/finalizarLocacao/{id}', 'LocacaoController@finalizarLocacao');
        Route::get('/gerarFaturamentoFinanceiro/{id}', 'LocacaoController@gerarFaturamentoFinanceiro');
        Route::get('/imprimirFatura/{id}', 'LocacaoController@imprimirFatura');
    });

    Route::group(['prefix' => '/dfe'], function(){
        Route::get('/', 'DFeController@index');
        Route::get('/teste', 'DFeController@teste');
        Route::get('/logs', 'DFeController@logs');
        Route::get('/getDocumentos', 'DFeController@getDocumentos');
        Route::get('/manifestar', 'DFeController@manifestar');
        Route::get('/download/{chave}', 'DFeController@download')->middleware('limiteProdutos')->middleware('limiteClientes');
        Route::get('/imprimirDanfe/{chave}', 'DFeController@imprimirDanfe');
        Route::get('/downloadXml/{chave}', 'DFeController@downloadXml');
        Route::get('/salvarFatura', 'DFeController@salvarFatura');
        Route::get('/novaConsulta', 'DFeController@novaConsulta');
        Route::get('/getDocumentosNovos', 'DFeController@getDocumentosNovos');
        Route::get('/getDocumentosNovosTeste', 'DFeController@getDocumentosNovosTeste');
        Route::get('/filtro', 'DFeController@filtro');
        Route::post('/salvar', 'DFeController@salvar');
        Route::get('/gerar-venda/{id}', 'DFeController@gerarVenda');
        Route::get('/sincronizar', 'DFeController@sincronizarCompras')->name('dfe.sincronizar');
    });

    Route::group(['prefix' => '/relatorios'], function(){
        Route::get('/', 'RelatorioController@index');
        //Route::get('/relatorios', 'RelatorioController@index')->name('relatorios.index');
        //Route::get('/nfe-numeracao-gaps', 'Relatorios\\NFeNumeracaoGapController@index')->name('relatorios.nfe_numeracao_gaps.index');
        // LISTAGEM / FILTRO
        Route::get('nfe-numeracao-gaps', 'NFeNumeracaoGapController@index')->name('relatorios.nfe_numeracao_gaps.index');
        Route::post('nfe-numeracao-gaps/inutilizar', 'NFeNumeracaoGapController@inutilizarFaixa')->name('relatorios.nfe_numeracao_gaps.inutilizar');
        Route::post('nfe-numeracao-gaps/acao-documento', 'NFeNumeracaoGapController@acaoDocumento')->name('relatorios.nfe_numeracao_gaps.acao_documento');

        Route::get('/filtroVendas', 'RelatorioController@filtroVendas');
        Route::get('/filtroVendas2', 'RelatorioController@filtroVendas2');
        Route::get('/filtroCompras', 'RelatorioController@filtroCompras');
        Route::get('/filtroComprasDetalhado', 'RelatorioController@filtroComprasDetalhado');
        Route::get('/filtroVendaProdutos', 'RelatorioController@filtroVendaProdutos');
        Route::get('/filtroVendaClientes', 'RelatorioController@filtroVendaClientes');
        Route::get('/filtroEstoqueMinimo', 'RelatorioController@filtroEstoqueMinimo');
        Route::get('/filtroVendaDiaria', 'RelatorioController@filtroVendaDiaria');
        Route::get('/filtroVendaDiariaPdv', 'RelatorioController@filtroVendaDiariaPdv');

        Route::get('/filtroLucro', 'RelatorioController@filtroLucro');
        Route::get('/relatorioLucroAnalitico', 'RelatorioController@relatorioLucroAnalitico');
        Route::get('/estoqueProduto', 'RelatorioController@estoqueProduto');
        Route::get('/comissaoVendas', 'RelatorioController@comissaoVendas');
        Route::get('/comissaoVendas2', 'RelatorioController@comissaoVendas2');
        Route::get('/tiposPagamento', 'RelatorioController@tiposPagamento');
        Route::get('/cadastroProdutos', 'RelatorioController@cadastroProdutos');
        Route::get('/vendaDeProdutos', 'RelatorioController@vendaDeProdutos');
        Route::get('/vendaDeProdutos2', 'RelatorioController@vendaDeProdutos2');
        Route::get('/listaPreco', 'RelatorioController@listaPreco');
        Route::get('/fiscal', 'RelatorioController@fiscal');
        Route::get('/porCfop', 'RelatorioController@porCfop');
        Route::get('/boletos', 'RelatorioController@boletos');
        Route::get('/comissaoAssessor', 'RelatorioController@comissaoAssessor');
        Route::get('/cte', 'RelatorioController@cte');
        Route::get('/cliente', 'RelatorioController@cliente');
        Route::get('/locacao', 'RelatorioController@locacao');
        Route::get('/perca', 'RelatorioController@perca');
        Route::get('/sangrias', 'RelatorioController@sangrias');
        Route::get('/taxas', 'RelatorioController@taxas');
        Route::get('/descontos', 'RelatorioController@descontos');
        Route::get('/acrescimos', 'RelatorioController@acrescimos');
        Route::get('/contas-recebidas', 'RelatorioController@contasRecebidas');
        Route::get('/curva', 'RelatorioController@curva');
        //Route::get('/pesagens', 'PesagemController@list')->name('pesagens.list');
        //Route::get('/pesagens', 'PesagemController@list')->name('pesagens.list');
        Route::get('/pesagem', 'RelatorioController@relatorioPesagem');
        Route::get('/pesagem/imprimir', 'RelatorioController@imprimirRelatorioPesagem');
        Route::get('/pesagem/imprimirA4/{id}', 'RelatorioController@imprimirPesagemA4');
        //Route::get('/pesagem/imprimir80mm', 'RelatorioController@imprimirPesagem80mm');
        Route::get('/pesagem/imprimir80mm/{id}', 'RelatorioController@imprimirPesagem80mm');
        Route::get('/pesagem/imprimir80mmSimples/{id}', 'RelatorioController@imprimirPesagem80mmSimples');
        Route::get('/teste/{id}', 'RelatorioController@imprimirRelatorioPesagem');
    });


    Route::group(['prefix' => '/pedidosMesa'], function(){
        Route::get('/', 'PedidoMesaController@index');
        Route::get('/naoAutorizados', 'PedidoMesaController@naoAutorizados');
        Route::get('/recusar/{id}', 'PedidoMesaController@recusar');
        Route::get('/ver/{id}', 'PedidoMesaController@ver');
        Route::get('/delete/{id}', 'PedidoMesaController@delete');
        Route::post('/alterarStatusPedido', 'PedidoMesaController@alterarStatusPedido');
        Route::put('/alterarEstado/{id}', 'PedidoMesaController@alterarEstado');

        Route::get('/controle', 'PedidoMesaController@controle');
        Route::get('/itensPendentes', 'PedidoMesaController@itensPendentes');
        Route::get('/entregue/{id}', 'PedidoMesaController@entregue');

    });

    Route::group(['prefix' => '/pedidosDelivery'], function(){
        Route::get('/', 'PedidoDeliveryController@today');
        Route::get('/verPedido/{id}', 'PedidoDeliveryController@verPedido');
        Route::get('/filtro', 'PedidoDeliveryController@filtro');
        Route::get('/alterarStatus/{id}', 'PedidoDeliveryController@alterarStatus');
        Route::get('/irParaFrenteCaixa/{id}', 'PedidoDeliveryController@irParaFrenteCaixa');
        Route::get('/alterarPedido', 'PedidoDeliveryController@alterarPedido');
        Route::get('/confirmarAlteracao', 'PedidoDeliveryController@confirmarAlteracao');
        Route::get('/print/{id}', 'PedidoDeliveryController@print');
        Route::get('/verCarrinhos', 'PedidoDeliveryController@verCarrinhos');
        Route::get('/verCarrinho/{id}', 'PedidoDeliveryController@verCarrinho');
        Route::get('/push/{id}', 'PedidoDeliveryController@push');
        Route::get('/emAberto', 'PedidoDeliveryController@emAberto');
        Route::post('/sendPush', 'PedidoDeliveryController@sendPush');
        Route::post('/sendPushWeb', 'PedidoDeliveryController@sendPushWeb');
        Route::post('/sendSms', 'PedidoDeliveryController@sendSms');

        //para frente de pedido
        Route::get('/frente', 'PedidoDeliveryController@frente');
        Route::get('/frenteComPedido/{id}', 'PedidoDeliveryController@frenteComPedido');
        Route::get('/clientes', 'PedidoDeliveryController@clientes');
        Route::get('/abrirPedidoCaixa', 'PedidoDeliveryController@abrirPedidoCaixa');
        Route::post('/novoClienteDeliveryCaixa', 'PedidoDeliveryController@novoClienteDeliveryCaixa');
        Route::post('/novoEnderecoClienteCaixa', 'PedidoDeliveryController@novoEnderecoClienteCaixa');
        Route::post('/setEndereco', 'PedidoDeliveryController@setEndereco');
        Route::post('/getEnderecoCaixa/{cliente_id}', 'PedidoDeliveryController@getEnderecoCaixa');
        Route::post('/saveItemCaixa', 'PedidoDeliveryController@saveItemCaixa');
        Route::get('/produtos', 'PedidoDeliveryController@produtos');
        Route::get('/deleteItem/{id}', 'PedidoDeliveryController@deleteItem');
        Route::get('/getProdutoDelivery/{id}', 'PedidoDeliveryController@getProdutoDelivery');
        Route::get('/frenteComPedidoFinalizar', 'PedidoDeliveryController@frenteComPedidoFinalizar');
        Route::get('/removerCarrinho/{id}', 'PedidoDeliveryController@removerCarrinho');

        Route::post('/store', 'PedidoDeliveryController@store');
        Route::get('/find/{id}', 'PedidoDeliveryController@find');

        Route::post('/finalizarFrente', 'PedidoDeliveryController@finalizarFrente');
        Route::get('/pedidosNaoLidos', 'PedidoDeliveryController@pedidosNaoLidos');
        Route::post('/lerPedido', 'PedidoDeliveryController@lerPedido');
        Route::get('/teste', 'PedidoDeliveryController@teste');
        Route::get('/delete/{id}', 'PedidoDeliveryController@delete');

        // Recursos históricos do Delivery mantidos dentro do grupo operacional existente.
        Route::get('/verificar-novos-pedidos', 'PedidoDeliveryController@verificarNovosPedidos');
        Route::get('/ultimoPedidoNovo', 'PedidoDeliveryController@ultimoPedidoNovo');
        Route::get('/kanban', 'PedidoDeliveryController@kanban');
        Route::post('/actualizarStatusKanban', 'PedidoDeliveryController@actualizarStatusKanban');
        Route::get('/marcarComoEntregue/{id}', 'PedidoDeliveryController@marcarComoEntregue');

    });


    Route::group(['prefix' => '/configDelivery'], function(){
        Route::get('/', 'ConfigDeliveryController@index');
        Route::post('/save', 'ConfigDeliveryController@save');
        Route::post('/saveCoords', 'ConfigDeliveryController@saveCoords');

        Route::get('/galeria', 'ConfigDeliveryController@galeria');
        Route::post('/saveImagem', 'ConfigDeliveryController@saveImagem');
        Route::get('/deleteImagem/{id}', 'ConfigDeliveryController@deleteImagem');

    });

    Route::group(['prefix' => '/carrosselDelivery'], function(){
        Route::get('/', 'CarrosselDeliveryController@index');
        Route::post('/save', 'CarrosselDeliveryController@save');
        Route::get('/delete/{id}', 'CarrosselDeliveryController@delete');
        Route::get('/down/{id}', 'CarrosselDeliveryController@down');
        Route::get('/up/{id}', 'CarrosselDeliveryController@up');
        Route::get('/alteraStatus/{id}', 'CarrosselDeliveryController@alteraStatus');

    });

    Route::group(['prefix' => '/configMercado'], function(){
        Route::get('/', 'MercadoConfigController@index');
        Route::post('/save', 'MercadoConfigController@save');
    });

    Route::group(['prefix' => 'categoriaDeLoja'], function(){
        Route::get('/', 'CategoriaLojaController@index');
        Route::get('/alterarStatus/{id}', 'CategoriaLojaController@alterarStatus');
    });

    Route::group(['prefix' => 'deliveryCategoria'], function(){
        Route::get('/', 'DeliveryConfigCategoriaController@index');
        Route::get('/delete/{id}', 'DeliveryConfigCategoriaController@delete');
        Route::get('/edit/{id}', 'DeliveryConfigCategoriaController@edit');
        Route::get('/additional/{id}', 'DeliveryConfigCategoriaController@additional');
        Route::get('/removeAditional/{id}', 'DeliveryConfigCategoriaController@removeAditional');
        Route::post('/saveAditional', 'DeliveryConfigCategoriaController@saveAditional');
        Route::get('/new', 'DeliveryConfigCategoriaController@new');

        Route::post('/request', 'DeliveryConfigCategoriaController@request');
        Route::post('/save', 'DeliveryConfigCategoriaController@save');
        Route::post('/update', 'DeliveryConfigCategoriaController@update');
    });

    Route::group(['prefix' => 'deliveryComplemento'], function(){
        Route::get('/', 'DeliveryComplementoController@index');
        Route::get('/delete/{id}', 'DeliveryComplementoController@delete');
        Route::get('/edit/{id}', 'DeliveryComplementoController@edit');
        Route::get('/new', 'DeliveryComplementoController@new');
        Route::get('/all', 'DeliveryComplementoController@all');
        Route::get('/allPedidoLocal', 'DeliveryComplementoController@allPedidoLocal');

        Route::post('/request', 'DeliveryComplementoController@request');
        Route::post('/save', 'DeliveryComplementoController@save');
        Route::post('/update', 'DeliveryComplementoController@update');
    });

    Route::group(['prefix' => 'deliveryProduto'], function(){
        Route::get('/', 'DeliveryConfigProdutoController@index');
        Route::get('/editMany', 'DeliveryConfigProdutoController@editMany');
        Route::get('/editManySearch', 'DeliveryConfigProdutoController@editManySearch');
        Route::get('/confirmMany', 'DeliveryConfigProdutoController@confirmMany');

        Route::get('/delete/{id}', 'DeliveryConfigProdutoController@delete');
        Route::get('/deleteImagem/{id}', 'DeliveryConfigProdutoController@deleteImagem');
        Route::get('/edit/{id}', 'DeliveryConfigProdutoController@edit');
        Route::get('/galeria/{id}', 'DeliveryConfigProdutoController@galeria');
        Route::get('/push/{id}', 'DeliveryConfigProdutoController@push');
        Route::get('/new', 'DeliveryConfigProdutoController@new');

        Route::get('/alterarDestaque/{id}', 'DeliveryConfigProdutoController@alterarDestaque');
        Route::get('/alterarStatus/{id}', 'DeliveryConfigProdutoController@alterarStatus');

        Route::post('/request', 'DeliveryConfigProdutoController@request');
        Route::post('/save', 'DeliveryConfigProdutoController@save');
        Route::post('/saveImagem', 'DeliveryConfigProdutoController@saveImagem');
        Route::post('/update', 'DeliveryConfigProdutoController@update');
        Route::get('/pesquisa', 'DeliveryConfigProdutoController@pesquisa');
        Route::post('/confirmManyPost', 'DeliveryConfigProdutoController@confirmManyPost');

    });

    Route::group(['prefix' => 'configNF'], function(){
        Route::get('/', 'ConfigNotaController@index');
        Route::post('/save', 'ConfigNotaController@save');
        Route::get('/certificado', 'ConfigNotaController@certificado');
        Route::get('/download', 'ConfigNotaController@download');
        Route::get('/senha', 'ConfigNotaController@senha');
        // Route::post('/certificado', 'ConfigNotaController@saveCertificado')->middleware('csv');
        Route::post('/certificado', 'ConfigNotaController@saveCertificado');
        Route::get('/teste', 'ConfigNotaController@teste');
        Route::get('/testeEmail', 'ConfigNotaController@testeEmail');
        Route::get('/deleteCertificado', 'ConfigNotaController@deleteCertificado');
        Route::get('/removeLogo/{id}', 'ConfigNotaController@removeLogo');
        Route::get('/removeSenha/{id}', 'ConfigNotaController@removeSenha');
        Route::get('/verificaSenha', 'ConfigNotaController@verificaSenha');
        Route::get('/enviar-certificado', 'ConfigNotaController@enviarCertificado');
        Route::get('/verifica-senha-acesso', 'ConfigNotaController@verificaSenhaAcesso');
        //Route::get('/traccarConfig', 'TraccarConfigController@register');
        //Route::post('/traccarConfig/save', 'TraccarConfigController@save');

    });

    Route::group(['prefix' => 'configEmail'], function(){
        Route::get('/', 'ConfigEmailController@index');
        Route::post('/save', 'ConfigEmailController@save');
        Route::get('/teste', 'ConfigEmailController@teste');
    });

    Route::group(['prefix' => 'escritorio'], function(){
        Route::get('/', 'EscritorioController@index');
        Route::post('/save', 'EscritorioController@save');
    });

    Route::group(['prefix' => 'caixa'], function(){
        Route::get('/', 'AberturaCaixaController@index');
        Route::get('/filtroUsuario', 'AberturaCaixaController@filtroUsuario');
        Route::get('/list', 'AberturaCaixaController@list');
        Route::get('/detalhes/{id}', 'AberturaCaixaController@detalhes');
        Route::get('/imprimir/{id}', 'AberturaCaixaController@imprimir');
        Route::get('/imprimir80/{id}', 'AberturaCaixaController@imprimir80');
        Route::get('/filtro', 'AberturaCaixaController@filtro');
    });

    Route::group(['prefix' => 'aberturaCaixa'], function(){
        Route::get('/verificaHoje', 'AberturaCaixaController@verificaHoje');
        Route::post('/abrir', 'AberturaCaixaController@abrir');
        Route::get('/diaria', 'AberturaCaixaController@diaria');
    });

    Route::get('/app', 'PedidoRestController@apk');

    Route::group(['prefix' => 'pedidos'], function(){
        Route::get('/', 'PedidoController@index');
        Route::get('/filtrar', 'PedidoController@filtrar');
        Route::post('/abrir', 'PedidoController@abrir');
        Route::get('/ver/{id}', 'PedidoController@ver');
        Route::get('/deleteItem/{id}', 'PedidoController@deleteItem');
        Route::get('/desativar/{id}', 'PedidoController@desativar');
        Route::get('/alterarStatus/{id}', 'PedidoController@alterarStatus');
        Route::get('/finalizar/{id}', 'PedidoController@finalizar');
        Route::get('/itensPendentes', 'PedidoController@itensPendentes');
        Route::post('/saveItem', 'PedidoController@saveItem');

        Route::get('/emAberto', 'PedidoController@emAberto');

        Route::post('/sms', 'PedidoController@sms');
        Route::get('/imprimirPedido/{id}', 'PedidoController@imprimirPedido');
        Route::get('/itensParaFrenteCaixa', 'PedidoController@itensParaFrenteCaixa');
        Route::get('/setarEndereco', 'PedidoController@setarEndereco');
        Route::get('/setarBairro', 'PedidoController@setarBairro');
        Route::get('/imprimirItens', 'PedidoController@imprimirItens');
        Route::get('/controleComandas', 'PedidoController@controleComandas');
        Route::get('/verDetalhes/{id}', 'PedidoController@verDetalhes');
        Route::get('/filtroComanda', 'PedidoController@filtroComanda');

        Route::get('/mesas', 'PedidoController@mesas');
        Route::get('/verMesa/{mesa_id}', 'PedidoController@verMesa');
        Route::get('/ativarMesa/{mesa_id}', 'PedidoController@ativarMesa');
        Route::post('/atribuirComanda', 'PedidoController@atribuirComanda');
        Route::post('/atribuirMesa', 'PedidoController@atribuirMesa');
        Route::post('/saveCliente', 'PedidoController@saveCliente');

        Route::get('/upload', 'PedidoController@upload');
        Route::post('/apk', 'PedidoController@apkUpload');
        Route::get('/download', 'PedidoController@download');
        Route::get('/download_generic', 'PedidoController@download_generic');
        Route::post('/setCliente', 'PedidoController@setCliente');
        Route::get('/get-comandas-novas', 'PedidoController@getComandasNovas');
        Route::get('/get-comandas-fechadas', 'PedidoController@getComandasFechadas');
        Route::get('/get-mesas', 'PedidoController@getMesas');

    });

    Route::group(['prefix' => 'sangriaCaixa'], function(){
        Route::post('/save', 'SangriaCaixaController@save');
        Route::get('/teste', 'SangriaCaixaController@teste');
        Route::get('/diaria', 'SangriaCaixaController@diaria');
        Route::get('/imprimir/{id}', 'SangriaCaixaController@imprimir');
    });

    Route::group(['prefix' => 'suprimentoCaixa'], function(){
        Route::post('/save', 'SuprimentoCaixaController@save');
        Route::get('/diaria', 'SuprimentoCaixaController@diaria');
        Route::get('/imprimir/{id}', 'SuprimentoCaixaController@imprimir');

    });

    Route::group(['prefix' => 'cidades'], function(){
        Route::get('/', 'CidadeController@index');
        Route::get('/nova', 'CidadeController@nova');
        Route::post('/save', 'CidadeController@save');
        Route::post('/update', 'CidadeController@update');
        Route::get('/editar/{id}', 'CidadeController@editar');
        Route::get('/delete/{id}', 'CidadeController@delete');
        Route::get('/filtro', 'CidadeController@filtro');

        Route::get('/all', 'CidadeController@all');
        Route::get('/find/{id}', 'CidadeController@find');
        Route::get('/findNome/{nome}', 'CidadeController@findNome');
        Route::get('/findOne', 'CidadeController@findOne');
        Route::get('/cidadePorCodigoIbge/{codigo_ibge}', 'CidadeController@cidadePorCodigoIbge');
    });

    Route::group(['prefix' => 'usuarios'],function(){
        Route::get('/', 'UsuarioController@lista');
        Route::get('/new', 'UsuarioController@new')->middleware('limiteUsuarios');
        Route::get('/edit/{id}', 'UsuarioController@edit');
        Route::get('/delete/{id}', 'UsuarioController@delete');
        Route::post('/save', 'UsuarioController@save');
        Route::post('/update', 'UsuarioController@update');
        Route::get('/setTema', 'UsuarioController@setTema');
        Route::get('/historico/{id}', 'UsuarioController@historico');
        Route::get('/set-location', 'UsuarioController@setLocation');
        Route::get('/{id}/generate-otp', 'UsuarioController@generateOtp')->name('usuarios.generate-otp');
        Route::get('/{id}/preview-otp', 'UsuarioController@previewOtp')->name('usuarios.preview-otp');
        Route::post('/{id}/save-otp',    'UsuarioController@saveOtp')->name('usuarios.save-otp');
        Route::post('/{id}/verify-current-otp', 'UsuarioController@verifyCurrentOtp')->name('usuarios.verify-current-otp');
    });
    Route::get('/401', function(){
        return view('401');
    });
    Route::get('/402', function(){
        return view('402');
    });
    Route::get('/403', function(){
        return view('403');
    });

    Route::group(['prefix' => 'categorias'],function(){
        Route::get('/', 'CategoryController@index');
        Route::get('/delete/{id}', 'CategoryController@delete');
        Route::get('/edit/{id}', 'CategoryController@edit');
        Route::get('/new', 'CategoryController@new');
        Route::get('/tributacao/{id}', 'CategoryController@tributacao');

        Route::post('/request', 'CategoryController@request');
        Route::post('/save', 'CategoryController@save');
        Route::post('/update', 'CategoryController@update');
        Route::post('/quickSave', 'CategoryController@quickSave');
        Route::post('/save-tributacao', 'CategoryController@saveTributacao');
    });

    Route::group(['prefix' => 'formasPagamento'],function(){
        Route::get('/', 'FormaPagamentoController@index');
        Route::get('/delete/{id}', 'FormaPagamentoController@delete');
        Route::get('/edit/{id}', 'FormaPagamentoController@edit');
        Route::get('/new', 'FormaPagamentoController@new');

        Route::post('/save', 'FormaPagamentoController@save');
        Route::post('/update', 'FormaPagamentoController@update');
    });

    Route::group(['prefix' => 'subcategorias'],function(){
        Route::get('/list/{categoria_id}', 'SubsCategoriaController@index');
        Route::get('/delete/{id}', 'SubsCategoriaController@delete');
        Route::get('/edit/{id}', 'SubsCategoriaController@edit');
        Route::get('/new/{categoria_id}', 'SubsCategoriaController@new');

        Route::post('/save', 'SubsCategoriaController@save');
        Route::post('/update', 'SubsCategoriaController@update');
        Route::post('/quickSave', 'SubsCategoriaController@quickSave');
    });

    Route::group(['prefix' => 'marcas'],function(){
        Route::get('/', 'MarcaController@index');
        Route::get('/delete/{id}', 'MarcaController@delete');
        Route::get('/edit/{id}', 'MarcaController@edit');
        Route::get('/new', 'MarcaController@new');

        Route::post('/save', 'MarcaController@save');
        Route::post('/update', 'MarcaController@update');
        Route::post('/quickSave', 'MarcaController@quickSave');
    });

    Route::group(['prefix' => 'gruposCliente'],function(){
        Route::get('/', 'GrupoClienteController@index');
        Route::get('/delete/{id}', 'GrupoClienteController@delete');
        Route::get('/edit/{id}', 'GrupoClienteController@edit');
        Route::get('/list/{id}', 'GrupoClienteController@list');
        Route::get('/new', 'GrupoClienteController@new');

        Route::post('/save', 'GrupoClienteController@save');
        Route::post('/update', 'GrupoClienteController@update');
    });

    Route::group(['prefix' => 'acessores'],function(){
        Route::get('/', 'AcessorController@index');
        Route::get('/comissaoFiltro', 'AcessorController@comissaoFiltro');
        Route::get('/delete/{id}', 'AcessorController@delete');
        Route::get('/edit/{id}', 'AcessorController@edit');
        Route::get('/list/{id}', 'AcessorController@list');
        Route::get('/comissao/{id}', 'AcessorController@comissao');
        Route::get('/comissaoDelete/{id}', 'AcessorController@comissaoDelete');
        Route::get('/new', 'AcessorController@new');
        Route::get('/pagarComissao', 'AcessorController@pagarComissao');

        Route::post('/save', 'AcessorController@save');
        Route::post('/update', 'AcessorController@update');
    });

    Route::group(['prefix' => 'divisaoGrade'],function(){
        Route::get('/', 'DivisaoGradeController@index');
        Route::get('/delete/{id}', 'DivisaoGradeController@delete');
        Route::get('/edit/{id}', 'DivisaoGradeController@edit');
        Route::get('/new', 'DivisaoGradeController@new');

        Route::post('/save', 'DivisaoGradeController@save');
        Route::post('/update', 'DivisaoGradeController@update');
    });

    Route::group(['prefix' => 'contaBancaria'],function(){
        Route::get('/', 'ContaBancariaController@index');
        Route::get('/delete/{id}', 'ContaBancariaController@delete');
        Route::get('/edit/{id}', 'ContaBancariaController@edit');
        Route::get('/new', 'ContaBancariaController@new');
        Route::get('/find/{id}', 'ContaBancariaController@find');
        Route::post('/save', 'ContaBancariaController@save');
        Route::post('/update', 'ContaBancariaController@update');
    });

    Route::group(['prefix' => 'contaFinanceira'],function(){
        Route::get('/', 'ContaFinanceiraController@index');
        Route::get('/delete/{id}', 'ContaFinanceiraController@delete');
        Route::get('/edit/{id}', 'ContaFinanceiraController@edit');
        Route::get('/new', 'ContaFinanceiraController@new');
        Route::get('/find/{id}', 'ContaFinanceiraController@find');
        Route::post('/save', 'ContaFinanceiraController@save');
        Route::post('/update', 'ContaFinanceiraController@update');
    });

    Route::group(['prefix' => 'categoriaContaFinanceira'],function(){
        Route::get('/', 'CategoriaContaFinanceiraController@index');
        Route::get('/delete/{id}', 'CategoriaContaFinanceiraController@delete');
        Route::get('/edit/{id}', 'CategoriaContaFinanceiraController@edit');
        Route::get('/new', 'CategoriaContaFinanceiraController@new');
        Route::post('/save', 'CategoriaContaFinanceiraController@save');
        Route::post('/update', 'CategoriaContaFinanceiraController@update');

        Route::get('/newSub/{id}', 'CategoriaContaFinanceiraController@newSub');
        Route::get('/editSub/{id}', 'CategoriaContaFinanceiraController@editSub');
        Route::post('/saveSub', 'CategoriaContaFinanceiraController@saveSub');
        Route::post('/updateSub', 'CategoriaContaFinanceiraController@updateSub');

    });

    Route::group(['prefix' => 'naturezaOperacao'],function(){
        Route::get('/', 'NaturezaOperacaoController@index');
        Route::get('/delete/{id}', 'NaturezaOperacaoController@delete');
        Route::get('/edit/{id}', 'NaturezaOperacaoController@edit');
        Route::get('/new', 'NaturezaOperacaoController@new');

        Route::post('/request', 'NaturezaOperacaoController@request');
        Route::post('/save', 'NaturezaOperacaoController@save');
        Route::post('/update', 'NaturezaOperacaoController@update');
        Route::get('/find/{id}', 'NaturezaOperacaoController@find');
    });

    Route::group(['prefix' => 'categoriasServico'],function(){
        Route::get('/', 'CategoriaServicoController@index');
        Route::get('/delete/{id}', 'CategoriaServicoController@delete');
        Route::get('/edit/{id}', 'CategoriaServicoController@edit');
        Route::get('/new', 'CategoriaServicoController@new');

        Route::post('/request', 'CategoriaServicoController@request');
        Route::post('/save', 'CategoriaServicoController@save');
        Route::post('/update', 'CategoriaServicoController@update');
    });

    Route::group(['prefix' => 'categoriasConta'],function(){
        Route::get('/', 'CategoriaContaController@index');
        Route::get('/delete/{id}', 'CategoriaContaController@delete');
        Route::get('/edit/{id}', 'CategoriaContaController@edit');
        Route::get('/new', 'CategoriaContaController@new');

        Route::post('/request', 'CategoriaContaController@request');
        Route::post('/save', 'CategoriaContaController@save');
        Route::post('/update', 'CategoriaContaController@update');
    });

    Route::group(['prefix' => 'motoboys'],function(){
        Route::get('/', 'MotoboyController@index');
        Route::get('/delete/{id}', 'MotoboyController@delete');
        Route::get('/edit/{id}', 'MotoboyController@edit');
        Route::get('/entregas/{id}', 'MotoboyController@entregas');
        Route::get('/create', 'MotoboyController@create');
        Route::post('/store', 'MotoboyController@store');
        Route::post('/update', 'MotoboyController@update');
        Route::get('/updatEntregas', 'MotoboyController@updatEntregas');
    });

    Route::group(['prefix' => 'contasPagar'],function(){
        Route::post('/salvarParcela', 'ContasPagarController@salvarParcela');
        Route::get('/', 'ContasPagarController@index');
        Route::get('/filtro', 'ContasPagarController@filtro');
        Route::get('/new', 'ContasPagarController@new');
        Route::get('/edit/{id}', 'ContasPagarController@edit');
        Route::get('/delete/{id}', 'ContasPagarController@delete');
        Route::get('/pagar/{id}', 'ContasPagarController@pagar');
        Route::get('/estorno/{id}', 'ContasPagarController@estorno');

        Route::post('/save', 'ContasPagarController@save');
        Route::post('/update', 'ContasPagarController@update');
        Route::post('/pagar', 'ContasPagarController@pagarConta');
        Route::post('/estorno', 'ContasPagarController@estornoConta');
        Route::get('/relatorio', 'ContasPagarController@relatorio');
        Route::get('/pagarMultiplos/{ds}', 'ContasPagarController@pagarMultiplos');
        Route::post('/pagar-multi', 'ContasPagarController@pagarMultiploStore');
        Route::get('/export','ContasPagarController@exportExcel');
        Route::get('/syncNotaFiscal','ContasPagarController@syncNotaFiscal');
        Route::put('/setVeiculo/{id}', 'ContasPagarController@setVeiculo')->name('contasPagar.setVeiculo');
        Route::post('/fecharMesRetencoes', 'ContasPagarController@fecharMesRetencoes')->name('compras.fecharMesRetencoes');
        Route::post('/baixarParcial', 'ContasPagarController@baixarParcial')->name('contasPagar.baixarParcial');
        Route::get('/detalhes/{id}', 'ContasPagarController@detalhes');
        Route::get('/adiantamento/{fornecedorId}/saldo', 'ContasPagarController@getSaldo')->name('contas-pagar.adiantamento.saldo');
        Route::post('/lotes/{loteId}/estornar', 'ContasPagarController@estornoLote')->name('contas-pagar.lote.estornar');
    });

    Route::resource('retencoes', 'RetencaoController');
    Route::get('/retencoes-print', 'RetencaoController@print')->name('retencoes.print');
    Route::get('/compraconferencia', 'CompraConferenciaController@index');

    Route::group(['prefix' => 'conferencia'], function () {
        Route::get('/', 'ConferenciaFiscalController@index')->name('conferencia.index');
        Route::post('/integrar-massa', 'ConferenciaFiscalController@integrarMassa')->name('conferencia.integrar-massa');
        Route::get('/exportar-excel', 'ConferenciaFiscalController@exportarExcel')->name('conferencia.exportar-excel');
        Route::get('/imprimir', 'ConferenciaFiscalController@imprimir')->name('conferencia.imprimir');
    });

    Route::group(['prefix' => 'contasReceber'],function(){
        Route::post('/salvarParcela', 'ContaReceberController@salvarParcela');
        Route::get('/', 'ContaReceberController@index');
        Route::get('/importacao', 'ContaReceberController@importacao');
        Route::post('/importacao', 'ContaReceberController@importacaoStore');
        Route::get('/downloadModelo', 'ContaReceberController@downloadModelo');
        Route::get('/filtro', 'ContaReceberController@filtro');
        Route::get('/new', 'ContaReceberController@new');
        Route::get('/edit/{id}', 'ContaReceberController@edit');
        Route::get('/delete/{id}', 'ContaReceberController@delete');
        Route::get('/receber/{id}', 'ContaReceberController@receber');
        Route::get('/estorno/{id}', 'ContaReceberController@estorno');

        Route::post('/save', 'ContaReceberController@save');
        Route::post('/update', 'ContaReceberController@update');
        Route::post('/receber', 'ContaReceberController@receberConta');
        Route::get('/relatorio', 'ContaReceberController@relatorio');
        Route::post('/estorno', 'ContaReceberController@estornoConta');

        Route::post('/receberSomente', 'ContaReceberController@receberSomente');
        Route::post('/receberComDivergencia', 'ContaReceberController@receberComDivergencia');
        Route::post('/receberComOutros', 'ContaReceberController@receberComOutros');
        Route::get('/detalhes_venda/{conta_id}', 'ContaReceberController@detalhesVenda');

        Route::get('/pendentes', 'ContaReceberController@pendentes');
        Route::get('/filtroPendente', 'ContaReceberController@filtroPendente');
        Route::get('/receberMultiplos/{ids}', 'ContaReceberController@receberMultiplos');
        Route::post('/receberMulti', 'ContaReceberController@receberMulti');

        Route::get('/export', 'ContaReceberController@exportExcel');
        Route::get('/syncNotaFiscal','ContaReceberController@syncNotaFiscal');
        Route::get('/visualizarDanfe/{id}', 'ContaReceberController@visualizarDanfe');

        Route::get('/detalhes/{id}', 'ContaReceberController@detalhes');
        Route::post('/baixarParcial', 'ContaReceberController@baixarParcial');

    });

    Route::group(['prefix' => 'produtos'],function(){
        Route::get('/teste', 'ProductController@teste');
        Route::get('/', 'ProductController@index');
        Route::get('/delete/{id}', 'ProductController@delete');
        Route::get('/delete-all', 'ProductController@deleteAll');
        Route::get('/edit/{id}', 'ProductController@edit');
        Route::get('/editGrade/{id}', 'ProductController@editGrade');
        Route::get('/new', 'ProductController@new')->middleware('limiteProdutos');
        Route::get('/all', 'ProductController@all');
        Route::get('/composto', 'ProductController@composto');
        Route::get('/naoComposto', 'ProductController@naoComposto');
        Route::get('/getProduto/{id}', 'ProductController@getProduto');
        Route::get('/getProdutoCodigoReferencia/{codigo}',
            'ProductController@getProdutoCodigoReferencia');
        Route::get('/getProdutoVenda/{id}/{lista_id}', 'ProductController@getProdutoVenda');
        Route::get('/getProdutoCodBarras/{id}', 'ProductController@getProdutoCodBarras');
        Route::get('/receita/{id}', 'ProductController@receita');
        Route::get('/duplicar/{id}', 'ProductController@duplicar');
        Route::get('/pesquisa', 'ProductController@pesquisa');
        Route::get('/pesquisaSelect2', 'ProductController@pesquisaSelect2');
        Route::get('/filtroCategoria', 'ProductController@filtroCategoria');
        Route::get('/getUnidadesMedida', 'ProductController@getUnidadesMedida');
        Route::post('/request', 'ProductController@request');
        Route::post('/save', 'ProductController@save');
        Route::post('/update', 'ProductController@update');
        Route::post('/getValue', 'ProductController@getValue');
        Route::post('/salvarProdutoDaNota', 'ProductController@salvarProdutoDaNota');
        Route::post('/salvarProdutoDaNotaComEstoque', 'ProductController@salvarProdutoDaNotaComEstoque');
        Route::post('/updateProdutoDaNotaComEstoque', 'ProductController@updateProdutoDaNotaComEstoque');
        Route::post('/setEstoque', 'ProductController@setEstoque');

        Route::get('/movimentacao/{id}', 'ProductController@movimentacao');
        Route::get('/movimentacaoImprimir/{id}', 'ProductController@movimentacaoImprimir');
        Route::get('/relatorio', 'ProductController@relatorio');
        Route::get('/set-estoque/{id}', 'StockController@setEstoqueLocais');

        Route::get('/importacao', 'ProductController@importacao');
        Route::get('/downloadModelo', 'ProductController@downloadModelo');
        Route::post('/importacao', 'ProductController@importacaoStore');

        Route::get('/grade/{id}', 'ProductController@grade');
        Route::post('/quickSave', 'ProductController@quickSave');
        Route::post('/atualizarGradeCompleta', 'ProductController@atualizarGradeCompleta');

        Route::get('/produtosRandom', 'ProductController@produtosRandom');
        Route::get('/produtosDaCategoria', 'ProductController@produtosDaCategoria');
        Route::get('/autocomplete', 'ProductController@autocomplete');
        Route::get('/autocompleteProduto', 'ProductController@autocompleteProduto');
        Route::get('/gerarCodigoEan', 'ProductController@gerarCodigoEan');
        Route::get('/etiqueta/{id}', 'ProductController@etiqueta');
        Route::post('/etiquetaStore', 'ProductController@etiquetaStore');

        Route::get('/verEtiquetasPadroes', 'ProductController@verEtiquetasPadroes');
        Route::get('/newEtiquetaPadrao', 'ProductController@newEtiquetaPadrao');
        Route::get('/deleteEtiqueta/{id}', 'ProductController@deleteEtiqueta');
        Route::get('/editEtiqueta/{id}', 'ProductController@editEtiqueta');

        Route::post('/saveEtiqueta', 'ProductController@saveEtiqueta');
        Route::post('/updateEtiqueta', 'ProductController@updateEtiqueta');
        Route::get('/exportacao', 'ProductController@exportacao');
        Route::get('/randGrade', 'ProductController@randGrade');

        Route::get('/atualizaIbpt', 'IbptController@atualizaIbpt');
        Route::get('/dup/{qtd}', 'ProductController@dup');
        Route::get('/exportacaoBalanca', 'ProductController@exportacaoBalanca');
        Route::post('/exportacaoBalanca', 'ProductController@exportacaoBalancaFile');
        Route::post('/corrige-locais', 'ProductController@corrigeLocais')->name('produtos.corrigeLocais');
        //Route::post('/corrige-locais', 'ProductController@corrigeLocais')->name('produtos.corrigeLocais')->middleware('auth');

        Route::get('/ajax/cst-ibs-cbs', 'ProductController@ajaxCstIbsCbs');
        Route::get('/ajax/class-trib-ibs-cbs', 'ProductController@ajaxClassTribIbsCbs');
        Route::get('/ajax/reducao-ibs-cbs', 'ProductController@ajaxReducaoIbsCbs');
        Route::post('/ibs-cbs/atualizar', 'ProdutoIbsCbsController@atualizar')->name('produtos.ibscbs.atualizar');
        Route::get('/searchProduto', 'ProductController@searchProduto')->name('produtos.searchProduto');
        Route::post('/salvarProdutoAjaxCompleto', 'ProductController@salvarProdutoAjaxCompleto');
    });

    Route::group(['prefix' => 'receita'],function(){
        Route::post('/save', 'ReceitaController@save');
        Route::post('/update', 'ReceitaController@update');
        Route::post('/saveItem', 'ReceitaController@saveItem');
        Route::get('/deleteItem/{id}', 'ReceitaController@deleteItem');

    });

    Route::group(['prefix' => 'vendasEmCredito'],function(){
        Route::get('/', 'CreditoVendaController@index');
        Route::get('/receber', 'CreditoVendaController@receber');
        Route::get('/delete/{id}', 'CreditoVendaController@delete');
        //Route::get('/somaVendas/{cliente_id}', 'CreditoVendaController@somaVendas');
        Route::get('/somaVendas/{clienteId}/{filialId?}', 'CreditoVendaController@somaVendas');


        Route::get('/emitirNFe', 'CreditoVendaController@emitirNFe');
        Route::get('/filtro', 'CreditoVendaController@filtro');
        Route::get('/apenasReceber', 'CreditoVendaController@apenasReceber');

    });

    Route::group(['prefix' => 'trocas'],function(){
        Route::get('/', 'TrocaController@index');
        Route::get('/filtro', 'TrocaController@filtro');
        Route::get('/nova', 'TrocaController@nova');
        Route::get('/autocomplete', 'TrocaController@autocomplete');
        Route::get('/getVenda', 'TrocaController@getVenda');
        Route::post('/save', 'TrocaController@save');

        Route::get('/creditoCliente/{clietne}', 'TrocaController@creditoCliente');
        Route::get('/delete/{id}', 'TrocaController@delete');

    });

    Route::group(['prefix' => 'vendasCaixa'],function(){
        Route::post('/save', 'VendaCaixaController@save');
        Route::get('/diaria', 'VendaCaixaController@diaria');
        Route::get('/detalhe-pagamento/{id}', 'VendaCaixaController@detalhesPagamento');
        Route::get('/calcComissao', 'VendaCaixaController@calcComissao');
        Route::get('/pix', 'VendaCaixaController@gerarQrCode');
        Route::get('/consultaPix/{id}', 'VendaCaixaController@consultaPix');

        Route::post('/save/troca', 'VendaCaixaController@saveTroca');
        Route::post('/save/prevenda', 'VendaCaixaController@savePreVenda');
        Route::get('/prevenda', 'VendaCaixaController@prevendas');
        Route::get('/prevendaAll', 'VendaCaixaController@prevendaAll');
        Route::post('/prevenda/devolver/{id}', 'VendaCaixaController@prevendaRetorno');
        Route::post('/enviar-whats', 'VendaCaixaController@enviarWhats');
        Route::get('/find/{id}', 'VendaCaixaController@find');
    });

    Route::group(['prefix' => 'tributos'], function(){
        Route::get('/', 'TributoController@index');
        Route::post('/save', 'TributoController@save');
    });

    Route::group(['prefix' => 'funcionamentoDelivery'], function(){
        Route::get('/', 'FuncionamentoDeliveryController@index');
        Route::post('/save', 'FuncionamentoDeliveryController@save');
        Route::get('/edit/{id}', 'FuncionamentoDeliveryController@edit');
        Route::get('/alterarStatus/{id}', 'FuncionamentoDeliveryController@alterarStatus');

    });

    Route::group(['prefix' => 'enviarXml'],function(){
        Route::get('/', 'EnviarXmlController@index');
        Route::get('/filtro', 'EnviarXmlController@filtro');
        Route::get('/download', 'EnviarXmlController@download');
        Route::get('/downloadNfce', 'EnviarXmlController@downloadNfce');
        Route::get('/downloadCte', 'EnviarXmlController@downloadCte');
        Route::get('/downloadCompraFiscal', 'EnviarXmlController@downloadCompraFiscal');
        Route::get('/downloadMdfe', 'EnviarXmlController@downloadMdfe');
        Route::get('/downloadEntrada', 'EnviarXmlController@downloadEntrada');
        Route::get('/downloadDevolucao', 'EnviarXmlController@downloadDevolucao');
        Route::get('/downloadNfse', 'EnviarXmlController@downloadNfse');
        Route::get('/email/{d1}/{d2}', 'EnviarXmlController@email');
        Route::get('/emailNfce/{d1}/{d2}', 'EnviarXmlController@emailNfce');
        Route::get('/emailCte/{d1}/{d2}', 'EnviarXmlController@emailCte');
        Route::get('/emailMdfe/{d1}/{d2}', 'EnviarXmlController@emailMdfe');
        Route::get('/emailEntrada/{d1}/{d2}', 'EnviarXmlController@emailEntrada');
        Route::get('/emailDevolucao/{d1}/{d2}', 'EnviarXmlController@emailDevolucao');
        Route::get('/emailNfse/{d1}/{d2}', 'EnviarXmlController@emailNfse');
        Route::get('/emailCompraFiscal/{d1}/{d2}', 'EnviarXmlController@emailCompraFiscal');
        Route::get('/send', 'EnviarXmlController@send');
        Route::get('/sendAll', 'EnviarXmlController@sendAll');
        Route::get('/downloadAll', 'EnviarXmlController@downloadAll');

        Route::get('/filtroCfop', 'EnviarXmlController@filtroCfop');
        Route::get('/filtroCfopGet', 'EnviarXmlController@filtroCfopGet');
        Route::post('/filtroCfopImprimir', 'EnviarXmlController@filtroCfopImprimir');
        Route::post('/filtroCfopImprimirGroup', 'EnviarXmlController@filtroCfopImprimirGroup');
    });

    Route::group(['prefix' => 'nf'],function(){
        Route::post('/gerarNf', 'NotaFiscalController@gerarNf')->middleware('limiteNFe');
        Route::post('/gerarNfWithXml', 'NotaFiscalController@gerarNfWithXml')->middleware('limiteNFe');
        Route::post('/consultaStatusSefaz', 'NotaFiscalController@consultaStatusSefaz');
        Route::get('/xmlTemp/{id}', 'NotaFiscalController@xmlTemp');
        Route::get('/gerarNf/{id}', 'NotaFiscalController@testeGerar');
        Route::get('/imprimir/{id}', 'NotaFiscalController@imprimir');
        Route::get('/imprimirSimples/{id}', 'NotaFiscalController@imprimirSimples');
        Route::get('/escpos/{id}', 'NotaFiscalController@escpos');
        Route::get('/imprimirCce/{id}', 'NotaFiscalController@imprimirCce');
        Route::get('/imprimirCancela/{id}', 'NotaFiscalController@imprimirCancela');
        Route::get('/consultar_cliente/{id}', 'NotaFiscalController@consultar_cliente');
        Route::post('/cancelar', 'NotaFiscalController@cancelar');
        Route::post('/consultar', 'NotaFiscalController@consultar');
        Route::post('/cartaCorrecao', 'NotaFiscalController@cartaCorrecao');
        Route::get('/teste', 'NotaFiscalController@teste');
        Route::get('/consultaCadastro', 'NotaFiscalController@consultaCadastro');
        Route::post('/inutilizar', 'NotaFiscalController@inutilizar');
        Route::get('/certificado', 'NotaFiscalController@certificado');
        Route::get('/enviarXml', 'NotaFiscalController@enviarXml');
        Route::get('/testeVenda/{id}', 'NotaFiscalController@testeVenda');

        Route::get('/filtro', 'NotaFiscalController@filtro');

    });

    Route::group(['prefix' => 'cte'],function(){
        Route::get('/', 'CteController@index');
        Route::get('/nova', 'CteController@nova');
        Route::get('/lista', 'CteController@lista');

        Route::get('/detalhar/{id}', 'CteController@detalhar');
        Route::get('/edit/{id}', 'CteController@edit');

        Route::get('/delete/{id}', 'CteController@delete');
        Route::post('/salvar', 'CteController@salvar');
        Route::post('/update', 'CteController@update');
        Route::get('/filtro', 'CteController@filtro');
        Route::get('/custos/{id}', 'CteController@custos');
        Route::post('/saveReceita', 'CteController@saveReceita');
        Route::post('/saveDespesa', 'CteController@saveDespesa');
        Route::post('/importarXml', 'CteController@importarXml');

        Route::get('/deleteReceita/{id}', 'CteController@deleteReceita');
        Route::get('/deleteDespesa/{id}', 'CteController@deleteDespesa');

        Route::get('/consultaChave', 'EmiteCteController@consultaChave');
        Route::get('/chaveNfeDuplicada', 'CteController@chaveNfeDuplicada');

        Route::get('/download-xml/{id}', 'EmiteCteController@downloadXml');
        Route::get('/manifesta', 'EmiteCteController@manifesta');
        Route::get('/consultaDocumentos', 'EmiteCteController@consultaDocumentos');
        Route::get('/manifestar', 'EmiteCteController@manifestar');
        Route::get('/manifestaFiltro', 'EmiteCteController@manifestaFiltro');
        Route::get('/manifestaImprimir/{chave}', 'EmiteCteController@manifestaImprimir');
        Route::get('/imprimir', 'CteController@imprimir');

        Route::get('/estadoFiscal/{id}', 'CteController@estadoFiscal');
        Route::post('/estadoFiscal', 'CteController@estadoFiscalStore');
        Route::get('/alterarStatus/{id}', 'CteController@alterarStatus');
        Route::get('/fatura/{id}', 'CteController@fatura');
        Route::post('/salvarFatura', 'CteController@salvarFatura');
        Route::get('/faturas', 'CteController@faturas');
        Route::get('/imprimirFatura/{fatura_id}', 'CteController@imprimirFatura');
        Route::get('/filtroFatura', 'CteController@filtroFatura');
        Route::get('/deleteFatura/{id}', 'CteController@deleteFatura');
    });

    Route::group(['prefix' => 'cteos'],function(){
        Route::get('/', 'CteOsController@index');
        Route::get('/nova', 'CteOsController@nova');
        Route::get('/lista', 'CteOsController@lista');
        Route::get('/filtro', 'CteOsController@filtro');
        Route::post('/update', 'CteOsController@update');
        Route::post('/salvar', 'CteOsController@salvar');
        Route::get('/xmlTemp/{id}', 'CteOsController@xmlTemp');

        Route::post('/enviar', 'CteOsController@enviar');
        Route::get('/delete/{id}', 'CteOsController@delete');

        Route::get('/detalhar/{id}', 'CteOsController@detalhar');
        Route::get('/edit/{id}', 'CteOsController@edit');

        Route::get('/estadoFiscal/{id}', 'CteOsController@estadoFiscal');
        Route::post('/estadoFiscal', 'CteOsController@estadoFiscalStore');

        Route::get('/imprimir/{id}', 'CteOsController@imprimir');
        Route::get('/download/{id}', 'CteOsController@download');
        Route::post('/consultar', 'CteOsController@consultar');

        Route::post('/cartaCorrecao', 'CteOsController@cartaCorrecao');
        Route::post('/cancelar', 'CteOsController@cancelar');

        Route::get('/imprimirCCe/{id}', 'CteOsController@imprimirCCe');
        Route::get('/imprimirCancela/{id}', 'CteOsController@imprimirCancela');
        Route::get('/dacteTemp/{id}', 'CteOsController@dacteTemp');
        Route::get('/enviarXml', 'CteOsController@enviarXml');


    });

    Route::group(['prefix' => 'cteSefaz'],function(){
        Route::post('/enviar', 'EmiteCteController@enviar');
        Route::get('/imprimir/{id}', 'EmiteCteController@imprimir');
        Route::get('/imprimirCCe/{id}', 'EmiteCteController@imprimirCCe');
        Route::get('/imprimirCancela/{id}', 'EmiteCteController@imprimirCancela');
        Route::post('/cancelar', 'EmiteCteController@cancelar');
        Route::post('/consultar', 'EmiteCteController@consultar');
        Route::post('/inutilizar', 'EmiteCteController@inutilizar');
        Route::post('/cartaCorrecao', 'EmiteCteController@cartaCorrecao');
        Route::get('/teste/{id}', 'EmiteCteController@teste');
        Route::get('/enviarXml', 'EmiteCteController@enviarXml');
        Route::get('/baixarXml/{id}', 'EmiteCteController@baixarXml');
        Route::get('/xmlTemp/{id}', 'EmiteCteController@xmlTemp');
        Route::get('/dacteTemp/{id}', 'EmiteCteController@dacteTemp');
        Route::get('/danfeTemp/{id}', 'EmiteCteController@danfeTemp');

    });


    Route::group(['prefix' => 'mdfe'],function(){
        Route::get('/', 'MdfeController@index');
        Route::get('/teste', 'MdfeController@teste');
        Route::get('/nova', 'MdfeController@nova');
        Route::get('/lista', 'MdfeController@lista');
        Route::get('/detalhar/{id}', 'MdfeController@detalhar');
        Route::get('/delete/{id}', 'MdfeController@delete');
        Route::get('/edit/{id}', 'MdfeController@edit');

        Route::post('/salvar', 'MdfeController@salvar');
        Route::post('/update', 'MdfeController@update');
        Route::get('/filtro', 'MdfeController@filtro');
        Route::get('/createWithNfe/{ids}', 'MdfeController@createWithNfe');
        Route::get('/estadoFiscal/{id}', 'MdfeController@estadoFiscal');
        Route::post('/estadoFiscal', 'MdfeController@estadoFiscalStore');
        Route::post('/importarXml', 'MdfeController@importarXml');
    });

    Route::group(['prefix' => 'mdfeSefaz'],function(){
        Route::post('/enviar', 'EmiteMdfeController@enviar')->middleware('limiteMDFe');
        Route::get('/imprimir/{id}', 'EmiteMdfeController@imprimir');
        Route::get('/baixarXml/{id}', 'EmiteMdfeController@baixarXml');
        Route::post('/cancelar', 'EmiteMdfeController@cancelar');
        Route::post('/consultar', 'EmiteMdfeController@consultar');

        Route::get('/naoEncerrados', 'EmiteMdfeController@naoEncerrados');
        Route::post('/encerrar', 'EmiteMdfeController@encerrar');
        Route::get('/enviarXml', 'EmiteMdfeController@enviarXml');
        Route::get('/xmlTemp/{id}', 'EmiteMdfeController@xmlTemp');
        Route::get('/teste/{id}', 'EmiteMdfeController@teste');
        // Route::get('/imprimirCancela/{id}', 'EmiteMdfeController@imprimirCancela');
    });

    Route::group(['prefix' => 'nfce'],function(){
        Route::post('/gerar', 'NFCeController@gerar')->middleware('limiteNFCe');
        Route::post('/transmitir-contigencia', 'NFCeController@transmitirContigencia');
        Route::get('/xmlTemp/{id}', 'NFCeController@xmlTemp');
        Route::get('/danfceTemp/{id}', 'NFCeController@danfceTemp');
        Route::get('/imprimir/{id}', 'NFCeController@imprimir');
        Route::get('/imprimirNaoFiscal/{id}', 'NFCeController@imprimirNaoFiscal');
        Route::get('/imprimirNaoFiscal2/{id}', 'NFCeController@imprimirNaoFiscal2');
        Route::get('/imprimirNaoFiscalCredito/{id}', 'NFCeController@imprimirNaoFiscalCredito');
        Route::post('/cancelar', 'NFCeController@cancelar');
        Route::get('/deleteVenda/{id}', 'NFCeController@deleteVenda');
        Route::get('/consultar/{id}', 'NFCeController@consultar');
        Route::get('/baixarXml/{id}', 'NFCeController@baixarXml');
        Route::get('/detalhes/{id}', 'NFCeController@detalhes');
        Route::get('/estadoFiscal/{id}', 'NFCeController@estadoFiscal');
        Route::post('/estadoFiscal', 'NFCeController@estadoFiscalStore');

        // Route::post('/consultar', 'NotaFiscalController@consultar');
        Route::get('/teste', 'NFCeController@teste');
        Route::post('/inutilizar', 'NFCeController@inutilizar');

        Route::get('/imprimirRascunhoPrevenda/{id}', 'NFCeController@imprimirRascunhoPrevenda');
        Route::get('/imprimirComprovanteAssessor/{id}', 'NFCeController@imprimirComprovanteAssessor');
        Route::get('/vendaNFe/{id}', 'VendaCaixaController@vendaNFe');
        Route::post('/consultaStatusSefaz', 'NFCeController@consultaStatusSefaz');
        Route::get('/ticket-troca/{id}', 'NFCeController@ticketTroca');
        Route::get('/imprimirPreVenda/{id}', 'NFCeController@imprimirPreVenda');

    });

    Route::group(['prefix' => 'cashback-config'],function(){
        Route::get('/', 'CashBackConfigController@index');
        Route::post('/store', 'CashBackConfigController@store');
    });

    Route::group(['prefix' => 'clientes'],function(){
        Route::get('/', 'ClienteController@index');
        Route::get('/buscar', 'ClienteController@buscar');
        Route::get('/upload/{id}', 'ClienteController@upload');
        Route::get('/download-documento/{id}', 'ClienteController@downloadDocumento');
        Route::post('/upload-store/{id}', 'ClienteController@uploadStore');
        Route::delete('/destroy-upload/{id}', 'ClienteController@destroyUpload')->name('clientes.destroy-upload');
        Route::get('/delete/{id}', 'ClienteController@delete');
        Route::get('/edit/{id}', 'ClienteController@edit');
        Route::get('/new', 'ClienteController@new')->middleware('limiteClientes');
        Route::get('/all', 'ClienteController@all');
        Route::get('/verificaLimite', 'ClienteController@verificaLimite');
        Route::get('/find/{id}', 'ClienteController@find');
        Route::get('/findCliente/{id}', 'ClienteController@findCliente');
        Route::get('/pesquisa', 'ClienteController@pesquisa');

        Route::post('/request', 'ClienteController@request');
        Route::post('/quickSave', 'ClienteController@quickSave');
        Route::post('/save', 'ClienteController@save');
        Route::post('/update', 'ClienteController@update');
        Route::get('/cpfCnpjDuplicado', 'ClienteController@cpfCnpjDuplicado');

        Route::get('/importacao', 'ClienteController@importacao');
        Route::get('/downloadModelo', 'ClienteController@downloadModelo');
        Route::post('/importacao', 'ClienteController@importacaoStore');
        Route::get('/relatorio', 'ClienteController@relatorio');
        Route::get('/consultaCadastrado/{doc}', 'ClienteController@consultaCadastrado');
        Route::get('/findOne/{id}', 'ClienteController@findOne');
        Route::get('/cashBacks/{id}', 'ClienteController@cashBacks');

        Route::get('/search/cliente', 'ClienteController@searchCliente')->name('clientes.search.cliente');

        Route::get('/historico/{id}', 'ClienteController@historico');
        Route::get('/receitas/{id}', 'ClienteController@buscarReceitas');
        Route::get('/imprimirReceita/{id}', 'ClienteController@imprimirReceita');
    });

    Route::group(['prefix' => 'clientesDelivery'],function(){
        Route::get('/', 'ClienteDeliveryController@index');
        Route::get('/edit/{id}', 'ClienteDeliveryController@edit');
        Route::get('/delete/{id}', 'ClienteDeliveryController@delete');
        Route::get('/all', 'ClienteDeliveryController@all');
        Route::post('/update', 'ClienteDeliveryController@update');

        Route::get('/pedidos/{id}', 'ClienteDeliveryController@pedidos');
        Route::get('/enderecos/{id}', 'ClienteDeliveryController@enderecos');
        Route::get('/enderecosEdit/{id}', 'ClienteDeliveryController@enderecoEdit');
        Route::get('/enderecosMap/{id}', 'ClienteDeliveryController@enderecosMap');
        Route::get('/favoritos/{id}', 'ClienteDeliveryController@favoritos');
        Route::get('/push/{id}', 'ClienteDeliveryController@push');
        Route::post('/updateEndereco', 'ClienteDeliveryController@updateEndereco');

        Route::get('/pesquisa', 'ClienteDeliveryController@pesquisa');
    });


    Route::group(['prefix' => 'transportadoras'],function(){
        Route::get('/', 'TransportadoraController@index');
        Route::get('/delete/{id}', 'TransportadoraController@delete');
        Route::get('/edit/{id}', 'TransportadoraController@edit');
        Route::get('/new', 'TransportadoraController@new');
        Route::get('/all', 'TransportadoraController@all');
        Route::get('/find/{id}', 'TransportadoraController@find');

        Route::post('/save', 'TransportadoraController@save');
        Route::post('/update', 'TransportadoraController@update');
        Route::post('/quickSave', 'TransportadoraController@quickSave');
    });

    Route::group(['prefix' => 'fornecedores'],function(){
        Route::get('/', 'ProviderController@index');
        Route::get('/pesquisa', 'ProviderController@pesquisa');
        Route::get('/delete/{id}', 'ProviderController@delete');
        Route::get('/edit/{id}', 'ProviderController@edit');
        Route::get('/new', 'ProviderController@new')->middleware('limiteFornecedor');
        Route::get('/all', 'ProviderController@all');
        Route::get('/find/{id}', 'ProviderController@find');

        Route::post('/request', 'ProviderController@request');
        Route::post('/save', 'ProviderController@save');
        Route::post('/update', 'ProviderController@update');
        Route::get('/consultaCadastrado/{doc}', 'ProviderController@consultaCadastrado');
        Route::post('/quickSave', 'ProviderController@quickSave');
        Route::get('/historico/{id}', 'ProviderController@historico');
        Route::get('/toggle-ativo/{id}', 'ProviderController@toggleAtivo');
        Route::get('/limpar-duplicidades', 'ProviderController@limparDuplicidades');
    });

    Route::group(['prefix' => 'compraFiscal', 'middleware' => ['limiteProdutos', 'limiteClientes']],function(){
        Route::get('/', 'CompraFiscalController@index');
        Route::post('/new', 'CompraFiscalController@new');
        Route::post('/salvarNfFiscal', 'CompraFiscalController@salvarNfFiscal');
        Route::post('/salvarItem', 'CompraFiscalController@salvarItem');
        Route::post('/salvarParcela', 'CompraFiscalController@salvarParcela');
        Route::get('/read', 'CompraFiscalController@read');
        Route::get('/teste', 'CompraFiscalController@teste');
        Route::get('/syncDataEmissaoRetroativa', 'CompraManualController@syncDataEmissaoRetroativa');
    });

    Route::group(['prefix' => 'compraManual'],function(){
        Route::get('/', 'CompraManualController@index');
        Route::get('/editar/{id}', 'CompraManualController@editar');
        Route::post('/salvar', 'CompraManualController@salvar');
        Route::post('/salvarNfFiscal', 'CompraManualController@salvarNfFiscal');
        Route::post('/salvarItem', 'CompraManualController@salvarItem');
        Route::get('/read', 'CompraManualController@read');

        Route::get('/ultimaCompra/{produtoId}', 'CompraManualController@ultimaCompra');

        Route::post('/update', 'CompraManualController@update');
        Route::get('/custo-medio', 'CompraManualController@custoMedio');
        Route::post('/updateItem', 'CompraManualController@updateItem');
        Route::post('/editarItem', 'CompraManualController@editarItem')->name('compraManual.editarItem');
        Route::post('/excluirItem', 'CompraManualController@excluirItem')->name('compraManual.excluirItem');


    });



    Route::group(['prefix' => 'ponto'], function(){
        Route::get('/', 'PontoController@dashboard');

        Route::group(['prefix' => 'relogios'], function(){
            Route::get('/', 'PontoRelogioController@index');
            Route::get('/new', 'PontoRelogioController@new');
            Route::get('/edit/{id}', 'PontoRelogioController@edit');
            Route::get('/delete/{id}', 'PontoRelogioController@delete');
            Route::post('/save', 'PontoRelogioController@save');
            Route::put('/update/{id}', 'PontoRelogioController@update');
        });

        Route::get('/importacao-afd', 'PontoAfdController@index');
        Route::post('/importacao-afd', 'PontoAfdController@store');
        Route::get('/importacao-afd/{id}', 'PontoAfdController@show');

        Route::get('/marcacoes', 'PontoMarcacaoController@index');
        Route::post('/marcacoes/tratar', 'PontoMarcacaoController@tratar');

        Route::get('/jornadas', 'PontoJornadaController@index');
        Route::post('/jornadas/save', 'PontoJornadaController@storeJornada');
        Route::post('/turnos/save', 'PontoJornadaController@storeTurno');
        Route::post('/escalas/save', 'PontoJornadaController@storeEscala');
        Route::get('/ajustes', 'PontoAjusteController@index');
        Route::post('/ajustes', 'PontoAjusteController@store');
        Route::post('/ajustes/aprovar', 'PontoAjusteController@aprovar');

        Route::get('/fechamentos', 'PontoFechamentoController@index');
        Route::post('/fechamentos/fechar', 'PontoFechamentoController@fechar');
        Route::post('/fechamentos/reabrir', 'PontoFechamentoController@reabrir');

        Route::get('/banco-horas', 'PontoBancoHorasController@index');
        Route::post('/banco-horas/recalcular', 'PontoBancoHorasController@recalcular');

        Route::get('/relatorios', 'PontoRelatorioController@index');
        Route::get('/relatorios/csv', 'PontoRelatorioController@csv');

        // Rotas adicionais do NOVO: espelho, fechamento, férias e exportação.
        Route::get('/espelho', 'PontoMarcacaoController@espelho')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/espelho/pdf/{id}', 'PontoMarcacaoController@espelhoPdf')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/fechamento', 'PontoFechamentoController@index')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/fechamento/processar', 'PontoFechamentoController@processarFechamento')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/fechamento/ajustar-saldo', 'PontoFechamentoController@ajustarSaldo')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/fechamento/reabrir', 'PontoFechamentoController@reabrirFechamento')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/ponto/dashboard', 'PontoController@dashboard')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/ponto/ferias/salvar', 'PontoController@salvarFerias')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/ponto/ferias/cancelar/{id}', 'PontoController@cancelarFerias')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/relatorios/exportar-afd', 'PontoRelatorioController@exportarAfd')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/ajuste-manual', 'PontoMarcacaoController@ajusteManual')->middleware(['verificaEmpresa', 'validaAcesso']);
    });

    Route::group(['prefix' => 'funcionarios'],function(){
        Route::get('/calcComissao', 'FuncionarioController@calcComissao');
        Route::get('/', 'FuncionarioController@index');
        Route::get('/delete/{id}', 'FuncionarioController@delete');
        Route::get('/edit/{id}', 'FuncionarioController@edit');
        Route::get('/new', 'FuncionarioController@new');
        Route::get('/all', 'FuncionarioController@all');
        Route::get('/contatos/{id}', 'FuncionarioController@contatos');
        Route::get('/editContato/{id}', 'FuncionarioController@editContato');
        Route::get('/deleteContato/{id}', 'FuncionarioController@deleteContato');
        Route::post('/saveContato', 'FuncionarioController@saveContato');
        Route::post('/updateContato', 'FuncionarioController@saveContato');

        Route::post('/request', 'FuncionarioController@request');
        Route::post('/save', 'FuncionarioController@save');
        Route::post('/update', 'FuncionarioController@update');

        Route::get('/comissao', 'FuncionarioController@comissao');
        Route::get('/pagarComissao', 'FuncionarioController@pagarComissao');
        Route::get('/comissaoFiltro', 'FuncionarioController@comissaoFiltro');

        // Agnaldo 14052026 - Importação Excel de Funcionários
        Route::post('/importExcel', 'FuncionarioController@importExcel')->name('funcionarios.importExcel');
        Route::get('/downloadLayout', 'FuncionarioController@downloadLayout')->name('funcionarios.downloadLayout');
    });

    Route::group(['prefix' => 'contatoFuncionario'],function(){
        Route::get('/{funcionaId}', 'FuncionarioController@index');
        Route::get('/delete/{id}', 'FuncionarioController@delete');
        Route::get('/edit/{id}', 'FuncionarioController@edit');
        Route::get('/new/{funcionarioId}', 'FuncionarioController@new');
        Route::post('/save', 'FuncionarioController@save');
        Route::post('/update', 'FuncionarioController@update');
    });

    Route::group(['prefix' => 'impressoras'],function(){
        Route::get('/', 'ImpressoraController@index');
        Route::get('/delete/{id}', 'ImpressoraController@delete');
        Route::get('/edit/{id}', 'ImpressoraController@edit');
        Route::get('/new', 'ImpressoraController@new');

        Route::post('/save', 'ImpressoraController@save');
        Route::post('/update', 'ImpressoraController@update');
        Route::get('/search', 'ImpressoraController@search');

    });

    Route::group(['prefix' => 'servicos'],function(){
        Route::get('/', 'ServiceController@index');
        Route::get('/delete/{id}', 'ServiceController@delete');
        Route::get('/edit/{id}', 'ServiceController@edit');
        Route::get('/new', 'ServiceController@new');
        Route::get('/all', 'ServiceController@all');
        Route::get('/find/{id}', 'ServiceController@find');

        Route::post('/request', 'ServiceController@request');
        Route::post('/save', 'ServiceController@save');
        Route::post('/update', 'ServiceController@update');
        Route::get('/pesquisa', 'ServiceController@pesquisa');
        Route::post('/getValue', 'ServiceController@getValue');

        Route::get('/autocomplete', 'ServiceController@autocomplete');

    });

    Route::group(['prefix' => 'orcamento'],function(){
        Route::get('/', 'BudgetController@index');
        Route::get('/delete/{id}', 'BudgetController@delete');
        Route::get('/new', 'BudgetController@new');

        Route::get('/searchClient', 'BudgetController@searchClient');
        Route::get('/searchDate', 'BudgetController@searchDate');

        Route::get('/os/{id}', 'BudgetController@os');
        Route::post('/save', 'BudgetController@save');
    });

    Route::group(['prefix' => 'ordemServico'],function(){
        Route::get('/', 'OrderController@index');
        Route::get('/new', 'OrderController@new');
        Route::get('/servicosordem/{id}', 'OrderController@servicosordem');
        Route::get('/deleteServico/{id}', 'OrderController@deleteServico');
        Route::get('/addRelatorio/{id}', 'OrderController@addRelatorio');
        Route::get('/editRelatorio/{id}', 'OrderController@editRelatorio');
        Route::get('/deleteRelatorio/{id}', 'OrderController@deleteRelatorio');
        Route::get('/alterarEstado/{id}', 'OrderController@alterarEstado');
        Route::post('/alterarEstado', 'OrderController@alterarEstadoPost');
        Route::get('/filtro', 'OrderController@filtro');

        Route::post('/addRelatorio', 'OrderController@saveRelatorio');
        Route::post('/updateRelatorio', 'OrderController@updateRelatorio');
        Route::get('/cashFlowFilter', 'OrderController@cashFlowFilter');
        Route::post('/save', 'OrderController@save');
        Route::post('/addServico', 'OrderController@addServico');
        Route::post('/find', 'OrderController@find');
        Route::post('/store-servico', 'OrderController@storeServico');
        Route::post('/store-produto', 'OrderController@storeProduto');

        Route::get('/print/{id}', 'OrderController@print');

        Route::get('/deleteFuncionario/{id}', 'OrderController@deleteFuncionario');
        Route::post('/saveFuncionario', 'OrderController@saveFuncionario');
        Route::post('/saveProduto', 'OrderController@saveProduto');
        Route::get('/deleteProduto/{id}', 'OrderController@deleteProduto');

        Route::get('/alterarStatusServico/{id}', 'OrderController@alterarStatusServico');
        Route::get('/imprimir/{id}', 'OrderController@imprimir');
        Route::get('/delete/{id}', 'OrderController@delete');
        Route::put('/update/{id}', 'OrderController@update');

        Route::get('/gerar_venda/{id}', 'OrderController@gerarVenda');
        Route::get('/gerar_nfse/{id}', 'OrderController@gerarNfse');
        Route::post('/store_venda', 'OrderController@storeVenda');
        Route::post('/store_pdv', 'OrderController@storePdv');
        Route::get('/gerarVendaCompleta/{id}', 'OrderController@gerarVendaCompleta');


        // Rotas adicionais do NOVO: faturamento, oficina, laudo técnico e WhatsApp.
        Route::get('/gerarVenda/{id}', 'OrderController@gerarVenda');
        Route::get('/gerarNfse/{id}', 'OrderController@gerarNfse');
        Route::get('/getVeiculosCliente/{clienteId}', 'OrderController@getVeiculosCliente');
        Route::post('/storeVeiculoCliente', 'OrderController@storeVeiculoCliente');
        Route::get('/historicoVeiculo/{veiculoId}', 'OrderController@historicoVeiculo');
        Route::post('/salvarChecklist', 'OrderController@salvarChecklist');
        Route::get('/enviarWhatsapp/{id}', 'OrderController@enviarWhatsapp');
    });

    Route::group(['prefix' => 'semRegistro'],function(){
        Route::get('/', 'ApplianceNotFounController@index');
        Route::get('/delete/{id}', 'ApplianceNotFounController@delete');
    });


    Route::group(['prefix' => 'fluxoCaixa'],function(){
        Route::get('/', 'FluxoCaixaController@index');
        Route::get('/filtro', 'FluxoCaixaController@filtro');
        Route::get('/relatorioIndex', 'FluxoCaixaController@relatorioIndex');
        Route::get('/relatorioFiltro/{data1}/{data2}', 'FluxoCaixaController@relatorioFiltro');
    });

    Route::group(['prefix' => 'orcamentoCliente'],function(){
        Route::get('/', 'ClientTempController@index');
        Route::get('/delete/{id}', 'ClientTempController@delete');
    });

    Route::group(['prefix' => 'nferemessa'],function(){
        Route::get('/', 'NfeRemessaController@index');
        Route::get('/filtro', 'NfeRemessaController@filtro');
        Route::get('/create', 'NfeRemessaController@create');
        Route::post('/store', 'NfeRemessaController@store');
        Route::put('/update/{id}', 'NfeRemessaController@update');
        Route::get('/delete/{id}', 'NfeRemessaController@delete');
        Route::get('/edit/{id}', 'NfeRemessaController@edit');
        Route::get('/clone/{id}', 'NfeRemessaController@clone');
        Route::get('/edit_xml/{id}', 'NfeRemessaController@editXml');

        Route::get('/gerarXml/{id}', 'NfeRemessaXmlController@gerarXml');
        Route::get('/imprimir/{id}', 'NfeRemessaXmlController@imprimir');
        Route::get('/rederizarDanfe/{id}', 'NfeRemessaXmlController@rederizarDanfe');
        Route::post('/transmitir', 'NfeRemessaXmlController@transmitir');
        Route::post('/gerarNfWithXml', 'NfeRemessaXmlController@gerarNfWithXml');
        Route::post('/consultar', 'NfeRemessaXmlController@consultar');
        Route::post('/cartaCorrecao', 'NfeRemessaXmlController@cartaCorrecao');
        Route::post('/cancelar', 'NfeRemessaXmlController@cancelar');

        Route::get('/imprimirCce/{id}', 'NfeRemessaXmlController@imprimirCce');
        Route::get('/imprimirCancela/{id}', 'NfeRemessaXmlController@imprimirCancela');
        Route::get('/baixarXml/{id}', 'NfeRemessaXmlController@baixarXml');
        Route::get('/enviarXml', 'NfeRemessaXmlController@enviarXml');
        Route::get('/estadoFiscal/{id}', 'NfeRemessaXmlController@estadoFiscal');
        Route::put('/estadoFiscal/{id}', 'NfeRemessaXmlController@estadoFiscalPut');

    });

    Route::resource('vendas-balcao', 'VendaBalcaoController');
    Route::get('vendas-balcao/destroy/{id}', 'VendaBalcaoController@destroy');
    Route::get('vendas-balcao-find', 'VendaBalcaoController@find');
    Route::post('vendas-balcao-store-pedido', 'VendaBalcaoController@storePedido');
    Route::post('vendas-balcao-store-nfce', 'VendaBalcaoController@storeNfce');

    Route::group(['prefix' => 'vendas'],function(){
        Route::get('/', 'VendaController@index');
        Route::get('/detalhe-pagamento/{id}', 'VendaController@detalhesPagamento');
        Route::get('/nova', 'VendaController@nova');
        // Route::get('/lista', 'VendaController@lista');
        Route::get('/detalhar/{id}', 'VendaController@detalhar');
        Route::get('/delete/{id}', 'VendaController@delete');
        Route::get('/inutilizar/{id}', 'VendaController@inutilizar')->name('vendas.inutilizar');
        Route::get('/edit/{id}', 'VendaController@edit');
        Route::get('/find/{id}', 'VendaController@find');
        Route::post('/salvar', 'VendaController@salvar');
        Route::post('/atualizar', 'VendaController@atualizar');
        Route::post('/salvarCrediario', 'VendaController@salvarCrediario');
        Route::get('/filtro', 'VendaController@filtro');
        Route::get('/rederizarDanfe/{id}', 'VendaController@rederizarDanfe');
        Route::get('/baixarXml/{id}', 'VendaController@baixarXml');
        Route::get('/imprimirPedido/{id}', 'VendaController@imprimirPedido');
        Route::get('/clone/{id}', 'VendaController@clone');
        Route::get('/gerarXml/{id}', 'VendaController@gerarXml');
        Route::post('/clone', 'VendaController@salvarClone');
        Route::post('/enviar-whats', 'VendaController@enviarWhats');

        Route::get('/calculaFrete', 'VendaController@calculaFrete');
        Route::get('/importacao', 'VendaController@importacao');
        Route::post('/importacao', 'VendaController@importacaoStore');
        Route::post('/importStore', 'VendaController@importStore');
        Route::get('/estadoFiscal/{id}', 'VendaController@estadoFiscal');
        Route::post('/estadoFiscal/', 'VendaController@estadoFiscalStore');
        Route::get('/carne', 'CarneController@index');
        Route::get('/calcComissao', 'VendaController@calcComissao');
        Route::get('/gerarFormasPagamento', 'VendaController@gerarFormasPagamento');
        Route::get('/numero_sequencial', 'VendaController@numeroSequencial');
        Route::get('/edit_xml/{id}', 'VendaController@editXml');
        Route::get('/syncDataEmissaoRetroativa', 'VendaController@syncDataEmissaoRetroativa');
        Route::post('/reforma/preview-item', 'VendaController@previewReformaItem')->name('vendas.reforma.previewItem');
        Route::post('/{venda}/nfe/reforma/recalc-item/{item}', 'VendaController@recalcItem')->name('vendas.nfe.reforma.recalcItem');
        Route::post('/{venda}/nfe/reforma/recalc-all', 'VendaController@recalcAll')->name('vendas.nfe.reforma.recalcAll');

        Route::post('/setEstadoCliente', 'VendaController@setEstadoCliente');
    });

    Route::group(['prefix' => 'compras'],function(){
        Route::get('/', 'PurchaseController@index');
        Route::get('/filtro', 'PurchaseController@filtro');
        Route::get('/view/{id}', 'PurchaseController@view');
        Route::get('/delete/{id}', 'PurchaseController@delete');
        Route::get('/detalhes/{id}', 'PurchaseController@detalhes');
        Route::get('/pesquisa', 'PurchaseController@pesquisa');
        Route::get('/downloadXml/{id}', 'PurchaseController@downloadXml');
        Route::get('/downloadXmlCancela/{id}', 'PurchaseController@downloadXmlCancela');
        Route::post('/save', 'PurchaseController@save');

        Route::get('/emitirEntrada/{id}', 'PurchaseController@emitirEntrada');
        Route::get('/danfeTemporaria', 'PurchaseController@danfeTemporaria');
        Route::get('/xmlTemporaria', 'PurchaseController@xmlTemporaria');
        Route::post('/gerarEntrada', 'PurchaseController@gerarEntrada');
        Route::post('/gerarEntradaWithXml', 'PurchaseController@gerarEntradaWithXml');
        Route::post('/cancelarEntrada', 'PurchaseController@cancelarEntrada');
        Route::post('/cartaCorrecao', 'PurchaseController@cartaCorrecao');
        Route::post('/consultar', 'PurchaseController@consultar');

        Route::get('/imprimir/{id}', 'PurchaseController@imprimir');
        Route::get('/imprimirCce/{id}', 'PurchaseController@imprimirCce');

        Route::get('/produtosSemValidade', 'PurchaseController@produtosSemValidade');
        Route::post('/salvarValidade', 'PurchaseController@salvarValidade');
        Route::post('/salvarChaveRef', 'PurchaseController@salvarChaveRef');
        Route::get('/validadeAlerta', 'PurchaseController@validadeAlerta');
        Route::get('/deleteChave/{id}', 'PurchaseController@deleteChave');
        Route::get('/estadoFiscal/{id}', 'PurchaseController@estadoFiscal');
        Route::post('/estadoFiscal', 'PurchaseController@estadoFiscalStore');
        Route::get('/setNaturezaPagamento', 'PurchaseController@setNaturezaPagamento');

        Route::get('/print/{id}', 'PurchaseController@print');
        Route::get('/print80/{id}', 'PurchaseController@print80');
        //Route::get('/print80/{id}', 'PurchaseController@imprimirPedidoCompra80mm');
        Route::get('/etiqueta/{id}', 'PurchaseController@etiqueta');
        Route::post('/etiquetaStore', 'PurchaseController@etiquetaStore');
        Route::get('/edit_xml', 'PurchaseController@editXml');
        Route::get('/setar-validade/{id}', 'PurchaseController@setarValidade');
        Route::post('/setar-validade', 'PurchaseController@setarValidadeStore');

        Route::get('/sem-validade', 'PurchaseController@comprasSemValidade');
        Route::get('/alerta-validade', 'PurchaseController@alertaValidade');
        Route::get('/alerta-estoque', 'PurchaseController@alertaEstoque');
        Route::get('/imprimir-alerta-estoque', 'PurchaseController@imprimirAlertaEstoque');
        Route::get('/item-compra', 'PurchaseController@itemCompra');
        Route::post('/set-dados-importacao-item', 'PurchaseController@setDadosImportacaoItem')
            ->name('compras.set-dados-importacao-item');
        Route::post('/recuperar-xml', 'PurchaseController@recuperarXmlSefaz');

        Route::get('/importacaoNfse', 'ImportacaoNfseController@index');
        Route::post('/importacaoNfse/importarLote', 'ImportacaoNfseController@importarLote');
        Route::get('/importacaoNfse/visualizar/{id}', 'ImportacaoNfseController@visualizar');
        Route::get('/visualizarNfse/{id}', 'ImportacaoNfseController@visualizar');
        //Route::post('/gerarContasRetencao', 'ContasPagarController@gerarContasRetencao');
        Route::post('/gerarContasRetencao', 'ContasPagarController@gerarContasRetencao');
        Route::post('/baixarParcial', 'ContasPagarController@baixarParcial');

        Route::group(['prefix' => 'apuracao-difal'], function() {
            Route::get('/', 'ApuracaoDifalController@index')->name('compraFiscal.difal.index');
            Route::get('/novo', 'ApuracaoDifalController@create')->name('compraFiscal.difal.create');
            Route::post('/processar', 'ApuracaoDifalController@processar')->name('compraFiscal.difal.processar');
            Route::post('/financeiro/{id}', 'ApuracaoDifalController@gerarFinanceiro')->name('compraFiscal.difal.financeiro');
            Route::get('/cancelar/{id}', 'ApuracaoDifalController@cancelar')->name('compraFiscal.difal.cancelar');

            // Se quiser uma rota para visualizar uma apuração antiga específica
            Route::get('/detalhes/{id}', 'ApuracaoDifalController@show')->name('compraFiscal.difal.show');
            Route::get('/imprimir/{id}', 'ApuracaoDifalController@imprimir')->name('compraFiscal.difal.imprimir');
            Route::post('/corrigir-cfops', 'ApuracaoDifalController@corrigirCfops')->name('compraFiscal.difal.corrigirCfops');
        });

        Route::group(['prefix' => 'reprocessar-cfop'], function(){
            Route::get('/', 'ReprocessarCfopCompraController@index');
            Route::post('/executar', 'ReprocessarCfopCompraController@executar');
        });
    });

    Route::group(['prefix' => 'inventario'],function(){
        Route::get('/', 'InventarioController@index');
        Route::get('/new', 'InventarioController@new');
        Route::post('/save', 'InventarioController@save');
        Route::get('/edit/{id}', 'InventarioController@edit');
        Route::get('/delete/{id}', 'InventarioController@delete');
        Route::get('/alterarStatus/{id}', 'InventarioController@alterarStatus');
        Route::post('/update', 'InventarioController@update');
        Route::get('/filtro', 'InventarioController@filtro');
        Route::get('/apontar/{id}', 'InventarioController@apontar');
        Route::get('/itens/{id}', 'InventarioController@itens');
        Route::post('/apontar', 'InventarioController@apontarSave');
        Route::get('/itensDelete/{id}', 'InventarioController@itensDelete');
        Route::get('/imprimir/{id}', 'InventarioController@imprimir');
        Route::get('/imprimirFiltro', 'InventarioController@imprimirFiltro');
        Route::get('/pesquisaItem', 'InventarioController@pesquisaItem');

        Route::get('/comparaEstoque/{id}', 'InventarioController@comparaEstoque');
        Route::get('/pendentes/{id}', 'InventarioController@pendentes');
        Route::get('/imprimirPendentes/{id}', 'InventarioController@imprimirPendentes');
        Route::get('/imprimirCompara/{id}', 'InventarioController@imprimirCompara');

        Route::get('/produtoJaAdicionadoInventario', 'InventarioController@produtoJaAdicionadoInventario');

    });

    Route::group(['prefix' => 'transferencia'],function(){
        Route::get('/', 'TransferenciaController@index');
        Route::post('/store', 'TransferenciaController@store');
        Route::get('/list', 'TransferenciaController@list');
        Route::get('/search', 'TransferenciaController@search');
        Route::get('/print/{id}', 'TransferenciaController@print');
        Route::get('/view/{id}', 'TransferenciaController@view');
        Route::put('/update-fiscal/{id}', 'TransferenciaController@updateFiscal')->name('transferencia.update-fiscal');
        Route::get('/xml-temp/{id}', 'TransferenciaController@xmlTemp')->name('transferencia.xml-temp');
        Route::get('/danfe-temp/{id}', 'TransferenciaController@danfeTemp')->name('transferencia.danfe-temp');
        Route::post('/transmitir-nfe', 'TransferenciaController@transmitirNfe')->name('transferencia.transmitir-nfe');
        Route::post('/corrigir-nfe', 'TransferenciaController@corrigirNfe')->name('transferencia.corrigir-nfe');
        Route::post('/cancelar-nfe', 'TransferenciaController@cancelarNfe')->name('transferencia.cancelar-nfe');
        Route::get('/imprimir-nfe/{id}', 'TransferenciaController@imprimirNfe')->name('transferencia.imprimir-nfe');
        Route::get('/imprimir-cancela/{id}', 'TransferenciaController@imprimirCancela')->name('transferencia.imprimir-cancela');
        Route::get('/imprimir-correcao/{id}', 'TransferenciaController@imprimirCorrecao')->name('transferencia.imprimir-correcao');
        Route::get('/atualizar-valores/{id}', 'TransferenciaController@atualizarValores')->name('transferencia.atualizar-valores');
        Route::post('/consultar-situacao-nfe', 'TransferenciaController@consultarSituacaoNfe');

    });

    Route::group(['prefix' => 'estoque'],function(){
        Route::get('/', 'StockController@index');
        Route::get('/kardex', 'StockKardexController@index');
        Route::get('/kardex/{produto}', 'StockKardexController@show');

        Route::get('/ajustes/novo', 'StockAdjustmentController@create');

        Route::get('/pesquisa', 'StockController@pesquisa');
        Route::get('/su', 'StockController@su');
        Route::get('/view/{id}', 'StockController@view');
        Route::get('/deleteApontamento/{id}', 'StockController@deleteApontamento');
        Route::get('/apontamentoProducao', 'StockController@apontamento');
        Route::get('/todosApontamentos', 'StockController@todosApontamentos');
        Route::get('/apontamentoManual', 'StockController@apontamentoManual');
        Route::get('/filtroApontamentos', 'StockController@filtroApontamentos');
        Route::post('/saveApontamento', 'StockController@saveApontamento');
        Route::post('/saveApontamentoManual', 'StockController@saveApontamentoManual');
        Route::get('/listApontamentos', 'StockController@listApontamentos');
        Route::get('/listApontamentos/delete/{id}', 'StockController@listApontamentosDelte');
        Route::post('/set-estoque-local', 'StockController@setEstoqueStore');
        Route::get('/historico/{id}', 'StockController@historico');
        Route::get('/relatorioPdf', 'StockController@relatorioPdf');
        Route::get('/relatorioFiscal', 'StockController@relatorioFiscal');

        Route::get('/add1', 'StockController@add1');
        Route::get('/zerarEstoque', 'StockController@zerarEstoque');
        Route::get('/alterarGerenciamento', 'StockController@alterarGerenciamento');

        // Implementation Agnaldo Borges
        Route::get('/ajusteManual', 'StockAdjustmentController@create');
        Route::get('/ajustes', 'StockAdjustmentController@index')->name('estoque.ajustes.index');
        Route::get('/ajustes/create', 'StockAdjustmentController@create')->name('estoque.ajustes.create');
        Route::post('/ajustes', 'StockAdjustmentController@store')->name('estoque.ajustes.store');
        Route::get('/ajustes/{id}', 'StockAdjustmentController@show')->name('estoque.ajustes.show');

        Route::get('/saldo-real', 'RelatorioEstoqueController@relatorioSaldoReal');
        Route::get('/extrato/{produto_id}', 'RelatorioEstoqueController@extratoMovimentacao');
        Route::post('/sincronizar-pesagens', 'PesagemController@sincronizarEstoquePassado')->name('estoque.sincronizar-pesagens');
    });

    Route::group(['prefix' => 'stock'], function () {
        Route::get('/disponibilidade/{produtoId}/{quantidade}', 'StockController@consultarDisponibilidadeEstoque')->name('stock.disponibilidade');
    });

    Route::group(['prefix' => 'gestao-estoque'], function () {
        Route::get('/', 'GestaoEstoqueController@index')->name('gestao-estoque.index');
        Route::post('/sincronizar', 'GestaoEstoqueController@sincronizar')->name('gestao-estoque.sincronizar');
    });

    Route::group(['prefix' => 'cotacao'],function(){
        Route::get('/', 'CotacaoController@index');
        Route::get('/new', 'CotacaoController@new');
        Route::post('/salvar', 'CotacaoController@salvar');

        Route::get('/deleteItem/{id}', 'CotacaoController@deleteItem');
        Route::get('/delete/{id}', 'CotacaoController@delete');
        Route::get('/edit/{id}', 'CotacaoController@edit');
        Route::get('/alterarStatus/{id}/{status}', 'CotacaoController@alterarStatus');
        Route::post('/saveItem', 'CotacaoController@saveItem');

        Route::get('/view/{id}', 'CotacaoController@view');
        Route::get('/clonar/{id}', 'CotacaoController@clonar');
        Route::post('/clonarSave', 'CotacaoController@clonarSave');
        Route::get('/response/{code}', 'CotacaoController@response');
        Route::get('/filtro', 'CotacaoController@filtro');
        Route::get('/searchProvider', 'CotacaoController@searchProvider');
        Route::get('/searchPiece', 'CotacaoController@searchPiece');
        Route::get('/sendMail/{id}', 'CotacaoController@sendMail');
        Route::get('/listaPorReferencia', 'CotacaoController@listaPorReferencia');
        Route::get('/listaPorReferencia/filtro', 'CotacaoController@listaPorReferenciaFiltro');
        Route::get('/referenciaView/{referencia}', 'CotacaoController@referenciaView');
        Route::get('/escolher/{id}', 'CotacaoController@escolher');
        Route::get('/imprimirMelhorResultado', 'CotacaoController@imprimirMelhorResultado');
        Route::get('/gerar-venda/{id}', 'CotacaoController@gerarVenda');
        Route::put('/salvar-venda/{id}', 'CotacaoController@salvarVenda');

    });

    Route::group(['prefix' => 'frenteCaixa'],function(){
        Route::get('/', 'FrontBoxController@index');
        Route::get('/print', 'FrontBoxController@print');
        Route::get('/edit/{id}', 'FrontBoxController@edit');
        Route::get('/list', 'FrontBoxController@list');
        Route::get('/devolucao', 'FrontBoxController@devolucao');
        Route::get('/filtro', 'FrontBoxController@filtro');

        Route::get('/filtroCliente', 'FrontBoxController@filtroCliente');
        Route::get('/filtroNFCe', 'FrontBoxController@filtroNFCe');
        Route::get('/filtroValor', 'FrontBoxController@filtroValor');
        Route::get('/filtroData', 'FrontBoxController@filtroData');
        Route::get('/fechar', 'FrontBoxController@fechar');
        Route::post('/fechar', 'FrontBoxController@fecharPost');
        Route::get('/fechamentos', 'FrontBoxController@fechamentos');
        Route::get('/listaFechamento/{id}', 'FrontBoxController@listaFechamento');

        Route::get('/deleteVenda/{id}', 'FrontBoxController@deleteVenda');
        Route::get('/retornaEstoque/{id}', 'FrontBoxController@retornaEstoque');
        Route::get('/deleteRascunho/{id}', 'FrontBoxController@deleteRascunho');
        Route::get('/deleteRascunhoPreVenda/{id}', 'FrontBoxController@deleteRascunhoPreVenda');
        Route::get('/config', 'FrontBoxController@config');
        Route::post('/configSave', 'FrontBoxController@configSave');

        Route::get('/pix', 'FrontBoxController@pix');
        Route::get('/troca', 'FrontBoxController@troca');
        Route::get('/editTroca', 'FrontBoxController@editTroca');

        Route::get('/prevenda', 'FrontBoxController@preVenda');
        Route::get('/prevenda/edit/{id}', 'FrontBoxController@preVenda');
        Route::get('/prevendaEdit/{id}', 'FrontBoxController@preVendaEdit');
        Route::get('/contigencia', 'FrontBoxController@contigencia');
    });


    Route::get('/ola', function() {
        return view('default/ola')->with('title', 'Bem vindo ao teste do SlymPDV');
    });

    Route::group(['prefix' => 'clienteDelivery'],function(){
        Route::get('/all', 'AppUserController@all');

    });

    Route::group(['prefix' => 'push'],function(){
        Route::get('/', 'PushController@index');
        Route::get('/new', 'PushController@new');
        Route::post('/save', 'PushController@save');
        Route::post('/update', 'PushController@update');

        Route::get('/send/{id}', 'PushController@send');
        Route::get('/edit/{id}', 'PushController@edit');
        Route::get('/delete/{id}', 'PushController@delete');

    });

    Route::group(['prefix' => 'codigoDesconto'],function(){
        Route::get('/', 'CodigoDescontoController@index');
        Route::get('/new', 'CodigoDescontoController@new');
        Route::post('/save', 'CodigoDescontoController@save');
        Route::post('/update', 'CodigoDescontoController@update');
        Route::get('/edit/{id}', 'CodigoDescontoController@edit');

        Route::get('/delete/{id}', 'CodigoDescontoController@delete');
        Route::get('/push/{id}', 'CodigoDescontoController@push');
        Route::post('/push', 'CodigoDescontoController@savePush');
        Route::get('/sms/{id}', 'CodigoDescontoController@sms');
        Route::post('/sms', 'CodigoDescontoController@saveSms');
        Route::get('/alterarStatus/{id}', 'CodigoDescontoController@alterarStatus');
    });

    Route::group(['prefix' => 'cuponsEcommerce'],function(){
        Route::get('/', 'CupomEcommerceController@index');
        Route::get('/create', 'CupomEcommerceController@create');
        Route::post('/store', 'CupomEcommerceController@store');
        Route::put('/update/{id}', 'CupomEcommerceController@update');
        Route::get('/edit/{id}', 'CupomEcommerceController@edit');
        Route::get('/delete/{id}', 'CupomEcommerceController@delete');

    });

    Route::group(['prefix' => 'tamanhosPizza'],function(){
        Route::get('/', 'TamanhoPizzaController@index');
        Route::get('/new', 'TamanhoPizzaController@new');
        Route::post('/save', 'TamanhoPizzaController@save');
        Route::post('/update', 'TamanhoPizzaController@update');
        Route::get('/edit/{id}', 'TamanhoPizzaController@edit');

        Route::get('/delete/{id}', 'TamanhoPizzaController@delete');

    });

    Route::group(['prefix' => 'categoriaDespesa'],function(){
        Route::get('/', 'CategoriaDespesaController@index');
        Route::get('/new', 'CategoriaDespesaController@new');
        Route::post('/save', 'CategoriaDespesaController@save');
        Route::post('/update', 'CategoriaDespesaController@update');
        Route::get('/edit/{id}', 'CategoriaDespesaController@edit');

        Route::get('/delete/{id}', 'CategoriaDespesaController@delete');

    });

    Route::group(['prefix' => 'veiculos'],function(){
        Route::get('/', 'VeiculoController@index');
        Route::get('/new', 'VeiculoController@new');
        Route::post('/save', 'VeiculoController@save');
        Route::post('/update', 'VeiculoController@update');
        Route::get('/edit/{id}', 'VeiculoController@edit');
        Route::get('/delete/{id}', 'VeiculoController@delete');
    });

    Route::group(['prefix' => 'manutencoes'], function () {
        Route::get('/', 'ManutencaoController@list');
        Route::get('/list', 'ManutencaoController@list');
        Route::get('/new', 'ManutencaoController@register');
        Route::post('/save', 'ManutencaoController@save');
        Route::get('/edit/{id}', 'ManutencaoController@edit');
        Route::post('/update/{id}', 'ManutencaoController@update');
        Route::get('/delete/{id}', 'ManutencaoController@delete');
        Route::get('/filtro', 'ManutencaoController@filtro');
    });

    Route::group(['prefix' => 'devolucao'],function(){
        Route::get('/', 'DevolucaoController@index');
        Route::get('/nova', 'DevolucaoController@new');
        Route::post('/new', 'DevolucaoController@renderizarXml');
        Route::post('/salvar', 'DevolucaoController@salvar');
        Route::post('/enviarSefaz', 'DevolucaoController@enviarSefaz');
        Route::post('/cancelar', 'DevolucaoController@cancelar');
        Route::get('/ver/{id}', 'DevolucaoController@ver');
        Route::get('/delete/{id}', 'DevolucaoController@delete');
        Route::get('/imprimir/{id}', 'DevolucaoController@imprimir');
        Route::get('/downloadXmlEntrada/{id}', 'DevolucaoController@downloadXmlEntrada');
        Route::get('/downloadXmlDevolucao/{id}', 'DevolucaoController@downloadXmlDevolucao');
        Route::get('/filtro', 'DevolucaoController@filtro');
        Route::get('/xmltemp/{id}', 'DevolucaoController@xmltemp');
        Route::get('/danfeTemp/{id}', 'DevolucaoController@danfeTemp');

        Route::post('/consultar', 'DevolucaoController@consultar');

        Route::post('/cartaCorrecao', 'DevolucaoController@cartaCorrecao');
        Route::get('/imprimirCce/{id}', 'DevolucaoController@imprimirCce');
        Route::get('/imprimirCancela/{id}', 'DevolucaoController@imprimirCancela');
        Route::get('/edit/{id}', 'DevolucaoController@edit');
        Route::get('/editManual/{id}', 'DevolucaoController@editManual');
        Route::post('/update', 'DevolucaoController@update');
        Route::put('/{id}/updateManual', 'DevolucaoController@updateManual');
        Route::get('/estadoFiscal/{id}', 'DevolucaoController@estadoFiscal');
        Route::post('/estadoFiscal', 'DevolucaoController@estadoFiscalStore');

        Route::get('/enviarXml', 'DevolucaoController@enviarXml');

    });

    Route::get('/graficos', 'DashboardAnaliticoController@index')->name('dashboard.analitico');
    Route::get('/graficos2', 'HomeController@index');
    Route::get('/getPlan', 'HomeController@getPlan');

    Route::group(['prefix' => 'graficos2'],function(){
        Route::get('/faturamentoDosUltimosSeteDias', 'HomeController@faturamentoDosUltimosSeteDias');
        Route::get('/faturamentoFiltrado', 'HomeController@faturamentoFiltrado');
        Route::get('/produtosFiltrado', 'HomeController@produtosFiltrado');

        Route::get('/boxConsulta', 'HomeController@boxConsulta');
        Route::get('/countProdutos', 'HomeController@countProdutos');
        Route::get('/contasPagar', 'HomeController@contasPagar');
        Route::get('/contasReceber', 'HomeController@contasReceber');
        Route::get('/vendasPdv', 'HomeController@vendasPdv');
        Route::get('/vendasPedido', 'HomeController@vendasPedido');
        Route::get('/orcamentos', 'HomeController@orcamentos');
        Route::get('/emissaoNfe', 'HomeController@emissaoNfe');
        Route::get('/emissaoNfce', 'HomeController@emissaoNfce');
        Route::get('/produtos', 'HomeController@produtos');

    });

    Route::group(['prefix' => 'bairrosDelivery'],function(){
        Route::get('/', 'BairroDeliveryController@index');
        Route::get('/filtro', 'BairroDeliveryController@filtro');
        Route::get('/delete/{id}', 'BairroDeliveryController@delete');
        Route::get('/edit/{id}', 'BairroDeliveryController@edit');
        Route::get('/new', 'BairroDeliveryController@new');

        Route::post('/request', 'BairroDeliveryController@request');
        Route::post('/save', 'BairroDeliveryController@save');
        Route::post('/update', 'BairroDeliveryController@update');
    });

    Route::group(['prefix' => 'bairrosDeliveryLoja'],function(){
        Route::get('/', 'BairroDeliveryLojaController@index');
        Route::get('/herdar', 'BairroDeliveryLojaController@herdar');
        Route::get('/new', 'BairroDeliveryLojaController@new');
        Route::get('/edit/{id}', 'BairroDeliveryLojaController@edit');
        Route::get('/delete/{id}', 'BairroDeliveryLojaController@delete');

        Route::post('/save', 'BairroDeliveryLojaController@save');
        Route::post('/update', 'BairroDeliveryLojaController@update');
    });

    Route::group(['prefix' => 'cidadeDelivery'],function(){
        Route::get('/', 'CidadeDeliveryController@index');
        Route::get('/delete/{id}', 'CidadeDeliveryController@delete');
        Route::get('/edit/{id}', 'CidadeDeliveryController@edit');
        Route::get('/new', 'CidadeDeliveryController@new');

        Route::post('/save', 'CidadeDeliveryController@save');
        Route::post('/update', 'CidadeDeliveryController@update');
    });

    Route::group(['prefix' => 'destaquesDelivery'],function(){
        Route::get('/', 'DestaqueDeliveryController@index');
        Route::get('/pesquisa', 'DestaqueDeliveryController@pesquisa');
        Route::get('/delete/{id}', 'DestaqueDeliveryController@delete');
        Route::get('/edit/{id}', 'DestaqueDeliveryController@edit');
        Route::get('/alterarStatus/{id}', 'DestaqueDeliveryController@alterarStatus');
        Route::get('/new', 'DestaqueDeliveryController@new');

        Route::post('/save', 'DestaqueDeliveryController@save');
        Route::post('/update', 'DestaqueDeliveryController@update');
    });

    Route::group(['prefix' => 'produtosDelivery'],function(){
        Route::get('/byCategoria/{id}', 'DeliveryConfigProdutoController@byCategoria');
        Route::get('/search', 'DeliveryConfigProdutoController@search');
        Route::get('/searchPizzas', 'DeliveryConfigProdutoController@searchPizzas');
        Route::get('/find/{id}', 'DeliveryConfigProdutoController@find');
        Route::get('/adicionais', 'DeliveryConfigProdutoController@adicionais');

        Route::post('/store', 'DeliveryConfigProdutoController@store');
        Route::get('/autocomplete', 'DeliveryConfigProdutoController@autocomplete');

    });

    Route::group(['prefix' => 'produtosDestaque'],function(){
        Route::get('/', 'DestaqueDeliveryMasterController@index');
        Route::get('/novoProduto', 'DestaqueDeliveryMasterController@novoProduto');
        Route::post('/save', 'DestaqueDeliveryMasterController@saveProduto');
    });

    Route::group(['prefix' => 'categoriasParaDestaque'],function(){
        Route::get('/', 'DestaqueDeliveryMasterController@listaCategoria');

        Route::get('/delete/{id}', 'DestaqueDeliveryMasterController@deleteCategoria');
        Route::get('/edit/{id}', 'DestaqueDeliveryMasterController@editCategoria');
        Route::get('/new', 'DestaqueDeliveryMasterController@newCategoria');

        Route::post('/save', 'DestaqueDeliveryMasterController@saveCategoria');
        Route::post('/update', 'DestaqueDeliveryMasterController@updateCategoria');
    });

    Route::group(['prefix' => 'categoriaMasterDelivery'],function(){
        Route::get('/', 'CategoriaMasterDeliveryController@index');
        Route::get('/delete/{id}', 'CategoriaMasterDeliveryController@delete');
        Route::get('/edit/{id}', 'CategoriaMasterDeliveryController@edit');
        Route::get('/new', 'CategoriaMasterDeliveryController@new');

        Route::post('/request', 'CategoriaMasterDeliveryController@request');
        Route::post('/save', 'CategoriaMasterDeliveryController@save');
        Route::post('/update', 'CategoriaMasterDeliveryController@update');
    });

    Route::group(['prefix' => 'mesas'],function(){
        Route::get('/', 'MesaController@index');
        Route::get('/delete/{id}', 'MesaController@delete');
        Route::get('/edit/{id}', 'MesaController@edit');
        Route::get('/new', 'MesaController@new');
        Route::get('/gerarToken/{id}', 'MesaController@gerarToken');

        Route::post('/save', 'MesaController@save');
        Route::post('/update', 'MesaController@update');
        Route::get('/gerarQrCode', 'MesaController@gerarQrCode');
        Route::get('/issue/{id}', 'MesaController@issue');
        Route::get('/issue2/{id}', 'MesaController@issue2');
        Route::get('/imprimirQrCode', 'MesaController@imprimirQrCode');

    });

    Route::group(['prefix' => 'lojas'],function(){
        Route::get('/', 'LojaController@index');
        Route::get('/filtro', 'LojaController@filtro');
        Route::get('/alterarStatus/{id}', 'LojaController@alterarStatus');
    });

    Route::group(['prefix' => 'bannerTopo'],function(){
        Route::get('/', 'BannerTopoController@index');
        Route::get('/delete/{id}', 'BannerTopoController@delete');
        Route::get('/edit/{id}', 'BannerTopoController@edit');
        Route::get('/new', 'BannerTopoController@new');

        Route::post('/save', 'BannerTopoController@save');
        Route::post('/update', 'BannerTopoController@update');
    });

    Route::group(['prefix' => 'bannerMaisVendido'],function(){
        Route::get('/', 'BannerMaisVendidoController@index');
        Route::get('/delete/{id}', 'BannerMaisVendidoController@delete');
        Route::get('/edit/{id}', 'BannerMaisVendidoController@edit');
        Route::get('/new', 'BannerMaisVendidoController@new');

        Route::post('/save', 'BannerMaisVendidoController@save');
        Route::post('/update', 'BannerMaisVendidoController@update');
    });

    Route::group(['prefix' => 'delivery'], function(){

        Route::get('/', 'MercadoController@index');
        Route::get('/categorias', 'MercadoController@categorias');
        Route::get('/produto/{id}', 'MercadoController@produto');
        Route::get('/login', 'MercadoController@login');
        Route::get('/logoff', 'MercadoController@logoff');
        Route::post('/login', 'MercadoController@loginUser');
        Route::get('/cadastrar', 'MercadoController@cadastrar');
        Route::get('/produtos/{categoria_id}', 'MercadoController@produtos');
        Route::post('/salvarRegistro', 'MercadoController@salvarRegistro');
        Route::post('/validaToken', 'MercadoController@validaToken');
        Route::get('/carrinho', 'MercadoController@carrinho');
        Route::post('/finalizar', 'MercadoController@finalizar');
        Route::post('/finalizarPedido', 'MercadoController@finalizarPedido');
        Route::get('/finalizado/{id}', 'MercadoController@finalizado');
        Route::get('/pedidoPendente', 'MercadoController@pedidoPendente');
        Route::get('/meusPedidos', 'MercadoController@meusPedidos');
        Route::get('/detalhePedido/{id}', 'MercadoController@detalhePedido');
        Route::get('/pedir_novamente/{id}', 'MercadoController@pedir_novamente');
        Route::get('/pesquisaProduto', 'MercadoController@pesquisaProduto');

        Route::get('/esqueci-senha', 'MercadoController@recuperarSenha');
        Route::post('/esqueci-senha', 'MercadoController@enviarSenha');

    });

    Route::group(['prefix' => 'deliveryProduto'], function(){
        Route::post('/addProduto', 'MercadoProdutoController@addProduto');
        Route::get('/addProduto/{id}', 'MercadoProdutoController@adicionarProduto');
        Route::post('/downProduto', 'MercadoProdutoController@downProduto');
        Route::get('/novo_cliente', 'MercadoProdutoController@novoCliente');
        Route::get('/carrinho', 'MercadoProdutoController@carrinho');
        Route::post('/alterCart', 'MercadoProdutoController@alterCart');
    });

    Route::group(['prefix' => 'orcamentoVenda'], function(){
        Route::get('/', 'OrcamentoController@index');
        Route::post('/valida-estoque', 'OrcamentoController@validaEstoque');
        Route::get('/create', 'OrcamentoController@create');
        Route::post('/salvar', 'OrcamentoController@salvar');
        Route::post('/update', 'OrcamentoController@update');
        Route::get('/detalhar/{id}', 'OrcamentoController@detalhar');
        Route::get('/edit/{id}', 'OrcamentoController@edit');
        Route::get('/delete/{id}', 'OrcamentoController@delete');
        Route::get('/imprimir/{id}', 'OrcamentoController@imprimir');
        Route::get('/imprimirCompleto/{id}', 'OrcamentoController@imprimirCompleto');
        Route::get('/rederizarDanfe/{id}', 'OrcamentoController@rederizarDanfe');
        Route::get('/enviarEmail', 'OrcamentoController@enviarEmail');
        Route::get('/deleteItem/{id}', 'OrcamentoController@deleteItem');
        Route::post('/addItem', 'OrcamentoController@addItem');
        // Route::post('/gerarVenda', 'OrcamentoController@gerarVenda');
        Route::get('/gerarVenda/{id}', 'OrcamentoController@gerarVenda');
        Route::post('/setValidade', 'OrcamentoController@setValidade');
        Route::post('/addPag', 'OrcamentoController@addPag');
        Route::get('/deleteParcela/{id}', 'OrcamentoController@deleteParcela');
        Route::get('/filtro', 'OrcamentoController@filtro');
        Route::get('/reprovar/{id}', 'OrcamentoController@reprovar');

        Route::get('/relatorioItens/{data1}/{data2}', 'OrcamentoController@relatorioItens');
        Route::post('/gerarPagamentos', 'OrcamentoController@gerarPagamentos');

        Route::get('/consultar_cliente/{id}', 'OrcamentoController@consultar_cliente');
        Route::post('/alterarCliente', 'OrcamentoController@alterarCliente');


    });

    Route::group(['prefix' => 'percentualuf'], function(){
        Route::get('/', 'PercentualController@index');
        Route::get('/novo/{uf}', 'PercentualController@novo');
        Route::get('/edit/{uf}', 'PercentualController@edit');
        Route::post('/save', 'PercentualController@save');
        Route::post('/update', 'PercentualController@update');
        Route::get('/verProdutos/{uf}', 'PercentualController@verProdutos');
        Route::get('/editPercentual/{id}', 'PercentualController@editPercentual');
        Route::post('/updatePercentualSingle', 'PercentualController@updatePercentualSingle');

    });

    Route::group(['prefix' => 'listaDePrecos'], function(){
        Route::get('/', 'ListaPrecoController@index');
        Route::get('/delete/{id}', 'ListaPrecoController@delete');
        Route::get('/edit/{id}', 'ListaPrecoController@edit');
        Route::get('/new', 'ListaPrecoController@new');

        Route::post('/save', 'ListaPrecoController@save');
        Route::post('/update', 'ListaPrecoController@update');

        Route::get('/ver/{id}', 'ListaPrecoController@ver');
        Route::get('/gerar/{id}', 'ListaPrecoController@gerar');
        Route::get('/editValor/{id}', 'ListaPrecoController@editValor');

        Route::post('/salvarPreco', 'ListaPrecoController@salvarPreco');

        Route::get('/pesquisa', 'ListaPrecoController@pesquisa');
        Route::get('/filtro', 'ListaPrecoController@filtro');

    });

    Route::group(['prefix' => 'pedido', 'middleware' => ['pedidoAtivo']], function(){
        Route::get('/', 'PedidoQrCodeController@index');
        Route::get('/open/{id}', 'PedidoQrCodeController@open');
        Route::get('/erro', 'PedidoQrCodeController@erro');
        Route::get('/cardapio/{id}', 'PedidoQrCodeController@cardapio');

        Route::get('/escolherSabores', 'PedidoQrCodeController@escolherSabores');
        Route::post('/adicionarSabor', 'PedidoQrCodeController@adicionarSabor');
        Route::get('/verificaPizzaAdicionada', 'PedidoQrCodeController@verificaPizzaAdicionada');
        Route::get('/removeSabor/{id}', 'PedidoQrCodeController@removeSabor');
        Route::get('/adicionais/{id}', 'PedidoQrCodeController@adicionais');
        Route::get('/adicionaisPizza', 'PedidoQrCodeController@adicionaisPizza');
        Route::get('/pesquisa', 'PedidoQrCodeController@pesquisa');
        Route::get('/pizzas', 'DeliveryController@pizzas');
        Route::get('/ver', 'PedidoQrCodeController@ver');

        Route::post('/addPizza', 'PedidoQrCodeController@addPizza')->middleware('mesaAtiva');
        Route::post('/addProd', 'PedidoQrCodeController@addProd')->middleware('mesaAtiva');

        Route::get('/refreshItem/{id}/{quantidade}', 'PedidoQrCodeController@refreshItem');
        Route::get('/removeItem/{id}', 'PedidoQrCodeController@removeItem');
        Route::get('/finalizar', 'PedidoQrCodeController@finalizar');
    });

    Route::group(['prefix' => 'configEcommerce'], function(){
        Route::get('/', 'ConfigEcommerceController@index');
        Route::post('/save', 'ConfigEcommerceController@save');
        Route::get('/verSite', 'ConfigEcommerceController@verSite');
    });

    Route::group(['prefix' => 'categoriaEcommerce'],function(){
        Route::get('/', 'CategoriaProdutoEcommerceController@index');
        Route::get('/delete/{id}', 'CategoriaProdutoEcommerceController@delete');
        Route::get('/edit/{id}', 'CategoriaProdutoEcommerceController@edit');
        Route::get('/new', 'CategoriaProdutoEcommerceController@new');

        Route::post('/save', 'CategoriaProdutoEcommerceController@save');
        Route::post('/update', 'CategoriaProdutoEcommerceController@update');

        Route::get('/subs/{id}', 'CategoriaProdutoEcommerceController@subs');
        Route::get('/newSub/{id}', 'CategoriaProdutoEcommerceController@newSub');
        Route::get('/editSubs/{id}', 'CategoriaProdutoEcommerceController@editSubs');
        Route::get('/deleteSub/{id}', 'CategoriaProdutoEcommerceController@deleteSub');
        Route::post('/saveSub', 'CategoriaProdutoEcommerceController@saveSub');
        Route::post('/updateSub', 'CategoriaProdutoEcommerceController@updateSub');

    });

    Route::group(['prefix' => 'clienteEcommerce'],function(){
        Route::get('/', 'ClienteEcommerceController@index');
        Route::get('/filtro', 'ClienteEcommerceController@filtro');
        Route::get('/delete/{id}', 'ClienteEcommerceController@delete');
        Route::get('/edit/{id}', 'ClienteEcommerceController@edit');
        Route::get('/new', 'ClienteEcommerceController@new');

        Route::post('/save', 'ClienteEcommerceController@save');
        Route::post('/update', 'ClienteEcommerceController@update');
    });

    Route::group(['prefix' => 'enderecosEcommerce'],function(){
        Route::get('/{cliente_id}', 'EnderecoEcommerceController@index');
        Route::get('/edit/{id}', 'EnderecoEcommerceController@edit');
        Route::post('/update', 'EnderecoEcommerceController@update');

    });

    Route::group(['prefix' => 'produtoEcommerce'], function(){
        Route::get('/', 'ProdutoEcommerceController@index');
        Route::get('/delete/{id}', 'ProdutoEcommerceController@delete');
        Route::get('/deleteImagem/{id}', 'ProdutoEcommerceController@deleteImagem');
        Route::get('/edit/{id}', 'ProdutoEcommerceController@edit');
        Route::get('/editGrade/{id}', 'ProdutoEcommerceController@editGrade');
        Route::get('/listGrade/{referecia}', 'ProdutoEcommerceController@listGrade');
        Route::get('/galeria/{id}', 'ProdutoEcommerceController@galeria');
        Route::get('/new', 'ProdutoEcommerceController@new');
        Route::get('/pesquisa', 'ProdutoEcommerceController@pesquisa');

        Route::post('/save', 'ProdutoEcommerceController@save');
        Route::post('/update', 'ProdutoEcommerceController@update');
        Route::post('/saveImagem', 'ProdutoEcommerceController@saveImagem');

        Route::get('/alterarStatus/{id}', 'ProdutoEcommerceController@alterarStatus');
        Route::get('/alterarControlarEstoque/{id}',
            'ProdutoEcommerceController@alterarControlarEstoque');
        Route::get('/alterarDestaque/{id}', 'ProdutoEcommerceController@alterarDestaque');

    });

    Route::group(['prefix' => 'pedidosEcommerce'], function(){
        Route::get('/', 'PedidoEcommerceController@index');
        Route::get('/filtro', 'PedidoEcommerceController@filtro');
        Route::get('/detalhar/{id}', 'PedidoEcommerceController@detalhar');
        Route::get('/gerarNFe/{id}', 'PedidoEcommerceController@gerarNFe');
        Route::get('/imprimir/{id}', 'PedidoEcommerceController@imprimir');

        Route::post('/salvarVenda', 'PedidoEcommerceController@salvarVenda');
        Route::get('/delete/{id}', 'PedidoEcommerceController@delete');
        Route::get('/verificaPagamentos', 'PedidoEcommerceController@verificaPagamentos');

        Route::post('/alterarStatus', 'PedidoEcommerceController@alterarStatus');
        Route::post('/alterarStatusPagamento', 'PedidoEcommerceController@alterarStatusPagamento');

    });

    Route::group(['prefix' => 'carrosselEcommerce'],function(){
        Route::get('/', 'CarrosselEcommerceController@index');
        Route::get('/delete/{id}', 'CarrosselEcommerceController@delete');
        Route::get('/edit/{id}', 'CarrosselEcommerceController@edit');
        Route::get('/new', 'CarrosselEcommerceController@new');

        Route::post('/save', 'CarrosselEcommerceController@save');
        Route::post('/update', 'CarrosselEcommerceController@update');
    });

    Route::group(['prefix' => 'autorPost'],function(){
        Route::get('/', 'AutorPostController@index');
        Route::get('/delete/{id}', 'AutorPostController@delete');
        Route::get('/edit/{id}', 'AutorPostController@edit');
        Route::get('/new', 'AutorPostController@new');

        Route::post('/save', 'AutorPostController@save');
        Route::post('/update', 'AutorPostController@update');
    });

    Route::group(['prefix' => 'categoriaPosts'],function(){
        Route::get('/', 'CategoriaPostController@index');
        Route::get('/delete/{id}', 'CategoriaPostController@delete');
        Route::get('/edit/{id}', 'CategoriaPostController@edit');
        Route::get('/new', 'CategoriaPostController@new');

        Route::post('/save', 'CategoriaPostController@save');
        Route::post('/update', 'CategoriaPostController@update');
    });

    Route::group(['prefix' => 'postBlog'],function(){
        Route::get('/', 'PostBlogController@index');
        Route::get('/delete/{id}', 'PostBlogController@delete');
        Route::get('/edit/{id}', 'PostBlogController@edit');
        Route::get('/new', 'PostBlogController@new');

        Route::post('/save', 'PostBlogController@save');
        Route::post('/update', 'PostBlogController@update');
    });

    Route::group(['prefix' => 'contatoEcommerce'],function(){
        Route::get('/', 'ContatoEcommerceController@index');
        Route::get('/pesquisa', 'ContatoEcommerceController@pesquisa');
        Route::get('/delete/{id}', 'ContatoEcommerceController@delete');
    });

    Route::group(['prefix' => 'informativoEcommerce'],function(){
        Route::get('/', 'InformativoController@index');
        Route::get('/pesquisa', 'InformativoController@pesquisa');
        Route::get('/delete/{id}', 'InformativoController@delete');
    });

    Route::group(['prefix' => 'tickets'], function(){
        Route::get('/', 'TicketController@index');
        Route::get('/new', 'TicketController@new');
        Route::get('/view/{id}', 'TicketController@view');
        Route::get('/finalizar/{id}', 'TicketController@finalizar');
        Route::post('/save', 'TicketController@save');
        Route::post('/novaMensagem', 'TicketController@novaMensagem');
        Route::post('/finalizar', 'TicketController@finalizarPost');
    });

    Route::group(['prefix' => 'nuvemshop'], function(){
        Route::get('/', 'NuvemShopAuthController@index');
        Route::get('/auth', 'NuvemShopAuthController@auth');
        Route::get('/app', 'NuvemShopAuthController@app');

        Route::get('/config', 'NuvemShopController@config');
        Route::post('/save', 'NuvemShopController@save');

        Route::get('/categorias', 'NuvemShopController@categorias');
        Route::get('/categoria_new', 'NuvemShopController@categoria_new');
        Route::get('/categoria_edit/{id_shop}', 'NuvemShopController@categoria_edit');
        Route::get('/categoria_delete/{id_shop}', 'NuvemShopController@categoria_delete');
        Route::post('/saveCategoria', 'NuvemShopController@saveCategoria');


        Route::get('/produtos', 'NuvemShopProdutoController@index');
        Route::get('/produto_new', 'NuvemShopProdutoController@produto_new');
        Route::get('/produto_edit/{id_shop}', 'NuvemShopProdutoController@produto_edit');
        Route::get('/produto_delete/{id_shop}', 'NuvemShopProdutoController@produto_delete');
        Route::get('/produto_galeria/{id_shop}', 'NuvemShopProdutoController@produto_galeria');
        Route::get('/delete_imagem/{produto_id}/{img_id}', 'NuvemShopProdutoController@delete_imagem');
        Route::post('/save_imagem', 'NuvemShopProdutoController@save_imagem');
        Route::post('/saveProduto', 'NuvemShopProdutoController@saveProduto');


        Route::get('/pedidos', 'NuvemShopPedidoController@index');
        Route::get('/filtro', 'NuvemShopPedidoController@filtro');
        Route::get('/detalhar/{id}', 'NuvemShopPedidoController@detalhar');
        Route::get('/clientes', 'NuvemShopPedidoController@clientes');
        Route::get('/imprimir/{id}', 'NuvemShopPedidoController@imprimir');
        Route::get('/gerarNFe/{id}', 'NuvemShopPedidoController@gerarNFe');
        Route::post('/salvarVenda', 'NuvemShopPedidoController@salvarVenda');
        Route::get('/delete/{id}', 'NuvemShopPedidoController@delete');

    });

    Route::group(['prefix' => 'tiposMovimentacao'], function () {
        Route::get('/', 'TipoMovimentacaoController@list');
        Route::get('/list', 'TipoMovimentacaoController@list');
        Route::get('/new', 'TipoMovimentacaoController@register');
        Route::post('/save', 'TipoMovimentacaoController@save');
        Route::get('/edit/{id}', 'TipoMovimentacaoController@edit');
        Route::post('/update/{id}', 'TipoMovimentacaoController@update');
        Route::get('/delete/{id}', 'TipoMovimentacaoController@delete');
    });

    Route::group(['prefix' => 'movimentacaoVeiculo'], function () {
        Route::get('/', 'MovimentacaoVeiculoController@list');
        Route::get('/list', 'MovimentacaoVeiculoController@list');
        Route::get('/new', 'MovimentacaoVeiculoController@register');
        Route::post('/save', 'MovimentacaoVeiculoController@save');
        Route::get('/edit/{id}', 'MovimentacaoVeiculoController@edit');
        Route::post('/update/{id}', 'MovimentacaoVeiculoController@update');
        Route::get('/delete/{id}', 'MovimentacaoVeiculoController@delete');
        Route::get('/filtro', 'MovimentacaoVeiculoController@filtro');
        Route::get('/funcionarios', 'FuncionarioController@index');
        Route::get('/dashboard', 'MovimentacaoVeiculoController@dashboard')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/relatorio', 'MovimentacaoVeiculoController@relatorio')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/imprimir/{id}', 'MovimentacaoVeiculoController@imprimir')->middleware(['verificaEmpresa', 'validaAcesso']);

        // Rotas adicionais da agenda, cancelamento, coletas e checklist.
        Route::get('/agenda', 'MovimentacaoVeiculoController@agenda')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/cancelar/{id}', 'MovimentacaoVeiculoController@cancelar')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/eventosCalendario', 'MovimentacaoVeiculoController@eventosCalendario')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/relatorioColetas', 'MovimentacaoVeiculoController@relatorioColetas')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::post('/registrarHorarioCliente/{id}', 'MovimentacaoVeiculoController@registrarHorarioCliente')->middleware(['verificaEmpresa', 'validaAcesso']);
        Route::get('/enviar-checklist/{id}', 'MovimentacaoVeiculoController@enviarChecklistWhatsApp');
        Route::get('/ver-checklist/{id}', 'MovimentacaoVeiculoController@verChecklist');
    });

    Route::group(['prefix' => 'atendimentoWeb'], function () {
        Route::get('/', 'SiteViewController@atendimentoWeb');
    });

    Route::group(['prefix' => 'traccarConfigs'], function () {
        Route::get('/', 'TraccarConfigController@list')->name('traccarConfigs.list');
        Route::get('/new', 'TraccarConfigController@register')->name('traccarConfigs.register');
        Route::post('/save', 'TraccarConfigController@save')->name('traccarConfigs.save');
        Route::get('/edit/{id}', 'TraccarConfigController@edit')->name('traccarConfigs.edit');
        Route::post('/update/{id}', 'TraccarConfigController@update')->name('traccarConfigs.update');
        Route::get('/delete/{id}', 'TraccarConfigController@delete')->name('traccarConfigs.delete');
        Route::get('/graficos', 'HomeController@index')->name('HomeController.index');
    });

    Route::group(['prefix' => 'traccar'], function () {
        Route::get('/list', 'TraccarIntegrationController@listarDispositivos')->name('traccar.devices.list');
        Route::post('/new', 'TraccarIntegrationController@criarDispositivo')->name('traccar.devices.create');
        Route::delete('/devices/{id}', 'TraccarIntegrationController@deletarDispositivo')->name('traccar.devices.delete');
        Route::get('/monitor', 'TraccarIntegrationController@monitorar')->name('traccar.monitor');
        Route::get('/rastreamentoVeiculo', 'SiteViewController@rastreamento');

        Route::get('/posicoes-tempo-real', 'TraccarIntegrationController@posicoesTempoReal');
        Route::get('/mapa', function() {
            return view('movimentacoes_veiculos.mapa')->with('title', 'Mapa em Tempo Real');
        })->name('traccar.mapa');
    });

    Route::group(['prefix' => 'pesagens'], function () {
        Route::get('/', 'PesagemController@list')->name('pesagens.list');
        Route::get('/list', 'PesagemController@list');
        Route::get('/new', 'PesagemController@register')->name('pesagens.register');
        Route::get('/register', 'PesagemController@register');
        Route::post('/save', 'PesagemController@save')->name('pesagens.save');
        Route::post('/cadastros-rapidos/{tipo}', 'PesagemController@cadastroRapido')->where('tipo', 'cliente|fornecedor|veiculo|funcionario')->name('pesagens.cadastros-rapidos.save');
        Route::get('/edit/{id}', 'PesagemController@edit')->name('pesagens.edit');
        Route::put('/update/{id}', 'PesagemController@update')->name('pesagens.update');
        Route::post('/concluir/{id}', 'PesagemController@concluir')->name('pesagens.concluir');
        Route::delete('/delete/{id}', 'PesagemController@delete')->name('pesagens.delete');
        Route::get('/getTotais/{id}', 'PesagemController@getTotais');
        Route::get('/getDados/{id}', 'PesagemController@getDados');
        Route::get('/getPesagemInfo/{id}', 'PesagemController@edit')->name('pesagens.info');
        Route::get('/search/veiculo', 'PesagemController@searchVeiculo')->name('pesagens.search.veiculo');
        Route::get('/search/cliente', 'PesagemController@searchCliente')->name('pesagens.search.cliente');
        Route::get('/search/fornecedor', 'PesagemController@searchFornecedor')->name('pesagens.search.fornecedor');
        Route::get('/search/motorista', 'PesagemController@searchMotorista')->name('pesagens.search.motorista');
        Route::get('/search/produto', 'PesagemController@searchProduto')->name('pesagens.search.produto');
        Route::post('/enviar-relatorio-whats/{id}', 'PesagemController@enviarRelatorioWhatsApp')->name('pesagens.enviarWhatsApp');
        Route::post('/toggle-publicacao/{id}/', 'PesagemController@togglePublicacao')->name('pesagens.togglePublicacao');
        Route::post('/togglePublicacao/{id}', 'PesagemController@togglePublicacao');
        Route::get('/gen/qrcode/{token}', 'PesagemController@gerarQRCode')->name('pesagens.qrcode');
        Route::get('/gen/criarVenda/{id}', 'PesagemController@criarVendaDePesagem')->name('pesagens.criarVenda');
        Route::get('/gen/criarCompra/{id}', 'PesagemController@criarCompraDePesagem')->name('pesagens.criarCompra');
        Route::post('/reabrir/{id}', 'PesagemController@reabrir')->name('pesagens.reabrir');
        Route::get('/relatorios/analitico', 'PesagemController@relatorioAnalitico')->name('pesagens.relatorios.analitico');

    });

    Route::group(['prefix' => 'pesagemNfe'], function () {
        Route::get('/', 'PesagemNfeController@index')->name('pesagem-nfe.index');
        Route::post('/processar', 'PesagemNfeController@processar')->name('pesagem-nfe.processar');
    });

    // Compatibilidade com o endereço legado.
    Route::get('/pesagem_nfe', fn () => redirect()->route('pesagem-nfe.index'));

    Route::group(['prefix' => 'pesagem_nfe'], function(){
        Route::post('/processar', 'PesagemNfeController@processar');
    });

    Route::group(['prefix' => 'tabelaPrecoNfe'], function () {
        Route::get('/', 'TabelaPrecoNfeController@index')->name('tabela-preco-nfe.index');
        Route::post('/save', 'TabelaPrecoNfeController@save')->name('tabela-preco-nfe.save');
        Route::delete('/delete/{id}', 'TabelaPrecoNfeController@delete')->name('tabela-preco-nfe.delete');
    });

    Route::group(['prefix' => 'monitor'], function () {
        Route::get('/pesagens', 'MonitorPesagemController@index')->name('monitor.pesagens');
        Route::get('/pesagens/data', 'MonitorPesagemController@data')->name('monitor.pesagens.data');
        Route::get('/pesagens/snapshot', 'MonitorStockSnapshotController@snapshot')->name('monitor.pesagens.snapshot');
    });

    Route::group(['prefix' => 'ticketsPesagem'], function () {
        Route::get('/', 'TicketPesagemController@list')->name('ticketsPesagem.list');
        Route::get('/list', 'TicketPesagemController@list');
        Route::get('/list/{pesagem_id}', 'TicketPesagemController@list')->name('ticketsPesagem.list.pesagem');
        Route::get('/new', 'TicketPesagemController@register')->name('ticketsPesagem.register');
        Route::post('/save', 'TicketPesagemController@save')->name('ticketsPesagem.save');
        Route::get('/edit/{id}', 'TicketPesagemController@edit')->name('ticketsPesagem.edit');
        Route::put('/update/{id}', 'TicketPesagemController@update')->name('ticketsPesagem.update');
        Route::delete('/delete/{id}', 'TicketPesagemController@delete')->name('ticketsPesagem.delete');
        Route::get('/getTicketsDados/{pesagem_id}', 'TicketPesagemController@getTicketsDados')->name('ticketsPesagem.getTicketsDados');
    });

    Route::group(['prefix' => 'balancas'], function () {
        Route::get('/', 'BalancaConfigController@list')->name('balancas.list');
        Route::post('/save/{id?}', 'BalancaConfigController@save')->name('balancas.save');
        Route::put('/update/{id}', 'BalancaConfigController@save')->name('balancas.update');
        Route::delete('/delete/{id}', 'BalancaConfigController@delete')->name('balancas.delete');
        Route::get('/balanca', 'BalancaConfigController@showBalanca');

        Route::post('/lerPeso', 'BalancaConfigController@lerPesoBalança');
        Route::get('/listarPortas', 'BalancaConfigController@listarPortas');
        Route::post('/testarBalanca', 'BalancaConfigController@testarBalanca');
    });

    Route::prefix('eletronicDocs')->group(function () {
        Route::get('/', 'EletronicDocsController@list')->name('eletronic_docs.list');
        Route::post('/download', 'EletronicDocsController@download')->name('eletronic_docs.download');
    });

    Route::prefix('logs')->group(function () {
        Route::get('/', 'LogController@list')->name('logs.list');
    });

    Route::group(['prefix' => 'balancas/leitor'], function () {
        Route::get('/', 'BalancaLeitorController@index')->name('balancas.leitor.index');
        Route::get('/configuradas', 'BalancaLeitorController@configuradas')->name('balancas.leitor.configuradas');
        Route::get('/{id}/status', 'BalancaLeitorController@status')->name('balancas.leitor.status');
        Route::get('/{id}/details', 'BalancaLeitorController@details')->name('balancas.leitor.details');
        Route::post('/{id}/open', 'BalancaLeitorController@open')->name('balancas.leitor.open');
        Route::post('/{id}/close', 'BalancaLeitorController@close')->name('balancas.leitor.close');
        Route::get('/{id}/read', 'BalancaLeitorController@read')->name('balancas.leitor.read');
        Route::post('/{id}/snapshot', 'BalancaLeitorController@snapshot')->name('balancas.leitor.snapshot');
        Route::post('/{id}/evidence', 'BalancaLeitorController@captureEvidence')->name('balancas.leitor.evidence');
        Route::post('/ticket/apply', 'BalancaLeitorController@applyToTicket')->name('balancas.leitor.ticket.apply');
    });

    Route::get('/adp', 'AdpDeviceDiscoveryController@index')->name('adp.index');

    Route::get('/adp/cameras_parametros', 'AdpCameraController@parametros')->name('adp.cameras.parametros');

    Route::group(['prefix' => 'adp/cameras'], function () {
        Route::get('/', 'AdpCameraController@list')->name('adp.cameras.list');
        Route::get('/parametros', 'AdpCameraController@parametros')->name('adp.cameras.parametros.inner');
        Route::post('/save/{id?}', 'AdpCameraController@save')->name('adp.cameras.save');
        Route::delete('/delete/{id}', 'AdpCameraController@delete')->name('adp.cameras.delete');
        Route::post('/import-from-devices', 'AdpCameraController@importFromDevices')->name('adp.cameras.importFromDevices');
        Route::post('/snapshot-preview', 'AdpCameraController@snapshotPreview')->name('adp.cameras.snapshotPreview');
    });

    Route::group(['prefix' => 'adp/discovery'], function () {
        Route::get('/configs', 'AdpDeviceDiscoveryController@configuracoes')->name('adp.discovery.configs');
        Route::get('/configs/runtime', 'AdpDeviceDiscoveryController@runtimeConfig')->name('adp.discovery.configs.runtime');
        Route::post('/configs/save', 'AdpDeviceDiscoveryController@salvarConfig')->name('adp.discovery.configs.save');
        Route::delete('/configs/delete/{id}', 'AdpDeviceDiscoveryController@excluirConfig')->name('adp.discovery.configs.delete');
        Route::get('/status', 'AdpDeviceDiscoveryController@status')->name('adp.discovery.status');
        Route::get('/devices', 'AdpDeviceDiscoveryController@devices')->name('adp.discovery.devices');
        Route::get('/scales', 'AdpDeviceDiscoveryController@scales')->name('adp.discovery.scales');
        Route::get('/cameras', 'AdpDeviceDiscoveryController@cameras')->name('adp.discovery.cameras');
        Route::post('/sync-devices', 'AdpDeviceDiscoveryController@syncDevices')->name('adp.discovery.syncDevices');
        Route::post('/import-devices', 'AdpDeviceDiscoveryController@importDevices')->name('adp.discovery.importDevices');
    });

    Route::group(['prefix' => 'connect-api'], function () {
        Route::get('/', 'ConnectApiInstanceController@index')->name('connect-api.index');
        Route::get('/companies', 'ConnectApiInstanceController@companies')->name('connect-api.companies');
        Route::post('/provision/{empresa}', 'ConnectApiInstanceController@provision')->name('connect-api.provision');
        Route::post('/instances/{id}/reprovision', 'ConnectApiInstanceController@reprovision')->name('connect-api.reprovision');
        Route::post('/instances/{id}/sync-webhook', 'ConnectApiInstanceController@syncWebhook')->name('connect-api.sync-webhook');
        Route::get('/instances/{id}/status', 'ConnectApiInstanceController@status')->name('connect-api.status');
        Route::get('/instances/{id}/qr', 'ConnectApiInstanceController@qr')->name('connect-api.qr');
        Route::post('/instances/{id}/pairing-code', 'ConnectApiInstanceController@pairingCode')->name('connect-api.pairing-code');
        Route::post('/instances/{id}/restart', 'ConnectApiInstanceController@restart')->name('connect-api.restart');
        Route::post('/instances/{id}/disconnect', 'ConnectApiInstanceController@disconnect')->name('connect-api.disconnect');
        Route::post('/instances/{id}/block', 'ConnectApiInstanceController@block')->name('connect-api.block');
        Route::post('/instances/{id}/unblock', 'ConnectApiInstanceController@unblock')->name('connect-api.unblock');
        Route::delete('/instances/{id}', 'ConnectApiInstanceController@deleteInstance')->name('connect-api.delete');
        Route::post('/instances/{id}/test', 'ConnectApiInstanceController@testMessage')->name('connect-api.test');
        Route::post('/send-whatsapp-button', 'ConnectApiInstanceController@sendWhatsAppButton')->name('connect-api.send-whatsapp-button');
    });

    Route::group(['prefix' => 'produto_prateleiras'], function () {
        Route::get('/',   'ProdutoPrateleiraController@list');
        Route::post('/save',  'ProdutoPrateleiraController@save');
        Route::post('/update/{id}', 'ProdutoPrateleiraController@update');
        Route::get('/delete/{id}', 'ProdutoPrateleiraController@delete');
        Route::get( '/restore/{id}', 'ProdutoPrateleiraController@restore');
        Route::get('/{id}/json', 'ProdutoPrateleiraController@getPrateleiraJson');
        Route::get('/etiquetas', 'ProdutoPrateleiraController@etiquetas');
        Route::get('/search', 'ProdutoPrateleiraController@searchPrateleiras');
    });

    Route::group(['prefix' => 'otp'], function () {
        Route::get('/setup', 'OtpController@showSetup')->name('otp.setup');
        //Route::post('/verify', 'OtpController@erify')->name('otp.verify');
        Route::post('/verify/{id?}', 'OtpController@verify')->name('otp.verify');
        //Route::post('/verify/{id}', 'OtpController@verify')->name('otp.verify');
    });

    /*
    Route::group(['prefix' => 'nfe-numeracao-gaps'], function(){
        Route::get('/', 'NFeNumeracaoGapController@index')->name('relatorios.nfe_numeracao_gaps.index');
        Route::get('/pdf', 'NFeNumeracaoGapController@pdf')->name('relatorios.nfe_numeracao_gaps.pdf');
        Route::post('/inutilizar', 'NFeNumeracaoGapController::class@inutilizarFaixa')->name('relatorios.nfe_numeracao_gaps.inutilizar');
        Route::post('/acao-documento', 'NFeNumeracaoGapController@acaoDocumento')->name('relatorios.nfe_numeracao_gaps.acao_documento');
    });
    */

    Route::group(['prefix' => 'nfe-numeracao-gaps'], function () {
        Route::get('/', 'NFeNumeracaoGapController@index')->name('relatorios.nfe_numeracao_gaps.index.direct');
        Route::get('/pdf', 'NFeNumeracaoGapController@pdf')->name('relatorios.nfe_numeracao_gaps.pdf');
        Route::post('/inutilizar', 'NFeNumeracaoGapController@inutilizarFaixa')->name('relatorios.nfe_numeracao_gaps.inutilizar.direct');
        Route::post('/acao-documento', 'NFeNumeracaoGapController@acaoDocumento')->name('relatorios.nfe_numeracao_gaps.acao_documento.direct');
        Route::get('/nfe-inutilizacoes', 'NFeNumeracaoGapController@inutilizacoes')->name('relatorios.nfe_inutilizacoes.index');
        Route::get('/nfe-inutilizacoes/pdf', 'NFeNumeracaoGapController@inutilizacoesPdf')->name('relatorios.nfe_inutilizacoes.pdf');
        Route::post('/nfe-inutilizacoes/inutilizar-manual', 'NFeNumeracaoGapController@inutilizarFaixaManual')->name('relatorios.nfe_inutilizacoes.inutilizar_manual');
        Route::post('/nfe-inutilizacoes/diagnosticar-faixa', 'NFeNumeracaoGapController@diagnosticarFaixa')->name('relatorios.nfe_inutilizacoes.diagnosticar_faixa');
        Route::get('/nfe-inutilizacoes/{id}/relatorio-individual', 'NFeNumeracaoGapController@relatorioInutilizacaoIndividual')->name('relatorios.nfe_inutilizacoes.relatorio_individual');
        Route::post('/nfe-inutilizacoes/{id}/ignorar-gap', 'NFeNumeracaoGapController@marcarInutilizacaoComoIgnorada')->name('relatorios.nfe_inutilizacoes.ignorar_gap');
        Route::post('/ignorar-gap', 'NFeNumeracaoGapController@ignorarGapManual')->name('relatorios.nfe_numeracao_gaps.ignorar_gap');
    });

    // Declare Novos Grupos de Rotas aqui Protegidos Pelo MidiWare
    // Siga o padrao abaixo

    // Folha de pagamento
    Route::group(['prefix' => 'folhaPagamento'], function(){
        Route::get('/', 'FolhaPagamentoController@index')->name('folha.index');
        Route::post('/importar', 'FolhaPagamentoController@importarTxt')->name('folha.importar');
        Route::get('/pdf/{id}', 'FolhaPagamentoController@gerarPdf')->name('folha.pdf');
        Route::delete('/excluir/{competencia}', 'FolhaPagamentoController@excluirCompetencia')
            ->name('folha.excluir')
            ->where('competencia', '.*');
    });

    // Pré-cadastros: aprovação e consulta
    Route::group(['prefix' => 'cadastros-pendentes'], function(){
        Route::get('/', 'PessoaPreCadastroController@index');
        Route::get('/aprovados', 'PessoaPreCadastroController@aprovados');
        Route::post('/aprovar/{id}', 'PessoaPreCadastroController@aprovar');
    });

    // Rotas do Grupo de Categorias
    Route::group(['prefix' => 'grupo-categorias'], function(){
        Route::get('/', 'GrupoCategoriaController@index')->name('grupo-categorias.index');
        Route::get('/novo', 'GrupoCategoriaController@create')->name('grupo-categorias.create');
        Route::post('/salvar', 'GrupoCategoriaController@store')->name('grupo-categorias.store');
        Route::get('/editar/{id}', 'GrupoCategoriaController@edit')->name('grupo-categorias.edit');
        Route::post('/atualizar/{id}', 'GrupoCategoriaController@update')->name('grupo-categorias.update');
        Route::get('/excluir/{id}', 'GrupoCategoriaController@destroy')->name('grupo-categorias.destroy');
    });

    // 2. Consultas e Relatórios Financeiros (RelatorioDespesaController)
    Route::group(['prefix' => 'relatorios-financeiros'], function(){
        Route::get('/despesas-semanal', 'RelatorioDespesaController@visualizacaoPlanilha');
        Route::get('/resumo-despesas', 'RelatorioResumoController@resumoDespesas');
        Route::get('/comparativo-meses', 'RelatorioResumoController@comparativoMeses');

        // Rotas do Prosoft padronizadas
        Route::get('/exportar-prosoft', 'ProsoftExportController@index');
        Route::post('/exportar-prosoft', 'ProsoftExportController@exportar');
        Route::get('/prosoft/ajuste-fiscal', 'ProsoftExportController@ajusteFiscal');
        Route::post('/prosoft/aplicar-ajuste', 'ProsoftExportController@aplicarAjusteLote');
    });

    Route::group(['prefix' => 'portaria'], function(){
        // Telas e Ações Principais
        Route::get('/', 'PortariaController@index');
        Route::post('/agendar', 'PortariaController@agendarVisita');
        Route::post('/entrada', 'PortariaController@registrarEntrada');
        Route::put('/saida/{id}', 'PortariaController@saida');
        Route::get('/saida/{id}', 'PortariaController@saida'); // Para a leitura do QR Code

        // Rotas para as buscas automáticas (AJAX) e Disparos
        Route::get('/busca-cpf/{cpf}', 'PortariaController@buscaCpf');
        Route::get('/busca-placa/{placa}', 'PortariaController@buscaPlaca');

        // Rota do botão de avisar no WhatsApp
        Route::post('/avisar-chegada', 'PortariaController@avisarChegadaAjax');

        // 🚀 AJUSTADO AQUI (Sem o /portaria dobrado)
        Route::get('/checar-resposta/{funcionario_id}', 'PortariaController@checarRespostaAjax');

        // Histórico
        Route::get('/historico', 'PortariaController@historico');

        // Rota para bloquear/desbloquear visitante
        Route::post('/bloquear-visitante', 'PortariaController@bloquearVisitanteAjax');

        Route::get('/imprimir-etiqueta/{id}', 'PortariaController@imprimirEtiqueta');

        // Rotas para Edição e Exclusão de Agendamento (Restrito ADM)
        Route::get('/agendamento/{id}/editar', 'PortariaController@editarAgendamento');
        Route::post('/agendamento/{id}/atualizar', 'PortariaController@atualizarAgendamento');
        Route::delete('/agendamento/{id}', 'PortariaController@excluirAgendamento');
    });

    Route::group(['prefix' => 'gestao-coletas'], function(){
        Route::get('/', 'GestaoColetaController@index');
        Route::post('/agendar/{id}', 'GestaoColetaController@agendar');
        Route::post('/recusar/{id}', 'GestaoColetaController@recusar');
    });

    // Manutenção: corrigir-notas
    Route::group(['prefix' => 'corrigir-notas'], function(){
        // ATENÇÃO: a consulta do NOVO não contém filtro explícito por empresa.
        // Conferir autorização administrativa e escopos do model antes de executar.
        Route::get('/', function() {
            $notas = \App\Models\ManifestaNfseTomada::whereNotNull('chave')->get();
            $atualizadas = 0;

            foreach ($notas as $nota) {
                $caminhoXml = public_path('xml_servico/' . $nota->chave . '.xml');

                if (file_exists($caminhoXml)) {
                    $xmlString = file_get_contents($caminhoXml);
                    $xmlClean = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xmlString);
                    $xml = simplexml_load_string($xmlClean);

                    if ($xml) {
                        // CORREÇÃO: A data está dentro de DPS -> infDPS -> dhEmi
                        $data = $xml->DPS->infDPS->dhEmi ?? $xml->infNFSe->DPS->infDPS->dhEmi ?? null;

                        // CORREÇÃO: O status de cancelamento no seu XML (107 = Cancelada / 100 = Autorizada)
                        $cStat = $xml->cStat ?? null;

                        if ($data) {
                            $nota->data_emissao = date('Y-m-d', strtotime((string)$data));
                        }

                        // Se cStat for 107, consideramos CANCELADA. Se for 100, é NORMAL/AUTORIZADA
                        $nota->situacao = ((string)$cStat == '107') ? 'CANCELADA' : 'NORMAL';

                        $nota->save();
                        $atualizadas++;
                    }
                }
            }
            return "Processo finalizado! $atualizadas notas atualizadas com sucesso.";
        });
    });

    // Manutenção: criar-atalho-foto
    Route::group(['prefix' => 'criar-atalho-foto'], function(){
        Route::get('/', function () {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
            return 'Atalho criado com sucesso! As fotos já devem aparecer.';
        });
    });

    // =======================================================
    // IMPORTAÇÃO DE CT-e (RECEBER)
    // =======================================================
    Route::group(['prefix' => 'importarCte'], function () {
        Route::get('/', 'ImportacaoCteController@index')->name('importarCte.index');
        Route::post('/lote', 'ImportacaoCteController@importarLote')->name('importarCte.importarLote');
    });

    // =======================================================
    // ROTAS DE TAREFAS
    // =======================================================
    Route::group(['prefix' => 'tarefas'], function () {
        Route::get('/', 'TarefaController@list')->name('tarefas.index');
        Route::get('/list', 'TarefaController@list')->name('tarefas.list');
        Route::post('/list', 'TarefaController@filtro')->name('tarefas.filtro');
        Route::get('/new', 'TarefaController@register')->name('tarefas.new');
        Route::post('/save', 'TarefaController@save')->name('tarefas.save');
        Route::get('/edit/{id}', 'TarefaController@edit')->name('tarefas.edit');
        Route::post('/update/{id}', 'TarefaController@update')->name('tarefas.update');
        Route::get('/delete/{id}', 'TarefaController@delete')->name('tarefas.delete');
        Route::get('/iniciar/{id}', 'TarefaController@iniciar')->name('tarefas.iniciar');
        Route::get('/finalizar/{id}', 'TarefaController@finalizar')->name('tarefas.finalizar');
        Route::get('/pausar/{id}', 'TarefaController@pausar')->name('tarefas.pausar');
        Route::get('/painel', 'TarefaController@painel')->name('tarefas.painel');
    });

    // =======================================================
    // ROTAS DE PAGAMENTO EM LOTE
    // =======================================================
    Route::group(['prefix' => 'pagamento-lote'], function () {
        Route::get('/', 'PagamentoLoteController@index')->name('pagamento-lote.index');
        Route::post('/gerar-cnab', 'PagamentoLoteController@gerarArquivoPix')->name('pagamento-lote.gerar-cnab');
    });

    // =======================================================
    // ROTAS DE FATURAMENTO
    // =======================================================
    Route::group(['prefix' => 'faturamento'], function () {
        Route::get('/', 'FaturamentoController@index')->name('faturamento.index');
        Route::get('/pdf', 'FaturamentoController@gerarPdfFaturamento')->name('faturamento.pdf');
    });

    // =======================================================
    // ROTAS DE TABELAS DE PREÇOS
    // =======================================================
    Route::group(['prefix' => 'tabelas-precos'], function () {
        Route::get('/', 'TabelaPrecoController@list')->name('tabelas-precos.index');
        Route::get('/new', 'TabelaPrecoController@register')->name('tabelas-precos.new');
        Route::get('/search-produto', 'TabelaPrecoController@searchProduto')->name('tabelas-precos.search-produto');
        Route::get('/edit/{id}', 'TabelaPrecoController@register')->name('tabelas-precos.edit');
        Route::post('/save', 'TabelaPrecoController@save')->name('tabelas-precos.save');
        Route::get('/delete/{id}', 'TabelaPrecoController@delete')->name('tabelas-precos.delete');
    });

    Route::group(['prefix' => 'funcoes'],function(){
        //Novas Rotas Futuras Declare aqui
        Route::post('/quickSave', 'FuncionarioController@quickSave');
    });

    Route::group(['prefix' => 'contas-empresa'],function(){
        Route::get('/extrato/{id?}', 'ContaEmpresaController@extrato')->name('contas-empresa.extrato');
        Route::get('/sincronizar/{id}', 'ContaEmpresaController@sincronizar')->name('contas-empresa.sincronizar');
        Route::get('/delete-lancamento/{id}', 'ContaEmpresaController@deleteLancamento')->name('contas-empresa.delete-lancamento');
        Route::get('/imprimir-extrato/{id}', 'ContaEmpresaController@imprimirExtrato')->name('contas-empresa.imprimir-extrato');
        Route::get('/imprimir-transacao/{id}', 'ContaEmpresaController@imprimirTransacao')->name('contas-empresa.imprimir-transacao');
    });

    Route::group(['prefix' => 'item-conta'],function(){
        Route::post('/salvar', 'ItemContaEmpresaController@store')->name('item-conta.store');
        Route::post('/delete-lancamento/{id}', 'ItemContaEmpresaController@deleteLancamento')->name('item-conta.deleteLancamento');
    });

    Route::group(['prefix' => 'requisicoes'], function() {
        Route::get('/', 'RequisicaoController@index')->name('requisicoes.index');
        Route::get('/create', 'RequisicaoController@create')->name('requisicoes.create');
        Route::post('/store', 'RequisicaoController@store')->name('requisicoes.store');
        Route::put('/finalizar/{id}', 'RequisicaoController@finalizar')->name('requisicoes.finalizar');
        Route::get('/imprimir-ficha', 'RequisicaoController@imprimirFichaFiltro')->name('requisicoes.imprimirFichaFiltro');
        Route::post('/migrarHistorico', 'RequisicaoController@migrarHistorico')->name('requisicoes.migrarHistorico');
        Route::get('/{requisicao}', 'RequisicaoController@show')->name('requisicoes.show');
        Route::get('/{requisicao}/edit', 'RequisicaoController@edit')->name('requisicoes.edit');
        Route::put('/{requisicao}', 'RequisicaoController@update')->name('requisicoes.update');
        Route::delete('/{requisicao}', 'RequisicaoController@destroy')->name('requisicoes.destroy');
        Route::get('/{requisicao}/imprimir', 'RequisicaoController@imprimir')->name('requisicoes.imprimir');
    });

    Route::group(['prefix' => 'dashboard-analitico'],function(){
        //Novas Rotas Futuras Declare aqui
        Route::get('/', 'DashboardAnaliticoController@index')->name('dashboard.analitico.index');
    });

    Route::group(['prefix' => 'adiantamentos'], function () {
        Route::get('/', 'AdiantamentoController@index')->name('adiantamentos.index');
        Route::post('/store', 'AdiantamentoController@store')->name('adiantamentos.store');
        Route::get('/buscar-pessoas', 'AdiantamentoController@buscarPessoas')->name('adiantamentos.buscarPessoas');
        Route::post('/cancelar/{id}', 'AdiantamentoController@cancelar')->name('adiantamentos.cancelar'); // Este já serve como "Excluir/Estornar"
        Route::get('/sincronizar', 'AdiantamentoController@sincronizar')->name('adiantamentos.sincronizar');
        Route::get('/extrato/{tipo}/{id}', 'AdiantamentoController@extrato')->name('adiantamentos.extrato');
        Route::get('/consulta-saldo/{tipo}/{id}', 'AdiantamentoController@getSaldoPessoa')->name('adiantamentos.getSaldoPessoa');

        // Novas rotas para Devolução e Edição
        Route::post('/devolver', 'AdiantamentoController@devolver')->name('adiantamentos.devolver');
        Route::get('/edit/{id}', 'AdiantamentoController@edit')->name('adiantamentos.edit');
        Route::post('/update/{id}', 'AdiantamentoController@update')->name('adiantamentos.update');
    });

    Route::group(['prefix' => 'apuracao'],function () {
        Route::get('/', 'ApuracaoController@index');
        Route::post('/filtrar', 'ApuracaoController@index');
        Route::post('/finalizar', 'ApuracaoController@finalizar');
        Route::get('/apuracao', 'ApuracaoController@index');
        Route::post('/apuracao/finalizar', 'ApuracaoController@finalizar');
    });

    Route::group(['prefix' => 'compras-lote'],function () {
        Route::get('/', 'CompraLoteController@index')->name('compras.lote.index');
        Route::post('/importar', 'CompraLoteController@importar')->name('compras.lote.importar');
    });

    Route::group(['prefix' => 'contabilidade'], function(){

        // Plano de Contas
        Route::get('/plano-contas', 'ContabilidadeController@index')->name('contabilidade.plano.index');
        Route::post('/plano-contas/importar', 'ContabilidadeController@importarPlanoContas')->name('contabilidade.plano.importar');

        // Configurações
        Route::get('/configuracoes', 'ContabilidadeConfigController@index')->name('contabilidade.configuracoes.index');
        Route::post('/configuracoes/save', 'ContabilidadeConfigController@save')->name('contabilidade.configuracoes.save');

        // EXPORTAÇÃO
        Route::get('/exportacao', 'ExportacaoContabilController@index')->name('contabilidade.exportacao.index');
        Route::post('/exportacao/previa', 'ExportacaoContabilController@processarPrevia')->name('contabilidade.exportacao.previa');
        Route::post('/exportacao/gerar', 'ExportacaoContabilController@gerarArquivo')->name('contabilidade.exportacao.gerar');

        // NOVA ROTA PARA EXPORTAÇÃO EXCEL
        Route::post('/exportacao/excel', 'ExportacaoContabilController@gerarExcel')->name('contabilidade.exportacao.excel');
        Route::post('/exportacao/terceiros', 'ContabilidadeController@gerarTerceiros')->name('contabilidade.exportacao.terceiros');

    });

    Route::group(['prefix' => 'nfse-tomadas'], function(){

        // --- Módulo de NFS-e Tomadas Nacional ---
        Route::get('/', 'NFSeTomadaController@index');
        Route::get('/sincronizar', 'NFSeTomadaController@sincronizarManual');
        Route::get('/sincronizar-data', 'NFSeTomadaController@sincronizarPorData');

        // 🔥 CORRIGIDO: Se você já tiver uma tela de detalhes da nota antiga, mantenha essa rota:
        Route::get('/visualizar/{id}', 'NFSeTomadaController@detalhes');

        // 🚀 ESSA É A ROTA DO BOTÃO "LANÇAR" DA SUA TABELA:
        Route::get('/detalhes/{id}', 'NFSeTomadaController@detalhesLançamento');

        // 🔒 PROCESSAMENTO DO FINANCEIRO (POST):
        Route::post('/salvar-importacao/{id}', 'NFSeTomadaController@salvarImportacaoPainel');

        // 🖨️ IMPRESSÃO DO ESPELHO DA NOTA:
        Route::get('/espelho/{id}', 'NFSeTomadaController@imprimirEspelho');
        Route::get('/xml/{id}', 'NFSeTomadaController@downloadXml')->name('nfse-tomadas.xml');
        Route::post('/sincronizar-manuais', 'NFSeTomadaController@buscarPagamentosManuais')->name('nfse.sincronizar-manuais');

    });

    /*
    Route::group(['prefix' => 'eletronicDocs'], function () {
        Route::get('/', 'EletronicDocsController@list')->name('eletronic_docs.list');
        Route::post('/download', 'EletronicDocsController@downloadXML')->name('eletronic_docs.download');
        Route::get('/view-xml', 'EletronicDocsController@viewXML')->name('eletronic_docs.viewXML');
    });
    */

    /*
    Route::group(['prefix' => 'eletronicDocs'], function () {
        Route::get('/', 'EletronicDocsController@list')->name('eletronic_docs.list');
        Route::post('/download', 'EletronicDocsController@downloadXML')->name('eletronic_docs.download');
        Route::get('/webdanfe', 'EletronicDocsController@loadWebDanfe')->name('eletronic_docs.webdanfe');
    });
    */

    /*
    Route::group(['prefix' => 'eletronicDocs'], function () {
        Route::get('/', 'EletronicDocsController@list')->name('eletronic_docs.list');
        Route::post('/download', 'EletronicDocsController@downloadXML')->name('eletronic_docs.download');
        Route::post('/download/api', 'EletronicDocsController@downloadXML_API')->name('eletronic_docs.download.api');
    });
    */

    /*
    Route::group(['prefix' => 'eletronicDocs'], function () {
        Route::get('/', 'EletronicDocsController@list')->name('eletronic_docs.list');
        Route::post('/download', 'EletronicDocsController@downloadXML')->name('eletronic_docs.download');
    });
    */

    /*
    Route::group(['prefix' => 'eletronicDocs'], function () {
        Route::get('/', 'EletronicDocsController@list')->name('eletronic_docs.list');
        Route::post('/download', 'EletronicDocsController@downloadXML')->name('eletronic_docs.download');
    });
    */

    /*
    Route::group(['prefix' => 'pesagens'], function () {
        Route::get('/', 'PesagemController@list')->name('pesagens.list'); // Nomeando a rota
        Route::get('/list', 'PesagemController@list')->name('pesagens.list'); // Nomeando a rota
        Route::get('/new', 'PesagemController@register')->name('pesagens.register');
        Route::get('/register', 'PesagemController@register');
        Route::post('/save', 'PesagemController@save')->name('pesagens.save');
        Route::get('/edit/{id}', 'PesagemController@edit')->name('pesagens.edit');
        Route::post('/update/{id}', 'PesagemController@update')->name('pesagens.update');
        Route::get('/delete/{id}', 'PesagemController@delete')->name('pesagens.delete');
        Route::get('/concluir/{id}', 'PesagemController@concluir')->name('pesagens.concluir');
    });

    Route::group(['prefix' => 'ticketsPesagem'], function () {
        Route::get('/', 'TicketPesagemController@list');
        Route::get('/list', 'TicketPesagemController@list');
        Route::get('/new', 'TicketPesagemController@register');
        Route::post('/save', 'TicketPesagemController@save');
        Route::get('/edit/{id}', 'TicketPesagemController@edit');
        Route::post('/update/{id}', 'TicketPesagemController@update');
        Route::get('/delete/{id}', 'TicketPesagemController@delete');
    });
    */

    /*
    Route::group(['prefix' => 'formBuild'], function () {
        Route::get('/', 'FormBuildingController@loadForm');
        Route::get('form_field/get', 'FormBuildingController@getFormFieldHTML');
    });
    */

    /*
    Route::group(['prefix' => 'traccar'], function () {
        Route::get('/list', 'TraccarIntegrationController@listarDispositivos');
        Route::post('/new', 'TraccarIntegrationController@criarDispositivo');
        Route::delete('/devices/{id}', 'TraccarIntegrationController@deletarDispositivo');
        Route::get('/monitor', 'TraccarIntegrationController@monitorar');
        Route::get('/rastreamentoVeiculo', 'SiteViewController@rastreamento');
    });
    */

    // Route::group(['prefix' => 'rastreamentoVeiculo'], function () {
    //      Route::get('/', 'SiteViewController@rastreamento');
    // });

    // Route::group(['prefix' => 'site-view'], function () {
    //     Route::get('/', 'SiteViewController@list')->name('site.view.list');
    // });

    Route::group(['prefix' => '/otica'], function(){
        Route::get('/', 'OticaController@list')->name('otica.index');
        Route::get('/list', 'OticaController@list')->name('otica.list');
        Route::post('/list', 'OticaController@list');
        Route::get('/new', 'OticaController@register')->name('otica.create');
        Route::post('/save', 'OticaController@save')->name('otica.store');
        Route::get('/edit/{id}', 'OticaController@register')->name('otica.edit');
        Route::post('/update/{id}', 'OticaController@update')->name('otica.update');
        Route::put('/update/{id}', 'OticaController@update');
        Route::get('/delete/{id}', 'OticaController@delete')->name('otica.destroy');
        Route::delete('/delete/{id}', 'OticaController@destroy');
        Route::get('/buscar-clientes', 'OticaController@buscarClientes')->name('otica.buscarClientes');
        Route::get('/buscar-produtos', 'OticaController@buscarProdutos')->name('otica.buscarProdutos');
        Route::post('/cliente-rapido', 'OticaController@cadastroRapidoCliente')->name('otica.clienteRapido');
        Route::get('/buscar-cidades', 'OticaController@buscarCidades')->name('otica.buscarCidades');
        Route::post('/enviar-whatsapp-direto', 'OticaController@enviarWhatsAppDireto')->name('otica.enviarWhatsAppDireto');
        Route::post('/alterar-status', 'OticaController@alterarStatus')->name('otica.alterarStatus');
        Route::get('/faturar/{id}', 'OticaController@faturar')->name('otica.faturar');
        Route::get('/imprimir-os/{id}', 'OticaController@imprimirOS')->name('otica.imprimirOS');
        Route::get('/imprimir-recibo/{id}', 'OticaController@imprimirRecibo')->name('otica.imprimirRecibo');
    });

});

// ==============================================================================
// DELIVERY PÚBLICO — fluxo histórico compatibilizado com a estrutura 5.0.181
// ==============================================================================
Route::get('/pedir/{parametro}', function ($parametro) {
    $empresaQuery = \Illuminate\Support\Facades\DB::table('empresas');

    if (ctype_digit((string) $parametro)) {
        $empresaQuery->where('id', (int) $parametro);
    } else {
        $empresaQuery->where('nome_fantasia', 'like', '%' . $parametro . '%');
    }

    $empresa = $empresaQuery->first();

    if (!$empresa) {
        abort(404, 'Empresa não encontrada para o Delivery.');
    }

    session()->put('empresa_id', $empresa->id);
    session()->forget(['cliente_log', 'telefone_cliente']);

    return view('delivery.login_pedido', [
        'nome_empresa' => $parametro,
        'config' => \App\Models\DeliveryConfig::where('empresa_id', $empresa->id)->first(),
        'tokenJs' => true,
        'title' => 'AUTENTICAR',
    ]);
});

Route::post('/pedir/{parametro}/entrar', function (\Illuminate\Http\Request $request, $parametro) {
    $empresaId = session('empresa_id');

    if (!$empresaId) {
        return redirect('/pedir/' . $parametro);
    }

    $telefone = preg_replace('/[^0-9]/', '', (string) $request->telefone);

    if (strlen($telefone) < 10 || strlen($telefone) > 13) {
        return redirect('/pedir/' . $parametro)
            ->with('message_erro', 'Informe um telefone válido para continuar.');
    }

    $cliente = \App\Models\ClienteDelivery::where('empresa_id', $empresaId)
        ->where('celular', $telefone)
        ->first();

    if (!$cliente) {
        $cliente = \App\Models\ClienteDelivery::create([
            'nome' => 'Cliente',
            'sobre_nome' => '.',
            'celular' => $telefone,
            'email' => uniqid('delivery_', true) . '@friccaozero.local',
            'senha' => md5(uniqid((string) $empresaId, true)),
            'ativo' => 1,
            'token' => random_int(100000, 999999),
            'empresa_id' => $empresaId,
            'cpf' => '',
            'foto' => '',
            'uid' => uniqid('cli_', false),
        ]);
    }

    session()->put('telefone_cliente', $telefone);
    session()->put('cliente_log', [
        'id' => $cliente->id,
        'nome' => $cliente->nome,
    ]);

    return redirect('/cardapio');
});

Route::group(['prefix' => '/autenticar'], function(){
    Route::get('/', 'DeliveryController@login');
    Route::post('/', 'DeliveryController@autenticar');
    Route::get('/registro', 'DeliveryController@registro');
    Route::get('/logoff', 'DeliveryController@logoff');
    Route::get('/novo', 'DeliveryController@autenticarCliente');
    Route::post('/registro', 'DeliveryController@salvarRegistro');
    Route::get('/esqueceu_a_senha', 'DeliveryController@recuperarSenha');
    Route::post('/esqueceu_a_senha', 'DeliveryController@enviarSenha');
    Route::post('/validaToken', 'DeliveryController@validaToken');
    Route::get('/ativar/{cliente_id}', 'DeliveryController@ativar');
    Route::post('/refreshToken', 'DeliveryController@refreshToken');
    Route::get('/saveTokenWeb', 'DeliveryController@saveTokenWeb');
    Route::get('/cliente/{cod}', 'DeliveryController@autenticarClienteEmail');
});

Route::group(['prefix' => '/cardapio'], function(){
    Route::get('/', 'DeliveryController@cardapio');
    Route::get('/{id}', 'DeliveryController@produtos');
    Route::get('/acompanhamento/{id}', 'DeliveryController@acompanhamento');
    Route::get('/verProduto/{id}', 'DeliveryController@verProduto');
});

Route::group(['prefix' => '/pizza'], function(){
    Route::get('/escolherSabores', 'DeliveryController@escolherSabores');
    Route::post('/adicionarSabor', 'DeliveryController@adicionarSabor');
    Route::get('/verificaPizzaAdicionada', 'DeliveryController@verificaPizzaAdicionada');
    Route::get('/removeSabor/{id}', 'DeliveryController@removeSabor');
    Route::get('/adicionais', 'DeliveryController@adicionais');
    Route::get('/pesquisa', 'DeliveryController@pesquisa');
    Route::get('/pizzas', 'DeliveryController@pizzas');
});

Route::group(['prefix' => '/info'], function(){
    Route::get('/', 'DeliveryController@infos');
    Route::get('/alterarEndereco/{id}', 'DeliveryController@alterarEndereco');
    Route::post('/atualizarSenha', 'DeliveryController@atualizarSenha');
    Route::post('/updateEndereco', 'DeliveryController@updateEndereco');
});

Route::group(['prefix' => '/carrinho'], function(){
    Route::get('/', 'CarrinhoController@carrinho');
    Route::post('/add', 'CarrinhoController@add');
    Route::post('/addPizza', 'CarrinhoController@addPizza');
    Route::get('/removeItem/{id}', 'CarrinhoController@removeItem');
    Route::get('/refreshItem/{id}/{quantidade}', 'CarrinhoController@refreshItem');
    Route::get('/forma_pagamento/{cupom?}', 'CarrinhoController@forma_pagamento');
    Route::post('/finalizarPedido', 'CarrinhoController@finalizarPedido');
    Route::get('/historico', 'CarrinhoController@historico');
    Route::get('/meus-pedidos', 'CarrinhoController@meusPedidos');
    Route::get('/pedir_novamente/{id}', 'CarrinhoController@pedir_novamente');
    Route::get('/finalizado/{id}', 'CarrinhoController@finalizado');
    Route::get('/configDelivery', 'CarrinhoController@configDelivery');
    Route::get('/cupons', 'CarrinhoController@cupons');
    Route::get('/getDadosCalculoEntrega', 'CarrinhoController@getDadosCalculoEntrega');
    Route::get('/cupom/{codigo}', 'CarrinhoController@cupom');
});

Route::group(['prefix' => '/enderecoDelivery'], function(){
    // Route::get('/{id}', 'EnderecoDeliveryController@index');
    Route::post('/save', 'EnderecoDeliveryController@save');
    Route::get('/', 'EnderecoDeliveryController@get');
    Route::get('/getValorBairro', 'EnderecoDeliveryController@getValorBairro');
});

Route::get('/delivery-logoff', function () {
    $empresaId = session('empresa_id');
    session()->forget(['telefone_cliente', 'cliente_log']);

    return $empresaId ? redirect('/pedir/' . $empresaId) : redirect('/');
});


Route::group(['prefix' => 'loja', 'middleware' => 'validaEcommerce'], function(){
    Route::get('/{link}', 'EcommerceController@index');
    Route::get('/{link}/categorias', 'EcommerceController@categorias');
    Route::get('/{link}/{id}/categorias', 'EcommerceController@produtosDaCategoria');
    Route::get('/{link}/{id}/subcategoria', 'EcommerceController@produtosDaSubCategoria');

    //blog
    Route::get('/{link}/blog', 'EcommerceController@blog');
    Route::get('/{link}/contato', 'EcommerceController@contato');
    Route::get('/{link}/{id}/posts', 'EcommerceController@postsCategoria');
    Route::get('/{link}/{id}/verPost', 'EcommerceController@verPost');
    Route::get('/{link}/{id}/verProduto', 'EcommerceController@verProduto');

    Route::post('/{link}/addProduto', 'EcommerceController@addProduto');
    Route::get('/{link}/carrinho', 'EcommerceController@carrinho');
    Route::get('/{link}/curtidas', 'EcommerceController@curtidas');
    Route::get('/{link}/{id}/deleteItemCarrinho', 'EcommerceController@deleteItemCarrinho');
    Route::get('/{link}/carrinho/atualizaItem', 'EcommerceController@atualizaItem');

    Route::get('/{link}/checkout', 'EcommerceController@checkout');
    Route::post('/{link}/checkout', 'EcommerceController@checkoutStore');
    Route::get('/{link}/logoff', 'EcommerceController@logoff');
    Route::get('/{link}/login', 'EcommerceController@login');
    Route::post('/{link}/login', 'EcommerceController@loginPost');
    Route::post('/{link}/pagamento', 'EcommerceController@pagamento');
    // Route::get('/{link}/pagamento', 'EcommerceController@pagamento');
    Route::get('/{link}/endereco', 'EcommerceController@endereco');
    Route::get('/{link}/esquecisenha', 'EcommerceController@esquecisenha');
    Route::post('/{link}/esquecisenha', 'EcommerceController@esquecisenhaPost');
    Route::get('/{link}/{id}/curtirProduto', 'EcommerceController@curtirProduto');

    Route::get('/{link}/pedido_detalhe/{id}', 'EcommerceController@pedidoDetalhe');
    Route::get('/{link}/pesquisa', 'EcommerceController@pesquisa');
});

Route::get('/teste-frete', 'EcommerceController@testeFrete');
Route::get('/buscaCupomEcommerce', 'EcommerceController@buscaCupomEcommerce');
Route::post('/ecommerceContato', 'EcommerceController@saveContato');
Route::post('/ecommerceInformativo', 'EcommerceController@saveInformativo');
Route::get('/ecommerceCalculaFrete', 'EcommerceController@calculaFrete');
Route::post('/ecommerceSetaFrete', 'EcommerceController@setaFrete');
Route::post('/ecommerceUpdateCliente', 'EcommerceController@ecommerceUpdateCliente');
Route::post('/ecommerceUpdateSenha', 'EcommerceController@ecommerceUpdateSenha');
Route::post('/ecommerceSaveEndereco', 'EcommerceController@ecommerceSaveEndereco');
Route::get('/correios', 'EcommerceController@correios');

Route::group(['prefix' => 'ecommercePay'], function(){
    Route::post('/boleto', 'EcommercePayController@paymentBoleto');
    Route::post('/pix', 'EcommercePayController@paymentPix');
    Route::post('/cartao', 'EcommercePayController@paymentCartao');
    Route::get('/consulta/{transacao_id}', 'EcommercePayController@consultaPagamento');
    Route::get('/finalizado/{hash}', 'EcommercePayController@finalizado');
    Route::post('/finalizaOrcamento', 'EcommercePayController@finalizaOrcamento');
});

Route::get('lojainexistente', function(){
    return view('lojainexistente');
});

Route::get('/habilitadoApi', function(){
    return view('habilitadoApi');
});

/*
|--------------------------------------------------------------------------
| Desabilitado por Wallace em 15052026
|--------------------------------------------------------------------------
| A rota pública /cron-master foi desabilitada porque executava
| Artisan::call('schedule:run') sem autenticação específica de cron.
| Use o cron do servidor apontando para php artisan schedule:run.
| Caso o provedor só permita chamada HTTP, use a rota protegida
| /admin/cron-master/{token}.
|
Route::get('/cron-master', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('schedule:run');
        return "Comandos agendados foram disparados com sucesso!";
    } catch (\Exception $e) {
        return "Erro ao disparar: " . $e->getMessage();
    }
});
*/

/*
|--------------------------------------------------------------------------
| Desabilitado por Wallace em 15052026
|--------------------------------------------------------------------------
| A rota pública /teste-dfe foi desabilitada porque expõe diagnóstico
| interno de empresas, filiais, logs e horários do robô DF-e.
| Esse diagnóstico deve virar Command Artisan ou tela administrativa
| protegida para SuperAdmin.
|
Route::get('/teste-dfe', function () {
    // Diagnóstico antigo do robô DF-e.
});
*/

Route::group([
    'prefix' => 'admin/cron-master',
    'middleware' => ['cron.protected', 'throttle:1,1'],
], function () {
    Route::get('/status/{token?}', function () {
        $monitor = app(\App\Services\Cron\CronMonitor::class);

        return response()->json([
            'success' => true,
            'message' => 'Status do cron carregado com sucesso.',
            'data' => $monitor->status(),
        ]);
    })->name('admin.cron-master.status');

    Route::get('/{token?}', function () {
        $monitor = app(\App\Services\Cron\CronMonitor::class);

        if (! $monitor->shouldRunInternalHttp()) {
            return response()->json([
                'success' => true,
                'executed' => false,
                'message' => $monitor->denyReasonForInternalHttp(),
                'data' => $monitor->status(),
            ]);
        }

        $lock = \Illuminate\Support\Facades\Cache::lock('admin-cron-master-schedule-run', 55);

        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'executed' => false,
                'message' => 'Cron já está em execução. Tente novamente em instantes.',
                'data' => $monitor->status(),
            ], 429);
        }

        try {
            app()->instance('fersoft.cron_source', 'internal_http');

            \Illuminate\Support\Facades\Artisan::call('schedule:run');
            $output = trim(\Illuminate\Support\Facades\Artisan::output());

            return response()->json([
                'success' => true,
                'executed' => true,
                'message' => 'Cron interno HTTP executado com sucesso.',
                'output' => $output,
                'data' => $monitor->status(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'executed' => false,
                'message' => 'Erro ao disparar cron: ' . $e->getMessage(),
                'data' => $monitor->status(),
            ], 500);
        } finally {
            optional($lock)->release();
        }
    })->name('admin.cron-master.run');
});

/*
|--------------------------------------------------------------------------
| Traccar - Event Forwarding
|--------------------------------------------------------------------------
|
| Endpoint sem sessão/CSRF para receber os eventos enviados diretamente
| pelo servidor Traccar. A autenticação é feita pelo próprio controller
| através do header configurado em TRACCAR_WEBHOOK_HEADER/SECRET.
|
*/
Route::post('/traccar/webhook', 'TraccarWebhookController@receberEvento')
    ->name('api.traccar.webhook');

/*Route::get('/executar-migrations-fersoft-admin', function () {
    try {
        // Roda apenas o arquivo da migration do grupo de categorias
        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_07_30_ccreate_grupo_categorias_tables.php', // Ajuste com o nome exato do arquivo que você criou
            '--force' => true
        ]);

        return '<h2>Migration executada com sucesso!</h2><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return '<h2>Erro ao executar migration:</h2><pre>' . $e->getMessage() . '</pre>';
    }
});

Route::get('/limpar-cache-fersoft', function() {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    return '<h3>Cache de rotas e configurações limpo com sucesso!</h3>';
});*/

/* Desabilitado por Wallace em 16022026
Route::group([
    'prefix' => 'updates',
    'middleware' => ['verificaEmpresa', 'validaAcesso', 'verificaContratoAssinado', 'limiteArmazenamento'],
], function () {
    Route::get('/', 'Updates\UpdateController@index')->name('updates.index');
    Route::post('/', 'Updates\UpdateController@apply')->name('updates.apply');
    Route::post('/{version}/downgrade', 'Updates\UpdateController@downgrade')->name('updates.downgrade');
    Route::get('/{version}/running', 'Updates\UpdateController@running')->name('updates.running');
    Route::get('/{version}/logs-stream', 'Updates\UpdateController@logsStream')->name('updates.logs.stream');
    Route::get('/{version}/logs/{log}', 'Updates\UpdateController@showLog')->name('updates.logs.show');
});
*/
