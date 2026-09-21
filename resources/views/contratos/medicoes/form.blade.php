@php
    $editing = isset($fatura) && $fatura;
    $selectedContract = old('contrato_eng_id', $editing ? $fatura->contrato_eng_id : optional($contratoSelecionado ?? null)->id);
    $selectedClient = old('cliente_id', $editing ? $fatura->cliente_id : optional($contratoSelecionado ?? null)->cliente_id);
    $initialItems = old('itens', $editing ? $fatura->itens->map(fn($i) => [
        'tipo_item'=>$i->tipo_item,
        'servico_id'=>$i->servico_id,
        'produto_id'=>$i->produto_id,
        'quantidade'=>$i->quantidade,
        'valor_unitario'=>$i->valor_unitario,
        'valor_total'=>$i->valor_total,
    ])->toArray() : []);
@endphp

<form method="POST" action="{{ $formAction }}">
    @csrf
    <div class="card card-custom gutter-b">
        <div class="card-header"><h3 class="card-title">{{ $editing ? 'Editar Medição / Faturamento' : 'Novo Lançamento / Medição' }}</h3></div>
        <div class="card-body">
            @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

            <div class="row">
                <div class="col-lg-4 form-group">
                    <label>Contrato</label>
                    <select name="contrato_eng_id" id="med_contrato" class="form-control">
                        <option value="">Serviço avulso</option>
                        @foreach($contratos as $contrato)
                            <option value="{{ $contrato->id }}" {{ (string)$selectedContract === (string)$contrato->id ? 'selected' : '' }}>#{{ $contrato->numero_contrato ?: $contrato->id }} - {{ optional($contrato->cliente)->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 form-group">
                    <label>Cliente *</label>
                    <select name="cliente_id" id="med_cliente" class="form-control select2" required>
                        <option value="">Selecione</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ (string)$selectedClient === (string)$cliente->id ? 'selected' : '' }}>{{ $cliente->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 form-group">
                    <label>Categoria financeira *</label>
                    <select name="categoria_conta_id" class="form-control" required>
                        <option value="">Selecione</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ (string)old('categoria_conta_id',$editing?$fatura->categoria_conta_id:'') === (string)$cat->id ? 'selected' : '' }}>{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 form-group"><label>Data emissão</label><input type="date" name="nf_data_emissao" class="form-control" value="{{ old('nf_data_emissao',$editing?optional($fatura->data_faturamento)->format('Y-m-d'):date('Y-m-d')) }}"></div>
                <div class="col-lg-3 form-group"><label>Código da obra</label><input name="codigo_obra" class="form-control" value="{{ old('codigo_obra',$fatura->codigo_obra ?? '') }}"></div>
                <div class="col-lg-3 form-group">
                    <label>Município prestação</label>
                    <select name="cidade_prestacao_id" class="form-control select2">
                        <option value="">Selecione</option>
                        @foreach($cidades as $cidade)
                            <option value="{{ $cidade->id }}" {{ (string)old('cidade_prestacao_id',$fatura->municipio_prestacao_id ?? '') === (string)$cidade->id ? 'selected' : '' }}>{{ $cidade->nome }} ({{ $cidade->uf }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach(['Pendente','Em Andamento','Finalizado','Cancelado'] as $status)
                            <option value="{{ $status }}" {{ old('status',$fatura->status ?? 'Pendente') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <h5>Itens / Serviços / Locações</h5>
                <button type="button" id="med_add_item" class="btn btn-sm btn-primary">+ Item</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="med_itens">
                    <thead><tr><th>Tipo</th><th>Serviço / Produto</th><th>Qtd.</th><th>Valor Unit.</th><th>Total</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-lg-4 form-group"><label>Valor Total *</label><input name="valor_total" id="med_valor_total" class="form-control font-weight-bold" readonly required value="{{ old('valor_total',$editing?number_format((float)$fatura->valor_total,2,',','.'):'0,00') }}"></div>
                <div class="col-lg-4 form-group"><label>Retenção</label><input name="valor_retencao" class="form-control" value="{{ old('valor_retencao',$editing?number_format((float)$fatura->valor_retencao,2,',','.'):'0,00') }}"></div>
                <div class="col-lg-4 form-group"><label>Observação</label><input name="observacao" class="form-control" value="{{ old('observacao',$fatura->observacao ?? '') }}"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <h5>Equipe da Medição</h5>
                <button type="button" id="med_add_func" class="btn btn-sm btn-light-primary">+ Funcionário</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="med_funcs">
                    <thead><tr><th>Funcionário</th><th>Função</th><th>Diárias</th><th>Valor diária</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>

            @if(!$editing)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <h5>Parcelas do Contas a Receber</h5>
                <button type="button" id="med_add_parcela" class="btn btn-sm btn-light-success">+ Parcela</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="med_parcelas">
                    <thead><tr><th>Vencimento</th><th>Valor</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            @else
                <div class="alert alert-light mt-3">Parcelas existentes são preservadas. Se ainda estiverem abertas, o valor total é redistribuído entre elas ao salvar.</div>
            @endif
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('contratos.medicoes.index') }}" class="btn btn-light-danger">Cancelar</a>
            <button class="btn btn-success">Salvar lançamento</button>
        </div>
    </div>
</form>

<script>
(function(){
    const services = @json($servicos->map(fn($s)=>['id'=>$s->id,'nome'=>$s->nome,'valor'=>$s->valor ?? 0]));
    const products = @json($produtos->map(fn($p)=>['id'=>$p->id,'nome'=>$p->nome,'valor'=>$p->valor_venda ?? 0]));
    const employees = @json($funcionarios->map(fn($f)=>['id'=>$f->id,'nome'=>$f->nome]));
    const contracts = @json($contratos->map(fn($c)=>[
        'id'=>$c->id,
        'cliente_id'=>$c->cliente_id,
        'itens'=>$c->itens->map(fn($i)=>[
            'tipo_item'=>$i->tipo_item,'servico_id'=>$i->servico_id,'produto_id'=>$i->produto_id,
            'quantidade'=>$i->quantidade_prevista,'valor_unitario'=>$i->valor_unitario,
        ])->values(),
    ])->values());
    const initialItems = @json($initialItems);
    const initialFuncs = @json(old('funcionarios', $editing ? $fatura->funcionarios->map(fn($f)=>[
        'funcionario_id'=>$f->funcionario_id,'funcao'=>$f->funcao,'diarias'=>$f->diarias,'valor_diaria'=>$f->valor_diaria
    ])->toArray() : []));

    const esc = v => String(v ?? '').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    const opts = (arr,sel) => '<option value="">Selecione</option>'+arr.map(i=>'<option value="'+i.id+'" '+(String(i.id)===String(sel)?'selected':'')+'>'+esc(i.nome)+'</option>').join('');

    let itemIdx=0;
    function addItem(item={}){
        const i=itemIdx++, tr=document.createElement('tr');
        tr.innerHTML='<td><select name="itens['+i+'][tipo_item]" class="form-control tipo"><option value="Servico">Serviço</option><option value="Locacao">Locação</option></select></td>'+
            '<td><select name="itens['+i+'][servico_id]" class="form-control servico">'+opts(services,item.servico_id)+'</select><select name="itens['+i+'][produto_id]" class="form-control produto" style="display:none">'+opts(products,item.produto_id)+'</select></td>'+
            '<td><input name="itens['+i+'][quantidade]" class="form-control qtd" value="'+esc(item.quantidade||1)+'"></td>'+
            '<td><input name="itens['+i+'][valor_unitario]" class="form-control valor" value="'+esc(item.valor_unitario||0)+'"></td>'+
            '<td><input name="itens['+i+'][valor_total]" class="form-control subtotal" readonly value="'+esc(item.valor_total||0)+'"></td>'+
            '<td><button type="button" class="btn btn-sm btn-danger del">×</button></td>';
        document.querySelector('#med_itens tbody').appendChild(tr);
        const tipo=tr.querySelector('.tipo'); tipo.value=item.tipo_item||'Servico';
        function toggle(){tr.querySelector('.servico').style.display=tipo.value==='Servico'?'':'none';tr.querySelector('.produto').style.display=tipo.value==='Locacao'?'':'none';}
        function calc(){const q=parseFloat(String(tr.querySelector('.qtd').value).replace(',','.'))||0;const v=parseFloat(String(tr.querySelector('.valor').value).replace('.','').replace(',','.'))||0;tr.querySelector('.subtotal').value=(q*v).toFixed(2).replace('.',',');calcTotal();}
        tipo.addEventListener('change',toggle); tr.querySelector('.qtd').addEventListener('input',calc); tr.querySelector('.valor').addEventListener('input',calc); tr.querySelector('.del').onclick=()=>{tr.remove();calcTotal();}; toggle();calc();
    }
    function calcTotal(){let total=0;document.querySelectorAll('#med_itens .subtotal').forEach(i=>total+=parseFloat(String(i.value).replace('.','').replace(',','.'))||0);document.getElementById('med_valor_total').value=total.toFixed(2).replace('.',',');}
    document.getElementById('med_add_item').onclick=()=>addItem();
    (initialItems.length?initialItems:[{}]).forEach(addItem);

    let funcIdx=0;
    function addFunc(f={}){
        const i=funcIdx++,tr=document.createElement('tr');
        tr.innerHTML='<td><select name="funcionarios['+i+'][funcionario_id]" class="form-control">'+opts(employees,f.funcionario_id)+'</select></td>'+
            '<td><input name="funcionarios['+i+'][funcao]" class="form-control" value="'+esc(f.funcao||'')+'"></td>'+
            '<td><input name="funcionarios['+i+'][diarias]" class="form-control" value="'+esc(f.diarias||1)+'"></td>'+
            '<td><input name="funcionarios['+i+'][valor_diaria]" class="form-control" value="'+esc(f.valor_diaria||0)+'"></td>'+
            '<td><button type="button" class="btn btn-sm btn-danger">×</button></td>';
        tr.querySelector('button').onclick=()=>tr.remove();
        document.querySelector('#med_funcs tbody').appendChild(tr);
    }
    document.getElementById('med_add_func').onclick=()=>addFunc();
    initialFuncs.forEach(addFunc);

    @if(!$editing)
    let parcelaIdx=0;
    function addParcela(p={}){
        const i=parcelaIdx++,tr=document.createElement('tr');
        tr.innerHTML='<td><input type="date" name="parcelas['+i+'][vencimento]" class="form-control" value="'+esc(p.vencimento||'{{ date('Y-m-d') }}')+'"></td>'+
            '<td><input name="parcelas['+i+'][valor]" class="form-control" value="'+esc(p.valor||'0,00')+'"></td>'+
            '<td><button type="button" class="btn btn-sm btn-danger">×</button></td>';
        tr.querySelector('button').onclick=()=>tr.remove();
        document.querySelector('#med_parcelas tbody').appendChild(tr);
    }
    document.getElementById('med_add_parcela').onclick=()=>addParcela();
    addParcela();
    @endif

    const contractSelect=document.getElementById('med_contrato');
    contractSelect.addEventListener('change',function(){
        const c=contracts.find(x=>String(x.id)===String(this.value));
        if(!c)return;
        document.getElementById('med_cliente').value=String(c.cliente_id);
        document.querySelector('#med_itens tbody').innerHTML=''; itemIdx=0;
        (c.itens.length?c.itens:[{}]).forEach(addItem);
    });
})();
</script>
