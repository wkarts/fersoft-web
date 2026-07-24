<script>
$(function () {
    const quickUrl = @json(route('pesagens.cadastros-rapidos.save', ['tipo' => '__tipo__']));
    let quickContext = null;
    const $quick = $('#modalCadastroRapido');
    const onlyDigits = value => String(value || '').replace(/\D/g, '');

    // Evita duplicidade visual caso exista HTML antigo em cache durante a atualização.
    const $quickActions = $('.quick-actions');
    if ($quickActions.length > 1) $quickActions.not($quickActions.last()).remove();

    const quickSelect2Config = { dropdownParent: $quick, width: '100%' };
    $('#quick_cidade_id').select2($.extend({}, quickSelect2Config, { placeholder: 'Digite para buscar uma cidade', allowClear: true }));
    $('#quick_motorista_id').select2($.extend({}, quickSelect2Config, {
        placeholder: 'Digite nome ou CPF para buscar motorista', allowClear: true,
        ajax: { url: @json(route('pesagens.search.motorista')), dataType: 'json', delay: 250,
            data: params => ({ term: params.term }), processResults: data => ({ results: data.results || [] }) }
    }));
    $('#quick_proprietario_tp, #quick_categoria_cnh').select2($.extend({}, quickSelect2Config, { minimumResultsForSearch: Infinity }));

    function clearErrors() {
        $('#quick_form .is-invalid').removeClass('is-invalid');
        $('#quick_form .invalid-feedback').text('');
        $('#quick_alert').addClass('d-none').text('');
    }

    function showAlert(message) { $('#quick_alert').removeClass('d-none').text(message); }

    function setCidade(nome, uf) {
        const normalizedName = String(nome || '').trim().toLocaleUpperCase('pt-BR');
        const normalizedUf = String(uf || '').trim().toUpperCase();
        const $option = $('#quick_cidade_id option').filter(function () {
            return $(this).data('nome') === normalizedName && $(this).data('uf') === normalizedUf;
        }).first();
        if ($option.length) {
            $('#quick_cidade_id').val($option.val()).trigger('change');
            return true;
        }
        return false;
    }

    function preencherEndereco($section, dados) {
        $section.find('[name="rua"]').val(dados.logradouro || '');
        $section.find('[name="bairro"]').val(dados.bairro || '');
        if (dados.cep) $section.find('[name="cep"]').val(onlyDigits(dados.cep));
        if ($section.hasClass('quick-person') && dados.localidade && !setCidade(dados.localidade, dados.uf)) {
            showAlert('Endereço preenchido, mas a cidade ' + dados.localidade + ' (' + dados.uf + ') não existe no cadastro do ERP. Selecione a cidade manualmente.');
        }
    }

    function buscarCep($button) {
        const $section = $button.closest('.quick-person, .quick-employee');
        const $cep = $section.find('[name="cep"]');
        const cep = onlyDigits($cep.val());
        $cep.val(cep);
        if (cep.length !== 8) { showAlert('Informe um CEP válido com 8 dígitos.'); return; }
        $button.prop('disabled', true).addClass('spinner');
        $.getJSON('https://viacep.com.br/ws/' + cep + '/json/')
            .done(function (dados) {
                if (dados.erro) { showAlert('CEP não encontrado.'); return; }
                preencherEndereco($section, dados);
            })
            .fail(function () { showAlert('Não foi possível consultar o CEP agora. Verifique a conexão e tente novamente.'); })
            .always(function () { $button.prop('disabled', false).removeClass('spinner'); });
    }

    function consultarCnpj() {
        const cnpj = onlyDigits($('#quick_cpf_cnpj').val());
        $('#quick_cpf_cnpj').val(cnpj);
        if (cnpj.length !== 14) { showAlert('Informe um CNPJ válido com 14 dígitos para consultar os dados.'); return; }
        const $button = $('#quick_consultar_cnpj').prop('disabled', true).addClass('spinner');
        $.getJSON('https://publica.cnpj.ws/cnpj/' + cnpj)
            .done(function (dados) {
                const estabelecimento = dados.estabelecimento || {};
                $('#quick_razao_social').val(dados.razao_social || estabelecimento.nome_fantasia || '');
                $('#quick_nome_fantasia').val(estabelecimento.nome_fantasia || dados.razao_social || '');
                $('.quick-person [name="telefone"]').val(estabelecimento.ddd1 && estabelecimento.telefone1 ? '(' + estabelecimento.ddd1 + ') ' + estabelecimento.telefone1 : '');
                preencherEndereco($('.quick-person'), {
                    logradouro: [estabelecimento.tipo_logradouro, estabelecimento.logradouro].filter(Boolean).join(' '),
                    numero: estabelecimento.numero,
                    bairro: estabelecimento.bairro,
                    cep: estabelecimento.cep,
                    localidade: estabelecimento.cidade && estabelecimento.cidade.nome,
                    uf: estabelecimento.estado && estabelecimento.estado.sigla
                });
                if (estabelecimento.numero) $('.quick-person [name="numero"]').val(estabelecimento.numero);
            })
            .fail(function () { showAlert('Não foi possível consultar o CNPJ agora. Preencha os dados manualmente e tente novamente mais tarde.'); })
            .always(function () { $button.prop('disabled', false).removeClass('spinner'); });
    }

    function restorePesagem() {
        if (!quickContext) return;
        const $pesagem = $('#modalPesagem');
        $pesagem.one('shown.bs.modal', () => $pesagem.removeData('quick-preserve'));
        $pesagem.modal('show');
        quickContext = null;
    }

    $(document).on('input', '#quick_cpf_cnpj, #quick_funcionario_cpf, #quick_pessoa_cep, #quick_funcionario_cep, #quick_form [name="proprietario_documento"]', function () {
        this.value = onlyDigits(this.value);
    });
    $(document).on('click', '.quick-buscar-cep', function () { buscarCep($(this)); });
    $('#quick_consultar_cnpj').on('click', consultarCnpj);

    $(document).on('click', '.quick-open', function (e) {
        e.preventDefault();
        clearErrors();
        $('#quick_form')[0].reset();
        $('#quick_cidade_id, #quick_motorista_id, #quick_categoria_cnh').val(null).trigger('change');
        $('#quick_proprietario_tp').val('0').trigger('change');
        const type = $(this).data('quick-type');
        const $pesagem = $('#modalPesagem');
        quickContext = $pesagem.hasClass('show') ? { field: type === 'funcionario' ? null : type } : null;
        $('#quick_type').val(type);
        $('#quick_title').text(type === 'motorista' ? 'Novo motorista' : 'Novo ' + type);
        const $sections = $('.quick-person,.quick-vehicle,.quick-employee');
        $sections.addClass('d-none').find(':input').prop('disabled', true);
        let $activeSection;
        if (type === 'cliente' || type === 'fornecedor') $activeSection = $('.quick-person');
        if (type === 'veiculo') $activeSection = $('.quick-vehicle');
        if (type === 'funcionario' || type === 'motorista') $activeSection = $('.quick-employee');
        $activeSection.removeClass('d-none').find(':input').prop('disabled', false);
        if (type === 'funcionario' || type === 'motorista') $('input[name="motorista"]').prop('checked', type === 'motorista');
        $('.quick-cnh').toggle(type === 'motorista');
        $('.quick-image').toggle(true).find(':input').prop('disabled', false);
        const openQuick = () => $quick.modal('show');
        if (quickContext) $pesagem.data('quick-preserve', true).one('hidden.bs.modal.quick', openQuick).modal('hide'); else openQuick();
    });

    $('input[name="motorista"]').on('change', function () { $('.quick-cnh').toggle(this.checked); });
    $quick.on('hidden.bs.modal', restorePesagem);
    $('#quick_form').on('submit', function (e) {
        e.preventDefault();
        clearErrors();
        const $button = $('#quick_submit').prop('disabled', true);
        const type = $('#quick_type').val();
        const target = type === 'motorista' ? 'funcionario' : type;
        $.ajax({
            url: quickUrl.replace('__tipo__', target), method: 'POST', dataType: 'json', data: new FormData(this),
            processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (response) {
            const item = response.data;
            let selector = null;
            if (quickContext) selector = quickContext.field === 'cliente' ? '#cliente_id' : quickContext.field === 'fornecedor' ? '#fornecedor_id' : quickContext.field === 'veiculo' ? '#veiculo_id' : quickContext.field === 'motorista' ? '#motorista_id' : null;
            if (selector && item) {
                const option = new Option(item.text, item.id, true, true);
                Object.keys(item).forEach(k => $(option).data(k, item[k]));
                $(selector).append(option).trigger('change');
            }
            $quick.modal('hide');
            if (!quickContext) abrirModalMensagem('Sucesso', response.message);
        }).fail(function (xhr) {
            if (xhr.status === 419) { showAlert('Sessão expirada. Atualize a página e tente novamente.'); return; }
            const errors = xhr.responseJSON && xhr.responseJSON.errors;
            showAlert((xhr.responseJSON && xhr.responseJSON.message) || 'Não foi possível salvar o cadastro.');
            if (errors) Object.keys(errors).forEach(function (field) {
                const $input = $('#quick_form [name="' + field + '"]:enabled').first();
                $input.addClass('is-invalid').closest('.form-group').find('.invalid-feedback').first().text(errors[field][0]);
            });
        }).always(() => $button.prop('disabled', false));
    });
});
</script>
