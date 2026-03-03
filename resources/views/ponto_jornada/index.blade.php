@extends('default.layout')
@section('content')
<div class="row">
  <div class="col-md-4">
    <div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Nova Jornada</h3></div><div class="card-body">
      <form method="post" action="/ponto/jornadas/save">@csrf
        <input class="form-control mb-2" name="nome" placeholder="Nome" required>
        <input class="form-control mb-2" name="regras_semana[1][entrada]" placeholder="Seg entrada (HH:MM)">
        <input class="form-control mb-2" name="regras_semana[1][saida]" placeholder="Seg saída (HH:MM)">
        <input class="form-control mb-2" type="number" name="tolerancia_atraso_min" placeholder="Tolerância atraso (min)">
        <input class="form-control mb-2" type="number" name="tolerancia_extra_min" placeholder="Tolerância extra (min)">
        <button class="btn btn-primary btn-sm">Salvar Jornada</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Novo Turno</h3></div><div class="card-body">
      <form method="post" action="/ponto/turnos/save">@csrf
        <input class="form-control mb-2" name="nome" placeholder="Nome" required>
        <input class="form-control mb-2" name="hora_inicio" type="time" required>
        <input class="form-control mb-2" name="hora_fim" type="time" required>
        <input class="form-control mb-2" type="number" name="intervalo_minutos" placeholder="Intervalo (min)">
        <label><input type="checkbox" name="cruza_meia_noite" value="1"> Cruza meia-noite</label><br>
        <button class="btn btn-primary btn-sm mt-2">Salvar Turno</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Nova Escala</h3></div><div class="card-body">
      <form method="post" action="/ponto/escalas/save">@csrf
        <input class="form-control mb-2" name="nome" placeholder="Nome" required>
        <select class="form-control mb-2" name="tipo"><option value="5x2">5x2</option><option value="6x1">6x1</option><option value="12x36">12x36</option><option value="personalizada">Personalizada</option></select>
        <input class="form-control mb-2" type="date" name="vigencia_inicio">
        <input class="form-control mb-2" type="date" name="vigencia_fim">
        <button class="btn btn-primary btn-sm">Salvar Escala</button>
      </form>
    </div></div>
  </div>
</div>

<div class="row">
  <div class="col-md-4"><div class="card card-custom"><div class="card-header"><h3 class="card-title">Jornadas</h3></div><div class="card-body"><ul>@foreach($jornadas as $j)<li>{{ $j->nome }}</li>@endforeach</ul></div></div></div>
  <div class="col-md-4"><div class="card card-custom"><div class="card-header"><h3 class="card-title">Turnos</h3></div><div class="card-body"><ul>@foreach($turnos as $t)<li>{{ $t->nome }} ({{ $t->hora_inicio }}-{{ $t->hora_fim }})</li>@endforeach</ul></div></div></div>
  <div class="col-md-4"><div class="card card-custom"><div class="card-header"><h3 class="card-title">Escalas</h3></div><div class="card-body"><ul>@foreach($escalas as $e)<li>{{ $e->nome }} ({{ $e->tipo }})</li>@endforeach</ul></div></div></div>
</div>
@endsection
