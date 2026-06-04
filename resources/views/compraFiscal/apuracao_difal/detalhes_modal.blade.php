<table class="table table-sm table-bordered table-striped">
    <thead>
        <tr class="bg-light">
            <th>Nota</th>
            <th>Fornecedor</th>
            <th>Vlr. Operação</th>
            <th>Base Dupla</th>
            <th>Aliq. Ori/Des</th>
            <th>DIFAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i)
        <tr>
            <td>{{ $i->numero_nota }}</td>
            <td>{{ $i->emitente_nome }}</td>
            <td>R$ {{ number_format($i->valor_operacao, 2, ',', '.') }}</td>
            <td>R$ {{ number_format($i->base_calculo_dupla, 2, ',', '.') }}</td>
            <td>{{ $i->aliquota_origem }}% / {{ $i->aliquota_destino }}%</td>
            <td class="text-danger font-weight-bold">R$ {{ number_format($i->valor_difal, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>