$(".btn-delete").on("click", function(e) {
    e.preventDefault();
    var form = $(this)
    .parents("form")
    .attr("id");

    swal({
        title: "Você está certo?",
        text:
        "Uma vez deletado, você não poderá recuperar esse item novamente!",
        icon: "warning",
        buttons: true,
        buttons: ["Cancelar", "Excluir"],
        dangerMode: true
    }).then(isConfirm => {
        if (isConfirm) {
            document.getElementById(form).submit();
        } else {
            swal("Este item está salvo!");
        }
    });
});

$("#inp-empresa_id").select2({
    minimumInputLength: 2,
    language: "pt-BR",
    placeholder: "Digite para buscar a empresa",
    width: "100%",
    ajax: {
        cache: true,
        url: path + "empresas/buscar-empresas",
        dataType: "json",
        data: function (params) {
            console.clear();
            var query = {
                pesquisa: params.term,
            };
            return query;
        },
        processResults: function (response) {
            var results = [];

            $.each(response, function (i, v) {
                var o = {};
                o.id = v.id;

                o.text = v.nome + " - " + v.nome_fantasia;
                o.value = v.id;
                results.push(o);
            });
            return {
                results: results,
            };
        },
    },
});

$("#inp-empresa_id").select2({
    minimumInputLength: 2,
    language: "pt-BR",
    placeholder: "Digite para buscar a empresa",
    width: "100%",
    ajax: {
        cache: true,
        url: path + "empresas/buscar-empresas",
        dataType: "json",
        data: function (params) {
            console.clear();
            var query = {
                pesquisa: params.term,
            };
            return query;
        },
        processResults: function (response) {
            var results = [];

            $.each(response, function (i, v) {
                var o = {};
                o.id = v.id;

                o.text = v.nome + " - " + v.nome_fantasia;
                o.value = v.id;
                results.push(o);
            });
            return {
                results: results,
            };
        },
    },
});

setTimeout(() => {
    $(".cliente_select2").select2({
        minimumInputLength: 2,
        language: "pt-BR",
        placeholder: "Digite para buscar o cliente",
        width: "90%",
        ajax: {
            cache: true,
            url: path + "clientes/buscar",
            dataType: "json",
            data: function (params) {
                console.clear();
                var query = {
                    pesquisa: params.term,
                };
                return query;
            },
            processResults: function (response) {
                var results = [];

                $.each(response, function (i, v) {
                    var o = {};
                    o.id = v.id;

                    o.text = v.razao_social + " - " + v.cpf_cnpj;
                    o.value = v.id;
                    results.push(o);
                });
                return {
                    results: results,
                };
            },
        },
    });
    $('.select2-selection__arrow').remove()
}, 600);

/*
setTimeout(() => {
    $(".cliente_select2").select2({
        minimumInputLength: 2,
        language: "pt-BR",
        placeholder: "Digite para buscar o cliente",
        width: "90%",
        ajax: {
            cache: true,
            url: path + "clientes/buscar",
            dataType: "json",
            data: function (params) {
                return {
                    pesquisa: params.term,
                };
            },
            processResults: function (response) {
                var results = [];
                $.each(response, function (i, v) {
                    results.push({
                        id: v.id,
                        text: v.razao_social + " - " + v.cpf_cnpj // Exibe nome e CPF/CNPJ
                    });
                });
                return {
                    results: results,
                };
            },
        },
    });
    $('.select2-selection__arrow').remove()
}, 600);

function convertMoedaToFloat(value) {
    if (!value) {
        return 0;
    }

    var number_without_mask = value.replaceAll(".", "").replaceAll(",", ".");
    return parseFloat(number_without_mask.replace(/[^0-9\.]+/g, ""));
}

function convertFloatToMoeda(value) {
    value = parseFloat(value)
    return value.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

$(function () {
    getAlertasSuper()
})

function getAlertasSuper(){
    $.get(path + 'alertas/all')
    .done((data) => {

        if(data.size > 0){
            $('.notifica-super').removeClass('d-none')
            $('.notifica-rows').html(data.view)
        }
    })
    .fail((err) => {
        console.log(err)
    })
}
*/
