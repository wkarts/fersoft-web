

var TAMANHOPIZZASELECIONADO = 0;
var SUBCATEGORIAS = [];
var SUBCATEGORIASECOMMERCE = [];
var SABORESESCOLHIDOS = [];
var PRODUTONOVO = false;

$(function () {
  verificaUnidadeCompra();

  try{
    SUBCATEGORIAS = JSON.parse($('#subs').val())
    SUBCATEGORIASECOMMERCE = JSON.parse($('#subs_ecommerce').val())
  }catch{}

  
  validaAtribuiDelivery();
  // console.log($('#composto').val())
  if($('#composto').val() == 'true'){
    getProdutosComposto(function(data){
      // $('input.autocomplete-produto').autocomplete({
      //   data: data,
      //   limit: 20, 
      //   onAutocomplete: function(val) {

      //   },
      //   minLength: 1,
      // });
    });
  }else{
    getProdutos(function(data){
      $('#tamanhos-pizza').css('display', 'none');
      // $('input.autocomplete-produto').autocomplete({
      //   data: data,
      //   limit: 20, 
      //   onAutocomplete: function(val) {
      //     let v = val.split('-')
      //     getProduto(v[0], (data) => {
      //       if(!data.delivery){
      //         $('#valor').val(data.valor_venda)

      //         console.log(data)
      //         if(data.delivery && data.delivery.pizza.length > 0){
      //           setaTamanhosPizza(data.delivery)
      //         }

      //         Materialize.updateTextFields();
      //       }else{
      //         Materialize.toast('Este produto já possui cadastro no delivery', 3000)

      //         $('input.autocomplete-produto').val('')
      //       }
      //     })
      //   },
      //   minLength: 1,
      // });



    });
  }
  verificaCategoria()

  setTimeout(() => {

    if($('#categoria')){
      montaSubs()
    }
  }, 200)

});



// $('input.typeahead').on({
//   'typeahead:selected': (e, value) => {
//     console.log(datum)
//   },

// });

$('input.autocomplete-produto').on('keyup', () => {
  $('#tamanhos-pizza').css('display', 'none');
  $('#sabores-pizza').css('display', 'none');

})

function getProdutos(data){
  $.ajax
  ({
    type: 'GET',
    url: path + 'produtos/all',
    dataType: 'json',
    success: function(e){
       // console.log(e);
       data(e)

     }, error: function(e){
      console.log(e)
    }

  });
}

function getProduto(id, data){
  $.ajax
  ({
    type: 'GET',
    url: path + 'produtos/getProduto/'+id,
    dataType: 'json',
    success: function(e){
       // console.log(e);
       data(e)

     }, error: function(e){
      console.log(e)
    }

  });
}

function getProdutosComposto(data){
  $.ajax
  ({
    type: 'GET',
    url: path + 'produtos/composto',
    dataType: 'json',
    success: function(e){

      data(e)

    }, error: function(e){
      console.log(e)
    }

  });
}

$('#unidade_compra').change(() => {
  verificaUnidadeCompra();
})
$('#unidade_venda').change(() => {
  verificaUnidadeCompra();
})

function verificaUnidadeCompra(){
  let unidadeCompra = $('#unidade_compra').val();
  let unidadeVenda = $('#unidade_venda').val();
  if(unidadeCompra != unidadeVenda){
    $('#conversao').css('display', 'block');
  }else{
    $('#conversao').css('display', 'none');
  }
}

function alterarDestaque(id){
  $.ajax
  ({
    type: 'GET',
    url: path + 'deliveryProduto/alterarDestaque/'+id,
    dataType: 'json',
    success: function(e){
       // console.log(e);
       console.log(e)

     }, error: function(e){
      console.log(e)
    }

  });
}

function alterarStatus(id){
  $.ajax
  ({
    type: 'GET',
    url: path + 'deliveryProduto/alterarStatus/'+id,
    dataType: 'json',
    success: function(e){
       // console.log(e);
       console.log(e)

     }, error: function(e){
      console.log(e)
    }

  });
}

function verificaCategoria(){
  let type = $('#categoria-select option:selected').data('type');

  if(type == 1){
    $('.produto-pizza').removeClass('d-none');
    $('.produto-comum').addClass('d-none');

  }else{
    $('.produto-pizza').addClass('d-none');
    $('.produto-comum').removeClass('d-none');
    
  }
}

$('#categoria-select').change(() => {
  verificaCategoria()
})

//chips

function getSaboresPizza(){
  $.get(path+'/pizza/pizzas')
  .done((data) => {
    let js = JSON.parse(data);

    let tags = [];
    js.map((v) => {

      if(v.produto.delivery && v.produto.delivery.galeria.length > 0)
        tags[v.produto.nome] = path+'imagens_produtos/'+v.produto.delivery.galeria[0].path
      else
        tags[v.produto.nome] = null
    })

    $('.chips-autocomplete').material_chip({
      autocompleteOptions: {
        data: tags,
        limit: Infinity,
        minLength: 1
      }
    });
  })
  .fail((err) => {
    console.log(err)
  })
}

$('#kt_select2_1').change(() => {
  let uri = window.location.pathname;
  if(uri.split('/')[2] != 'apontamentoManual' && uri.split('/')[2] != 'receita'){
    let id = $('#kt_select2_1').val()
    getProduto(id, (data) => {
      if(!data.delivery){
        $('#valor').val(data.valor_venda)


        if(data.delivery && data.delivery.pizza.length > 0){
          setaTamanhosPizza(data.delivery)
        }

      }else{
        swal('Erro', 'Este produto já possui cadastro no delivery', 'error')
        $('#kt_select2_1').val('null').change();
      }
    })
  }
})

// data: {
//   'Apple': ,
//   'Microsoft': null,
//   'Google': null
// },

function setaTamanhosPizza(data){
  let tags = [];
  getSaboresPizza();
  data.pizza.map((v) => {
    tags.push({tag: v.tamanho.nome + ' - R$ ' + v.valor, item: v})
  });
  $('#tamanhos').material_chip({
    data: tags,
  });

  $('#tamanhos-pizza').css('display', 'block');
  $('#sabores-pizza').css('display', 'block');

}


$('.chips-autocomplete').on('chip.add', function(e, chip){
  console.log(chip)
});

$('#tamanhos').on('chip.select', function(e, chip){
  console.log(chip.item)
  TAMANHOPIZZASELECIONADO = chip.item.tamanho_id;
  console.log(TAMANHOPIZZASELECIONADO)
  $('#tamanho_pizza_id').val(TAMANHOPIZZASELECIONADO);
});

$('#sabores-esc').on('delete', function(e, chip){
  console.log(chip)
});


$('#atribuir_delivery').click(() => {
  validaAtribuiDelivery();
})

function validaAtribuiDelivery(){
  let delivery = $('#atribuir_delivery').is(':checked');
  if(delivery){
    $('#delivery').css('display', 'block')
  }else{
    $('#delivery').css('display', 'none')
  }
}

$('#novo-produto').click(() => {
  if(!PRODUTONOVO){
    $('#novo-prod').css('display', 'block')
    $('#ref-prod').css('display', 'none')
  }else{
    $('#novo-prod').css('display', 'none')
    $('#ref-prod').css('display', 'block')
  }

  PRODUTONOVO = !PRODUTONOVO
})

$('#percentual_lucro').keyup(() => {
  let valorCompra = parseFloat($('#valor_compra').val().replace(',', '.'));
  let percentualLucro = parseFloat($('#percentual_lucro').val().replace(',', '.'));

  if(valorCompra > 0 && percentualLucro > 0){
    let valorVenda = valorCompra + (valorCompra * (percentualLucro/100));
    valorVenda = formatReal(valorVenda);
    valorVenda = valorVenda.replace('.', '')
    valorVenda = valorVenda.substring(3, valorVenda.length)

    $('#valor_venda').val(valorVenda)
  }else{
    $('#valor_venda').val('0')
  }
})

$('#valor_venda').keyup(() => {
  let valorCompra = parseFloat($('#valor_compra').val().replace(',', '.'));
  let valorVenda = parseFloat($('#valor_venda').val().replace(',', '.'));

  if(valorCompra > 0 && valorVenda > 0){
    let dif = (valorVenda - valorCompra)/valorCompra*100;
    // valorVenda = formatReal(valorVenda);
    // valorVenda = valorVenda.replace('.', '')
    // valorVenda = valorVenda.substring(3, valorVenda.length)

    $('#percentual_lucro').val(dif)
  }else{
    $('#percentual_lucro').val('0')
  }
})

function formatReal(v){
  return v.toLocaleString('pt-br', {style: 'currency', currency: 'BRL', minimumFractionDigits: casas_decimais});
}

$('#categoria').change(() => {
  montaSubs()
})

function montaSubs(){
  let categoria_id = $('#categoria').val()
  let subs = SUBCATEGORIAS.filter((x) => {
    return x.categoria_id == categoria_id
  })

  let options = ''
  subs.map((s) => {
    options += '<option value="'+s.id+'">'
    options += s.nome
    options += '</option>'
  })
  $('#sub_categoria_id').html('<option value="">--</option>')
  $('#sub_categoria_id').append(options)
}

$('#categoria-select').change(() => {
  montaSubsEcommerce()
})

function montaSubsEcommerce(){
  let categoria_id = $('#categoria-select').val()
  let subs = SUBCATEGORIASECOMMERCE.filter((x) => {
    return x.categoria_id == categoria_id
  })

  let options = ''
  subs.map((s) => {
    options += '<option value="'+s.id+'">'
    options += s.nome
    options += '</option>'
  })
  $('#sub_categoria_ecommerce_id').html('<option value="">--</option>')
  $('#sub_categoria_ecommerce_id').append(options)
}

function gerarCode(){
  $.get(path+'produtos/gerarCodigoEan')
  .done((res) => {
    $('#codBarras').val(res)
  })
  .fail((err) => {
    swal("Erro", "Erro ao buscar código", "error")
  })
}

// $('#locais').change(() => {
//   console.clear()
//   let locais = $('#locais').val()
//   let options = $('#locais option:selected')
//   console.log(options)
//   if(locais.length > 0){
//     $('.estoque-matriz').addClass('d-none')
//   }

//   let html = ''
//   options.map((x, v) => {
//     console.log(v.value)
//     console.log(v.text)

//     html += '<div class="form-group validated col-12 col-lg-6">'+
//     '<label class="col-form-label">Estoque para '+v.text+'</label>'+
//     '<div class="">'+
//     '<input type="text" class="form-control" name="local[]" value="">'+
//     '</div></div>'
//   })
//   html += '<br>'

//   console.log(html)

//   $('.estoque-filial').html(html)
// })




