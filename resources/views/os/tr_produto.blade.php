<tr class="datatable-row" style="left: 0px;">

	<td class="datatable-cell"><span class="codigo" style="width: 300px;">{{ $item->produto->nome }}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($item->quantidade) }}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($item->valor_unitario) }}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($item->sub_total) }}</span></td>

	<td class="datatable-cell">
		<span class="codigo" style="width: 120px;">
			<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteProduto/{{ $item->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
				<span class="la la-trash"></span>
			</a>

		</span>
	</td>

</tr>