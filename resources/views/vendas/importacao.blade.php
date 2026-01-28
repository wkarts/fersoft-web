@extends('default.layout')

@section('content')
    <style>
        /* Ajustes visuais para o input customizado */
        .custom-file-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .input-group-text.bg-primary {
            background-color: #3699FF !important;
            border-color: #3699FF !important;
        }
        .btn-success.btn-import {
            padding-left: 2rem;
            padding-right: 2rem;
        }
    </style>

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <form method="post" enctype="multipart/form-data" action="{{ url('/vendas/importacao') }}">
            @csrf

            <div class="card card-custom gutter-b example example-compact">
                <div class="card-header">
                    <h3 class="card-title">Importação de XMLs</h3>
                </div>

                <div class="card-body">
                    <p class="text-muted">Compacte em <strong>.zip</strong> os arquivos XML antes de importar.</p>

                    <div class="form-group">
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                            <span class="input-group-text bg-primary text-white">
                                <i class="la la-file-archive"></i>
                            </span>
                            </div>
                            <div class="custom-file">
                                <input
                                    type="file"
                                    class="custom-file-input"
                                    id="zipFile"
                                    name="file"
                                    accept=".zip"
                                    required
                                >
                                <label class="custom-file-label" for="zipFile">
                                    Selecionar arquivo ZIP
                                </label>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Apenas arquivos .zip contendo XML serão aceitos.
                        </small>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-success btn-import">
                        <i class="la la-upload"></i> Importar XML
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Atualiza o label com o nome do arquivo selecionado
        document.addEventListener('DOMContentLoaded', function(){
            const zipInput = document.getElementById('zipFile');
            zipInput.addEventListener('change', function(){
                const fileName = this.files.length
                    ? this.files[0].name
                    : 'Selecionar arquivo ZIP';
                this.nextElementSibling.innerText = fileName;
            });
        });
    </script>
@endsection
