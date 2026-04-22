@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Importação de NFS-e em Lote (Nacional)</h3>
    </div>
    <div class="card-body">
        
        {{-- Exibição de Alertas de Erro/Sucesso --}}
        @if(session('mensagem_erro'))
            <div class="alert alert-custom alert-light-danger fade show mb-5" role="alert">
                <div class="alert-icon"><i class="flaticon-warning"></i></div>
                <div class="alert-text">{!! session('mensagem_erro') !!}</div>
            </div>
        @endif
        @if(session('mensagem_sucesso'))
            <div class="alert alert-custom alert-light-success fade show mb-5" role="alert">
                <div class="alert-icon"><i class="flaticon2-check-mark"></i></div>
                <div class="alert-text">{{ session('mensagem_sucesso') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ url('compras/importacaoNfse/importarLote') }}" enctype="multipart/form-data">
            @csrf
            <div class="row align-items-end">
                
                <div class="col-lg-3">
                    <label>Veículo</label>
                    <select name="veiculo_id" class="form-control custom-select">
                        <option value="">Selecione um veículo (opcional)</option>
                        @foreach($veiculos as $v)
                            <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-lg-3">
                    <label>Categoria de Conta <strong class="text-danger">*</strong></label>
                    <select name="categoria_conta_id" class="form-control custom-select" required>
                        <option value="">Selecione a categoria</option>
                        @foreach($categoriasDeConta as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- NOVO CAMPO DE PRAZO DE PAGAMENTO --}}
                <div class="col-lg-3">
                    <label>Prazo de Pagamento <strong class="text-danger">*</strong></label>
                    <select name="prazo_pagamento" class="form-control custom-select" required>
                        <option value="0">À Vista (Mesma data da Emissão)</option>
                        <option value="15">15 dias</option>
                        <option value="20">20 dias</option>
                        <option value="30">30 dias</option>
                        <option value="45">45 dias</option>
                        <option value="60">60 dias</option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <label>Arquivos XML <strong class="text-danger">*</strong></label>
                    <div class="custom-file">
                        <input type="file" name="xmls[]" id="xmlFileInput" multiple accept=".xml" class="custom-file-input" required>
                        <label class="custom-file-label" for="xmlFileInput" data-browse="Buscar">Escolher arquivos</label>
                    </div>
                </div>
            </div>

            {{-- Área Mágica do JavaScript: Preview dos XMLs --}}
            <div class="row mt-5" id="tabela-preview-container" style="display: none;">
                <div class="col-12">
                    <h5 class="text-primary border-bottom pb-2">Resumo dos XMLs Selecionados</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nº NFS-e</th>
                                    <th>Prestador (Fornecedor)</th>
                                    <th>Valor (R$)</th>
                                    <th>Status Leitura</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-preview-body">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-right">
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="la la-upload"></i> Importar Lote
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('xmlFileInput').addEventListener('change', function(e) {
        const files = this.files;
        const tbody = document.getElementById('tabela-preview-body');
        const container = document.getElementById('tabela-preview-container');
        const label = document.querySelector('.custom-file-label');
        
        tbody.innerHTML = ''; // Limpa a tabela

        if (files.length === 0) {
            container.style.display = 'none';
            label.textContent = 'Escolher arquivos';
            return;
        }

        label.textContent = files.length + ' arquivo(s) selecionado(s)';
        container.style.display = 'block';

        // Lê e extrai dados de cada XML
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Remove os namespaces do XML para facilitar a extração dos dados no JS
                const xmlStr = e.target.result.replace(/ xmlns(.*?)=(".*?")/g, '');
                const parser = new DOMParser();
                const doc = parser.parseFromString(xmlStr, "application/xml");

                const infNFSe = doc.querySelector("infNFSe");
                
                if (infNFSe) {
                    const num = infNFSe.querySelector("nNFSe") ? infNFSe.querySelector("nNFSe").textContent : '-';
                    const emitNome = infNFSe.querySelector("emit > xNome") ? infNFSe.querySelector("emit > xNome").textContent : 'Desconhecido';
                    
                    // Busca o valor líquido ou valor do serviço
                    let valor = infNFSe.querySelector("valores > vLiq");
                    if(!valor) valor = infNFSe.querySelector("valores > vServ");
                    
                    const valorFormatado = valor ? parseFloat(valor.textContent).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'}) : 'R$ 0,00';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="font-weight-bold text-info">${num}</td>
                        <td>${emitNome}</td>
                        <td class="font-weight-bold">${valorFormatado}</td>
                        <td class="text-success"><i class="flaticon2-check-mark text-success"></i> Pronto</td>
                    `;
                    tbody.appendChild(tr);
                } else {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td colspan="3">${file.name}</td>
                        <td class="text-danger"><i class="flaticon2-cross text-danger"></i> XML fora do padrão</td>
                    `;
                    tbody.appendChild(tr);
                }
            };
            reader.readAsText(file);
        });
    });
</script>
@endsection