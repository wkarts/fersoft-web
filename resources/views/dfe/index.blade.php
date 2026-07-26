@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-body">
        <form method="get" action="/dfe/filtro">
            <div class="row align-items-end">
                {{-- DATA INICIAL --}}
                <div class="form-group col-lg-2 col-md-6">
                    <label class="col-form-label">Data Inicial</label>
                    <div class="input-group date">
                        <input type="text" name="data_inicial" class="form-control datepicker" readonly value="{{ $data_inicial }}" id="kt_datepicker_3" />
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="la la-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- DATA FINAL --}}
                <div class="form-group col-lg-2 col-md-6">
                    <label class="col-form-label">Data Final</label>
                    <div class="input-group date">
                        <input type="text" name="data_final" class="form-control datepicker" readonly value="{{ $data_final }}" id="kt_datepicker_3" />
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="la la-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- STATUS IMPORTAÇÃO --}}
                <div class="form-group col-lg-2 col-md-4">
                    <label class="col-form-label">Importação</label>
                    <select name="status_importacao" class="form-control custom-select">
                        <option value="todos" {{ $status_importacao == 'todos' ? 'selected' : '' }}>TODOS</option>
                        <option value="importadas" {{ $status_importacao == 'importadas' ? 'selected' : '' }}>IMPORTADAS</option>
                        <option value="pendentes" {{ $status_importacao == 'pendentes' ? 'selected' : '' }}>PENDENTES</option>
                    </select>
                </div>

                {{-- TIPO --}}
                <div class="form-group col-lg-2 col-md-4">
                    <label class="col-form-label">Tipo</label>
                    <select name="tipo" class="form-control custom-select">
                        <option value="--">TODOS</option>
                        <option value="1" {{ $tipo == '1' ? 'selected' : '' }}>Ciência</option>
                        <option value="2" {{ $tipo == '2' ? 'selected' : '' }}>Confirmada</option>
                        <option value="3" {{ $tipo == '3' ? 'selected' : '' }}>Desconhecida</option>
                        <option value="4" {{ $tipo == '4' ? 'selected' : '' }}>Não Realizada</option>
                    </select>
                </div>

                {{-- UNIDADE --}}
                <div class="form-group col-lg-2 col-md-4">
                    <label class="col-form-label">Unidade</label>
                    <select name="filial_id" class="form-control custom-select">
                        <option value="">Todas</option>
                        <option value="matriz" {{ $filial_id == 'matriz' ? 'selected' : '' }}>Matriz</option>
                        @foreach($filiais as $f)
                            <option value="{{$f->id}}" {{ $filial_id == $f->id ? 'selected' : '' }}>{{$f->descricao}}</option>
                        @endforeach
                    </select>
                </div>

                {{-- FORNECEDOR --}}
                <div class="form-group col-lg-3 col-md-6">
                    <label class="col-form-label">Fornecedor</label>
                    <input type="text" name="fornecedor" class="form-control" value="{{ $fornecedor ?? '' }}" placeholder="Nome...">
                </div>

                {{-- N° NOTA --}}
                <div class="form-group col-lg-2 col-md-6">
                    <label class="col-form-label">Nº Nota</label>
                    <input type="text" name="nNf" class="form-control" value="{{ $nNf ?? '' }}" placeholder="Número...">
                </div>

                {{-- BOTÃO ENTENDER A ROTINA (CORRIGIDO DATA-TARGET) --}}
                <div class="col-lg-3 mb-2">
                    <button type="button" class="btn btn-info font-weight-bold btn-block" data-toggle="modal" data-target="#modalManualDfe">
                        <i class="fa fa-info-circle"></i> Entender a Rotina
                    </button>
                </div>

                {{-- BOTÃO FILTRAR --}}
                <div class="col-lg-2 mb-2">
                    <button type="submit" class="btn btn-primary font-weight-bold btn-block">
                        <i class="la la-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<h4 class="mt-2 mb-2 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Manifesto</h4>

<div class="row mb-4"> 
    <div class="col-md-12">
        <a href="/dfe/novaConsulta" class="btn btn-success btn-sm @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
            <i class="la la-refresh"></i>
            Nova Consulta
        </a>

      <a href="{{ route('dfe.sincronizar') }}" class="btn btn-info"> <i class="fas fa-sync"></i> Sincronizar Notas
		</a>

        @if($busca_automatica)
        <a href="/dfe/logs" class="btn btn-warning btn-sm float-right @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
            <i class="la la-file"></i>
            Logs Automáticos
        </a>
        @endif
    </div>
</div>

<h5 class="mb-2 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Total de registros: <strong style="color: green">{{sizeof($docs)}}</strong></h5>

<input type="hidden" value="{{json_encode($docs)}}" id="docs">

<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
    <div class="col-sm-12">
        <div class="wizard wizard-3" id="kt_wizard_v3">
            <div class="wizard-nav">
                <div class="wizard-steps px-8 py-2 px-lg-15 py-lg-1"> 
                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
            <form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
                <div class="pb-5" data-wizard-type="step-content">
                    <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                        <div class="row">
                            <div class="col-xl-12">
                                <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                    <table class="datatable-table" style="max-width: 100%;">
                                        <thead class="datatable-head">
                                            <tr class="datatable-row" style="left: 0px;">
                                                <th class="datatable-cell"><span style="width: 150px;">FORNECEDOR</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">Nº NOTA</span></th>
                                                <th class="datatable-cell"><span style="width: 90px;">VALOR</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">EMISSÃO</span></th>
                                                <th class="datatable-cell"><span style="width: 100px;">STATUS SEFAZ</span></th> <!-- COLUNA NOVA -->
                                        <th class="datatable-cell"><span style="width: 100px;">MANIFESTO</span></th> <!-- SEU STATUS ANTIGO -->
                                                <th class="datatable-cell"><span style="width: 80px;">ERP / FIN.</span></th>
                                                <th class="datatable-cell"><span style="width: 120px;">AÇÕES</span></th>
                                                <th class="datatable-cell"><span style="width: 180px;">CHAVE DE ACESSO</span></th>
                                            </tr>
                                        </thead>
                                        <tbody class="datatable-body">
                                            @foreach($docs as $d)
                                            <tr class="datatable-row" style="left: 0px;">
                                                <td class="datatable-cell"><span style="width: 150px; white-space: normal;">{{$d->nome}}</span></td>
                                                <td class="datatable-cell"><span style="width: 80px;">{{ $d->nNf > 0 ? $d->nNf : '---' }}</span></td>
                                                <td class="datatable-cell"><span style="width: 90px;">R$ {{number_format($d->valor, 2, ',', '.')}}</span></td>
                                                <td class="datatable-cell"><span style="width: 80px;">{{ \Carbon\Carbon::parse($d->data_emissao)->format('d/m/y')}}</span></td>
                                                <td class="datatable-cell">
                                                    <span style="width: 100px;">
                                                        @if($d->situacao_sefaz == 'CANCELADA')
                                                            <span class="label label-danger label-inline font-weight-bolder shadow-sm">Cancelada</span>
                                                        @elseif($d->situacao_sefaz == 'AUTORIZADA' || empty($d->situacao_sefaz))
                                                            <span class="label label-success label-inline font-weight-bolder shadow-sm">Autorizada</span>
                                                        @else
                                                            <span class="label label-warning label-inline font-weight-bolder shadow-sm">{{ $d->situacao_sefaz }}</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="datatable-cell">
                                                    <span style="width: 100px;">
                                                        <!-- Coluna que exibe Confirmação, Ciência, etc -->
                                                        <span class="label label-light-primary label-inline font-weight-bold">{{$d->estado()}}</span>
                                                    </span>
                                                </td>
                                                <td class="datatable-cell">
                                                    <span style="width: 80px; display: flex; align-items: center; gap: 5px;">
                                                        @if($d->compra_id > 0)
                                                            <span class="badge badge-success" title="Compra">C</span>
                                                        @else
                                                            <i class="la la-clock-o text-warning" style="font-size: 18px;" title="Pendente"></i>
                                                        @endif

                                                        @if($d->fatura_salva)
                                                            <span class="badge badge-info" title="Financeiro Salvo">F</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="datatable-cell">
                                                    <span style="width: 120px; display: flex; gap: 4px;">
                                                        @if(!empty($d->chave))
                                                            <a href="/dfe/download/{{$d->chave}}" class="btn btn-icon btn-xs btn-success" title="XML"><i class="la la-download"></i></a>
                                                            <a href="/dfe/imprimirDanfe/{{$d->chave}}" target="_blank" class="btn btn-icon btn-xs btn-primary" title="Imprimir"><i class="la la-print"></i></a>
                                                            <a href="/dfe/importar/{{$d->chave}}" class="btn btn-icon btn-xs btn-info" title="Importar XML"><i class="la la-shopping-cart"></i></a>
                                                        @endif
                                                        @if($d->tipo != 2)
                                                            <a href="javascript:;" onclick="setarEvento('{{$d->chave}}')" data-toggle="modal" data-target="#modal1" class="btn btn-icon btn-xs btn-warning" title="Manifestar"><i class="la la-legal"></i></a>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="datatable-cell">
                                                    <span class="text-muted" style="width: 180px; font-size: 11px; display: block; word-wrap: break-word; white-space: normal;">
                                                        {{$d->chave}}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pb-5" data-wizard-type="step-content">
                    <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                        <div class="row">
                            @foreach($docs as $d)
                            <div class="col-sm-6 col-lg-6 col-md-6 col-xl-6">
                                <div class="card card-custom gutter-b example example-compact">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h3 style="width: 230px; font-size: 15px; height: 10px;" class="card-title">{{$d->nome}}</h3>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label">Documento:</span>
                                            <a class="kt-widget__data text-success">{{ $d->documento }}</a>
                                        </div>
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label">Valor:</span>
                                            <a class="kt-widget__data text-success">{{number_format($d->valor, 2)}}</a>
                                        </div>
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label">Data:</span>
                                            <a class="kt-widget__data text-success">{{ \Carbon\Carbon::parse($d->data_emissao)->format('d/m/Y H:i:s')}}</a>
                                        </div>
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label">Nº NFe:</span>
                                            <a class="kt-widget__data text-success">{{ $d->nNf }}</a>
                                        </div>
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label">Chave:</span>
                                            <a class="kt-widget__data text-success" style="word-break: break-all;">{{ $d->chave }}</a>
                                        </div>
                                        <div class="kt-widget__info">
                                            <span class="kt-widget__label">Estado:</span>
                                            <a class="kt-widget__data text-success">{{ $d->estado() }}</a>
                                        </div>
                                        <div class="kt-widget__info mt-2 mb-3">
                                            <span class="kt-widget__label">Status ERP:</span>
                                            @if($d->compra_id > 0)
                                                <span class="label label-success label-inline font-weight-bolder">Compra ✔</span>
                                            @else
                                                <span class="label label-warning label-inline font-weight-bolder text-dark">Compra ⏳</span>
                                            @endif

                                            @if($d->fatura_salva)
                                                <span class="label label-info label-inline font-weight-bolder">Financeiro ✔</span>
                                            @else
                                                <span class="label label-light-danger label-inline font-weight-bolder text-dark">Financeiro ⏳</span>
                                            @endif
                                        </div>

                                        @if($d->tipo == 1 || $d->tipo == 2)
                                        <a style="width: 100%;" href="/dfe/download/{{$d->chave}}" class="btn btn-success">Completa</a>
                                        <a style="width: 100%;" href="/dfe/imprimirDanfe/{{$d->chave}}" class="btn btn-primary mt-1">Imprimir</a>
                                        @elseif($d->tipo == 3)
                                        <a style="width: 100%;" class="btn btn-danger">Desconhecida</a>
                                        @elseif($d->tipo == 4)
                                        <a style="width: 100%;" class="btn btn-warning">Não realizada</a>
                                        @else
                                        <a style="width: 100%;" class="btn btn-info mt-1" onclick="setarEvento('{{$d->chave}}')" data-toggle="modal" data-target="#modal1">Manifestar</a>
                                        @endif
                                    </div>

                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <form method="get" action="/dfe/manifestar">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Manifestação de Destinatário</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
				</div>
				<div class="modal-body">
					<input type="hidden" id="nome" name="nome" />
					<input type="hidden" id="cnpj" name="cnpj" />
					<input type="hidden" id="valor" name="valor" />
					<input type="hidden" id="data_emissao" name="data_emissao" />
					<input type="hidden" id="num_prot" name="num_prot" />
					<input type="hidden" id="chave" name="chave" />

					<div class="form-group validated col-sm-6 col-lg-6">
						<label class="col-form-label">Tipo</label>
						<select class="custom-select form-control" name="evento" id="tipo_evento">
							<option value="2">Confirmação</option>
							<option value="1">Ciencia de operção</option>
							<option value="3">Desconhecimento</option>
							<option value="4">Operação não realizada</option>
						</select>
					</div>

					<div class="form-group validated col-sm-12 col-lg-12" id="div-just" style="display: none">
						<label class="col-form-label">Justificativa</label>
						<div class="">
							<input id="justificativa" type="text" class="form-control" name="justificativa" value="">
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="salvarEdit" class="btn btn-success font-weight-bold spinner-white spinner-right">Manifestar</button>
				</div>
			</div>
		</div>
	</form>
</div>

<div class="modal fade" id="modalManualDfe" tabindex="-1" role="dialog" aria-labelledby="modalManualDfeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalManualDfeLabel">
                    <i class="fa fa-info-circle"></i> Manual Técnico: Rotina DF-e e Impacto no SPED Fiscal
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <p class="text-muted">Este guia orienta a operação correta do módulo de DF-e, detalhando como o sistema processa os impostos, estoque e financeiro para garantir que a geração do <b>SPED Fiscal (EFD ICMS/IPI)</b> não sofra rejeições.</p>
                <hr>
                <h4 class="text-info"><i class="fa fa-cogs"></i> 1. O que a Rotina Faz</h4>
                <p>A rotina de DF-e realiza a comunicação direta com o ambiente nacional da SEFAZ para gerenciar as notas emitidas por terceiros contra o CNPJ da empresa.</p>
                <ul>
                    <li><b>Manifestação Obrigatória:</b> Permite registrar os eventos exigidos pela fiscalização: <i>Ciência da Operação, Confirmação, Desconhecimento</i> ou <i>Operação Não Realizada</i>.</li>
                    <li><b>Download e Guarda Digital:</b> Baixa o arquivo XML oficial diretamente da SEFAZ assim que a nota é manifestada, armazenando-o na pasta física segura do servidor.</li>
                    <li><b>Conferência Visual:</b> Permite renderizar a DANFE em PDF na tela para checagem rápida antes de qualquer lançamento no sistema.</li>
                </ul>
                <hr>
                <h4 class="text-info"><i class="fa fa-exchange"></i> 2. Inteligência Fiscal na Importação (CFOP e ICMS)</h4>
                <p>Ao avançar para a tela de importação, o sistema lê a estrutura interna do XML e aplica regras automáticas para adequar a nota do fornecedor às diretrizes de entrada da empresa:</p>
                <ul>
                    <li><b>Conversão de CFOP (De/Para):</b> O sistema converte CFOPs de venda interestaduais ou específicos para o formato de entrada correto. Por exemplo, o CFOP de combustível <b>1929</b> é convertido de forma automática para <b>1653</b>.</li>
                    <li><b>Blindagem de Uso e Consumo (CFOP 1556):</b> Para mercadorias destinadas ao uso ou consumo, a legislação veda o crédito de ICMS. O sistema zera de forma compulsória as colunas de <b>Base de Cálculo, Alíquota e Valor do ICMS</b> (<code>vbc_icms = 0</code>, <code>p_icms = 0</code>, <code>v_icms = 0</code>) para que a escrituração não gere autuações na Receita Federal.</li>
                    <li><b>Junção de CST/CSOSN:</b> O sistema captura a tag de impostos e une automaticamente o dígito indicador de <b>Origem da Mercadoria</b> com a Situação Tributária (gerando códigos estáveis de 3 dígitos, ex: <b>060</b>), garantindo que o Registro C170 do SPED seja preenchido corretamente.</li>
                </ul>
                <hr>
                <h4 class="text-info"><i class="fa fa-cubes"></i> 3. Integração com Estoque, Financeiro e Entidades</h4>
                <p>A finalização e o salvamento da nota alimentam de maneira unificada e imediata os seguintes módulos do ERP:</p>
                <ul>
                    <li><b>Movimentação de Estoque:</b> Calcula a entrada fracionada real com base na <b>Conversão Unitária</b> parametrizada (ex: se comprou em Caixa e vende em Unidade), alimentando o histórico de movimentações e recalculando o custo de compra do produto.</li>
                    <li><b>Gestão Flexível de Faturas:</b> O sistema pré-carrega as parcelas originais contidas no XML. O operador tem total liberdade na tela para <b>incluir novas parcelas ou excluir duplicatas</b>, adequando o Contas a Pagar ao acordo financeiro real feito com o fornecedor.</li>
                    <li><b>Vínculo de Fornecedores:</b> Se o emitente da nota for novo, o sistema realiza o <b>cadastro automatizado</b>, localizando inclusive o código IBGE correto do município pelo nome da cidade presente no XML.</li>
                    <li><b>Memória de Associação:</b> Ao vincular o item do XML a um produto do seu estoque pela primeira vez, o sistema grava essa associação. Nas próximas compras desse fornecedor, o sistema lembrará do vínculo sozinho.</li>
                </ul>
                <hr>
                <div class="alert alert-warning">
                    <h5 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Cuidados Críticos para a Geração do SPED Fiscal</h5>
                    <p class="mb-0">Para que o arquivo gerado pelo <b>SpedController</b> seja validado com sucesso pelo contador, valide sempre se:</p>
                    <ul class="mt-2 mb-0">
                        <li>O produto vinculado possui um código <b>NCM</b> válido e a classificação do <b>Tipo do Item</b> (ex: 00 para Revenda, 07 para Consumo) configurada no cadastro de produtos.</li>
                        <li>A chave de acesso possui 44 dígitos numéricos. Notas de serviços municipais (NFS-e) que vêm no lote são ignoradas por não pertencerem ao SPED Fiscal (EFD ICMS/IPI).</li>
                        <li>A nota está sendo salva com a marcação interna correta de origem de terceiros (<b>xml_importado = 1</b>), o que instrui o gerador do SPED a extrair os itens detalhadamente das tabelas de compras.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar Manual</button>
            </div>
        </div>
    </div>
</div>
@section('scripts') <script>
    $(document).ready(function() {
        // Ativa o calendário para qualquer input que tenha a classe 'datepicker'
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',      // Formato brasileiro
            todayHighlight: true,      // Destaca o dia de hoje
            orientation: "bottom left", // Abre o calendário para baixo
            autoclose: true,           // Fecha o calendário automaticamente ao escolher a data
            language: 'pt-BR'          // Deixa os meses e dias em português (se a biblioteca de tradução estiver carregada)
        });
    });
</script>
@endsection
@endsection
