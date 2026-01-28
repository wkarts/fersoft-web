<h1>Envio de XML da CTeOs {{$cte->numero_emissao}}</h1>

<h2>Emissão: {{ \Carbon\Carbon::parse($cte->created_at)->format('d/m/Y H:i:s')}}</h2>


<h4>Att, {{$usuario}}</h4>