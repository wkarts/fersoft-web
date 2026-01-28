<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<h2>Acesso {{ env("APP_NAME") }}</h2>

	<p>Olá {{ $nome }}, utilize as credenciais abaixo para acessar</p>

	<a href="{{ env('PATH_URL') }}">{{ env('PATH_URL') }}</a>
	<h4>login: {{ $login }}</h4>
	<h4>senha: {{ $senha }}</h4>
</body>
</html>