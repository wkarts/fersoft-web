<html>
<head>
	<title></title>
</head>
<body>
	<h2>Olá <strong>{{ $cashBackCliente->cliente->razao_social }}</strong></h2>

	<p>O valor do seu CashBack é de <strong>R$ {{ moeda($cashBackCliente->valor_credito) }}, com validade até {{ __date($cashBackCliente->data_expiracao, 0) }}</strong></p>

</body>
</html>