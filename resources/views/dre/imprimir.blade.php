{{-- resources/views/dre/imprimir.blade.php --}}
@extends('relatorios.default')

@section('content')
    <style>
        /* ===== Formato A4 e margens ===== */
        @page { size: A4 portrait; margin:2cm }
        body { margin:0; padding:0; }

        /* ===== Tipografia ===== */
        body, .content {
            font-family: "Times New Roman", serif;
            color: #222;
            font-size: 10pt;
        }
        .header, .footer {
            position: fixed;
            left: 0;
            right: 0;
            text-align: center;
            font-family: Arial, sans-serif;
            color: #444;
        }
        .header {
            top: 0;
            border-bottom: 1px solid #666;
            padding: 0.3cm 0;
        }
        .footer {
            bottom: 0;
            border-top: 1px solid #666;
            padding: 0.3cm 0;
            font-size: 9pt;
        }
        .content { margin: 4cm 0 3cm; }

        h1 { font-size: 16pt; margin: .2em 0; }
        h2 { font-size: 12pt; margin: .1em 0; font-weight: normal; }

        /* ===== Tabela de DRE ===== */
        .dre-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: .5cm;
        }
        .dre-table th, .dre-table td {
            border: 1px solid #aaa;
            padding: 6px 8px;
        }
        .dre-table thead th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: left;
        }
        .dre-table tbody tr.category td {
            background: #e8e8e8;
            font-weight: bold;
        }
        .dre-table td.amount {
            text-align: right;
            font-family: "Courier New", monospace;
        }
        /* créditos (positivos) e débitos (negativos) */
        .dre-table td.credit { color: #006600; }
        .dre-table td.debit  { color: #cc0000; }

        /* ===== Totais ===== */
        .dre-table tfoot td {
            font-weight: bold;
            background: #f9f9f9;
        }
        .dre-table tfoot tr.profit td {
            background: #dff0d8;
        }
        .dre-table tfoot tr.loss td {
            background: #f2dede;
        }
    </style>

    {{-- Cabeçalho --}}
    <div class="header">
        <h1>Demonstrativo de Resultado do Exercício</h1>
        <h2>Período: {{ $dre->inicio->format('d/m/Y') }} – {{ $dre->fim->format('d/m/Y') }}</h2>
        @if($tributacao->regime != 1)
            <h2>Alíquota de Imposto: {{ number_format($dre->percentual_imposto,2,',','.') }} %</h2>
        @endif
        @if($dre->observacao)
            <h2>Observação: {{ $dre->observacao }}</h2>
        @endif
    </div>

    {{-- Conteúdo principal --}}
    <div class="content">
        <table class="dre-table">
            <thead>
            <tr>
                <th>Descrição</th>
                <th style="width:120px">Valor (R$)</th>
                <th style="width:120px">Saldo (R$)</th>
            </tr>
            </thead>
            <tbody>
            @php $saldo = 0; @endphp

            @foreach($dre->categorias as $idx => $cat)
                @php
                    // apenas para exibir o subtítulo com (+) ou (–)
                    $sign = in_array($idx, [0,2]) ? '+' : '-';
                @endphp
                <tr class="category">
                    <td colspan="3">{{ $cat->nome }} ({{ $sign }})</td>
                </tr>

                @foreach($cat->lancamentos as $l)
                    @php
                        // na saldo usamos $aj; para exibir o valor usamos $raw:
                        if ($idx === 0) {
                            // receitas
                            $aj  = $l->valor;
                            $raw = $l->valor;
                        } elseif ($idx === 1 || $idx >= 3) {
                            // deduções e despesas
                            $aj  = - $l->valor;
                            $raw = - $l->valor;
                        } elseif ($idx === 2 && $l->nome === 'Faturamento Líquido') {
                            // Faturamento Líquido não afeta o saldo, mas exibimos o valor positivo
                            $aj  = 0;
                            $raw = $l->valor;
                        } else {
                            // caso de lançamentos extras em categoria "líquido"
                            $aj  = $l->valor;
                            $raw = $l->valor;
                        }
                        $saldo += $aj;
                    @endphp
                    <tr>
                        <td>{{ $l->nome }}</td>
                        <td class="amount {{ $raw >= 0 ? 'credit' : 'debit' }}">
                            {{ number_format($raw, 2, ',', '.') }}
                        </td>
                        <td class="amount {{ $saldo >= 0 ? 'credit' : 'debit' }}">
                            {{ number_format($saldo, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
            <tfoot>
            @php
                $lucro  = $dre->lucro_prejuizo;
                $classe = $lucro >= 0 ? 'profit' : 'loss';
            @endphp
            <tr class="{{ $classe }}">
                <td>Lucro (Prejuízo) no Período</td>
                <td class="amount {{ $lucro >= 0 ? 'credit' : 'debit' }}">
                    {{ number_format($lucro, 2, ',', '.') }}
                </td>
                <td class="amount {{ $saldo >= 0 ? 'credit' : 'debit' }}">
                    {{ number_format($saldo, 2, ',', '.') }}
                </td>
            </tr>
            </tfoot>
        </table>
    </div>

    {{-- Rodapé --}}
    <div class="footer">
        <small>Emitido em {{ now()->format('d/m/Y H:i') }}</small>
    </div>
@endsection
