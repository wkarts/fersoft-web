<script>
$(function () {
    const quickUrl = @json(route('pesagens.cadastros-rapidos.save', ['tipo' => '__tipo__']));
    let quickContext = null;
    const $quick = $('#modalCadastroRapido');
    $('[data-toggle="tooltip"]').tooltip();
    function clearErrors() { $('#quick_form .is-invalid').removeClass('is-invalid'); $('#quick_form .invalid-feedback').text(''); $('#quick_alert').addClass('d-none').text(''); }
    function restorePesagem() {
        if (!quickContext) return;
        const $pesagem = $('#modalPesagem');
        $pesagem.one('shown.bs.modal', () => $pesagem.removeData('quick-preserve'));
        $pesagem.modal('show'); quickContext = null;
    }
    $(document).on('click', '.quick-open', function (e) {
        e.preventDefault(); clearErrors(); $('#quick_form')[0].reset();
        const type = $(this).data('quick-type'); const $pesagem = $('#modalPesagem');
        quickContext = $pesagem.hasClass('show') ? { field: type === 'funcionario' ? null : type } : null;
        $('#quick_type').val(type); $('#quick_title').text(type === 'motorista' ? 'Novo motorista' : 'Novo ' + type);
        $('.quick-person,.quick-vehicle,.quick-employee').addClass('d-none');
        if (type === 'cliente' || type === 'fornecedor') $('.quick-person').removeClass('d-none');
        if (type === 'veiculo') $('.quick-vehicle').removeClass('d-none');
        if (type === 'funcionario' || type === 'motorista') { $('.quick-employee').removeClass('d-none'); $('input[name="motorista"]').prop('checked', type === 'motorista'); }
        $('.quick-cnh').toggle(type === 'motorista');
        $('.quick-image').toggle(type === 'cliente' || type === 'fornecedor' || type === 'veiculo' || type === 'funcionario' || type === 'motorista');
        const openQuick = () => $quick.modal('show');
        if (quickContext) { $pesagem.data('quick-preserve', true).one('hidden.bs.modal.quick', openQuick).modal('hide'); } else openQuick();
    });
    $('input[name="motorista"]').on('change', function () { $('.quick-cnh').toggle(this.checked); });
    $quick.on('hidden.bs.modal', restorePesagem);
    $('#quick_form').on('submit', function (e) {
        e.preventDefault(); clearErrors(); const $button = $('#quick_submit').prop('disabled', true); const type=$('#quick_type').val();
        const target = type === 'motorista' ? 'funcionario' : type;
        $.ajax({ url: quickUrl.replace('__tipo__', target), method: 'POST', dataType: 'json', data: new FormData(this), processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept':'application/json' } })
        .done(function (response) {
            const item=response.data; let selector=null;
            if (quickContext) selector = quickContext.field === 'cliente' ? '#cliente_id' : quickContext.field === 'fornecedor' ? '#fornecedor_id' : quickContext.field === 'veiculo' ? '#veiculo_id' : quickContext.field === 'motorista' ? '#motorista_id' : null;
            if (selector && item) { const option=new Option(item.text,item.id,true,true); Object.keys(item).forEach(k => $(option).data(k,item[k])); $(selector).append(option).trigger('change'); }
            $quick.data('saved', true).modal('hide'); if (!quickContext) abrirModalMensagem('Sucesso', response.message);
        }).fail(function(xhr) {
            if (xhr.status === 419) { $('#quick_alert').removeClass('d-none').text('Sessão expirada. Atualize a página e tente novamente.'); return; }
            const errors=xhr.responseJSON && xhr.responseJSON.errors; $('#quick_alert').removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message) || 'Não foi possível salvar o cadastro.');
            if(errors) Object.keys(errors).forEach(function(field){ const $input=$('#quick_form [name="'+field+'"]').first(); $input.addClass('is-invalid').siblings('.invalid-feedback').text(errors[field][0]); });
        }).always(()=>$button.prop('disabled',false));
    });
});
</script>
