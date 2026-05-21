<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>OS Técnica #{{ $os->id }}</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 14px; 
            color: #222; 
            background-color: #f0f0f0; 
            margin: 0; 
            padding: 20px;
        }
        .container {
            max-width: 800px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 40px; 
            border: 1px solid #ccc;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 5px 0; color: #111; font-size: 26px; text-transform: uppercase; }
        .header h3 { margin: 5px 0; color: #555; font-size: 18px; }
        
        .info-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; background-color: #fcfcfc; border-radius: 5px; }
        .info-box p { margin: 5px 0; }
        
        .section-title { font-weight: bold; text-transform: uppercase; margin-top: 25px; color: #333; font-size: 16px; border-left: 5px solid #3699FF; padding-left: 10px;}
        
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; text-align: center; }
        .table th { background-color: #f3f6f9; border: 1px solid #ccc; padding: 10px; font-weight: bold; color: #333; text-transform: uppercase;}
        .table td { border: 1px solid #ccc; padding: 10px; font-size: 15px;}
        .table td.lado { font-weight: bold; background-color: #fafafa; }

        .notes { border: 1px dashed #999; padding: 15px; background-color: #fffde7; margin-top: 20px; border-radius: 5px;}

        @media print {
            body { background-color: #fff; padding: 0; }
            .container { box-shadow: none; border: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ $config->nome ?? 'LABORATÓRIO ÓTICO' }}</h2>
            <h3>ORDEM DE SERVIÇO TÉCNICA - #{{ str_pad($os->id, 5, '0', STR_PAD_LEFT) }}</h3>
            <p style="margin:0; font-size: 13px;">Emissão: {{ \Carbon\Carbon::parse($os->data)->format('d/m/Y') }} | Entrega Prevista: <strong>{{ \Carbon\Carbon::parse($os->data_entrega)->format('d/m/Y') }}</strong></p>
        </div>

        <div class="info-box">
            <p><strong>Cliente:</strong> {{ $os->cliente->razao_social ?? 'Não informado' }}</p>
            <p><strong>Médico / CRM:</strong> {{ $os->medico ?? 'N/A' }}</p>
        </div>

        <div class="section-title">Receita: Longe</div>
        <table class="table">
            <tr>
                <th>Lado</th><th>Esférico</th><th>Cilíndrico</th><th>Eixo</th><th>DNP</th><th>DP</th>
            </tr>
            <tr>
                <td class="lado">Direito (OD)</td>
                <td>{{ $os->esf_od_longe }}</td><td>{{ $os->cil_od_longe }}</td><td>{{ $os->eixo_od_longe }}</td><td>{{ $os->dnp_od_longe }}</td><td>{{ $os->dp_od_longe }}</td>
            </tr>
            <tr>
                <td class="lado">Esquerdo (OE)</td>
                <td>{{ $os->esf_oe_longe }}</td><td>{{ $os->cil_oe_longe }}</td><td>{{ $os->eixo_oe_longe }}</td><td>{{ $os->dnp_oe_longe }}</td><td>{{ $os->dp_oe_longe }}</td>
            </tr>
        </table>

        <div class="section-title">Receita: Perto</div>
        <table class="table">
            <tr>
                <th>Lado</th><th>Esférico</th><th>Cilíndrico</th><th>Eixo</th><th>Adição</th><th>Altura</th>
            </tr>
            <tr>
                <td class="lado">Direito (OD)</td>
                <td>{{ $os->esf_od_perto }}</td><td>{{ $os->cil_od_perto }}</td><td>{{ $os->eixo_od_perto }}</td><td>{{ $os->adicao_od_perto }}</td><td>{{ $os->altura_od_perto }}</td>
            </tr>
            <tr>
                <td class="lado">Esquerdo (OE)</td>
                <td>{{ $os->esf_oe_perto }}</td><td>{{ $os->cil_oe_perto }}</td><td>{{ $os->eixo_oe_perto }}</td><td>{{ $os->adicao_oe_perto }}</td><td>{{ $os->altura_oe_perto }}</td>
            </tr>
        </table>

        <div class="section-title">Especificações dos Materiais</div>
        <div class="info-box" style="margin-top: 10px;">
            <p><strong>Armação:</strong> {{ $os->armacao ?? 'Não especificada' }}</p>
            <p><strong>Lente:</strong> {{ $os->lente ?? 'Não especificada' }}</p>
            <p><strong>Tipo de Lente:</strong> {{ $os->tipo_lente ?? 'N/A' }}</p>
            <p><strong>Tratamento:</strong> {{ $os->tratamento ?? 'N/A' }}</p>
        </div>

        @if($os->observacao)
        <div class="notes">
            <strong>Observações Gerais do Pedido:</strong><br>
            {{ $os->observacao }}
        </div>
        @endif
    </div>

    <script>
        // Auto-impressão
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>