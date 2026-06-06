@extends('default.layout')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Nova Apuração de Difal</h3>
                <!-- 🟢 BOTÃO DE AJUDA ADICIONADO -->
                <button type="button" class="btn btn-outline-info btn-sm ml-auto" data-toggle="modal" data-target="#modalManualAjudas">
                    <i class="fa fa-question-circle"></i> Como usar esta tela?
                </button>
            </div>
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
                        <button type="button" id="btn-corrigir-cfop" class="btn btn-warning"><i class="fa fa-magic"></i> Corrigir CFOPs Incorretos (2102 -> 2556)</button>

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

    <!-- 🟢 MODAL DO MANUAL DE OPERAÇÃO FISCAL (ATUALIZADO COM CÁLCULO LEGAL) -->
    <div class="modal fade" id="modalManualAjudas" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fa fa-book"></i> Manual de Operação - Central de Apuração DIFAL</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">

                    <h5><strong>1. O que faz a Rotina de Apuração de DIFAL?</strong></h5>
                    <p>Esta rotina localiza e processa compras interestaduais destinadas ao <strong>Uso e Consumo</strong> da empresa que possuem diferencial de alíquota a recolher. Ela analisa itens com <strong>CFOP 2556 e CST 090</strong>, realizando o cálculo de base dupla por dentro conforme as exigências da SEFAZ Bahia.</p>

                    <!-- 1.1 COMO É FEITO O CÁLCULO LEGAL DO IMPOSTO -->
                    <div class="alert alert-secondary border-info p-3 my-3">
                        <h6 class="text-info font-weight-bold"><i class="fa fa-calculator"></i> 1.1 Como o Imposto é Calculado? (Metodologia da Base Dupla)</h6>
                        <p class="small text-muted mb-2">Seguindo a legislação do Estado da Bahia para Uso e Consumo, o cálculo é realizado em base dupla por dentro de cada item, seguindo as diretrizes abaixo:</p>

                        <!-- 💡 NOTA EXPLICATIVA SOBRE NOTAS COM ICMS ZERADO -->
                        <div class="bg-light p-2 mb-3 rounded border border-warning" style="font-size: 0.85em;">
                            <strong class="text-warning"><i class="fa fa-exclamation-triangle"></i> Nota Importante sobre Notas com ICMS Zerado:</strong><br>
                            Se a nota fiscal de origem vier com o valor do ICMS zerado (comum em compras do Simples Nacional ou regimes especiais), <strong>o sistema calcula o crédito presumido automaticamente</strong>. Ele identifica a UF do fornecedor e aplica a alíquota regulamentada (<strong>7%</strong> para Sul/Sudeste exceto ES, e <strong>12%</strong> para as demais regiões), garantindo o abatimento legal do imposto.
                        </div>

                        <ol class="small pl-3 mb-3">
                            <li><strong>Exclusão do ICMS de Origem Presumido:</strong> O sistema calcula e remove o percentual de ICMS de direito da operação (7% ou 12%) sobre o valor total do item.</li>
                            <li><strong>Formação da Base Dupla:</strong> Inclui-se a alíquota interna da Bahia (20,5%) sobre o valor líquido obtido, gerando a nova <strong>Base de Cálculo Dupla</strong>.</li>
                            <li><strong>Apuração do DIFAL:</strong> Aplica-se os 20,5% sobre a base nova e subtrai-se o ICMS original presumido da nota. A diferença é o valor real devido.</li>
                        </ol>

                        <div class="bg-white p-2 rounded border">
                            <span class="badge badge-info mb-1">Exemplo Prático (Alíquota de Origem 12%):</span><br>
                            <table class="table table-sm table-borderless small mb-0">
                                <tr><td>• <strong>Valor do Item na Nota (Mesmo vindo com ICMS zerado):</strong></td><td>R$ 1.000,00</td></tr>
                                <tr><td>• <strong>ICMS Origem Presumido pelo Sistema (12%):</strong></td><td>R$ 120,00</td></tr>
                                <tr><td>• <strong>Valor Líquido sem o ICMS:</strong></td><td>R$ 880,00 (R$ 1.000,00 - R$ 120,00)</td></tr>
                                <tr><td>• <strong>Nova Base Dupla (BA 20,5%):</strong></td><td><strong>R$ 1.106,91</strong> <span class="text-muted">(Calculado: R$ 880,00 / 0,795)</span></td></tr>
                                <tr><td>• <strong>ICMS Interno da Bahia (20,5%):</strong></td><td>R$ 226,91 (20,5% sobre R$ 1.106,91)</td></tr>
                                <tr class="font-weight-bold text-danger"><td>• Valor Líquido do DIFAL devido:</td><td>R$ 106,91 <span class="text-muted font-weight-normal">(R$ 226,91 - R$ 120,00 presumido)</span></td></tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <h5><strong>2. O que faz o botão "Corrigir CFOPs Incorretos"?</strong></h5>
                    <p>É uma ferramenta de saneamento fiscal automático para corrigir falhas frequentes ocorridas no momento da importação do XML de compra. O sistema varre o período inserido e corrige o banco de dados conforme as regras abaixo:</p>

                    <table class="table table-sm table-bordered bg-light">
                        <thead>
                        <tr class="thead-dark">
                            <th>Erro Identificado no Banco</th>
                            <th>Ajuste Automático</th>
                            <th>Impacto na Apuração</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><strong>CFOP 2102 + CST 090</strong> (Fora da BA)</td>
                            <td>Substitui para <strong>CFOP 2556</strong></td>
                            <td>Entra no cálculo do DIFAL Interestadual.</td>
                        </tr>
                        <tr>
                            <td><strong>CFOP 2102 + CST 090</strong> (Dentro de BA)</td>
                            <td>Substitui para <strong>CFOP 1556</strong></td>
                            <td>Isento (Não gera DIFAL interestadual).</td>
                        </tr>
                        <tr>
                            <td><strong>CFOP 1403 + CST 060</strong> (ST Interna)</td>
                            <td>Substitui para <strong>CFOP 1407</strong></td>
                            <td>Consumo Interno já retido por ST.</td>
                        </tr>
                        <tr>
                            <td><strong>CFOP 2403 + CST 060</strong> (ST Interestadual)</td>
                            <td>Substitui para <strong>CFOP 2407 ou 1407</strong></td>
                            <td>Consumo com Substituição Tributária.</td>
                        </tr>
                        <tr>
                            <td><strong>Sem CFOP / Emissão Própria</strong></td>
                            <td>Aplica <strong>CFOP 1102 e CST 051</strong></td>
                            <td>Corrige notas fiscais de entrada própria.</td>
                        </tr>
                        </tbody>
                    </table>

                    <hr>

                    <h5><strong>3. Passo a Passo para Apurar e Fechar o Mês</strong></h5>
                    <ol class="pl-3">
                        <li class="mb-2"><strong>Defina o Período:</strong> Insira a Data Inicial e Final do mês cheio que deseja fechar (ex: 01/05 a 31/05).</li>
                        <li class="mb-2"><strong>Saneie os Dados:</strong> Clique no botão laranja <em>"Corrigir CFOPs Incorretos"</em> para garantir que nenhum lançamento errado fique de fora do imposto.</li>
                        <li class="mb-2"><strong>Confira os Valores:</strong> Clique em <em>"Buscar Notas"</em>. Avalie a listagem das notas, a base dupla gerada e o valor do DIFAL em vermelho.</li>
                        <li class="mb-2"><strong>Destino Financeiro:</strong> Selecione a <strong>SEFAZ</strong> no campo de fornecedor e defina a <strong>Categoria Financeira</strong> de impostos.</li>
                        <li class="mb-2"><strong>Finalize:</strong> Clique em <em>"Confirmar e Gravar"</em>. O período será bloqueado e um título de <strong>Conta a Pagar</strong> será gerado automaticamente para o seu financeiro emitir e pagar a guia DAE.</li>
                    </ol>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Entendi, Fechar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            // 1. PROCESSAR E CALCULAR BUSCA DO DIFAL
            $('#form-calcular').on('submit', function(e) {
                e.preventDefault();
                $('#preloader').css('display', 'block');

                $.post("{{ route('compraFiscal.difal.processar') }}", $(this).serialize(), function(response) {
                    $('#preloader').css('display', 'none');

                    if (response.status === 'vazio') {
                        swal("Aviso", response.message, "info");
                        $('#resultado-calculo').fadeOut();
                        return;
                    }

                    let html = '';
                    if(response.itens && response.itens.length > 0) {
                        response.itens.forEach(item => {
                            html += `<tr>
                        <td>${item.numero_nota}</td>
                        <td>${item.emitente_nome}</td>
                        <td>${item.valor_operacao}</td>
                        <td>${item.aliquota_origem}%</td>
                        <td>${item.aliquota_destino}%</td>
                        <td>${item.base_calculo_dupla}</td>
                        <td class="text-danger font-weight-bold">${item.valor_difal}</td>
                    </tr>`;
                        });
                        $('#corpo-conferencia').html(html);
                        $('#total-apurado').text('R$ ' + response.total_difal);
                        $('#resultado-calculo').fadeIn();
                    } else {
                        swal("Aviso", "Nenhuma nota interestadual pendente encontrada para o período.", "warning");
                        $('#resultado-calculo').fadeOut();
                    }
                }).fail(function() {
                    $('#preloader').css('display', 'none');
                    swal("Erro", "Erro ao processar apuração.", "error");
                });
            });

            // 2. BOTÃO DE CORREÇÃO FISCAL INTELIGENTE
            $('#btn-corrigir-cfop').click(function() {
                swal({
                    title: "Executar Saneamento Fiscal?",
                    text: "O sistema irá varrer o período selecionado e aplicar as correções automáticas de CFOP e CST nas compras de Uso/Consumo e Emissões Próprias.",
                    icon: "warning",
                    buttons: ["Cancelar", "Sim, Corrigir Banco"],
                    dangerMode: false,
                }).then((willCorrect) => {
                    if (willCorrect) {
                        $('#preloader').css('display', 'block');

                        $.post("{{ route('compraFiscal.difal.corrigirCfops') }}", {
                            _token: "{{ csrf_token() }}",
                            data_inicial: $('#data_inicial').val(),
                            data_final: $('#data_final').val()
                        }, function(response) {
                            $('#preloader').css('display', 'none');
                            if(response.status === 'success') {
                                swal("Sucesso", response.message, "success");
                                $('#form-calcular').submit(); // Recarrega a busca automaticamente
                            } else {
                                swal("Aviso", response.message, "warning");
                            }
                        }).fail(function() {
                            $('#preloader').css('display', 'none');
                            swal("Erro", "Erro interno ao processar a correção no servidor.", "error");
                        });
                    }
                });
            });

            // 3. CONFIRMAR, GRAVAR E GERAR CONTAS A PAGAR DO DIFAL
            $('#btn-salvar-apuracao').click(function() {
                let fornecedor = $('#fornecedor_id').val();
                if(!fornecedor) {
                    swal("Alerta", "Selecione a SEFAZ/Fornecedor para destino do pagamento!", "warning");
                    return;
                }

                swal({
                    title: "Confirmar e Gravar Apuração?",
                    text: "Esta ação fechará o período fiscal selecionado e gerará automaticamente uma conta a pagar para a SEFAZ de destino.",
                    icon: "warning",
                    buttons: ["Cancelar", "Sim, Encerrar Período"],
                    dangerMode: false,
                }).then((willSave) => {
                    if (willSave) {
                        $('#preloader').css('display', 'block');

                        $.post("{{ route('compraFiscal.difal.processar') }}", {
                            _token: "{{ csrf_token() }}",
                            data_inicial: $('#data_inicial').val(),
                            data_final: $('#data_final').val(),
                            fornecedor_id: fornecedor,
                            categoria_id: $('#categoria_id').val(),
                            confirmar_gravacao: true
                        }, function(res) {
                            $('#preloader').css('display', 'none');
                            swal("Sucesso", "Apuração gravada com sucesso! O título para emissão do DAE foi lançado no financeiro.", "success")
                                .then(() => {
                                    window.location.href = "{{ route('compraFiscal.difal.index') }}";
                                });
                        }).fail(function(err) {
                            $('#preloader').css('display', 'none');
                            swal("Erro", "Falha ao gravar apuração no servidor.", "error");
                        });
                    }
                });
            });
        });
    </script>
@endsection
