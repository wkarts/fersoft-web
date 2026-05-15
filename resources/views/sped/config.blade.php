@extends('default.layout', ['title' => 'SPED Configuração'])
@section('content')
<div class="card card-custom gutter-b">
  
  <!-- SELETOR DE MATRIZ / FILIAL -->
    <div class="alert alert-custom alert-light-primary fade show mb-8" role="alert">
        <div class="alert-icon"><i class="flaticon2-settings text-primary"></i></div>
        <div class="alert-text font-weight-bold">
            Você está editando as configurações de: 
            <strong class="text-dark" style="font-size: 1.1em;">
                @if(empty($filialSelecionada))
                    MATRIZ
                @else
                    FILIAL (ID: {{ $filialSelecionada }})
                @endif
            </strong>
        </div>

        <!-- Formulário que recarrega a página trocando a filial -->
        <form method="GET" action="{{ route('sped-config.index') }}" class="form-inline ml-auto">
            <label class="mr-2 font-weight-bolder text-dark">Mudar para:</label>
            <select name="filial_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                <option value="">Matriz</option>
                @foreach($filiais as $f)
                    <option value="{{ $f->id }}" @if($filialSelecionada == $f->id) selected @endif>
                        Filial - {{ $f->descricao ?? $f->razao_social ?? $f->id }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card-body">
        <br>
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

            <!-- ============================================= -->
            <!-- FORMULÁRIO 1: CONFIGURAÇÕES GERAIS DO SPED    -->
            <!-- ============================================= -->
            <h6 class="mb-0">Configuração SPED</h6>
            <form method="post" action="{{ route('sped-config.store') }}" class="mt-3">
                @csrf
                <div class="row">

                    <div class="col-lg-3 col-6">
                        <label>Código conta analítica</label>
                        <input type="text" name="codigo_conta_analitica" class="form-control" value="{{ $item != null ? $item->codigo_conta_analitica : '' }}">
                    </div>

                    <div class="col-lg-3 col-6">
                        <label>Código da receita</label>
                        <input type="text" name="codigo_receita" class="form-control" value="{{ $item != null ? $item->codigo_receita : '' }}">
                    </div>

                    <div class="col-lg-2 col-6 mt-3 mt-lg-0">
                        <label>Gerar bloco K</label>
                        <select class="custom-select form-control" name="gerar_bloco_k">
                            <option @if($item && $item->gerar_bloco_k == '0') selected @endif value="0">Não</option>
                            <option @if($item && $item->gerar_bloco_k == '1') selected @endif value="1">Sim</option>
                        </select>
                    </div>

                    <div class="col-lg-4 col-6 mt-3 mt-lg-0">
                        <label>Layout bloco K</label>
                        <select class="custom-select form-control" name="layout_bloco_k">
                            <option @if($item && $item->layout_bloco_k == '0') selected @endif value="0">
                                Leiaute simplificado
                            </option>
                            <option @if($item && $item->layout_bloco_k == '1') selected @endif value="1">
                                Leiaute completo
                            </option>
                            <option @if($item && $item->layout_bloco_k == '2') selected @endif value="2">
                                Leiaute restrito aos saldos de estoque
                            </option>
                        </select>
                    </div>
					
					<div class="col-lg-6 col-12 mt-4">
                        <label>Perfil do SPED</label>
                        <select class="custom-select form-control form-control-lg" name="perfil">
                            <option @if($item && $item->perfil == 'A') selected @endif value="A">Perfil A (Mais Detalhado - Grandes Empresas)</option>
                            <option @if($item && $item->perfil == 'B') selected @endif value="B">Perfil B (Padrão - Maioria das Empresas)</option>
                            <option @if($item && $item->perfil == 'C') selected @endif value="C">Perfil C (Simplificado)</option>
                        </select>
                    </div>

                    <div class="col-lg-6 col-12 mt-4">
                        <label>Indicador de Atividade</label>
                        <select class="custom-select form-control form-control-lg" name="ind_ativ">
                            <option @if($item && $item->ind_ativ == '0') selected @endif value="0">Industrial ou Equiparado</option>
                            <option @if($item && $item->ind_ativ == '1') selected @endif value="1">Outros</option>
                        </select>
                    </div>
                    
					<div class="col-12">
                        <br>
                        <button class="btn btn-success" type="submit"> 
                            Salvar Configurações
                        </button>
                    </div>
                </div>
            </form>

            <hr class="mt-8 mb-8" />

            <!-- ============================================= -->
            <!-- FORMULÁRIO 2: REGRAS DO BLOCO 1400 (IPM)      -->
            <!-- ============================================= -->
            <h6 class="mb-4 text-primary">Regras do Bloco 1400 (Valor Adicionado - IPM)</h6>
            
            <form method="post" action="{{ route('sped_regras.store') }}" class="mb-5">
                @csrf
                <div class="row align-items-end">
                    <div class="col-lg-2">
                        <label>CFOP</label>
                        <input type="text" name="cfop" class="form-control" placeholder="Ex: 5102" required maxlength="4">
                    </div>

                    <div class="col-lg-3 mt-3 mt-lg-0">
                        <label>Código IPM (Tabela SEFAZ)</label>
                        <input type="text" name="codigo_ipm" class="form-control" placeholder="Ex: BA24" required>
                    </div>

                    <div class="col-lg-5 mt-3 mt-lg-0">
                        <label>Descrição (Controle interno)</label>
                        <input type="text" name="descricao" class="form-control" placeholder="Ex: Venda de sucata no estado">
                    </div>

                    <div class="col-lg-2 mt-3 mt-lg-0">
                        <button class="btn btn-info w-100" type="submit">
                            <i class="la la-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>CFOP Vinculado</th>
                            <th>Código IPM</th>
                            <th>Descrição / Observação</th>
                            <th width="100px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($regras1400 ?? [] as $r)
                        <tr>
                            <td class="align-middle"><strong>{{ $r->cfop }}</strong></td>
                            <td class="align-middle"><span class="label label-inline label-primary font-weight-bold">{{ $r->codigo_ipm }}</span></td>
                            <td class="align-middle">{{ $r->descricao }}</td>
                            <td class="align-middle">
                                <form action="{{ route('sped_regras.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Deseja realmente excluir esta regra?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger w-100">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhuma regra cadastrada. O Bloco 1400 não será gerado.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>
@endsection