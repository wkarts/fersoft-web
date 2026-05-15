<div class="modal fade" id="modal_retencoes" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Retenções conta a pagar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">INSS</label>
                        <input type="tel" id="valor_inss" name="valor_inss" class="form-control money" value="{{ isset($conta) ? moeda($conta->valor_inss) : '' }}">
                    </div>

                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">ISS</label>
                        <input type="tel" id="valor_iss" name="valor_iss" class="form-control money" value="{{ isset($conta) ? moeda($conta->valor_iss) : '' }}">
                    </div>

                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">PIS</label>
                        <input type="tel" id="valor_pis" name="valor_pis" class="form-control money" value="{{ isset($conta) ? moeda($conta->valor_pis) : '' }}">
                    </div>

                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">COFINS</label>
                        <input type="tel" id="valor_cofins" name="valor_cofins" class="form-control money" value="{{ isset($conta) ? moeda($conta->valor_cofins) : '' }}">
                    </div>

                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">IR</label>
                        <input type="tel" id="valor_ir" name="valor_ir" class="form-control money" value="{{ isset($conta) ? moeda($conta->valor_ir) : '' }}">
                    </div>

                    {{-- Campo CSLL Corrigido --}}
                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">CSLL</label>
                        <input type="tel" id="valor_csll" name="valor_csll" class="form-control money" value="{{ isset($conta) ? moeda($conta->valor_csll) : '' }}">
                    </div>

                    <div class="form-group col-md-3 col-6">
                        <label class="col-form-label">Outras retenções</label>
                        <input type="tel" id="outras_retencoes" name="outras_retencoes" class="form-control money" value="{{ isset($conta) ? moeda($conta->outras_retencoes) : '' }}">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                @if(isset($conta) && !$conta->retencoes_processadas)
                    <button type="button" class="btn btn-primary font-weight-bold" onclick="gerarContasRetencao({{ $conta->id }})">
                        <i class="fa fa-sync"></i> Gerar Contas de Retenção
                    </button>
                @else
                    <span class="badge badge-light-success">Retenções já processadas</span>
                @endif
                <button type="button" class="btn btn-light-success font-weight-bold salvar-retencoes" data-dismiss="modal">Salvar</button>
            </div>
        </div>
    </div>
</div>