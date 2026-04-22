<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Define a orientação paisagem e as margens */
        @page { 
            margin: 20px 25px 80px 25px; 
        }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; }
        
        /* CABEÇALHO E TERMOS JUNTOS NA MESMA BORDA */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { border: 1px solid #000; padding: 5px; }
        .header-bold { font-weight: bold; font-size: 12px; text-align: center; }
        .term-text { text-align: left; line-height: 1.2; font-size: 9px; padding: 6px !important; }
        
        /* DADOS DO COLABORADOR - BEM PRÓXIMOS */
        .colaborador-box { border: 1px solid #000; padding: 3px 5px; margin-bottom: 8px; }
        .colaborador-table { width: 100%; border-collapse: collapse; }
        .colaborador-table td { border: none; padding: 1px 2px; font-size: 10px; vertical-align: middle; }
        .label-bold { font-weight: bold; width: 15%; }
        
        /* TABELA PRINCIPAL */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; text-align: center; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 4px 3px; font-size: 9px; }
        .main-table th { font-weight: bold; }
        /* Essa classe remove a borda inferior da linha esticada se quiser, mas deixamos padrão para fechar o quadro */
        
        /* RODAPÉ FIXO NO FUNDO */
        .footer-fixed { 
            position: fixed; 
            bottom: -50px; 
            left: 0; 
            right: 0;
            height: 60px;
        }
        .footer-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .footer-table td { border: none; padding: 2px; }
        .footer-label { font-weight: bold; width: 8%; }
        
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($requisicoesAgrupadas as $func_id => $reqs)
        @php
            $funcionario = $reqs->first()->funcionario;
            $empresa = $config->nome_fantasia ?? $config->razao_social ?? 'Sua Empresa';
        @endphp

        <table class="header-table">
            <tr>
                <td width="15%" class="header-bold">{{ mb_strtoupper($empresa) }}</td>
                <td width="70%" class="header-bold">CONTROLE DE FORNECIMENTO DE EQUIPAMENTO DE PROTEÇÃO INDIVIDUAL E UNIFORME</td>
                <td width="15%" style="text-align: center;">Versão: FS_02</td>
            </tr>
            <tr>
                <td colspan="3" class="term-text">
                    Declaro haver recebido gratuitamente da empresa os EPI mencionados abaixo, para utilização durante minha jornada de trabalho e fui treinado para o uso correto, guarda e conservação do mesmo. É de meu conhecimento que:<br>
                    > O EPI é de uso individual;<br>
                    > É de minha responsabilidade a guarda e conservação dos EPI fornecidos pelo empregador<br>
                    > Estou ciente que sofrerei penalidades administrativas por quaisquer danos ao EPI, devido a utilização incorreta e falta de conservação do mesmo;<br>
                    > Como funcionário tenho o compromisso de devolver os EPI para realização da troca ou quando a utilização do mesmo não for mais necessário para realização de minhas atividades na empresa;<br>
                    > A utilização de EPI é obrigatória durante a realização de atividades com riscos, conforme orientações da segurança do trabalho fornecidas através de treinamentos específicos, ordem de serviço e da chefia do departamento.<br>
                    Declaro ter recebido a devida orientação de utilização correta dos equipamentos de proteção individual antes efetuação da entrega.<br>
                    Declaro também estar ciente do conteúdo do Artigo 158 da C.L.T.: "Constitui ato faltoso do empregado a recusa injustificada ao uso dos equipamentos de proteção individual fornecido pela empresa".<br>
                    Os EPI encontram-se a disposição no setor administrativo / operacional da empresa, favor solicitá-los conforme necessidade.
                </td>
            </tr>
        </table>

        <div class="colaborador-box">
            <table class="colaborador-table">
                <tr>
                    <td class="label-bold">Nome da Empresa:</td>
                    <td>{{ mb_strtoupper($empresa) }}</td>
                </tr>
                <tr>
                    <td class="label-bold">Nome do Colaborador:</td>
                    <td>{{ $funcionario->nome }}</td>
                </tr>
                <tr>
                    <td class="label-bold">Função:</td>
                    {{-- Usa a relação para garantir o nome --}}
                    <td>{{ optional($funcionario->getRelation('funcao'))->nome ?? 'Função não definida' }}</td>
                </tr>
                <tr>
                    <td class="label-bold">Assinatura do funcionário:</td>
                    <td>____________________________________________________________________</td>
                </tr>
            </table>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th width="8%">Data</th>
                    <th width="5%">Qtde</th>
                    <th width="25%">Descrição do Material</th>
                    <th width="10%">Nº C.A.</th>
                    <th width="5%">Uso</th>
                    <th width="5%">Motivo</th>
                    <th width="12%">Fabricante</th>
                    <th width="15%">Assinatura</th>
                    <th width="15%">Resp Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reqs as $r)
                    @foreach($r->itens as $it)
                    <tr>
                        <td>{{ date('d/m/Y', strtotime($r->data_requisicao)) }}</td>
                        <td>{{ number_format($it->quantidade, 2, ',', '') }}</td>
                        <td style="text-align: left;">{{ $it->produto->nome }}</td>
                        <td>CA: {{ $it->produto->ca_numero ?? $it->ca_snapshot ?? '' }}</td>
                        <td>{{ $it->uso ? substr($it->uso, 0, 1) : '' }}</td>
                        <td>{{ $it->motivo ? substr($it->motivo, 0, 1) : '' }}</td>
                        <td>{{ $it->produto->fabricante ?? '' }}</td>
                        <td></td>
                        <td>{{ $r->responsavel->nome ?? '' }}</td>
                    </tr>
                    @endforeach
                @endforeach
                
                <tr>
                    <td style="height: 180px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-fixed">
            <table class="footer-table">
                <tr>
                    <td class="footer-label">MOTIVO:</td>
                    <td width="16%">A = ADMISSÃO</td>
                    <td width="16%">S = SUBSTITUIÇÃO</td>
                    <td width="16%">D = DOLO</td>
                    <td width="16%">E = EVENTUAL</td>
                    <td width="16%">P = PERMANENTE</td>
                </tr>
                <tr>
                    <td class="footer-label">USO:</td>
                    <td>1 = EVENTUAL</td>
                    <td>2 = PERMANENTE</td>
                    <td colspan="3"></td>
                </tr>
            </table>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>