<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 1px solid #000; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $config->nome_fantasia }}</h2>
        <p>REQUISIÇÃO DE MATERIAIS / EPI - Nº {{ $item->id }}</p>
    </div>

    <p><strong>Funcionário:</strong> {{ $item->funcionario->nome }}</p>
    <p><strong>Data/Hora:</strong> {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</p>
    <p><strong>Status:</strong> {{ $item->status }}</p>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->itens as $i)
            <tr>
                <td>{{ $i->produto->nome }}</td>
                <td>{{ number_format($i->quantidade, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: center;">
        <p>_________________________________________________</p>
        <p>Assinatura do Funcionário</p>
    </div>
</body>
</html>