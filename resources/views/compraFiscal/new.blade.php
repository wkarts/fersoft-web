@extends('default.layout')
@section('content')
    <style>
        .btn-file {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-file input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            opacity: 0;
            cursor: pointer;
        }
        #danfeHeader {
            display: none;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background-color: #f4f6f9;
            border: 1px solid #e4e6ef;
        }
        .danfe-col { padding: 0 1rem; }
        .titulo-danfe { font-size: 1.8rem; font-weight: 800; letter-spacing: .05em; color: #181c32; }
        .subtitulo { font-size: 0.9rem; color: #7e8299; margin-top: 0.25rem; font-weight: 500; }
        .badge-danfe { font-size: 0.8rem; padding: 0.4rem 0.8rem; }
        .numero-series { font-size: 1rem; font-weight: 600; margin-top: 0.5rem; color: #3f4254; }
        .danfe-key { font-family: monospace; font-size: 0.95rem; word-break: break-all; color: #181c32; font-weight: 600; background: #e4e6ef; padding: 0.5rem; border-radius: 4px;}
        #xmlDetails { display: none; }
        .label-title { font-size: 0.8rem; font-weight: 700; color: #a1a5b7; text-transform: uppercase; margin-top: 0.5rem; letter-spacing: 0.5px;}
        .label-value { font-size: 1.05rem; color: #3f4254; margin-bottom: 1rem; font-weight: 500;}
        @media (max-width: 576px) {
            #danfeHeader { flex-direction: column; text-align: center; }
            #danfeHeader .danfe-col { margin-bottom: 1rem; }
        }
    </style>

    <div class="container-fluid py-6" id="kt_content">
        <form method="POST" enctype="multipart/form-data" action="{{ url('compraFiscal/new') }}">
            @csrf
            <div class="card card-custom shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-6 pb-0">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label font-weight-bolder text-dark">Importação de XML da NF-e</span>
                        <span class="text-muted mt-1 font-weight-bold font-size-sm">Faça o upload do arquivo XML para iniciar a importação de compra</span>
                    </h3>
                </div>

                <div class="card-body">
                    {{-- UPLOAD DE XML --}}
                    <div class="form-group row align-items-center bg-light-primary p-6 rounded mb-8">
                        <label for="xmlFileInput" class="col-md-3 col-form-label font-weight-bold text-primary text-right">
                            <i class="flaticon2-file text-primary mr-2"></i> Arquivo XML
                        </label>
                        <div class="col-md-9 d-flex align-items-center">
                            <label class="btn btn-primary btn-file mb-0 mr-3 shadow-sm font-weight-bold">
                                Escolher arquivo
                                <input type="file" id="xmlFileInput" name="file" accept=".xml">
                            </label>
                            <div id="filename" class="text-dark-50 font-weight-bold flex-grow-1">
                                Nenhum arquivo selecionado
                            </div>
                            <button type="button" id="clearBtn" class="btn btn-light-danger btn-sm ml-2 font-weight-bold">
                                <i class="la la-trash"></i> Limpar
                            </button>
                        </div>
                    </div>

                    {{-- CABEÇALHO DANFE --}}
                    <div id="danfeHeader" class="d-flex align-items-center shadow-sm">
                        <div class="danfe-col flex-fill text-uppercase">
                            <div id="danfeEmitName" class="font-weight-bolder text-dark font-size-lg"></div>
                            <div id="danfeEmitAddr" class="subtitulo"></div>
                        </div>

                        <div class="danfe-col text-center flex-fill border-left border-right border-light-dark">
                            <div class="titulo-danfe">DANFE</div>
                            <div class="subtitulo">Documento Auxiliar da Nota Fiscal Eletrônica</div>
                            <div id="danfeTipoNF" class="mt-3 badge badge-primary badge-danfe"></div>
                            <div class="numero-series">
                                Nº <span id="xmlNumberHeader" class="text-primary"></span> &nbsp;|&nbsp; Série <span id="xmlSerieHeader" class="text-primary"></span>
                            </div>
                        </div>

                        <div class="danfe-col flex-fill text-right">
                            <div class="label-title mb-2">Chave de Acesso</div>
                            <div id="danfeKey" class="danfe-key text-center"></div>
                        </div>
                    </div>

                    {{-- DETALHES DO XML --}}
                    <div id="xmlDetails" class="card card-custom border shadow-none mt-4">
                        <div class="card-body">
                            <div class="row bg-light-secondary p-4 rounded mb-6">
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="label-title">Chave NF-e</div>
                                    <div id="xmlKey" class="label-value font-weight-bold text-info"></div>
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
                            </div>

                            <div class="row">
                                {{-- Emitente --}}
                                <div class="col-12 col-md-6 border-right">
                                    <h5 class="font-weight-bolder text-dark mb-6">
                                        <i class="flaticon2-delivery-truck text-primary mr-2"></i> Emitente
                                    </h5>
                                    <div class="label-title">Nome / Razão Social</div>
                                    <div id="emitName" class="label-value font-weight-bold"></div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="label-title">CNPJ</div>
                                            <div id="emitCNPJ" class="label-value"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="label-title">Inscrição Estadual</div>
                                            <div id="emitIE" class="label-value"></div>
                                        </div>
                                    </div>
                                    <div class="label-title">Telefone</div>
                                    <div id="emitFone" class="label-value"></div>
                                    <div class="label-title">Endereço</div>
                                    <div id="emitAddr" class="label-value"></div>
                                </div>

                                {{-- Destinatário --}}
                                <div class="col-12 col-md-6 pl-6">
                                    <h5 class="font-weight-bolder text-dark mb-6">
                                        <i class="flaticon2-user text-success mr-2"></i> Destinatário
                                    </h5>
                                    <div class="label-title">Nome / Razão Social</div>
                                    <div id="destName" class="label-value font-weight-bold"></div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="label-title">CPF / CNPJ</div>
                                            <div id="destCNPJCPF" class="label-value"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="label-title">Inscrição Estadual</div>
                                            <div id="destIE" class="label-value"></div>
                                        </div>
                                    </div>
                                    <div class="label-title">Telefone</div>
                                    <div id="destFone" class="label-value"></div>
                                    <div class="label-title">Endereço</div>
                                    <div id="destAddr" class="label-value"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-top text-right pt-6 pb-6">
                    <button type="submit" class="btn btn-success font-weight-bolder px-8">
                        <i class="la la-check"></i> Importar XML para o Sistema
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input     = document.getElementById('xmlFileInput');
            const clearBtn  = document.getElementById('clearBtn');
            const filename  = document.getElementById('filename');
            const setText   = (id, txt) => document.getElementById(id).textContent = txt || '';
            const showFlex  = id => document.getElementById(id).style.display = 'flex';
            const showBlock = id => document.getElementById(id).style.display = 'block';
            const hide      = id => document.getElementById(id).style.display = 'none';

            // formatações
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

            // limpa tudo
            function clearAll() {
                input.value = '';
                filename.textContent = 'Nenhum arquivo selecionado';
                [
                    'xmlKey','xmlNumber','xmlSerie','xmlEmissao','xmlSaida',
                    'emitName','emitCNPJ','emitIE','emitFone','emitAddr',
                    'destName','destCNPJCPF','destIE','destFone','destAddr',
                    'danfeEmitName','danfeEmitAddr','danfeTipoNF','danfeKey',
                    'xmlNumberHeader','xmlSerieHeader'
                ].forEach(id => setText(id,''));
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

                    // infNFe e chave
                    const inf  = doc.querySelector('infNFe');
                    let chave  = inf?.getAttribute('Id') || doc.querySelector('chNFe')?.textContent || '';
                    if (chave.startsWith('NFe')) chave = chave.slice(3);
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
                    setText('danfeTipoNF', tp==='0'? '0 – ENTRADA' : '1 – SAÍDA');
                    setText('danfeKey', chave.match(/.{1,4}/g)?.join(' ') || chave);

                    // IDE: número e série
                    const ide = doc.querySelector('ide');
                    let num = '', ser = '';
                    if (ide) {
                        num = ide.querySelector('nNF')?.textContent || '';
                        ser = ide.querySelector('serie')?.textContent || '';
                        setText('xmlNumber', num);
                        setText('xmlSerie', ser);
                        setText('xmlEmissao', fmtDateTime(ide.querySelector('dhEmi')?.textContent || ide.querySelector('dEmi')?.textContent));
                        setText('xmlSaida',    fmtDateTime(ide.querySelector('dhSaiEnt')?.textContent || ide.querySelector('dSaiEnt')?.textContent));
                    }
                    // preenche header com nº e série
                    setText('xmlNumberHeader', num);
                    setText('xmlSerieHeader', ser);

                    showFlex('danfeHeader');
                    showBlock('xmlDetails');

                    // Emitente completo
                    if (emit) {
                        setText('emitName', emit.querySelector('xNome')?.textContent);
                        setText('emitCNPJ', fmtCNPJ(emit.querySelector('CNPJ')?.textContent));
                        setText('emitIE',   emit.querySelector('IE')?.textContent);
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
                        setText('destIE',   dest.querySelector('IE')?.textContent);
                        setText('destFone', fmtPhone(dest.querySelector('fone')?.textContent));
                        const dd = dest.querySelector('enderDest');
                        setText('destAddr',
                            `${dd.querySelector('xLgr')?.textContent||''}, nº ${dd.querySelector('nro')?.textContent||''}` +
                            ` • ${dd.querySelector('xBairro')?.textContent||''}` +
                            ` / ${dd.querySelector('xMun')?.textContent||''}-${dd.querySelector('UF')?.textContent||''}` +
                            ` • CEP ${fmtCEP(dd.querySelector('CEP')?.textContent)}`
                        );
                    }
                };
                reader.onerror = () => alert('Erro ao ler o XML');
                reader.readAsText(file);
            });
        });
    </script>
@endsection
