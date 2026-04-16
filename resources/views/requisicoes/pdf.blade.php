<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ficha de EPI</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 10px; }
        .text-justify { text-align: justify; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        .legenda { margin-top: 20px; font-size: 10px; }
        .assinaturas { margin-top: 40px; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        {{ $empresa }}<br>
        CONTROLE DE FORNECIMENTO DE EQUIPAMENTO DE PROTEÇÃO INDIVIDUAL E UNIFORME
    </div>

    <div class="text-justify">
        Declaro haver recebido gratuitamente da empresa os EPI mencionados abaixo, para utilização durante minha jornada de trabalho e fui treinado para o uso correto, guarda e conservação do mesmo.
    </div>

    <div class="text-justify">
        <strong>É de meu conhecimento que:</strong><br>
        > O EPI é de uso individual;<br>
        > É de minha responsabilidade a guarda e conservação dos EPI fornecidos pelo empregador;<br>
        > Estou ciente que sofrerei penalidades administrativas por quaisquer danos ao EPI, devido a utilização incorreta...<br>
        > Como funcionário tenho o compromisso de devolver os EPI para realização da troca...<br>
        > A utilização de EPI é obrigatória durante a realização de atividades com riscos...
    </div>

    <div>
        <strong>Nome do Colaborador:</strong> {{ $requisicao->funcionario->nome }}<br>
        <strong>Assinatura do funcionário:</strong> ____________________________________
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Qtde</th>
                <th>Descrição do Material</th>
                <th>Nº C.A.</th>
                <th>Uso</th>
                <th>Motivo</th>
                <th>Fabricante</th>
                <th>Assinatura</th>
                <th>Resp Entrega</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requisicao->itens as $item) {{-- Ajuste conforme seu relacionamento --}}
            <tr>
                <td>{{ \Carbon\Carbon::parse($requisicao->data_requisicao)->format('d/m/Y') }}</td>
                <td>{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                <td>{{ $item->produto->nome }}</td>
                <td>CA: {{ $item->produto->ca_numero ?? '-' }}</td>
                <td>{{ $item->uso }}</td>
                <td>{{ $item->motivo }}</td>
                <td>{{ $item->produto->fabricante ?? '-' }}</td>
                <td></td>
                <td>{{ $requisicao->responsavel->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legenda">
        <strong>MOTIVO:</strong> A = ADMISSÃO | S = SUBSTITUIÇÃO | D = DOLO | E = EVENTUAL | P = PERMANENTE<br>
        <strong>USO:</strong> 1 = EVENTUAL | 2 = PERMANENTE
    </div>

</body>
</html>