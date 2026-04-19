<!DOCTYPE html>
<html>
<head>
    <style>
        @page { 
            margin: 0.5cm; 
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            padding-bottom: 10px;
            border-bottom: 2px solid #3699FF;
        }
        .header h2 { 
            margin: 0; 
            color: #3699FF; 
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p { 
            margin: 5px 0; 
            font-size: 11px; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 9px; /* Diminuído levemente para garantir respiro nas bordas */
        }
        
        /* Ajuste das Bordas: Escurecidas para maior visibilidade */
        th, td { 
            border: 1px solid #b5b5c3; /* Cinza mais escuro para destacar as linhas */
            padding: 6px 3px; 
            vertical-align: middle;
        }
        
        th { 
            background-color: #f3f6f9; 
            color: #181c32;
            text-transform: uppercase;
            font-weight: bold;
        }

        /* Zebra e Alinhamentos */
        tbody tr:nth-child(even) { background-color: #fcfcfc; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        /* Cores de Destaque com Bordas Reforçadas */
        .bg-entrada { 
            background-color: #d9eaff !important; /* Azul um tom acima para contraste */
            color: #004a99; 
            border-left: 1px solid #84a9d3 !important;
            border-right: 1px solid #84a9d3 !important;
        }
        .bg-saida { 
            background-color: #fff4de !important; 
            color: #8a6d3b; 
            border-left: 1px solid #e4d5b7 !important;
            border-right: 1px solid #e4d5b7 !important;
        }

        .footer { 
            margin-top: 15px; 
            padding: 12px;
            background-color: #3699FF;
            color: white;
            text-align: right; 
            font-size: 13px; 
            border-radius: 3px;
        }
        
        small { color: #7e8299; font-size: 7.5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Inventário Fiscal de Estoque</h2>
        <p><strong>{{ $empresa->nome }}</strong> | CNPJ: {{ $empresa->cnpj }}</p>
        <p>Itens: {{ count($estoque) }} | Emissão: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 22%;">Produto / Referência</th>
                <th rowspan="2" style="width: 12%;">Categoria</th>
                <th rowspan="2" style="width: 7%;">NCM</th>
                <th colspan="3" class="bg-entrada">CST Entrada (Compra)</th>
                <th colspan="3" class="bg-saida">CST Saída (Venda)</th>
                <th rowspan="2" style="width: 7%;">Saldo</th>
                <th rowspan="2" style="width: 8%;">Vl. Unit.</th>
                <th rowspan="2" style="width: 10%;">Subtotal</th>
            </tr>
            <tr>
                <th class="bg-entrada">ICMS</th>
                <th class="bg-entrada">PIS</th>
                <th class="bg-entrada">COF</th>
                <th class="bg-saida">ICMS</th>
                <th class="bg-saida">PIS</th>
                <th class="bg-saida">COF</th>
            </tr>
        </thead>
        <tbody>
            @php $totalGeral = 0; @endphp
            @foreach($estoque as $e)
                @php 
                    $subTotal = $e->quantidade * $e->valor_compra;
                    $totalGeral += $subTotal;
                @endphp
                <tr>
                    <td>
                        <span class="font-bold">{{ $e->produto_nome }}</span><br>
                        <small>REF: {{ $e->referencia ?? 'N/A' }}</small>
                    </td>
                    <td class="text-center">{{ $e->categoria_nome }}</td>
                    <td class="text-center">{{ $e->NCM }}</td>
                    
                    <td class="text-center bg-entrada font-bold">{{ $e->cst_icms_entrada }}</td>
                    <td class="text-center bg-entrada font-bold">{{ $e->cst_pis_entrada }}</td>
                    <td class="text-center bg-entrada font-bold">{{ $e->cst_cofins_entrada }}</td>
                    
                    <td class="text-center bg-saida font-bold">{{ $e->cst_icms_saida }}</td>
                    <td class="text-center bg-saida font-bold">{{ $e->cst_pis_saida }}</td>
                    <td class="text-center bg-saida font-bold">{{ $e->cst_cofins_saida }}</td>
                    
                    <td class="text-right font-bold">{{ number_format($e->quantidade, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($e->valor_compra, 2, ',', '.') }}</td>
                    <td class="text-right font-bold">R$ {{ number_format($subTotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <strong>VALOR TOTAL PATRIMONIAL: R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong>
    </div>
</body>
</html>