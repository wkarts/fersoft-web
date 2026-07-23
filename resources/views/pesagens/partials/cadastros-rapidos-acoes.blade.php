<div id="quick-actions-pesagens" class="quick-actions d-flex align-items-center justify-content-end" role="group" aria-label="Cadastros rápidos">
    <button type="button" class="btn btn-sm btn-outline-secondary quick-action-icon quick-open" data-quick-type="cliente" data-hint="Cadastrar cliente" title="Cadastrar cliente" aria-label="Cadastrar cliente"><i class="fa fa-user"></i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-action-icon quick-open" data-quick-type="fornecedor" data-hint="Cadastrar fornecedor" title="Cadastrar fornecedor" aria-label="Cadastrar fornecedor"><i class="fa fa-truck"></i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-action-icon quick-open" data-quick-type="veiculo" data-hint="Cadastrar veículo" title="Cadastrar veículo" aria-label="Cadastrar veículo"><i class="fa fa-car"></i></button>
    <button type="button" class="btn btn-sm btn-outline-secondary quick-action-icon quick-open" data-quick-type="motorista" data-hint="Cadastrar motorista" title="Cadastrar motorista" aria-label="Cadastrar motorista"><i class="fa fa-id-card"></i></button>
</div>
<style>
    .quick-actions { gap: .35rem; }
    .quick-action-icon { position: relative; width: 30px; height: 30px; padding: 0 !important; display: inline-flex; align-items: center; justify-content: center; }
    .quick-action-icon i { font-size: 13px; }
    .quick-action-icon::after { content: attr(data-hint); position: absolute; right: 0; bottom: calc(100% + 7px); z-index: 1080; display: block; width: max-content; max-width: 190px; padding: .3rem .5rem; border-radius: .2rem; background: #1f2937; color: #fff; font-size: 12px; font-weight: 400; line-height: 1.2; opacity: 0; pointer-events: none; transform: translateY(3px); transition: opacity .15s ease, transform .15s ease; }
    .quick-action-icon:hover::after, .quick-action-icon:focus::after { opacity: 1; transform: translateY(0); }
    @media (max-width: 575.98px) { .quick-actions { justify-content: flex-start !important; margin-top: .5rem; } }
</style>
