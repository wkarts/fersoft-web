@extends('default.layout')
@section('content')
<div class="container-fluid">
    <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title">Nova Apuração de Difal</h3></div>
        <div class="card-body">
            <form id="form-calcular">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <label>Data Inicial</label>
                        <input type="date" name="data_inicial" id="data_inicial" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Data Final</label>
                        <input type="date" name="data_final" id="data_final" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Fornecedor (Pagamento)</label>
                        <select name="fornecedor_id" id="fornecedor_id" class="form-control select2" style="width: 100%;">
                            <option value="">Selecione a SEFAZ</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f->id }}">{{ $f->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Categoria Financeira</label>
                        <select name="categoria_id" id="categoria_id" class="form-control select2" style="width: 100%;">
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Buscar Notas</button>
                    <a href="{{ route('compraFiscal.difal.index') }}" class="btn btn-default">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mt-3" id="resultado-calculo" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-sm table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr><th>Nota</th><th>Fornecedor</th><th>Vlr. Nota</th><th>Aliq. Origem</th><th>Aliq. Destino</th><th>Base Dupla</th><th>DIFAL</th></tr>
                        </thead>
                        <tbody id="corpo-conferencia"></tbody>
                        <tfoot>
                            <tr class="bg-warning">
                                <td colspan="6" class="text-right"><strong>TOTAL DO DIFAL:</strong></td>
                                <td id="total-apurado" class="font-weight-bold text-danger">R$ 0,00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="card-footer text-right">
                    <button type="button" id="btn-salvar-apuracao" class="btn btn-success btn-lg"><i class="fa fa-save"></i> Confirmar e Gravar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('.select2').select2();

    $('#form-calcular').on('submit', function(e) {
        e.preventDefault();
        $.post("{{ route('compraFiscal.difal.processar') }}", $(this).serialize(), function(response) {
            let html = '';
            response.itens.forEach(item => {
                html += `<tr><td>${item.numero_nota}</td><td>${item.emitente_nome}</td><td>${item.valor_operacao}</td><td>${item.aliquota_origem}%</td><td>${item.aliquota_destino}%</td><td>${item.base_calculo_dupla}</td><td class="text-danger font-weight-bold">${item.valor_difal}</td></tr>`;
            });
            $('#corpo-conferencia').html(html);
            $('#total-apurado').text('R$ ' + response.total_difal);
            $('#resultado-calculo').fadeIn();
        });
    });

    $('#btn-salvar-apuracao').click(function() {
        let fornecedor = $('#fornecedor_id').val();
        if(!fornecedor) { alert("Selecione o fornecedor!"); return; }

        if(confirm('Deseja finalizar?')) {
            $.post("{{ route('compraFiscal.difal.processar') }}", {
                _token: "{{ csrf_token() }}",
                data_inicial: $('#data_inicial').val(),
                data_final: $('#data_final').val(),
                fornecedor_id: fornecedor,
                categoria_id: $('#categoria_id').val(),
                confirmar_gravacao: true 
            }, function() {
                alert("Gravado com sucesso!");
                window.location.href = "{{ route('compraFiscal.difal.index') }}";
            });
        }
    });
});
</script>
@endsection