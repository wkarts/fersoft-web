@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-6">
            <h2 class="page-title">Controle de Portaria</h2>
        </div>
        
        <!-- BOTÕES PROTEGIDOS (Só Admin/Super) -->
        @if(session('user_logged')['adm'] == 1)
        <div class="col-6 text-right">
            <a href="{{ url('/portaria/historico') }}" class="btn btn-info mr-2">
                <i class="fas fa-list"></i> Ver Histórico
            </a>
            
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgendamento">
                + Novo Agendamento
            </button>
        </div>
        @endif
    </div>

    <!-- BLOCO 1: REGISTRO DE ENTRADA PRINCIPAL -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Registrar Entrada (Autorização)</h5>
        </div>
        <div class="card-body">
            <form action="{{ url('/portaria/entrada') }}" method="POST" id="form_entrada_principal">
                @csrf
                <!-- Guarda de qual agendamento essa visita veio (se houver) -->
                <input type="hidden" name="agendamento_id" id="agendamento_id_main" value="">
                
                <!-- Controle interno para saber se a foto já veio do banco -->
                <input type="hidden" id="tem_foto_salva" value="0">

                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>CPF (Busca Automática)</label>
                        <input type="text" name="cpf" id="cpf_visitante" class="form-control" placeholder="000.000.000-00" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Nome do Visitante</label>
                        <input type="text" name="nome" id="nome_visitante" class="form-control" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Quem veio visitar?</label>
                        <select name="funcionario_id" id="funcionario_id_main" class="form-control" required>
                            <option value="">Selecione...</option>
                            @foreach($funcionarios as $func)
                                <option value="{{ $func->id }}">{{ $func->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Placa do Veículo</label>
                        <input type="text" name="placa_veiculo" id="placa_veiculo" class="form-control" placeholder="ABC-1234">
                        <small id="info_veiculo" class="text-success" style="display:none;"></small>
                    </div>
                </div>

                <!-- BLOCO DA CÂMERA -->
                <div class="row mt-2 border p-3 rounded bg-light">
                    <div class="col-12 text-center">
                        <label class="font-weight-bold">Foto do Visitante</label><br>
                        
                        <button type="button" class="btn btn-secondary mb-2" id="btn_abrir_camera">
                            <i class="fas fa-camera"></i> Abrir Câmera
                        </button>

                        <div id="camera_area" style="display: none;">
                            <video id="video_camera" width="320" height="240" autoplay muted playsinline style="border: 1px solid #ccc; max-width: 100%;"></video><br>
                            <button type="button" class="btn btn-dark mt-2 mr-2" id="btn_trocar_camera">
                                <i class="fas fa-sync-alt"></i> Virar Câmera
                            </button>
                            <button type="button" class="btn btn-info mt-2" id="btn_capturar_foto">📸 Tirar Foto</button>
                        </div>

                        <div id="result_area" style="display: none;">
                            <canvas id="canvas_foto" width="320" height="240" style="display:none;"></canvas>
                            <!-- Adicionei 'object-fit: cover' para a foto não ficar esticada -->
                            <img id="foto_preview" src="" style="width: 320px; height: 240px; object-fit: cover; border: 2px solid #28a745; max-width: 100%;"><br>
                            <button type="button" class="btn btn-warning mt-2" id="btn_refazer_foto">🔄 Tirar Nova Foto</button>
                        </div>

                        <!-- Input oculto para enviar a foto ao Laravel -->
                        <input type="hidden" name="foto_base64" id="foto_base64">
                    </div>
                </div>
                <!-- FIM DO BLOCO DA CÂMERA -->

                <!-- TERMO LGPD E EPI -->
                <div class="row mt-4 mb-3">
                    <div class="col-12">
                        <div class="form-check border p-2 rounded" style="background-color: #fff3cd; border-color: #ffeeba;">
                            <input class="form-check-input" type="checkbox" name="termo_lgpd_epi" id="termo_lgpd_epi" value="1" required style="margin-left: 5px; transform: scale(1.3);">
                            <label class="form-check-label font-weight-bold text-danger" for="termo_lgpd_epi" style="margin-left: 25px; cursor: pointer;">
                                Confirmo que o visitante recebeu os EPIs (se necessário) e está ciente das normas de segurança e política de privacidade (LGPD).
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-12 text-right">
                        <button type="button" class="btn btn-warning mr-2" id="btn_avisar_whatsapp">
                            <i class="fab fa-whatsapp"></i> Avisar Funcionário
                        </button>
                        <button type="submit" class="btn btn-success" id="btn_autorizar_entrada">Autorizar Entrada (Gravar)</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- O RESTO DO HTML (BLOCOS 2, 3 E MODAL) CONTINUAM IGUAIS -->
    <div class="row">
        <!-- BLOCO 2: AGENDAMENTOS DO DIA -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Agendamentos Previstos para Hoje</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Visitante</th>
                                <th>Placa</th>
                                <th>Procura por</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_agendamentos">
                            @forelse($agendamentosHoje as $agenda)
                            <tr>
                                <td class="align-middle">{{ \Carbon\Carbon::parse($agenda->data_hora_prevista)->format('H:i') }}</td>
                                <td class="align-middle">{{ $agenda->visitante->nome }}</td>
                                <td class="align-middle">
                                    @if($agenda->placa_veiculo_prevista)
                                        <span class="badge badge-info" style="font-size: 14px;">{{ $agenda->placa_veiculo_prevista }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $agenda->funcionario->nome }}</td>
                                <td class="align-middle">
                                    <!-- Container Flex para manter os botões na mesma linha com um pequeno espaçamento -->
                                    <div class="d-flex align-items-center" style="gap: 5px;">
                                        <button type="button" class="btn btn-sm btn-info btn-puxar-agenda font-weight-bold"
                                            data-cpf="{{ $agenda->visitante->cpf }}"
                                            data-nome="{{ $agenda->visitante->nome }}"
                                            data-funcionario="{{ $agenda->funcionario_id }}"
                                            data-placa="{{ $agenda->placa_veiculo_prevista }}"
                                            data-agenda-id="{{ $agenda->id }}"
                                            title="Puxar para autorizar">
                                            <i class="fas fa-arrow-up"></i> Puxar
                                        </button>

                                        @if(session('user_logged')['adm'] == 1)
                                            <a href="{{ url('/portaria/agendamento/'.$agenda->id.'/editar') }}" class="btn btn-sm btn-warning" title="Editar Agendamento">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Margem e padding zerados no form para não quebrar o layout -->
                                            <form action="{{ url('/portaria/agendamento/'.$agenda->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Deseja realmente excluir este agendamento?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Excluir Agendamento">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhum agendamento pendente para hoje.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BLOCO 3: PESSOAS ATUALMENTE NA EMPRESA -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Pessoas na Empresa (Ativos)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Entrada</th>
                                <th>Visitante</th>
                                <th>Visitado</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_presentes">
                            @forelse($presentes as $movimento)
                            <tr>
                                <td>{{ $movimento->data_hora_entrada->format('H:i') }}</td>
                                <td>{{ $movimento->visitante->nome }}</td>
                                <td>{{ $movimento->funcionarioVisitado->nome }}</td>
                                <td>
                                    <form action="{{ url('/portaria/saida/'.$movimento->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Registrar Saída
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">Nenhum visitante na empresa no momento.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE AGENDAMENTO -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Agendar Visita</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ url('/portaria/agendar') }}" method="POST">
        @csrf
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>CPF do Visitante</label>
                    <input type="text" name="cpf" id="cpf_agenda" class="form-control" placeholder="000.000.000-00" required>
                </div>
                <div class="col-md-8 form-group">
                    <label>Nome do Visitante</label>
                    <input type="text" name="nome" id="nome_agenda" class="form-control" required>
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-md-4 form-group">
                    <label>Tipo de Vínculo</label>
                    <select name="tipo_vinculo" id="tipo_vinculo" class="form-control">
                        <option value="">Particular / Nenhum</option>
                        <option value="cliente">É um Cliente</option>
                        <option value="fornecedor">É um Fornecedor</option>
                    </select>
                </div>
                <div class="col-md-8 form-group" id="div_vinculo_cliente" style="display: none;">
                    <label>Selecione o Cliente</label>
                    <select name="cliente_id" class="form-control">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 form-group" id="div_vinculo_fornecedor" style="display: none;">
                    <label>Selecione o Fornecedor</label>
                    <select name="fornecedor_id" class="form-control">
                        <option value="">Selecione...</option>
                        @foreach($fornecedores as $f)
                            <option value="{{ $f->id }}">{{ $f->razao_social ?? $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Quem ele vem visitar?</label>
                    <select name="funcionario_id" class="form-control" required>
                        <option value="">Selecione...</option>
                        @foreach($funcionarios as $func)
                            <option value="{{ $func->id }}">{{ $func->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Data e Hora</label>
                    <input type="datetime-local" name="data_hora_prevista" class="form-control" required>
                </div>
                <div class="col-md-3 form-group">
                    <label>Placa do Veículo (Opcional)</label>
                    <input type="text" name="placa_veiculo" class="form-control" placeholder="ABC-1234">
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Agendamento</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
window.addEventListener('load', function() {

    // 1. Busca CPF Entrada (COM INTELIGÊNCIA)
    $(document).on('focusout', '#cpf_visitante', function() {
        let cpf = $(this).val(); 
        if (cpf.length >= 11) {
            let cpfLimpo = cpf.replace(/[^0-9]/g, '');
            $('#nome_visitante').attr('placeholder', 'Buscando...');
            
            $.ajax({
                url: "{{ url('/portaria/busca-cpf') }}/" + cpfLimpo,
                type: 'GET',
                success: function(response) {
                    if (response.encontrado) {
                        
                        // A. ALERTA DE LISTA NEGRA
                        if (response.bloqueado) {
                            swal("ACESSO NEGADO!", "Este visitante está na Lista Negra.\nMotivo: " + response.motivo_bloqueio, "error");
                            $('#nome_visitante').val('');
                            $('#btn_autorizar_entrada').prop('disabled', true); // Trava o botão
                            return; // Para tudo aqui
                        }

                        // Destrava o botão caso estivesse bloqueado antes
                        $('#btn_autorizar_entrada').prop('disabled', false);

                        // B. PREENCHE NOME
                        $('#nome_visitante').val(response.nome);

                        // C. MEMÓRIA DA ÚLTIMA VISITA (Placa e Funcionario)
                        if (response.ultimo_visitado) {
                            $('#funcionario_id_main').val(response.ultimo_visitado).change(); // Atualiza o select
                        }
                        if (response.ultima_placa) {
                            $('#placa_veiculo').val(response.ultima_placa);
                        }

                        // D. MOSTRA A FOTO AUTOMATICAMENTE (Se tiver)
                        if (response.foto) {
                            $('#foto_preview').attr('src', response.foto).show();
                            $('#result_area').fadeIn();
                            $('#camera_area').hide();
                            $('#btn_abrir_camera').hide();
                            $('#tem_foto_salva').val('1'); // Avisa o formulário que não precisa tirar foto nova
                            
                        } else {
                            // Se não tem foto, zera a área e pede pra tirar
                            $('#foto_preview').attr('src', '').hide();
                            $('#result_area').hide();
                            $('#btn_abrir_camera').show();
                            $('#tem_foto_salva').val('0');
                        }

                    } else {
                        // Novo Visitante
                        $('#btn_autorizar_entrada').prop('disabled', false);
                        $('#nome_visitante').val('');
                        $('#nome_visitante').attr('placeholder', 'Novo visitante...');
                        
                        // Reseta a foto
                        $('#tem_foto_salva').val('0');
                        $('#foto_preview').attr('src', '').hide();
                        $('#result_area').hide();
                        $('#btn_abrir_camera').show();
                    }
                },
                error: function() { $('#nome_visitante').attr('placeholder', ''); }
            });
        }
    });

    // 2. Busca CPF Agendamento (Simples)
    $(document).on('focusout', '#cpf_agenda', function() {
        let cpf = $(this).val(); 
        if (cpf.length >= 11) {
            let cpfLimpo = cpf.replace(/[^0-9]/g, '');
            $('#nome_agenda').attr('placeholder', 'Buscando...');
            $.ajax({
                url: "{{ url('/portaria/busca-cpf') }}/" + cpfLimpo,
                type: 'GET',
                success: function(response) {
                    if (response.encontrado) {
                        $('#nome_agenda').val(response.nome);
                    } else {
                        $('#nome_agenda').val('');
                        $('#nome_agenda').attr('placeholder', 'Digite o nome...');
                    }
                }
            });
        }
    });

    // 3. Busca Placa
    $(document).on('focusout', '#placa_veiculo', function() {
        let placa = $(this).val().replace(/[^a-zA-Z0-9]/g, ''); 
        if (placa.length >= 7) {
            $.ajax({
                url: "{{ url('/portaria/busca-placa') }}/" + placa,
                type: 'GET',
                success: function(response) {
                    if (response.encontrado) {
                        let texto = '🚗 ' + response.descricao + ' ' + (response.cor ? '(' + response.cor + ')' : '');
                        $('#info_veiculo').text(texto).fadeIn();
                    } else { $('#info_veiculo').fadeOut(); }
                }
            });
        } else { $('#info_veiculo').fadeOut(); }
    });

    // 4. Tipo de Vínculo (Modal)
    $(document).on('change', '#tipo_vinculo', function() {
        let tipo = $(this).val();
        $('#div_vinculo_cliente, #div_vinculo_fornecedor').hide();
        if (tipo === 'cliente') { $('#div_vinculo_cliente').fadeIn(); } 
        else if (tipo === 'fornecedor') { $('#div_vinculo_fornecedor').fadeIn(); }
    });

    // 5. Puxar Dados do Agendamento (Também dispara a busca para puxar a foto!)
    $(document).on('click', '.btn-puxar-agenda', function() {
        let cpf = $(this).data('cpf');
        $('#cpf_visitante').val(cpf);
        $('#nome_visitante').val($(this).data('nome'));
        $('#funcionario_id_main').val($(this).data('funcionario'));
        $('#placa_veiculo').val($(this).data('placa'));
        $('#agendamento_id_main').val($(this).data('agenda-id'));
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Simula o foco fora do CPF para o sistema puxar a foto automaticamente
        $('#cpf_visitante').trigger('focusout');
    });

    // 6. Botão Avisar WhatsApp (COM ESPERA DE RESPOSTA EM TEMPO REAL)
    $(document).on('click', '#btn_avisar_whatsapp', function() {
        let visitanteNome = $('#nome_visitante').val();
        let funcionarioId = $('#funcionario_id_main').val();
        let placa = $('#placa_veiculo').val();

        if (!visitanteNome || !funcionarioId) {
            swal("Atenção", "Preencha o nome do visitante e quem ele vem visitar!", "warning");
            return;
        }

        // Dispara o pedido via AJAX para o Controller enviar o WhatsApp
        $.post("{{ url('/portaria/avisar-chegada') }}", {
            _token: '{{ csrf_token() }}',
            nome_visitante: visitanteNome,
            funcionario_id: funcionarioId,
            placa: placa
        })
        .done(function() {
            // Abre o alerta com SweetAlert2 aguardando a resposta
            Swal.fire({
                title: 'Aguardando Resposta...',
                text: 'Mensagem enviada no WhatsApp do funcionário. Aguardando liberação...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();

                    // Fica checando a cada 3 segundos se o funcionário respondeu
                    window.intervaloChecagem = setInterval(function() {
                        $.get("{{ url('/portaria/checar-resposta') }}/" + funcionarioId, function(response) {
                            if (response.respondido) {
                                clearInterval(window.intervaloChecagem); // Para de checar

                                if (response.status === 'liberado') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Entrada Autorizada!',
                                        text: 'O funcionário liberou a entrada do visitante pelo WhatsApp.',
                                        timer: 4000
                                    });
                                } else if (response.status === 'negado') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Entrada Negada!',
                                        text: 'O funcionário negou a entrada deste visitante.',
                                        timer: 4000
                                    });
                                }
                            }
                        });
                    }, 3000);
                },
                willClose: () => {
                    clearInterval(window.intervaloChecagem);
                }
            });
        })
        .fail(function() {
            Swal.fire('Erro', 'Não foi possível enviar a mensagem no WhatsApp.', 'error');
        });
    });

    // 7. Câmera
    const video = document.getElementById('video_camera');
    const canvas = document.getElementById('canvas_foto');
    const fotoPreview = document.getElementById('foto_preview');
    const inputFoto = document.getElementById('foto_base64');
    
    let streamAtual = null;
    let modoCamera = 'user'; 

    function iniciarCamera() {
        if (streamAtual) { streamAtual.getTracks().forEach(track => track.stop()); }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: modoCamera } })
            .then(function(mediaStream) {
                streamAtual = mediaStream;
                video.srcObject = mediaStream;
                $(video).show();
                $('#btn_abrir_camera').hide();
                $('#camera_area').fadeIn();
            })
            .catch(function(err) {
                swal("Erro de Permissão", "Acesso à câmera negado ou dispositivo sem câmera traseira.", "error");
                console.log(err);
            });
    }

    $(document).on('click', '#btn_abrir_camera', function() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            swal("Erro de Segurança", "A câmera foi bloqueada pelo navegador.", "error");
            return;
        }
        iniciarCamera();
    });

    $(document).on('click', '#btn_trocar_camera', function() {
        modoCamera = (modoCamera === 'user') ? 'environment' : 'user';
        iniciarCamera();
    });

    $(document).on('click', '#btn_capturar_foto', function() {
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        let image_data_url = canvas.toDataURL('image/jpeg');
        fotoPreview.src = image_data_url;
        inputFoto.value = image_data_url;
        if (streamAtual) { streamAtual.getTracks().forEach(track => track.stop()); }
        $('#camera_area').hide();
        $('#result_area').fadeIn();
        // Avisa o sistema que agora temos uma foto preenchida
        $('#tem_foto_salva').val('1'); 
    });

    $(document).on('click', '#btn_refazer_foto', function() {
        $('#result_area').hide();
        inputFoto.value = ''; 
        $('#tem_foto_salva').val('0'); // Se ele apagou a foto, precisa tirar outra
        iniciarCamera();
    });

    // 8. Validação de Foto Obrigatória e Trava de Duplo Clique
    $(document).on('submit', '#form_entrada_principal', function(e) {
        
        let temFotoSalva = $('#tem_foto_salva').val();
        
        // Verifica se tirou a foto OU se já veio com foto do banco
        if (temFotoSalva === '0') {
            e.preventDefault(); // Impede o formulário de salvar
            swal("Foto Obrigatória", "É obrigatório registrar a foto do visitante para liberar a entrada.", "warning");
            return false;
        }

        // Se passou, bloqueia o botão para não dar duplo clique
        let btn = $(this).find('button[type="submit"]');
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processando...');
        btn.prop('disabled', true);
    });
    
    // Evita duplo clique no modal de agendamento (que não precisa de foto)
    $(document).on('submit', 'form:not(#form_entrada_principal)', function() {
        let btn = $(this).find('button[type="submit"]');
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processando...');
        btn.prop('disabled', true);
    });

    // 9. Gatilho Automático de Impressão da Etiqueta
    @if(session('imprimir_etiqueta_id'))
        window.open("{{ url('/portaria/imprimir-etiqueta') }}/{{ session('imprimir_etiqueta_id') }}", "_blank", "width=350,height=500,toolbar=no,scrollbars=no,menubar=no");
    @endif
  
    // 10. Atualização Automática das Tabelas (A cada 30 segundos)
    setInterval(function() {
        $.ajax({
            url: window.location.href, // O sistema finge que está acessando a própria página
            type: 'GET',
            success: function(html_atualizado) {
                // Ele recorta só as tabelas do HTML novo e cola na tela, sem piscar e sem atrapalhar a câmera!
                let novosAgendamentos = $(html_atualizado).find('#tbody_agendamentos').html();
                let novosPresentes = $(html_atualizado).find('#tbody_presentes').html();
                
                // Aplica as atualizações na tela
                if (novosAgendamentos) { $('#tbody_agendamentos').html(novosAgendamentos); }
                if (novosPresentes) { $('#tbody_presentes').html(novosPresentes); }
            }
        });
    }, 30000); // 30000 = 30 segundos. Você pode mudar esse tempo se quiser.
  
});
</script>
@endsection