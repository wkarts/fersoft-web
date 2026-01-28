<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'appUser'],function(){
	Route::post('/signup', 'AppUserController@signup');
	Route::post('/login', 'AppUserController@login');
	Route::post('/novoEndereco', 'AppUserController@novoEndereco')->middleware('token');
	Route::get('/testeConn', 'AppUserController@testeConn');
	Route::get('/enderecos', 'AppUserController@enderecos')->middleware('token');

	Route::post('/saveToken', 'AppUserController@saveToken');
	Route::post('/atualizaToken', 'AppUserController@atualizaToken');
	Route::post('/appComToken', 'AppUserController@appComToken');
	Route::post('/refreshToken', 'AppUserController@refreshToken');
	Route::post('/validaToken', 'AppUserController@validaToken');
	Route::post('/validaCupom', 'AppUserController@validaCupom')->middleware('token');
	Route::post('/redefinirSenha', 'AppUserController@redefinirSenha');

});

Route::group(['prefix' => 'appProduto'],function(){
	Route::get('/categorias/{usuario_id}', 'AppProdutoController@categorias');
	Route::get('/destaques/{usuario_id}', 'AppProdutoController@destaques');
	Route::get('/adicionais/{produto_id}', 'AppProdutoController@adicionais');
	Route::get('/pesquisaProduto', 'AppProdutoController@pesquisaProduto');
	Route::post('/favorito', 'AppProdutoController@favorito')->middleware('token');
	Route::post('/enviaProduto', 'AppProdutoController@enviaProduto')->middleware('token');
	Route::get('/tamanhosPizza', 'AppProdutoController@tamanhosPizza');
	Route::post('/pizzaValorPorTamanho', 'AppProdutoController@pizzaValorPorTamanho');
	Route::post('/saboresPorTamanho', 'AppProdutoController@saboresPorTamanho');
	Route::get('/dividePizza', 'ProdutoRestController@dividePizza');

});

Route::group(['prefix' => 'appCarrinho'],function(){
	Route::get('/index', 'AppCarrinhoController@index')->middleware('token');
	Route::get('/historico', 'AppCarrinhoController@historico')->middleware('token');
	Route::get('/itensCarrinho', 'AppCarrinhoController@itensCarrinho')->middleware('token');
	Route::post('/pedirNovamente', 'AppCarrinhoController@pedirNovamente')->middleware('token');
	Route::post('/removeItem', 'AppCarrinhoController@removeItem')->middleware('token');
	Route::get('/validaPedidoEmAberto', 'AppCarrinhoController@validaPedidoEmAberto')
	->middleware('token');
	Route::get('/valorEntrega', 'AppCarrinhoController@valorEntrega');
	Route::post('/finalizar', 'AppCarrinhoController@finalizar')->middleware('token');
	Route::get('/config', 'AppCarrinhoController@config');
	Route::post('/cancelar', 'AppCarrinhoController@cancelar')->middleware('token');
	Route::get('/funcionamento', 'AppCarrinhoController@funcionamento');

	Route::get('/getBairros', 'AppCarrinhoController@getBairros');
	Route::get('/getValorBairro/{id}', 'AppCarrinhoController@getValorBairro');

});

// App Gargom
Route::group(['prefix' => 'pedidoProduto'],function(){
	Route::get('/maisPedidos', 'ProdutoRestController@maisPedidos');
	Route::get('/adicionais', 'ProdutoRestController@adicionais');
	Route::get('/tamanhosPizza', 'ProdutoRestController@tamanhosPizza');
	Route::get('/saboresPorTamanho', 'ProdutoRestController@saboresPorTamanho');
	Route::get('/pizzaValorPorTamanho', 'ProdutoRestController@pizzaValorPorTamanho');
	Route::get('/pesquisaRest', 'ProdutoRestController@pesquisa');
	Route::get('/dividePizza', 'ProdutoRestController@dividePizza');
});

Route::group(['prefix' => 'pedidos'],function(){
	Route::get('/comandasAberta', 'PedidoRestController@comandasAberta');
	Route::get('/mesas', 'PedidoRestController@mesas');
	Route::get('/mesasTodas', 'PedidoRestController@mesasTodas');
	Route::get('/abrirComanda', 'PedidoRestController@abrirComanda');
	Route::get('/addProduto', 'PedidoRestController@addProduto');
	Route::get('/deleteItem', 'PedidoRestController@deleteItem');
	Route::get('/emAberto', 'PedidoRestController@emAberto');

});

//pagseguro

Route::group(['prefix' => '/pagseguro'], function(){
	Route::get('/getSessao', 'PagSeguroController@getSessao');
	Route::get('/getFuncionamento', 'PagSeguroController@getFuncionamento');
	Route::post('/cartoes', 'PagSeguroController@cartoes')->middleware('token');

	Route::post('/efetuaPagamento', 'PagSeguroController@efetuaPagamentoApp');
	Route::get('/consultaJS', 'PagSeguroController@consultaJS');
});

//fim pagseguro

Route::group(['prefix' => 'appFiscal'],function(){

	Route::group(['prefix' => 'clientes'],function(){
		Route::get('/', 'AppFiscal\\ClienteController@clientes')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\ClienteController@salvar');
		Route::post('/delete', 'AppFiscal\\ClienteController@delete');
		Route::get('/consultaCnpj', 'AppFiscal\\ClienteController@consultaCnpj');
	});

	Route::group(['prefix' => 'fornecedores'],function(){
		Route::get('/', 'AppFiscal\\FornecedorController@fornecedores')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\FornecedorController@salvar');
		Route::post('/delete', 'AppFiscal\\FornecedorController@delete');
	});

	Route::group(['prefix' => 'usuario'],function(){
		Route::post('/', 'AppFiscal\\UsuarioController@index');
		Route::post('/salvarImagem', 'AppFiscal\\UsuarioController@salvarImagem');
	});

	Route::group(['prefix' => 'configEmitente'],function(){
		Route::get('/', 'AppFiscal\\ConfigEmitenteController@index')->middleware('authApp');
		Route::get('/dadosCertificado', 'AppFiscal\\ConfigEmitenteController@dadosCertificado')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\ConfigEmitenteController@salvar');
		Route::post('/salvarCertificado', 'AppFiscal\\ConfigEmitenteController@salvarCertificado');
	});

	Route::get('/cidades', 'AppFiscal\\ClienteController@cidades')->middleware('authApp');
	Route::get('/ufs', 'AppFiscal\\ClienteController@ufs')->middleware('authApp');

	Route::group(['prefix' => 'categorias'],function(){
		Route::get('/', 'AppFiscal\\CategoriaController@all')->middleware('authApp');
		Route::get('/isDelivery', 'AppFiscal\\CategoriaController@isDelivery')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\CategoriaController@salvar');
		Route::post('/delete', 'AppFiscal\\CategoriaController@delete');
	});

	Route::group(['prefix' => 'produtos'],function(){
		Route::get('/', 'AppFiscal\\ProdutoController@all')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\ProdutoController@salvar');
		Route::post('/delete', 'AppFiscal\\ProdutoController@delete');
		Route::get('/dadosParaCadastro', 'AppFiscal\\ProdutoController@dadosParaCadastro')->middleware('authApp');
		Route::get('/tributosPadrao', 'AppFiscal\\ProdutoController@tributosPadrao')->middleware('authApp');
		Route::post('/salvarImagem', 'AppFiscal\\ProdutoController@salvarImagem');

	});

	Route::group(['prefix' => 'naturezas'],function(){
		Route::get('/', 'AppFiscal\\NaturezaController@index')->middleware('authApp');
	});

	Route::group(['prefix' => 'transportadoras'],function(){
		Route::get('/', 'AppFiscal\\TransportadoraController@index')->middleware('authApp');
	});

	Route::group(['prefix' => 'vendas'],function(){
		Route::get('/', 'AppFiscal\\VendaController@index')->middleware('authApp');
		Route::get('/orcamentos', 'AppFiscal\\VendaController@orcamentos')->middleware('authApp');
		Route::get('/find/{id}', 'AppFiscal\\VendaController@getVenda')->middleware('authApp');
		Route::post('/filtroVendas', 'AppFiscal\\VendaController@filtroVendas');
		Route::get('/tiposDePagamento', 'AppFiscal\\VendaController@tiposDePagamento')->middleware('authApp');
		Route::get('/listaDePrecos', 'AppFiscal\\VendaController@listaDePrecos')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\VendaController@salvar');
		Route::post('/salvarVendaPorOrcamento', 'AppFiscal\\VendaController@salvarVendaPorOrcamento');
		Route::post('/salvarOrcamento', 'AppFiscal\\VendaController@salvarOrcamento');
		Route::post('/atualizarOrcamento', 'AppFiscal\\VendaController@atualizarOrcamento');

		Route::post('/delete', 'AppFiscal\\VendaController@delete');
		Route::post('/deleteOrcamento', 'AppFiscal\\VendaController@deleteOrcamento');

		Route::get('/renderizarDanfe/{id}', 'AppFiscal\\VendaController@renderizarDanfe')->middleware('authApp');
		Route::get('/renderizarPedido/{id}', 'AppFiscal\\VendaController@renderizarPedido');
		Route::get('/renderizarXml/{id}', 'AppFiscal\\VendaController@renderizarXml')->middleware('authApp');
		Route::get('/ambiente', 'AppFiscal\\VendaController@ambiente')->middleware('authApp');
	});

	Route::group(['prefix' => 'notaFiscal'],function(){
		Route::post('/transmitir', 'AppFiscal\\NotaFiscalAppController@transmitir');
		Route::post('/cancelar', 'AppFiscal\\NotaFiscalAppController@cancelar');
		Route::post('/corrigir', 'AppFiscal\\NotaFiscalAppController@corrigir');
		Route::post('/consultar', 'AppFiscal\\NotaFiscalAppController@consultar');
		Route::get('/imprimir/{id}', 'AppFiscal\\NotaFiscalAppController@imprimir')->middleware('authApp');
		Route::get('/imprimirCorrecao/{id}', 'AppFiscal\\NotaFiscalAppController@imprimirCorrecao')->middleware('authApp');
		Route::get('/imprimirCancelada/{id}', 'AppFiscal\\NotaFiscalAppController@imprimirCancelada')->middleware('authApp');
		Route::get('/getXml/{id}', 'AppFiscal\\NotaFiscalAppController@getXml')->middleware('authApp');
		Route::get('/renderizarDanfe/{id}', 'AppFiscal\\NotaFiscalAppController@renderizarDanfe');

	});

	Route::group(['prefix' => 'vendasCaixa'],function(){
		Route::get('/', 'AppFiscal\\VendaCaixaController@index')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\VendaCaixaController@salvar');
		Route::get('/find/{id}', 'AppFiscal\\VendaCaixaController@getVenda')->middleware('authApp');
		Route::get('/renderizarDanfe/{id}', 'AppFiscal\\VendaCaixaController@renderizarDanfe')->middleware('authApp');
		Route::get('/ambiente', 'AppFiscal\\VendaCaixaController@ambiente')->middleware('authApp');
		Route::post('/filtroVendas', 'AppFiscal\\VendaCaixaController@filtroVendas');
		Route::post('/delete', 'AppFiscal\\VendaCaixaController@delete');
		Route::get('/cupomNaoFiscal/{id}', 'AppFiscal\\VendaCaixaController@cupomNaoFiscal')->middleware('authApp');

		Route::get('/teste', 'AppFiscal\\VendaCaixaController@teste');
		Route::get('/caixaAberto', 'AppFiscal\\VendaCaixaController@caixaAberto');
		Route::post('/abrirCaixa', 'AppFiscal\\VendaCaixaController@abrirCaixa');
		Route::post('/salvar-pre-venda', 'AppFiscal\\VendaCaixaController@salvarPreVenda');

		Route::get('/imprimir-pre-venda', 'AppFiscal\\VendaCaixaController@imprimirPreVenda')->middleware('authApp');
		Route::get('/preVendas', 'AppFiscal\\VendaCaixaController@preVendas')->middleware('authApp');

	});

	Route::group(['prefix' => 'nfce'],function(){
		Route::post('/transmitir', 'AppFiscal\\NfceAppController@transmitir');
		Route::get('/imprimir/{id}', 'AppFiscal\\NfceAppController@imprimir')->middleware('authApp');
		Route::post('/cancelar', 'AppFiscal\\NfceAppController@cancelar');
		Route::post('/consultar', 'AppFiscal\\NfceAppController@consultar');
		Route::get('/imprimir/{id}', 'AppFiscal\\NfceAppController@imprimir')->middleware('authApp');
		Route::get('/getXml/{id}', 'AppFiscal\\NfceAppController@getXml')->middleware('authApp');

	});

	Route::group(['prefix' => 'dfe'],function(){
		Route::get('/', 'AppFiscal\\DFeController@index')->middleware('authApp');
		Route::post('/manifestar', 'AppFiscal\\DFeController@manifestar');
		Route::get('/novosDocumentos', 'AppFiscal\\DFeController@novosDocumentos')->middleware('authApp');
		Route::post('/filtroManifestos', 'AppFiscal\\DFeController@filtroManifestos');
		Route::get('/renderizarDanfe/{id}', 'AppFiscal\\DFeController@renderizarDanfe')->middleware('authApp');
		Route::get('/find/{id}', 'AppFiscal\\DFeController@find')->middleware('authApp');
	});

	Route::group(['prefix' => 'inventarios'],function(){
		Route::get('/', 'AppFiscal\\InventarioController@index')->middleware('authApp');
		Route::get('/getItens/{id}', 'AppFiscal\\InventarioController@getItens');
		Route::get('/estados', 'AppFiscal\\InventarioController@estados')
		->middleware('authApp');
		Route::post('/salvarItem', 'AppFiscal\\InventarioController@salvarItem')->middleware('authApp');
		Route::post('/itemJaIncluso', 'AppFiscal\\InventarioController@itemJaIncluso')->middleware('authApp');
		Route::post('/removeItem', 'AppFiscal\\InventarioController@removeItem')->middleware('authApp');
	});

	Route::group(['prefix' => 'home'],function(){
		Route::get('/dadosGrafico', 'AppFiscal\\HomeController@dadosGrafico')->middleware('authApp');
	});

	Route::group(['prefix' => 'contasReceber'],function(){
		Route::get('/categoriasConta', 'AppFiscal\\ContaReceberController@categoriasConta')->middleware('authApp');
		Route::get('/', 'AppFiscal\\ContaReceberController@contas')->middleware('authApp');
		Route::get('/filtro', 'AppFiscal\\ContaReceberController@filtro')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\ContaReceberController@salvar');
		Route::post('/receber', 'AppFiscal\\ContaReceberController@receber');
		Route::post('/delete', 'AppFiscal\\ContaReceberController@delete');
	});

	Route::group(['prefix' => 'contasPagar'],function(){
		Route::get('/categoriasConta', 'AppFiscal\\ContaPagarController@categoriasConta')->middleware('authApp');
		Route::get('/', 'AppFiscal\\ContaPagarController@contas')->middleware('authApp');
		Route::get('/filtro', 'AppFiscal\\ContaPagarController@filtro')->middleware('authApp');
		Route::post('/salvar', 'AppFiscal\\ContaPagarController@salvar');
		Route::post('/pagar', 'AppFiscal\\ContaPagarController@pagar');
		Route::post('/delete', 'AppFiscal\\ContaPagarController@delete');
	});

	Route::group(['prefix' => 'caixa'],function(){
		Route::get('/', 'AppFiscal\\CaixaController@index')->middleware('authApp');
		Route::post('/suprimento', 'AppFiscal\\CaixaController@suprimento');
		Route::post('/sangria', 'AppFiscal\\CaixaController@sangria');
		Route::post('/fechar', 'AppFiscal\\CaixaController@fechar');
	});

});

//rotas de delivery
Route::middleware(['authDelivery'])->group(function () {
	Route::group(['prefix' => 'delivery'], function(){
		Route::get('/categorias', 'Delivery\\ProdutoController@all');
		Route::get('/produto/{id}', 'Delivery\\ProdutoController@find');
		Route::get('/config', 'Delivery\\ConfigController@index');
		Route::get('/cupom', 'Delivery\\ConfigController@cupom');

		Route::post('/endereco-save', 'Delivery\\ClienteController@enderecoSave');
		Route::post('/endereco-update', 'Delivery\\ClienteController@enderecoUpdate');
		Route::post('/update-endereco-padrao', 'Delivery\\ClienteController@updateEnderecoPadrao');

		Route::post('/login', 'Delivery\\ClienteController@login');
		Route::post('/send-code', 'Delivery\\ClienteController@sendCode');
		Route::post('/refresh-code', 'Delivery\\ClienteController@refreshCode');
		Route::post('/cliente-save', 'Delivery\\ClienteController@clienteSave');
		Route::post('/cliente-update', 'Delivery\\ClienteController@clienteUpdate');
		Route::post('/cliente-update-senha', 'Delivery\\ClienteController@clienteUpdateSenha');
		Route::get('/find-cliente', 'Delivery\\ClienteController@findCliente');
		Route::post('/pedido-save', 'Delivery\\PedidoController@save');

		Route::get('/adicionais', 'Delivery\\ProdutoController@adicionais');
		Route::get('/carrossel', 'Delivery\\ProdutoController@carrossel');
		Route::get('/bairros', 'Delivery\\ConfigController@bairros');
		Route::post('/gerar-qrcode', 'Delivery\\PedidoController@gerarQrcode');
		Route::post('/status-pix', 'Delivery\\PedidoController@consultaPix');
		Route::post('/ultimo-pedido-confirmar', 'Delivery\\PedidoController@ultimoPedidoParaConfirmar');
		Route::post('/consulta-pedido-lido', 'Delivery\\PedidoController@consultaPedidoLido');

	});
});

//rotas de delivery
Route::middleware(['authDelivery'])->group(function () {
	Route::group(['prefix' => 'cardapio'], function(){
		Route::post('/open-table', 'Cardapio\\PedidoController@openTable');
		Route::post('/get-pedido', 'Cardapio\\PedidoController@getPedido');
		Route::post('/pedido-save', 'Cardapio\\PedidoController@save');

		Route::get('/mesas', 'Cardapio\\PedidoController@mesas');
		// Route::get('/produto/{id}', 'Delivery\\ProdutoController@find');
		// Route::get('/config', 'Delivery\\ConfigController@index');
		// Route::get('/cupom', 'Delivery\\ConfigController@cupom');

		// Route::post('/endereco-save', 'Delivery\\ClienteController@enderecoSave');
		// Route::post('/endereco-update', 'Delivery\\ClienteController@enderecoUpdate');
		// Route::post('/update-endereco-padrao', 'Delivery\\ClienteController@updateEnderecoPadrao');

		// Route::post('/login', 'Delivery\\ClienteController@login');
		// Route::post('/send-code', 'Delivery\\ClienteController@sendCode');
		// Route::post('/refresh-code', 'Delivery\\ClienteController@refreshCode');
		// Route::post('/cliente-save', 'Delivery\\ClienteController@clienteSave');
		// Route::post('/cliente-update', 'Delivery\\ClienteController@clienteUpdate');
		// Route::post('/cliente-update-senha', 'Delivery\\ClienteController@clienteUpdateSenha');
		// Route::get('/find-cliente', 'Delivery\\ClienteController@findCliente');
		// Route::post('/pedido-save', 'Delivery\\PedidoController@save');

		// Route::get('/adicionais', 'Delivery\\ProdutoController@adicionais');
		// Route::get('/carrossel', 'Delivery\\ProdutoController@carrossel');
		// Route::get('/bairros', 'Delivery\\ConfigController@bairros');
		// Route::post('/gerar-qrcode', 'Delivery\\PedidoController@gerarQrcode');
		// Route::post('/status-pix', 'Delivery\\PedidoController@consultaPix');
		// Route::post('/ultimo-pedido-confirmar', 'Delivery\\PedidoController@ultimoPedidoParaConfirmar');
		// Route::post('/consulta-pedido-lido', 'Delivery\\PedidoController@consultaPedidoLido');

	});
});

//rotas do ecommerce
Route::middleware(['authEcommerce'])->group(function () {

	Route::group(['prefix' => '/produtos'], function(){
		Route::get('/categoria/{id}', 'Api\\ProdutoController@categoria');
		Route::get('/subcategorias/{id}', 'Api\\ProdutoController@subcategorias');
		Route::get('/destaques', 'Api\\ProdutoController@destaques');
		Route::get('/maisVendidos', 'Api\\ProdutoController@maisVendidos');
		Route::get('/novosProdutos', 'Api\\ProdutoController@novosProdutos');
		Route::get('/categoriasEmDestaque', 'Api\\ProdutoController@categoriasEmDestaque');
		Route::get('/categorias', 'Api\\ProdutoController@categorias');
		Route::get('/carrossel', 'Api\\ProdutoController@carrossel');
		Route::get('/porCategoria/{id}', 'Api\\ProdutoController@porCategoria');
		Route::get('/porId', 'Api\\ProdutoController@porId');
		Route::get('/pesquisa', 'Api\\ProdutoController@pesquisa');
		Route::post('/favorito', 'Api\\ProdutoController@favorito');
	});

	Route::group(['prefix' => '/config'], function(){
		Route::get('/', 'Api\\ConfigController@index');
		Route::post('/salvarEmail', 'Api\\ConfigController@salvarEmail');
		Route::post('/salvarContato', 'Api\\ConfigController@salvarContato');
	});

	Route::group(['prefix' => '/carrinho'], function(){
		Route::get('/itens', 'Api\\CarrinhoController@itens');
		Route::post('/salvarPedido', 'Api\\CarrinhoController@salvarPedido');
		Route::get('/getPedido', 'Api\\CarrinhoController@getPedido');
		Route::post('/processarPagamentoCartao', 'Api\\CarrinhoController@processarPagamentoCartao');
		Route::post('/processarPagamentoBoleto', 'Api\\CarrinhoController@processarPagamentoBoleto');
		Route::post('/processarPagamentoPix', 'Api\\CarrinhoController@processarPagamentoPix');

		Route::get('/getStatusPix', 'Api\\CarrinhoController@getStatusPix');
		Route::get('/calcularFrete', 'Api\\CarrinhoController@calcularFrete');
		Route::get('/getCupom', 'Api\\CarrinhoController@getCupom');

	});

	Route::group(['prefix' => '/clientes'], function(){
		Route::post('/salvar', 'Api\\ClienteController@salvar');
		Route::post('/atualizar', 'Api\\ClienteController@atualizar');
		Route::post('/cadastroDuplicado', 'Api\\ClienteController@cadastroDuplicado');
		Route::get('/findWithCart', 'Api\\ClienteController@findWithCart');
		Route::get('/findWithData', 'Api\\ClienteController@findWithData');
		Route::post('/alterarSenha', 'Api\\ClienteController@alterarSenha');
		Route::post('/login', 'Api\\ClienteController@login');
		Route::post('/esqueciMinhaSenha', 'Api\\ClienteController@esqueciMinhaSenha');
	});

	Route::group(['prefix' => '/enderecos'], function(){
		Route::post('/salvar', 'Api\\EnderecoController@salvar');
		Route::post('/atualizar', 'Api\\EnderecoController@atualizar');
	});

});

Route::group(['prefix' => 'pdv'], function () {
    Route::post('/login', 'PDVA\\LoginController@login');
    Route::post('/produtos', 'PDVA\\ProdutoController@produtos');
    Route::post('/categorias', 'PDVA\\ProdutoController@categorias');
    Route::post('/clientes', 'PDVA\\ClienteController@all');
    Route::post('/store-venda', 'PDVA\\VendaController@store');
    Route::get('/bandeiras-cartao', 'PDVA\\VendaController@bandeirasCartao');
    Route::get('/dados-empresa', 'PDVA\\LoginController@dadosEmpresa');
    Route::get('/contas-empresa', 'PDVA\\VendaController@contasEmpresa');
    Route::get('/tipos-pagamento', 'PDVA\\VendaController@tiposPagamento');
    Route::get('/get-caixa', 'PDVA\\VendaController@getCaixa');
    Route::get('/get-vendas-caixa', 'PDVA\\VendaController@getVendasCaixa');
    Route::post('/store-caixa', 'PDVA\\VendaController@storeCaixa');
    Route::post('/store-sangria', 'PDVA\\VendaController@storeSangria');
    Route::post('/store-suprimento', 'PDVA\\VendaController@storeSuprimento');
    Route::get('/data-home', 'PDVA\\VendaController@dataHome');
    Route::get('/lista-preco', 'PDVA\\ProdutoController@listaPreco');
    Route::get('/empresa-ativa', 'PDVA\\LoginController@empresaAtiva');
    Route::get('/locais-usuario', 'PDVA\\LoginController@locaisUsuario');

});

// Route::group(['prefix' => 'pdv'], function(){
// 	Route::post('/teste', 'Pdv\\ConfigController@teste');
// 	Route::group(['prefix' => '/login'], function(){
// 		Route::post('/', 'Pdv\\LoginController@login');
// 	});
// 	Route::group(['prefix' => '/produtos'], function(){
// 		Route::get('/', 'Pdv\\ProdutoController@index')->middleware('authPdv');
// 		Route::get('/limit', 'Pdv\\ProdutoController@limit')->middleware('authPdv');
// 		Route::get('/count', 'Pdv\\ProdutoController@count')->middleware('authPdv');
// 	});
// 	Route::group(['prefix' => '/config'], function(){
// 		Route::get('/', 'Pdv\\ConfigController@index')->middleware('authPdv');
// 	});
// 	Route::group(['prefix' => '/clientes'], function(){
// 		Route::get('/', 'Pdv\\ClienteController@index')->middleware('authPdv');
// 	});
// 	Route::group(['prefix' => '/vendedores'], function(){
// 		Route::get('/', 'Pdv\\VendedorController@index')->middleware('authPdv');
// 	});
// 	Route::group(['prefix' => '/pedidos'], function(){
// 		Route::get('/', 'Pdv\\PedidoController@index')->middleware('authPdv');
// 		Route::get('/setImpresso', 'Pdv\\PedidoController@setImpresso')->middleware('authPdv');
// 	});
// 	Route::group(['prefix' => '/vendas'], function(){
// 		Route::post('/salvar', 'Pdv\\VendaController@salvar')->middleware('authPdv');
// 		Route::get('/rascunhos', 'Pdv\\VendaController@rascunhos')->middleware('authPdv');
// 		Route::get('/teste', 'Pdv\\VendaController@teste');
// 	});
// 	Route::group(['prefix' => '/caixa'], function(){
// 		Route::get('/{usuario_id}', 'Pdv\\CaixaController@index')->middleware('authPdv');
// 		Route::post('/abrir', 'Pdv\\CaixaController@abrir')->middleware('authPdv');
// 	});
// });

//marktplace

Route::group(['prefix' => '/marktplace'], function(){
	Route::get('/lojas', 'MP\\LojaController@lojas');
	Route::get('/search', 'MP\\LojaController@search');
	Route::get('/banners', 'MP\\LojaController@banners');
	Route::get('/cupons', 'MP\\LojaController@cupons');
	Route::get('/loja', 'MP\\LojaController@getLoja');
	Route::get('/categorias', 'MP\\LojaController@categorias');
	Route::get('/categoriasDeProduto/{loja_id}', 'MP\\ProdutoController@categorias');
	Route::get('/adicionaisDeProduto', 'MP\\ProdutoController@adicionaisDeProduto');
	Route::post('/login', 'MP\\LoginController@login');

	Route::get('/avaliacoes', 'MP\\LojaController@avaliacoes');

	Route::group(['prefix' => '/loja'], function(){
		Route::post('/like', 'MP\\LojaController@like');
	});

	Route::group(['prefix' => '/cliente'], function(){
		Route::post('/cadastrar', 'MP\\LoginController@cadastrar');
		Route::post('/atualizar', 'MP\\LoginController@atualizar');
		Route::post('/salvarEndereco', 'MP\\LoginController@salvarEndereco');
		Route::post('/atualizarEndereco', 'MP\\LoginController@atualizarEndereco');
		Route::get('/find/{cliente_id}', 'MP\\LoginController@find');
		Route::post('/salvarImagem', 'MP\\LoginController@salvarImagem');

	});

	Route::group(['prefix' => '/pedidos'], function(){
		Route::post('/gerarPix', 'MP\\PedidoController@gerarPix');
		Route::post('/gerarPedido', 'MP\\PedidoController@gerarPedido');
		Route::post('/gerarPedidoCartao', 'MP\\PedidoController@gerarPedidoCartao');
		Route::get('/consultaPix/{id}', 'MP\\PedidoController@consultaPix');
		Route::get('/consultaPedidoLido/{id}', 'MP\\PedidoController@consultaPedidoLido');
		Route::get('/ultimoPedidoParaConfirmar/{user_id}', 'MP\\PedidoController@ultimoPedidoParaConfirmar');
		Route::get('/countPedidos/{user_id}', 'MP\\PedidoController@countPedidos');
		Route::get('/all', 'MP\\PedidoController@all');
		Route::post('/avaliar', 'MP\\PedidoController@avaliar');
	});
});

//app comandas

Route::group(['prefix' => '/controle_comandas'], function(){
	Route::get('/', 'ControleComanda\\HomeController@index')->middleware('authAppComanda');
	Route::get('/mesas', 'ControleComanda\\HomeController@mesas')->middleware('authAppComanda');
	Route::get('/tamanhosPizza', 'ControleComanda\\HomeController@tamanhosPizza')->middleware('authAppComanda');
	Route::post('/deleteComanda', 'ControleComanda\\HomeController@deleteComanda');
	Route::post('/deleteItem', 'ControleComanda\\HomeController@deleteItem');
	Route::post('/entregue', 'ControleComanda\\HomeController@entregue');
	Route::post('/abrirComanda', 'ControleComanda\\HomeController@abrirComanda');

	Route::get('/produtos', 'ControleComanda\\ProdutoController@index')->middleware('authAppComanda');
	Route::get('/pizzas', 'ControleComanda\\ProdutoController@pizzas')->middleware('authAppComanda');
	Route::get('/adicionais', 'ControleComanda\\ProdutoController@adicionais')->middleware('authAppComanda');
	Route::get('/categorias', 'ControleComanda\\ProdutoController@categorias')->middleware('authAppComanda');
	Route::post('/salvarItem', 'ControleComanda\\HomeController@salvarItem');

	Route::get('/pedido/{id}', 'ControleComanda\\HomeController@pedido')->middleware('authAppComanda');
	Route::post('/transferir-comanda', 'ControleComanda\\HomeController@transferirComanda');

});

//Route::post('/sync/data', 'SyncDataController@sync');

//use App\Http\Controllers\SyncDataController;
//Route::post('/sync/data', [SyncDataController::class, 'sync']);









Route::group(['prefix' => 'updates'], function () {
    Route::get('/', 'Updates\UpdateApiController@current')->name('api.updates.current');
    Route::get('/check', 'Updates\UpdateApiController@check')->name('api.updates.check');
    Route::post('/', 'Updates\UpdateApiController@apply')->name('api.updates.apply');
    Route::get('/history', 'Updates\UpdateApiController@history')->name('api.updates.history');
    Route::get('/{version}/logs/{log}', 'Updates\UpdateApiController@log')->name('api.updates.log');
});
