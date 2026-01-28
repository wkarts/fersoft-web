$("#btn-add-servico").on('click', function(){
  let servico_id = $('#kt_select2_1').val()
  let valor_servico = $('.valor_servico').val()
  let qtd_servico = $('.qtd_servico').val()
  let ordem_servico_id = $('.ordem_servico_id').val()

  if(servico_id && valor_servico && qtd_servico){
    let data = {
      servico_id: servico_id,
      valor_servico: valor_servico,
      qtd_servico: qtd_servico,
      ordem_servico_id: ordem_servico_id,
      _token: $('#token').val()
    }
    $.post(path + 'ordemServico/store-servico', data)
    .done(res => {
      // console.log(res.view)
      $('.tabela-servicos tbody').append(res.view)
      $('.total-servico').text("R$ " + convertFloatToMoeda(res.total))

      $('#kt_select2_1').val('').change()
      $('.valor_servico').val('')
      $('.qtd_servico').val('')

    })
    .fail(err => {
      console.log(err)
    })
  }else{
    swal("Erro", "Informe os dados corretamento para adicionar", "error")
  }
});

$("#btn-add-produto").on('click', function(){
  let produto_id = $('#kt_select2_3').val()
  let valor_produto = $('.valor_produto').val()
  let qtd_produto = $('.qtd_produto').val()
  let ordem_servico_id = $('.ordem_servico_id').val()

  if(produto_id && valor_produto && qtd_produto){
    let data = {
      produto_id: produto_id,
      valor_produto: valor_produto,
      qtd_produto: qtd_produto,
      ordem_servico_id: ordem_servico_id,
      _token: $('#token').val()
    }
    $.post(path + 'ordemServico/store-produto', data)
    .done(res => {
      // console.log(res.view)
      $('.tabela-produto tbody').append(res.view)
      $('.total-produto').text("R$ " + convertFloatToMoeda(res.total))

      $('#kt_select2_3').html('')
      $('.valor_produto').val('')
      $('.qtd_produto').val('')

    })
    .fail(err => {
      console.log(err)
    })
  }else{
    swal("Erro", "Informe os dados corretamento para adicionar", "error")
  }
});