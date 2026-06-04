{{-- resources/views/devolucao/new.blade.php --}}
@extends('default.layout')

@section('content')
    <style>
        .btn-file {
            position: relative;
            overflow: hidden;
        }
        .btn-file input[type="file"] {
            position: absolute;
            top: 0; right: 0;
            min-width: 100%; min-height: 100%;
            font-size: 100px; text-align: right;
            opacity: 0; cursor: pointer;
        }
        #danfeHeader {
            display: none;
            border-top: 2px solid #dee2e6;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
            margin-bottom: 1.5rem;
            background-color: #f8f9fa;
        }
        .danfe-col { padding: 0 1rem; }
        .titulo-danfe { font-size: 1.5rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .subtitulo { font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem; }
        .badge-danfe { font-size: 0.75rem; }
        .numero-series { font-size: 0.9rem; margin-top: 0.5rem; }
        .danfe-key { font-family: monospace; font-size: 0.85rem; word-break: break-all; color: #212529; }
        #xmlDetails { display: none; }
        .label-title { font-size: 0.85rem; font-weight: 600; color: #495057; text-transform: uppercase; margin-top: 0.5rem; }
        .label-value { font-size: 1rem; color: #343a40; margin-bottom: 0.75rem; }
        @media (max-width: 576px) {
            #danfeHeader { flex-direction: column; text-align: center; }
            #danfeHeader .danfe-col { margin-bottom: 1rem; }
        }
    </style>

    <div class="container-fluid py-4" id="kt_content">
        <form method="POST" enctype="multipart/form-data" action="/devolucao/new">
            @csrf
            {{-- Guarda em JS o valor atual da seleção para ser reenviado, se necessário --}}
            <input type="hidden" name="destinoPadrao" id="destinoPadrao" value="{{ $destinoPadrao }}">

            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 font-weight-bold">Importação de XML da NF-e</h5>
                    <button type="button" class="btn btn-light-primary font-weight-bold btn-sm" data-toggle="modal" data-target="#modal-manual-devolucao">
                        <i class="la la-book icon-sm"></i> Manual de Uso da Rotina
                    </button>
                </div>
              
                <div class="card-body">
                    {{-- UPLOAD DE XML --}}
                    <div class="form-group row align-items-center">
                        <label for="xmlFileInput" class="col-md-3 col-form-label label-title">
                            Arquivo XML
                        </label>
                        <div class="col-md-9 d-flex align-items-center">
                            <label class="btn btn-outline-primary btn-file mb-0 mr-3">
                                Escolher arquivo
                                <input type="file" id="xmlFileInput" name="file" accept=".xml">
                            </label>
                            <div id="filename" class="text-muted flex-grow-1">
                                Nenhum arquivo selecionado
                            </div>
                            <button type="button" id="clearBtn" class="btn btn-outline-secondary btn-sm ml-2">
                                Limpar
                            </button>
                        </div>
                    </div>
                    {{-- CABEÇALHO DANFE --}}
                    <div id="danfeHeader" class="d-flex align-items-center">
                        <div class="danfe-col flex-fill text-uppercase">
                            <div id="danfeEmitName" class="font-weight-bold"></div>
                            <div id="danfeEmitAddr" class="subtitulo"></div>
                        </div>
                        <div class="danfe-col text-center flex-fill">
                            <div class="titulo-danfe">DANFE</div>
                            <div class="subtitulo">
                                Documento Auxiliar da Nota Fiscal Eletrônica
                            </div>
                            <div id="danfeTipoNF" class="mt-2 badge badge-info badge-danfe"></div>
                            <div class="numero-series">
                                Nº <span id="xmlNumberHeader"></span> &nbsp; Série <span id="xmlSerieHeader"></span>
                            </div>
                        </div>
                        <div class="danfe-col flex-fill text-right">
                            <div class="label-title">Chave de Acesso</div>
                            <div id="danfeKey" class="danfe-key"></div>
                        </div>
                    </div>

                    {{-- SELEÇÃO: QUEM SERÁ O DESTINATÁRIO DA DEVOLUÇÃO --}}
                    <div class="form-group row mt-4">
                        <label class="col-md-3 col-form-label label-title">
                            Quem será o destinatário da devolução?
                        </label>
                        <div class="col-md-9 d-flex align-items-center">
                            <div class="custom-control custom-radio mr-4">
                                <input type="radio"
                                       id="destEmitente"
                                       name="destinatario_devolucao"
                                       value="emitente"
                                       class="custom-control-input"
                                    {{ $destinoPadrao === 'emitente' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="destEmitente">
                                    Emitente da nota (Fornecedor original)
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio"
                                       id="destDestinatario"
                                       name="destinatario_devolucao"
                                       value="destinatario"
                                       class="custom-control-input"
                                    {{ $destinoPadrao === 'destinatario' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="destDestinatario">
                                    Destinatário da nota (Cliente original)
                                </label>
                            </div>
                        </div>
                    </div>
                    {{-- FIM SELEÇÃO DESTINATÁRIO --}}

                    {{-- DETALHES DO XML --}}
                    <div id="xmlDetails" class="card mt-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="label-title">Chave NF-e</div>
                                    <div id="xmlKey" class="label-value"></div>
                                    <div class="label-title">Número</div>
                                    <div id="xmlNumber" class="label-value"></div>
                                    <div class="label-title">Série</div>
                                    <div id="xmlSerie" class="label-value"></div>
                                </div>
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="label-title">Data de Emissão</div>
                                    <div id="xmlEmissao" class="label-value"></div>
                                    <div class="label-title">Data Saída/Entrada</div>
                                    <div id="xmlSaida" class="label-value"></div>
                                </div>
                                <div class="col-12 col-sm-12 col-lg-4">
                                    <div class="label-title">Valor Produtos</div>
                                    <div id="vProd" class="label-value"></div>
                                    <div class="label-title">Valor Total NF-e</div>
                                    <div id="vNF" class="label-value"></div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                {{-- Emitente --}}
                                <div class="col-12 col-md-6">
                                    <h6 class="font-weight-bold mt-3">Emitente</h6>
                                    <div class="label-title">Nome / Razão Social</div>
                                    <div id="emitName" class="label-value"></div>
                                    <div class="label-title">CNPJ</div>
                                    <div id="emitCNPJ" class="label-value"></div>
                                    <div class="label-title">Inscrição Estadual</div>
                                    <div id="emitIE" class="label-value"></div>
                                    <div class="label-title">Telefone</div>
                                    <div id="emitFone" class="label-value"></div>
                                    <div class="label-title">Endereço</div>
                                    <div id="emitAddr" class="label-value"></div>
                                </div>
                                {{-- Destinatário --}}
                                <div class="col-12 col-md-6">
                                    <h6 class="font-weight-bold mt-3">Destinatário</h6>
                                    <div class="label-title">Nome / Razão Social</div>
                                    <div id="destName" class="label-value"></div>
                                    <div class="label-title">CPF / CNPJ</div>
                                    <div id="destCNPJCPF" class="label-value"></div>
                                    <div class="label-title">Inscrição Estadual</div>
                                    <div id="destIE" class="label-value"></div>
                                    <div class="label-title">Telefone</div>
                                    <div id="destFone" class="label-value"></div>
                                    <div class="label-title">Endereço</div>
                                    <div id="destAddr" class="label-value"></div>
                                </div>
                            </div>
                            <hr>
                            {{-- Itens da nota --}}
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="font-weight-bold mt-3">Produtos</h6>
                                    <div style="overflow-x:auto">
                                        <table class="table table-bordered table-sm" id="itensTable">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Descrição</th>
                                                <th>EAN</th>
                                                <th>NCM</th>
                                                <th>CFOP</th>
                                                <th>Qtd</th>
                                                <th>Unid.</th>
                                                <th>Valor Unit.</th>
                                                <th>Total</th>
                                            </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            {{-- Fim Itens --}}
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-right border-0">
                    <button type="submit" class="btn btn-success">
                        <i class="la la-check"></i> Importar XML
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input     = document.getElementById('xmlFileInput');
            const clearBtn  = document.getElementById('clearBtn');
            const filename  = document.getElementById('filename');
            const setText   = (id, txt) => document.getElementById(id).textContent = txt || '';
            const showFlex  = id => document.getElementById(id).style.display = 'flex';
            const showBlock = id => document.getElementById(id).style.display = 'block';
            const hide      = id => document.getElementById(id).style.display = 'none';

            // format helpers
            const fmtDateTime = dt => {
                if (!dt) return '';
                const [d,t] = dt.split('T');
                const [y,m,day] = d.split('-');
                const time = (t||'').split('-')[0];
                return `${day}/${m}/${y}` + (time ? ` ${time}` : '');
            };
            const onlyNum = s => (s||'').replace(/\D/g,'');
            const fmtCNPJ = s => {
                let v = onlyNum(s).padStart(14,'0');
                return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
            };
            const fmtCPF  = s => {
                let v = onlyNum(s).padStart(11,'0');
                return v.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
            };
            const fmtPhone = s => {
                let v = onlyNum(s);
                if (v.length === 11) return v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                if (v.length === 10) return v.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
                return s || '';
            };
            const fmtCEP = s => {
                let v = onlyNum(s).padStart(8,'0');
                return v.replace(/^(\d{5})(\d{3})$/, '$1-$2');
            };

            function clearAll() {
                input.value = '';
                filename.textContent = 'Nenhum arquivo selecionado';
                [
                    'xmlKey','xmlNumber','xmlSerie','xmlEmissao','xmlSaida',
                    'emitName','emitCNPJ','emitIE','emitFone','emitAddr',
                    'destName','destCNPJCPF','destIE','destFone','destAddr',
                    'danfeEmitName','danfeEmitAddr','danfeTipoNF','danfeKey',
                    'xmlNumberHeader','xmlSerieHeader','vProd','vNF'
                ].forEach(id => setText(id,''));
                document.querySelector('#itensTable tbody').innerHTML = '';
                hide('danfeHeader');
                hide('xmlDetails');
            }
            clearBtn.addEventListener('click', clearAll);

            input.addEventListener('change', () => {
                const file = input.files[0];
                if (!file) return clearAll();
                filename.textContent = file.name;

                const reader = new FileReader();
                reader.onload = e => {
                    const doc = new DOMParser().parseFromString(e.target.result, 'application/xml');
                    const inf  = doc.querySelector('infNFe');
                    let chave  = inf?.getAttribute('Id') || doc.querySelector('chNFe')?.textContent || '';
                    if (chave && chave.startsWith('NFe')) chave = chave.slice(3);
                    setText('xmlKey', chave);

                    // cabeçalho DANFE
                    const emit = doc.querySelector('emit');
                    if (emit) {
                        setText('danfeEmitName', emit.querySelector('xNome')?.textContent);
                        const en = emit.querySelector('enderEmit');
                        setText('danfeEmitAddr',
                            `${en.querySelector('xLgr')?.textContent||''}, nº ${en.querySelector('nro')?.textContent||''}` +
                            ` • ${en.querySelector('xMun')?.textContent||''}-${en.querySelector('UF')?.textContent||''}`
                        );
                    }
                    const tp = doc.querySelector('tpNF')?.textContent;
                    setText('danfeTipoNF', tp==='0'? '0 – Entrada' : '1 – Saída');
                    setText('danfeKey', chave.match(/.{1,4}/g)?.join(' ') || chave);

                    // IDE: número, série, datas
                    const ide = doc.querySelector('ide');
                    let num = '', ser = '';
                    if (ide) {
                        num = ide.querySelector('nNF')?.textContent || '';
                        ser = ide.querySelector('serie')?.textContent || '';
                        setText('xmlNumber', num);
                        setText('xmlSerie', ser);
                        setText('xmlEmissao', fmtDateTime(ide.querySelector('dhEmi')?.textContent || ide.querySelector('dEmi')?.textContent));
                        setText('xmlSaida', fmtDateTime(ide.querySelector('dhSaiEnt')?.textContent || ide.querySelector('dSaiEnt')?.textContent));
                    }
                    setText('xmlNumberHeader', num);
                    setText('xmlSerieHeader', ser);

                    // Totais
                    const tot = doc.querySelector('ICMSTot');
                    setText('vProd', tot?.querySelector('vProd')?.textContent ? 'R$ '+Number(tot.querySelector('vProd').textContent).toLocaleString('pt-BR', {minimumFractionDigits:2}) : '');
                    setText('vNF',   tot?.querySelector('vNF')?.textContent ? 'R$ '+Number(tot.querySelector('vNF').textContent).toLocaleString('pt-BR', {minimumFractionDigits:2}) : '');

                    showFlex('danfeHeader');
                    showBlock('xmlDetails');

                    // Emitente completo
                    if (emit) {
                        setText('emitName', emit.querySelector('xNome')?.textContent);
                        setText('emitCNPJ', fmtCNPJ(emit.querySelector('CNPJ')?.textContent));
                        setText('emitIE', emit.querySelector('IE')?.textContent);
                        setText('emitFone', fmtPhone(emit.querySelector('fone')?.textContent));
                        const ed = emit.querySelector('enderEmit');
                        setText('emitAddr',
                            `${ed.querySelector('xLgr')?.textContent||''}, nº ${ed.querySelector('nro')?.textContent||''}` +
                            ` • ${ed.querySelector('xBairro')?.textContent||''}` +
                            ` / ${ed.querySelector('xMun')?.textContent||''}-${ed.querySelector('UF')?.textContent||''}` +
                            ` • CEP ${fmtCEP(ed.querySelector('CEP')?.textContent)}`
                        );
                    }

                    // Destinatário completo
                    const dest = doc.querySelector('dest');
                    if (dest) {
                        setText('destName', dest.querySelector('xNome')?.textContent);
                        const cpf  = dest.querySelector('CPF')?.textContent;
                        const cnpj = dest.querySelector('CNPJ')?.textContent;
                        setText('destCNPJCPF', cnpj? fmtCNPJ(cnpj) : fmtCPF(cpf));
                        setText('destIE', dest.querySelector('IE')?.textContent);
                        setText('destFone', fmtPhone(dest.querySelector('fone')?.textContent));
                        const dd = dest.querySelector('enderDest');
                        setText('destAddr',
                            `${dd.querySelector('xLgr')?.textContent||''}, nº ${dd.querySelector('nro')?.textContent||''}` +
                            ` • ${dd.querySelector('xBairro')?.textContent||''}` +
                            ` / ${dd.querySelector('xMun')?.textContent||''}-${dd.querySelector('UF')?.textContent||''}` +
                            ` • CEP ${fmtCEP(dd.querySelector('CEP')?.textContent)}`
                        );
                    }

                    // Itens (produtos)
                    const prods = doc.querySelectorAll('det');
                    let tbody = document.querySelector('#itensTable tbody');
                    tbody.innerHTML = '';
                    let count = 1;
                    prods.forEach(det => {
                        const prod = det.querySelector('prod');
                        if (!prod) return;
                        tbody.innerHTML += `<tr>
                            <td>${count++}</td>
                            <td>${prod.querySelector('xProd')?.textContent || ''}</td>
                            <td>${prod.querySelector('cEAN')?.textContent || ''}</td>
                            <td>${prod.querySelector('NCM')?.textContent || ''}</td>
                            <td>${prod.querySelector('CFOP')?.textContent || ''}</td>
                            <td>${prod.querySelector('qCom')?.textContent || ''}</td>
                            <td>${prod.querySelector('uCom')?.textContent || ''}</td>
                            <td>${prod.querySelector('vUnCom')?.textContent || ''}</td>
                            <td>${prod.querySelector('vProd')?.textContent || ''}</td>
                        </tr>`;
                    });
                };
                reader.onerror = () => alert('Erro ao ler o XML');
                reader.readAsText(file);
            });
        });

        // Atualiza hidden input destinoPadrao quando o usuário muda a seleção
        document.addEventListener('DOMContentLoaded', () => {
            const radios      = document.querySelectorAll('input[name="destinatario_devolucao"]');
            const hiddenInput = document.getElementById('destinoPadrao');
            radios.forEach(radio => {
                radio.addEventListener('change', () => {
                    hiddenInput.value = radio.value;
                });
            });
        });
    </script>
<div class="modal fade" id="modal-manual-devolucao" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-primary font-weight-bold">
                    <i class="la la-book text-primary icon-lg"></i> Manual Operacional: Módulo de Devolução NF-e
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <div class="alert alert-custom alert-light-primary fade show mb-5" role="alert">
                    <div class="alert-icon"><i class="la la-info-circle"></i></div>
                    <div class="alert-text">
                        <strong>Automação Inteligente:</strong> Este módulo simplifica a emissão de Notas Fiscais de Devolução através da leitura do arquivo XML original. O sistema calcula impostos, preenche itens e faz a gestão correta dos cadastros de forma automática com base nas suas seleções.
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-custom gutter-b border shadow-none">
                            <div class="card-body">
                                <h4 class="font-weight-bold text-dark mb-4">1. Importação e Tipo de Alvo</h4>
                                <ol class="list-timeline list-timeline-light">
                                    <li class="mb-4">
                                        <span class="font-weight-bold text-primary">Carregar o XML:</span> Clique em <em>Escolher arquivo</em> e selecione a NF-e original.
                                    </li>
                                    <li class="mb-4">
                                        <span class="font-weight-bold text-danger">Definição do Destinatário:</span> Este é o passo mais importante!
                                        <div class="bg-light p-3 rounded mt-2 border-left border-danger">
                                            <p class="mb-1"><strong>• Emitente da Nota:</strong> Selecione se estiver a devolver uma mercadoria compradada ao seu <u>Fornecedor</u> (Devolução de Compra).</p>
                                            <p class="mb-0"><strong>• Destinatário da Nota:</strong> Selecione se um <u>Cliente</u> devolveu uma mercadoria sua e você está a emitir uma Nota de Entrada.</p>
                                        </div>
                                    </li>
                                    <li>
                                        <span class="font-weight-bold text-success">Executar:</span> Clique em <em>Importar XML</em> para processar as informações.
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-custom gutter-b border shadow-none">
                            <div class="card-body">
                                <h4 class="font-weight-bold text-dark mb-4">2. Conferência de Itens e Frete</h4>
                                <ul class="list-unstyled">
                                    <li class="mb-4 d-flex align-items-start">
                                        <i class="la la-check-circle text-success icon-lg mr-2 mt-1"></i>
                                        <div>
                                            <strong class="text-dark">Revisão de Produtos:</strong> O sistema carrega todos os itens. Se precisar de alterar o CFOP, CST/CSOSN ou a quantidade (em caso de devolução parcial), clique no botão de ações ao lado do subtotal do produto.
                                        </div>
                                    </li>
                                    <li class="mb-4 d-flex align-items-start">
                                        <i class="la la-truck text-warning icon-lg mr-2 mt-1"></i>
                                        <div>
                                            <strong class="text-dark">Dados de Transporte:</strong> Escolha a transportadora. Se for nova, clique no botão <span class="badge badge-warning font-weight-bold">+</span> para cadastrá-la no momento apenas com o CNPJ. Configure a modalidade do frete, pesos e placa do veículo.
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-custom border shadow-none bg-light-success">
                    <div class="card-body">
                        <h4 class="font-weight-bold text-success mb-3">3. Guardar e Emitir</h4>
                        <p class="text-dark-75">
                            Insira o <strong>Motivo da Devolução</strong> e as <strong>Observações</strong> necessárias nos campos de texto (esses dados vão sair impressos nas Informações Complementares do DANFE). 
                            Confira o valor total exibido no rodapé e clique em <span class="badge badge-success">Salvar</span>.
                        </p>
                        <p class="mb-0 text-muted font-size-sm">
                            <i class="la la-arrow-right font-size-sm"></i> O sistema fará a atualização automática do stock (caso esteja configurado) e enviará o arquivo XML gerado ao seu escritório de contabilidade assim que a transmissão for concluída com sucesso.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary font-weight-bold" data-dismiss="modal">Entendi, Fechar Manual</button>
            </div>
        </div>
    </div>
</div>
@endsection
