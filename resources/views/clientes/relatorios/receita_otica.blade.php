<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receita Ótica - {{ $cliente->razao_social }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom: 10px; }
        .section-title { background: #eee; padding: 5px; font-weight: bold; margin-top: 15px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: center; }
        .footer { margin-top: 50px; text-align: center; }
        .sign { border-top: 1px solid #000; width: 300px; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>

<div class="header">
    <h2>{{ $config->nome_fantasia }}</h2>
    <p>{{ $config->logradouro }}, {{ $config->numero }} - {{ $config->municipio }}/{{ $config->uf }}</p>
    <p>Telefone: {{ $config->telefone }} | CNPJ: {{ $config->cnpj }}</p>
</div>

<h3 style="text-align: center;">RECEITA ÓTICA</h3>

<p><strong>Cliente:</strong> {{ $cliente->razao_social }}</p>
<p><strong>CPF/CNPJ:</strong> {{ $cliente->cpf_cnpj }} | <strong>Data:</strong> {{ $receita->data }}</p>
<p><strong>Médico:</strong> {{ $receita->medico ?? 'Não informado' }}</p>

<div class="section-title">GRAU DE LONGE</div>
<table>
    <thead>
        <tr>
            <th>OLHO</th>
            <th>ESFÉRICO</th>
            <th>CILÍNDRICO</th>
            <th>EIXO</th>
            <th>DNP</th>
            <th>ALTURA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>DIREITO (OD)</td>
            <td>{{ $receita->esf_od_longe }}</td>
            <td>{{ $receita->cil_od_longe }}</td>
            <td>{{ $receita->eixo_od_longe }}</td>
            <td>{{ $receita->dnp_od_longe }}</td>
            <td>{{ $receita->altura_od_longe }}</td>
        </tr>
        <tr>
            <td>ESQUERDO (OE)</td>
            <td>{{ $receita->esf_oe_longe }}</td>
            <td>{{ $receita->cil_oe_longe }}</td>
            <td>{{ $receita->eixo_oe_longe }}</td>
            <td>{{ $receita->dnp_oe_longe }}</td>
            <td>{{ $receita->altura_oe_longe }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">GRAU DE PERTO / ADICIONAL</div>
<table>
    <thead>
        <tr>
            <th>OLHO</th>
            <th>ESFÉRICO</th>
            <th>CILÍNDRICO</th>
            <th>EIXO</th>
            <th>DNP</th>
            <th>ALTURA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>DIREITO (OD)</td>
            <td>{{ $receita->esf_od_perto }}</td>
            <td>{{ $receita->cil_od_perto }}</td>
            <td>{{ $receita->eixo_od_perto }}</td>
            <td>{{ $receita->dnp_od_perto }}</td>
            <td>{{ $receita->altura_od_perto }}</td>
        </tr>
        <tr>
            <td>ESQUERDO (OE)</td>
            <td>{{ $receita->esf_oe_perto }}</td>
            <td>{{ $receita->cil_oe_perto }}</td>
            <td>{{ $receita->eixo_oe_perto }}</td>
            <td>{{ $receita->dnp_oe_perto }}</td>
            <td>{{ $receita->altura_oe_perto }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">DETALHES DOS ÓCULOS</div>
<table>
    <tr>
        <td style="text-align: left;"><strong>Lente:</strong> {{ $receita->lente }}</td>
        <td><strong>Valor:</strong> R$ {{ number_format($receita->valor_lente, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="text-align: left;"><strong>Armação:</strong> {{ $receita->armacao }}</td>
        <td><strong>Valor:</strong> R$ {{ number_format($receita->valor_armacao, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: left;"><strong>Referência/Observação:</strong> {{ $receita->referencia }}</td>
    </tr>
</table>

<div class="footer">
    <div class="sign">Assinatura / Carimbo</div>
    <p style="font-size: 10px; margin-top: 20px;">Documento gerado por FerSoft ERP em {{ date('d/m/Y H:i') }}</p>
</div>

</body>
</html>