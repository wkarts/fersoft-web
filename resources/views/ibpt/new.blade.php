@extends('default.layout')
@section('content')
    <style type="text/css">
        .btn-file {
            position: relative;
            overflow: hidden;
        }

        .btn-file input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: inherit;
            display: block;
        }

        .progress {
            height: 25px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }

        .progress-bar {
            transition: width 0.4s ease;
        }

        #csv-preview {
            margin-top: 20px;
            display: none;
        }
    </style>

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <form id="ibpt-form" method="post" enctype="multipart/form-data">
            <div class="card card-custom gutter-b example example-compact">
                <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                    <div class="col-lg-12">
                        <input type="hidden" name="id" value="{{{ isset($cliente) ? $cliente->id : 0 }}}">
                        <br>

                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">
                                    @if(isset($ibpt)) Atualizar @else Nova @endif Tabela IBPT
                                    @if(isset($ibpt)) <strong style="margin-left: 5px;" class="text-info">{{$ibpt->uf}}</strong> @endif
                                </h3>
                            </div>
                        </div>
                        @csrf

                        <div class="row">
                            <div class="col-xl-2"></div>
                            <div class="col-xl-8">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="ibpt_id" value="{{ isset($ibpt) ? $ibpt->id : 0 }}">
                                        <div class="form-group validated col-sm-10 col-lg-10">
                                            <label class="col-form-label">.CSV</label>
                                            <div class="">
                                            <span class="btn btn-primary btn-file">
                                                Procurar arquivo<input accept=".csv" name="file" type="file" id="csv-file">
                                            </span>
                                                <label class="text-info" id="filename"></label>
                                            </div>
                                        </div>
                                    </div>

                                    @if(isset($estados))
                                        <div class="col-lg-4">
                                            <div class="form-group validated col-sm-10 col-lg-10">
                                                <label class="col-form-label">UF</label>
                                                <div class="">
                                                    <select name="uf" class="custom-select">
                                                        <option value="{{ $uf_tenante }}" selected>{{ strtoupper($uf_tenante) }} (Automático)</option>
                                                        <option disabled>-------------</option>
                                                        @foreach($estados as $e)
                                                            <option value="{{$e}}">{{$e}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="col-lg-4">
                                        <div class="form-group validated col-sm-10 col-lg-10">
                                            <label class="col-form-label">Versão</label>
                                            <div class="">
                                                <input type="text" name="versao" value="@if(isset($ibpt)) {{$ibpt->versao}} @endif" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-xl-2"></div>
                            <div class="col-lg-3 col-sm-6 col-md-4">
                                <button id="send-csv" style="width: 100%" type="button" class="btn btn-success spinner-white spinner-right">
                                    <i class="la la-check"></i>
                                    <span class="">Importar CSV</span>
                                </button>
                            </div>
                        </div>

                        <div class="progress" id="progress-container">
                            <div class="progress-bar bg-success" id="progress-bar" role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>

                        <div id="csv-preview" class="table-responsive">
                            <h4>Prévia do Arquivo CSV</h4>
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descrição</th>
                                    <th>Nacional Federal</th>
                                    <th>Importado Federal</th>
                                    <th>Estadual</th>
                                    <th>Municipal</th>
                                </tr>
                                </thead>
                                <tbody id="csv-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById("csv-file").addEventListener("change", function(event) {
            let file = event.target.files[0];
            if (!file) return;

            document.getElementById("filename").textContent = file.name;
            document.getElementById("csv-preview").style.display = "block";
            let reader = new FileReader();

            reader.onload = function(e) {
                let csvData = e.target.result;
                let rows = csvData.split("\n").slice(1, 6);
                let tableBody = document.getElementById("csv-table-body");
                tableBody.innerHTML = "";

                rows.forEach(function(row) {
                    let columns = row.split(";");
                    if (columns.length >= 6) {
                        let newRow = `<tr>
                        <td>${columns[0]}</td>
                        <td>${columns[3]}</td>
                        <td>${columns[4]}</td>
                        <td>${columns[5]}</td>
                        <td>${columns[6]}</td>
                        <td>${columns[7]}</td>
                    </tr>`;
                        tableBody.innerHTML += newRow;
                    }
                });

                // **Atualiza o campo versão com base no CSV**
                let versao = rows.length > 0 ? rows[0].split(";")[11] : 'Desconhecida';
                document.querySelector("input[name='versao']").value = versao;
            };

            reader.readAsText(file);
        });

        document.getElementById("send-csv").addEventListener("click", function() {
            let formData = new FormData(document.getElementById("ibpt-form"));
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "/ibpt/importar", true);
            xhr.setRequestHeader("X-CSRF-TOKEN", "{{ csrf_token() }}");

            document.getElementById("progress-container").style.display = "block";

            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    let percentComplete = Math.round((event.loaded / event.total) * 100);
                    document.getElementById("progress-bar").style.width = percentComplete + "%";
                    document.getElementById("progress-bar").innerText = percentComplete + "%";
                }
            };

            xhr.onload = function() {
                if (xhr.status == 200) {
                    let response = JSON.parse(xhr.responseText);

                    // **Atualiza a versão automaticamente**
                    if (response.versao) {
                        document.querySelector("input[name='versao']").value = response.versao;
                    }

                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 2000);
                    } else {
                        alert("Importação concluída com sucesso!");
                        setTimeout(function () {
                            window.location.href = "/ibpt";
                        }, 2000);
                    }
                } else {
                    alert("Erro na importação. Tente novamente.");
                }
            };

            xhr.send(formData);

            function updateProgress() {
                fetch('/ibpt/progress')
                    .then(response => response.json())
                    .then(data => {
                        let progressBar = document.getElementById("progress-bar");
                        let progressContainer = document.getElementById("progress-container");

                        if (data.progress > 0) {
                            progressContainer.style.display = "block";
                            progressBar.style.width = data.progress + "%";
                            progressBar.innerText = data.progress + "%";
                        }

                        if (data.progress < 100) {
                            setTimeout(updateProgress, 1000);
                        } else {
                            setTimeout(() => {
                                window.location.href = "/ibpt";
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error("Erro ao buscar progresso:", error);
                    });
            }

            updateProgress();
        });
    </script>

@endsection
