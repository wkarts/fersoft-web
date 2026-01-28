<div class="table-responsive">
	<table class="table">
		<thead>
			<tr>
				<th>Empresa</th>
				<th>Mensagem</th>
				<th>Tipo</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			@foreach($data as $i)
			<tr>
				<td>{{ $i->empresa->nome }}</td>
				<td>{{ $i->mensagem }}</td>
				<td>{{ $i->tipo }}</td>
				<td>
					<a href="/super-admin/altera-status/{{ $i->id }}" class="btn btn-success">
						<i class="la la-check"></i>
					</a>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>