<div class="quick-actions d-flex flex-wrap align-items-center ml-2 mt-1" role="group" aria-label="Cadastros rápidos">
    <span class="quick-actions-label text-muted small mr-1">Cadastros rápidos:</span>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-open" data-quick-type="cliente"><i class="fa fa-user"></i> <span>Cliente</span></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-open" data-quick-type="fornecedor"><i class="fa fa-truck"></i> <span>Fornecedor</span></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-open" data-quick-type="veiculo"><i class="fa fa-car"></i> <span>Veículo</span></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-open" data-quick-type="funcionario"><i class="fa fa-users"></i> <span>Colaborador</span></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-open" data-quick-type="motorista"><i class="fa fa-id-card"></i> <span>Motorista</span></button>
</div>
<style>
    .quick-actions { gap: .35rem; }
    .quick-actions .btn { white-space: nowrap; }
    @media (max-width: 767.98px) {
        .quick-actions { margin-left: 0 !important; margin-top: .5rem !important; width: 100%; }
        .quick-actions-label { width: 100%; }
        .quick-actions .btn { flex: 1 1 calc(50% - .35rem); }
    }
</style>
