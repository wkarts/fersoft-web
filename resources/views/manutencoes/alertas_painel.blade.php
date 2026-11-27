@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')

    <!-- Dependências de estilo do Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="fer-extra fer-extra--support fer-extra--manutencoes card card-custom gutter-b">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-6 flex-wrap">
                <div>
                    <h3 class="card-title font-weight-bolder">Torre de Controle de Manutenções e Frotas</h3>
                    <span class="text-muted">Acompanhamento de revisões preventivas, quebras corretivas e aprovações</span>
                </div>
                <div class="mt-2">
                    <a href="{{ route('manutencao.relatorio.necessidades') }}" class="btn btn-light-danger font-weight-bold mr-2">
                        <i class="la la-car"></i> Veículos Bloqueados
                    </a>
                    <a href="{{ route('manutencao.relatorio.aprovacoes') }}" class="btn btn-light-warning font-weight-bold mr-2">
                        <i class="la la-check-circle"></i> Aprovações Pendentes
                    </a>
                    <button type="button" class="btn btn-primary font-weight-bolder" data-toggle="modal" data-target="#modalNovoAlerta">
                        <i class="la la-plus"></i> Novo Alerta / Quebra
                    </button>
                </div>
            </div>

            {{-- FILTROS RÁPIDOS --}}
            <form method="get" action="{{ route('manutencao.alertas.index') }}" class="mb-6 bg-light-secondary p-4 rounded">
                <div class="row align-items-end">
                    <div class="form-group col-lg-3 col-md-4">
                        <label class="font-weight-bold">Veículo</label>
                        <select name="veiculo_id" class="form-control" style="height: calc(1.5em + 1.3rem + 2px);">
                            <option value="">Todos os Veículos</option>
                            @foreach($veiculos as $v)
                                @php $vObj = (object) $v; @endphp
                                <option value="{{ $vObj->id }}" {{ request('veiculo_id') == $vObj->id ? 'selected' : '' }}>
                                    {{ $vObj->placa }} - {{ $vObj->modelo ?? $vObj->marca ?? 'Sem modelo' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-3 col-md-4">
                        <label class="font-weight-bold">Fase</label>
                        <select name="fase" class="form-control">
                            <option value="">Todas</option>
                            <option value="identificado" {{ request('fase') == 'identificado' ? 'selected' : '' }}>Identificado / Quebrado</option>
                            <option value="aguardando_cotacao" {{ request('fase') == 'aguardando_cotacao' ? 'selected' : '' }}>Aguardando Cotação</option>
                            <option value="aguardando_autorizacao" {{ request('fase') == 'aguardando_autorizacao' ? 'selected' : '' }}>Aguardando Autorização</option>
                            <option value="aguardando_peca" {{ request('fase') == 'aguardando_peca' ? 'selected' : '' }}>Aguardando Peça</option>
                            <option value="em_manutencao" {{ request('fase') == 'em_manutencao' ? 'selected' : '' }}>Em Oficina</option>
                            <option value="resolvido" {{ request('fase') == 'resolvido' ? 'selected' : '' }}>Resolvido</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label class="font-weight-bold">Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="preventiva" {{ request('tipo') == 'preventiva' ? 'selected' : '' }}>Preventiva</option>
                            <option value="corretiva" {{ request('tipo') == 'corretiva' ? 'selected' : '' }}>Corretiva</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-4 col-md-12">
                        <button type="submit" class="btn btn-primary font-weight-bold"><i class="la la-filter"></i> Filtrar</button>
                        <a href="{{ route('manutencao.alertas.index') }}" class="btn btn-light-danger ml-2 font-weight-bold">Limpar</a>
                    </div>
                </div>
            </form>

            {{-- TABELA DE ALERTAS --}}
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover">
                    <thead>
                    <tr>
                        <th>Veículo</th>
                        <th>Tipo / Prioridade</th>
                        <th>Alerta / Descrição</th>
                        <th>Status / Fase</th>
                        <th>Circulação</th>
                        <th>Valor Est.</th>
                        <th class="text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($alertasOficina as $item)
                        @php
                            $item = (object) $item;
                            $alertaId = $item->id ?? null;
                            $veiculoId = $item->veiculo_id ?? null;
                            $placa = $item->placa ?? 'N/A';
                            $km = $item->quilometragem ?? 0;
                            $fase = $item->fase ?? 'identificado';
                            $prioridade = $item->prioridade ?? 'Alta';
                            $tipo = $item->tipo ?? 'corretiva';

                            $badgePrioridade = match($prioridade) {
                                'Urgente' => 'danger',
                                'Alta'    => 'warning',
                                'Media'   => 'info',
                                default   => 'secondary'
                            };

                            $badgeFase = match($fase) {
                                'identificado'           => 'secondary',
                                'aguardando_cotacao'     => 'info',
                                'aguardando_autorizacao' => 'warning',
                                'aguardando_peca'        => 'primary',
                                'em_manutencao'          => 'dark',
                                'resolvido'              => 'success',
                                default                  => 'light'
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="font-weight-bolder text-dark font-size-h6">{{ $placa }}</span>
                                <div class="text-muted small">KM: {{ number_format((float)$km, 0, '', '.') }}</div>
                            </td>
                            <td>
                                <span class="badge badge-light-{{ $tipo == 'preventiva' ? 'primary' : 'danger' }} font-weight-bold">
                                    {{ ucfirst($tipo) }}
                                </span>
                                <div class="mt-1">
                                    <span class="badge badge-{{ $badgePrioridade }}">{{ $prioridade }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="font-weight-bold text-dark-75">{{ $item->titulo ?? '' }}</span>
                                <div class="text-muted small">{{ Str::limit($item->descricao ?? '', 60) }}</div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $badgeFase }} font-weight-bold">
                                    {{ strtoupper(str_replace('_', ' ', $fase)) }}
                                </span>
                            </td>
                            <td>
                                @if(!empty($item->impedir_circulacao) && $fase != 'resolvido')
                                    <span class="label label-inline label-light-danger font-weight-bold">⛔ Bloqueado</span>
                                @else
                                    <span class="label label-inline label-light-success font-weight-bold">Liberado</span>
                                @endif
                            </td>
                            <td class="font-weight-bold">
                                R$ {{ number_format((float)($item->valor_estimado ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="text-right text-nowrap">
                                {{-- BOTÃO DE APROVAÇÃO (SE ESTIVER PENDENTE DE AUTORIZAÇÃO) --}}
                                @if($alertaId && in_array($fase, ['identificado', 'aguardando_autorizacao', 'aguardando_cotacao']))
                                    <form method="post" action="{{ route('manutencao.alertas.aprovar', $alertaId) }}" class="d-inline" onsubmit="return confirm('Confirmar aprovação desta manutenção/compra?');">
                                        @csrf
                                        <button type="submit" class="btn btn-icon btn-light-success btn-sm mr-1" title="Aprovar Manutenção">
                                            <i class="la la-check"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- BOTÃO ABRIR O.S. DE MANUTENÇÃO --}}
                                @if($veiculoId)
                                    <a href="/manutencoes/new?veiculo_id={{ $veiculoId }}&descricao={{ urlencode($item->titulo ?? '') }}" class="btn btn-icon btn-light-primary btn-sm" title="Abrir Ordem de Serviço">
                                        <i class="la la-wrench"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-6">Nenhum alerta ou quebra cadastrada.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                @if(is_object($alertas) && method_exists($alertas, 'links'))
                    {{-- Substitua {{ $alertas->links() }} por: --}}
                    <div class="d-flex justify-content-center mt-4">
                        @if(is_object($alertasOficina) && method_exists($alertasOficina, 'links'))
                            {{ $alertasOficina->links() }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL NOVO ALERTA / QUEBRA --}}
    <div class="modal fer-extra-modal fade" id="modalNovoAlerta" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="{{ route('manutencao.alertas.salvar') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Registrar Novo Alerta / Quebra de Veículo</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Veículo *</label>
                                <select name="veiculo_id" class="form-control select2-modal" style="width: 100%;" required>
                                    <option value="">Selecione o veículo...</option>
                                    @foreach($veiculos as $v)
                                        <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo ?? $v->marca ?? 'Sem modelo' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Tipo *</label>
                                <select name="tipo" class="form-control" required>
                                    <option value="corretiva">Corretiva (Quebra/Sinistro)</option>
                                    <option value="preventiva">Preventiva (Revisão)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Prioridade</label>
                                <select name="prioridade" class="form-control">
                                    <option value="Baixa">Baixa</option>
                                    <option value="Media">Média</option>
                                    <option value="Alta" selected>Alta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-8">
                                <label>Título / Motivo Principal *</label>
                                <input type="text" name="titulo" class="form-control" placeholder="Ex: Radiador furado / Troca de óleo / Embreagem patinando" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Valor Estimado (R$)</label>
                                <input type="text" name="valor_estimado" class="form-control" placeholder="0,00">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label>Descrição Detalhada do Problema</label>
                                <textarea name="descricao" class="form-control" rows="3" placeholder="Relato do motorista ou laudo mecânico..."></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Fase Inicial</label>
                                <select name="fase" class="form-control">
                                    <option value="identificado">Identificado / Quebrado</option>
                                    <option value="aguardando_cotacao">Aguardando Cotação</option>
                                    <option value="aguardando_autorizacao">Aguardando Autorização da Diretoria</option>
                                    <option value="aguardando_peca">Aguardando Chegada de Peça</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 pt-8">
                                <div class="checkbox-inline">
                                    <label class="checkbox checkbox-danger">
                                        <input type="checkbox" name="impedir_circulacao" value="1" checked>
                                        <span></span>
                                        <strong class="text-danger">Interditar Veículo (Bloquear saída na agenda de viagens)</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">Salvar Alerta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts de inicialização do Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-busca').select2();
            $('#modalNovoAlerta').on('shown.bs.modal', function () {
                $('.select2-modal').select2({
                    dropdownParent: $('#modalNovoAlerta')
                });
            });
        });
    </script>
@endsection
