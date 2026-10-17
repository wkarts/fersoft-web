$(function () {
  getModelos(function (data) {
    iniciarAutocompleteServico('input.autocomplete-modelo', '#autocomplete-produto', data);
  });

  getProdutos(function (data) {
    iniciarAutocompleteServico('input.autocomplete-produto', '#autocomplete-modelo', data);
  });
});

function iniciarAutocompleteServico(selector, outroCampo, data) {
  var campo = $(selector);
  if (!campo.length || typeof campo.autocomplete !== 'function') return;

  function aoSelecionar(valor) {
    if ($(outroCampo).val().length > 1) setDescricao();
  }

  // O layout refinado carrega jQuery UI; o layout legado ainda usa a API Materialize.
  if ($.ui && $.ui.autocomplete) {
    var opcoes = Array.isArray(data) ? data : Object.keys(data || {});
    campo.autocomplete({
      minLength: 1,
      source: function (request, response) {
        response($.ui.autocomplete.filter(opcoes, request.term).slice(0, 20));
      },
      select: function (event, ui) {
        // No jQuery UI, select roda antes de o valor escolhido chegar ao input.
        $(this).val(ui.item.value);
        aoSelecionar(ui.item.value);
      }
    });
    campo.autocomplete('widget').addClass('fer-service-autocomplete');
  } else {
    campo.autocomplete({
      data: data,
      limit: 20,
      onAutocomplete: aoSelecionar,
      minLength: 1
    });
  }
}

function setDescricao() {
  var servico = $('#autocomplete-produto').val().split('-');
  var modelo = $('#autocomplete-modelo').val().split('-');

  var description = servico[1] + ", Modelo: " + modelo[1];
  $('#description').val(description);
  alert("Setando uma sugestao de descrição de serviço!");
}

function getModelos(data) {
  $.ajax({
    type: 'GET',
    url: path + 'modelos/all',
    dataType: 'json',
    success: function (e) { data(e); },
    error: function (e) { console.log(e); }
  });
}

function getProdutos(data) {
  $.ajax({
    type: 'GET',
    url: path + 'produtos/all',
    dataType: 'json',
    success: function (e) { data(e); },
    error: function (e) { console.log(e); }
  });
}
