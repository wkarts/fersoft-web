<h1>Envio de XML da NFSe {{$nfse->numero_nfse}}</h1>

<h2>Emissão: {{ \Carbon\Carbon::parse($nfse->created_at)->format('d/m/Y H:i:s')}}</h2>


<h4>Att, {{$usuario}}</h4>