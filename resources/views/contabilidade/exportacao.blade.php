@extends('default.layout')

@section('content')
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <h3 class="card-title">Exportação Contábil (Prosoft)</h3>
            <div class="card-toolbar d-flex align-items-center">
                <!-- Botão de Ajuda separado -->
                <button type="button" class="btn btn-outline-info font-weight-bold mr-4" data-toggle="modal" data-target="#modalManualContabil">
                    <i class="fa fa-question-circle"></i> Como funciona?
                </button>
              
                <!-- Botões de Geração agrupados -->
                <button type="button" class="btn btn-success mr-2" onclick="gerarArquivo()">
                    <i class="fa fa-file-export"></i> Gerar Arquivo Prosoft (.txt)
                </button>
              
                <button type="button" class="btn btn-success" onclick="gerarExcel()">
                    <i class="fa fa-file-excel"></i> Gerar Excel (CSV)
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="formFiltro" class="mb-5">
                  <div class="row align-items-end">
                      <div class="col-lg-3">
                          <label>Filial</label>
                          <select name="filial_id" class="form-control select2" id="filial_id" style="width: 100%;">
                              <option value="matriz">Matriz</option>
                              @foreach(__locaisAtivos() as $key => $local)
                                  <option value="{{ $key }}">{{ $local }}</option>
                              @endforeach
                          </select>
                      </div>
                    
             
                    <div class="col-lg-2">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="{{date('Y-m-01')}}">
                    </div>
                    <div class="col-lg-2">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" id="data_fim" class="form-control" value="{{date('Y-m-t')}}">
                    </div>
                    <div class="col-lg-2">
                        <button type="button" class="btn btn-primary" onclick="processarPrevia()">
                            <i class="fa fa-sync"></i> Processar Prévia
                        </button>
                    </div>
                </div>
            </form>

            <ul class="nav nav-tabs nav-tabs-line" role="tablist">
                @foreach(['pagar_prov'=>'Pagar (Prov)', 'pagar_baixa'=>'Pagar (Baixa)', 'receber_prov'=>'Receber (Prov)', 'receber_baixa'=>'Receber (Baixa)', 'manual'=>'Banco / Caixa'] as $id => $label)
                <li class="nav-item"><a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#tab_{{$id}}">{{$label}}</a></li>
                @endforeach
            </ul>

            <div class="tab-content mt-5">
                @foreach(['pagar_prov', 'pagar_baixa', 'receber_prov', 'receber_baixa', 'manual'] as $id)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab_{{$id}}">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabela_{{$id}}">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Histórico</th>
                                    <th>Crédito</th>
                                    <th>Débito</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() { 
        $('.select2').select2(); 
    });

    function processarPrevia() {
        // Feedback visual ajustado para 7 colunas (colspan="7")
        $('tbody').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Carregando...</td></tr>');

        $.post("{{ route('contabilidade.exportacao.previa') }}", {
            _token: "{{ csrf_token() }}",
            filial_id: $('#filial_id').val(),
            data_inicio: $('#data_inicio').val(),
            data_fim: $('#data_fim').val()
        }, function(response) {
            ['pagar_prov', 'pagar_baixa', 'receber_prov', 'receber_baixa', 'manual'].forEach(id => {
                renderizarTabela('#tabela_' + id + ' tbody', response[id] || []);
            });
        }).fail(function(xhr) {
            alert("Erro detalhado: " + xhr.status + " - " + xhr.responseText);
            console.error(xhr.responseText);
        });
    }
  
  function gerarArquivo() {
        // 1. Trava de Segurança (Aviso de ERRO)
        if ($('.badge-danger').length > 0) {
            let continuar = confirm("⚠️ ATENÇÃO: Existem lançamentos com ERRO na tela.\n\nSe você gerar o arquivo agora, as contas contábeis em falta irão em branco e o contador pode rejeitar essas linhas.\n\nDeseja gerar o arquivo mesmo assim?");
            if (!continuar) {
                return; 
            }
        }

        // 2. Pega os valores dos filtros
        let filial = $('#filial_id').val();
        let inicio = $('#data_inicio').val();
        let fim = $('#data_fim').val();
        let url = "{{ route('contabilidade.exportacao.gerar') }}";
        let token = "{{ csrf_token() }}"; // Precisamos enviar o token de segurança no POST

        // 3. Cria um formulário invisível para forçar o método POST
        let form = $('<form>', {
            'action': url,
            'method': 'POST',
            'target': '_blank' // Faz abrir em uma nova aba, igual ao window.open
        }).append($('<input>', {
            'name': '_token',
            'value': token,
            'type': 'hidden'
        })).append($('<input>', {
            'name': 'filial_id',
            'value': filial,
            'type': 'hidden'
        })).append($('<input>', {
            'name': 'data_inicio',
            'value': inicio,
            'type': 'hidden'
        })).append($('<input>', {
            'name': 'data_fim',
            'value': fim,
            'type': 'hidden'
        }));

        // Adiciona o form na tela, envia e depois remove para não deixar "sujeira"
        $('body').append(form);
        form.submit();
        form.remove();
    }

    function renderizarTabela(seletor, dados) {
    let html = '';
    dados.forEach(item => {
        // Define a cor da etiqueta de acordo com o status contábil
        let statusClass = 'badge-danger'; // Vermelho para ERRO
        if (item.status === 'OK') {
            statusClass = 'badge-success'; // Verde para OK
        } else if (item.status === 'SEM PROVISÃO') {
            statusClass = 'badge-warning text-white'; // Amarelo para Sem Provisão
        }
        
        let btnCategoria = item.categoria_id ? 
            `<a href="/categoriasConta/edit/${item.categoria_id}" target="_blank" class="btn btn-icon btn-light-warning btn-sm mr-1" title="Editar Categoria"><i class="fa fa-tags"></i></a>` : '';

        let btnBanco = item.conta_banco_id ? 
            `<a href="/contas-empresa/${item.conta_banco_id}/edit" target="_blank" class="btn btn-icon btn-light-info btn-sm" title="Editar Banco"><i class="fa fa-university"></i></a>` : '';

        html += `<tr>
            <td class="text-nowrap">${item.data}</td>
            <td><strong>${item.entidade}</strong><br><small class="text-muted">${item.historico}</small></td>
            <td><span class="text-danger font-weight-bold">${item.credito}</span></td>
            <td><span class="text-primary font-weight-bold">${item.debito}</span></td>
            <td>${item.categoria}</td>
            <td class="text-nowrap font-weight-bold">R$ ${item.valor}</td>
            <td class="text-nowrap">
                <div class="d-flex align-items-center">
                    <span class="badge ${statusClass} mr-2">${item.status}</span>
                    ${btnCategoria}
                    ${btnBanco}
                </div>
            </td>
        </tr>`;
    });
    
    $(seletor).html(html || '<tr><td colspan="7" class="text-center text-muted">Nenhum lançamento encontrado.</td></tr>');
}
  
  function gerarExcel() {
        if ($('.badge-danger').length > 0) {
            let continuar = confirm("⚠️ ATENÇÃO: Existem lançamentos com ERRO na tela.\n\nDeseja gerar o arquivo Excel mesmo assim?");
            if (!continuar) {
                return; 
            }
        }

        let filial = $('#filial_id').val();
        let inicio = $('#data_inicio').val();
        let fim = $('#data_fim').val();
        let url = "{{ route('contabilidade.exportacao.excel') }}";
        let token = "{{ csrf_token() }}"; 

        let form = $('<form>', {
            'action': url,
            'method': 'POST',
            'target': '_blank'
        }).append($('<input>', { 'name': '_token', 'value': token, 'type': 'hidden' }))
          .append($('<input>', { 'name': 'filial_id', 'value': filial, 'type': 'hidden' }))
          .append($('<input>', { 'name': 'data_inicio', 'value': inicio, 'type': 'hidden' }))
          .append($('<input>', { 'name': 'data_fim', 'value': fim, 'type': 'hidden' }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
  
</script>
<!-- Modal do Manual Contábil -->
<div class="modal fade" id="modalManualContabil" tabindex="-1" role="dialog" aria-labelledby="modalManualContabilTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalManualContabilTitle"><i class="fa fa-book"></i> Manual de Operação: Exportação Contábil</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6><strong>1. Como a Rotina Funciona?</strong></h6>
                <p>O FerSoft lê todos os seus lançamentos financeiros (pagamentos, recebimentos e Banco/Caixa) e os classifica automaticamente para a contabilidade. O segredo dessa automação são as <strong>Categorias Financeiras</strong>. É a categoria que diz ao sistema qual conta contábil usar.</p>
                <hr>

                <h6><strong>2. Entendendo os Status e Erros</strong></h6>
                <p>A tela faz uma auditoria em tempo real. Preste atenção às etiquetas:</p>
                <ul>
                    <li><span class="badge badge-success">OK</span> O lançamento está perfeito e pronto para exportar.</li>
                    <li><span class="badge badge-warning">SEM PROVISÃO</span> Ocorreu uma baixa no banco, mas a conta a pagar/receber original está sem a conta contábil configurada.</li>
                    <li><span class="badge badge-danger">ERRO</span> O lançamento não tem conta contábil.</li>
                </ul>
                <p><strong>🛠️ Como corrigir os erros?</strong> Vá até o menu de <em>Categorias de Conta</em>, procure a categoria que está em branco na tela, edite-a e preencha as "Contas Contábeis" (Conta Provisão, Despesa/Receita).</p>
                <hr>

                <h6><strong>3. Regra dos Adiantamentos</strong></h6>
                <p>Quando fizer ou receber um adiantamento, <strong>sempre</strong> utilize as categorias específicas de "Adiantamento de Clientes" ou "Adiantamento a Fornecedores". Isso garante que o valor seja classificado corretamente no seu Patrimônio (Ativo/Passivo) e não misturado com suas Vendas/Custos normais.</p>
                <hr>

                <h6><strong>4. Exportação</strong></h6>
                <ul>
                    <li><strong>Prosoft (.txt):</strong> Gera o arquivo posicional no padrão exigido pelo sistema contábil Prosoft. Verifique sempre se selecionou a Filial correta no filtro (Matriz = 001, Filial = 002).</li>
                    <li><strong>Excel (.csv):</strong> Gera uma planilha universal para conferência. Abre separada por colunas (Data, Histórico, Valor, Categoria, etc).</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Entendi, Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection