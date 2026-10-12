<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContabilidadeService;
use App\Models\Filial;

class ExportacaoContabilController extends BaseController
{
    // Apenas para cumprir exigências do BaseController
    protected $model = Filial::class;
    protected $redirectPage = '/';

    public function rules(): array { return []; }
    public function messages(): array { return []; }

    public function index()
    {
        $title = 'Exportação Contábil Prosoft';
        $filiais = Filial::where('empresa_id', $this->empresa_id)->get();
        return view('contabilidade.exportacao', compact('filiais', 'title'));
    }

    public function processarPrevia(Request $request)
    {
        $service = new ContabilidadeService();
        $filial = ($request->filial_id == 'matriz') ? (object)['id' => 'matriz'] : Filial::findOrFail($request->filial_id);

        $dados = $service->processarAuditoria($request->data_inicio, $request->data_fim, $filial);

        // Agrupa as provisões na prévia também para o visual bater com o arquivo
        $dados['pagar_prov'] = $this->agruparProvisoes($dados['pagar_prov']);
        $dados['receber_prov'] = $this->agruparProvisoes($dados['receber_prov']);

        return response()->json($dados);
    }

    public function gerarArquivo(Request $request, \App\Services\ContabilidadeService $service)
    {
        $filialId = $request->input('filial_id');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        // 1. Identificação do Código Prosoft da Empresa e da Filial
        if ($filialId === 'matriz' || empty($filialId) || $filialId === '-1') {
            // Código Prosoft da Matriz
            $codigoEmpresaProsoft = '0160'; // Código de empresa cadastrado no Prosoft para a Matriz
            $codigoFilialProsoft  = '001';  // Indicador de filial/matriz no registro
        } else {
            $filial = \App\Models\Filial::findOrFail($filialId);

            // Pega o código próprio da filial cadastrado no sistema (ou o ID com 4 dígitos como fallback)
            $codigoProprio = $filial->codigo_empresa_emissao
                ?? $filial->codigo_filial
                ?? $filial->codigo_prosoft
                ?? $filial->id;

            $codigoEmpresaProsoft = str_pad($codigoProprio, 4, '0', STR_PAD_LEFT);
            $codigoFilialProsoft  = str_pad($filial->id, 3, '0', STR_PAD_LEFT);
        }

        // 2. Processa os dados filtrados pela unidade selecionada
        $dados = $service->processarAuditoria($dataInicio, $dataFim, $filialId);

        // Agrupa as provisões para o arquivo TXT
        $dados['pagar_prov']   = $this->agruparProvisoes($dados['pagar_prov']);
        $dados['receber_prov'] = $this->agruparProvisoes($dados['receber_prov']);

        $linhasTxt = [];
        $ordem = 1;

        $processarAba = function($linhas) use (&$linhasTxt, &$ordem, $codigoFilialProsoft) {
            foreach ($linhas as $item) {
                if ($item['status'] === 'ERRO' || $item['status'] === 'SEM PROVISÃO') {
                    continue;
                }

                $data = str_replace('/', '', $item['data']);

                $debito = str_pad(substr(preg_replace('/[^0-9]/', '', $item['debito']), 0, 5), 5, '0', STR_PAD_LEFT);
                $credito = str_pad(substr(preg_replace('/[^0-9]/', '', $item['credito']), 0, 5), 5, '0', STR_PAD_LEFT);

                // CORREÇÃO: Preenchimento com espaços em branco à esquerda para evitar erro de CNPJ inválido
                $terc_D = !empty($item['terceiro_debito'])
                    ? str_pad($item['terceiro_debito'], 14, ' ', STR_PAD_LEFT)
                    : str_pad('', 14, ' ');

                $terc_C = !empty($item['terceiro_credito'])
                    ? str_pad($item['terceiro_credito'], 14, ' ', STR_PAD_LEFT)
                    : str_pad('', 14, ' ');

                $valorLimpo = str_replace(['R$', ' ', '.'], '', $item['valor']);
                $valorLimpo = str_replace(',', '.', $valorLimpo);
                $valor = str_pad($valorLimpo, 16, '0', STR_PAD_LEFT);

                $historico = strtoupper(trim(preg_replace('/\s+/', ' ', $item['historico'])));
                $historico = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $historico);
                $historico = str_pad(substr($historico, 0, 240), 240, ' ', STR_PAD_RIGHT);

                $linha = 'LC1' .
                    str_pad($ordem, 5, '0', STR_PAD_LEFT) .
                    '   ' .
                    '1' .
                    $data .
                    str_pad('', 10, ' ') .
                    str_pad('', 5, ' ') .
                    str_pad('', 30, ' ') .
                    str_pad($codigoFilialProsoft, 3, '0', STR_PAD_LEFT) .
                    $debito .
                    $terc_D .
                    str_pad('', 5, ' ') .
                    $credito .
                    $terc_C .
                    str_pad('', 5, ' ') .
                    $valor .
                    $historico .
                    '  ' .
                    str_pad('', 74, ' ');

                $linhasTxt[] = $linha;
                $ordem++;
            }
        };

        $processarAba($dados['pagar_prov']);
        $processarAba($dados['pagar_baixa']);
        $processarAba($dados['receber_prov']);
        $processarAba($dados['receber_baixa']);
        $processarAba($dados['manual']);

        $conteudo = implode("\r\n", $linhasTxt);
        if (!empty($conteudo)) {
            $conteudo .= "\r\n";
        }

        // Nome dinâmico por unidade: ctblctos0160.txt (Matriz) ou ctblctos0161.txt (Filial)
        $nomeArquivo = "ctblctos{$codigoEmpresaProsoft}.txt";

        return response($conteudo)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');
    }

    public function gerarExcel(Request $request, \App\Services\ContabilidadeService $service)
    {
        $filialId = $request->input('filial_id');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        $dados = $service->processarAuditoria($dataInicio, $dataFim, $filialId);

        // Agrupa as provisões para o CSV do Excel
        $dados['pagar_prov'] = $this->agruparProvisoes($dados['pagar_prov']);
        $dados['receber_prov'] = $this->agruparProvisoes($dados['receber_prov']);

        $linhasCsv = [];
        // CORREÇÃO: Cabeçalho com colunas Terceiro Débito e Crédito separadas
        $linhasCsv[] = implode(';', ['DATA', 'HISTORICO', 'TERCEIRO DEBITO', 'TERCEIRO CREDITO', 'CREDITO', 'DEBITO', 'N DOCUMENTO', 'CATEGORIA', 'VALOR']);

        $processarAba = function($linhas) use (&$linhasCsv) {
            foreach ($linhas as $item) {
                if ($item['status'] === 'ERRO' || $item['status'] === 'SEM PROVISÃO') {
                    continue;
                }

                $data = $item['data'];
                $historico = str_replace(';', ',', $item['historico']);
                $categoria = str_replace(';', ',', $item['categoria']);

                // CORREÇÃO: Formatação dos terceiros individualmente
                $terc_D = !empty($item['terceiro_debito']) ? '="' . $item['terceiro_debito'] . '"' : '';
                $terc_C = !empty($item['terceiro_credito']) ? '="' . $item['terceiro_credito'] . '"' : '';

                $credito = $item['credito'] === '---' ? '' : $item['credito'];
                $debito = $item['debito'] === '---' ? '' : $item['debito'];
                $documento = $item['documento'] ?? '';
                $valor = $item['valor'];

                // Adiciona a linha respeitando as novas colunas
                $linhasCsv[] = implode(';', [
                    $data, $historico, $terc_D, $terc_C, $credito, $debito, $documento, $categoria, $valor
                ]);
            }
        };

        $processarAba($dados['pagar_prov']);
        $processarAba($dados['pagar_baixa']);
        $processarAba($dados['receber_prov']);
        $processarAba($dados['receber_baixa']);
        $processarAba($dados['manual']);

        $conteudo = implode("\r\n", $linhasCsv);
        $conteudo = mb_convert_encoding($conteudo, 'ISO-8859-1', 'UTF-8');

        $sufixoUnidade = ($filialId === 'matriz' || empty($filialId)) ? 'matriz' : 'filial_' . $filialId;
        $nomeArquivo = "exportacao_universal_{$sufixoUnidade}_" . date('Ymd_His') . ".csv";

        return response($conteudo)
            ->header('Content-Type', 'text/csv; charset=iso-8859-1')
            ->header('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');
    }

    /**
     * FUNÇÃO NOVA: Agrupa provisões da mesma nota e soma os valores.
     */
    private function agruparProvisoes($provisoes)
    {
        $agrupado = [];

        foreach ($provisoes as $item) {
            // Se tiver erro, passa direto e não agrupa
            if ($item['status'] === 'ERRO' || $item['status'] === 'SEM PROVISÃO') {
                $agrupado[] = $item;
                continue;
            }

            // A chave junta o Número da Nota + Contas (Debito/Credito) + Terceiro
            // Assim, ele sabe exatamente quem ele deve somar
            $doc = $item['documento'] ?? 'SEM_DOC';
            $chave = $doc . '_' . $item['debito'] . '_' . $item['credito'] . '_' . ($item['terceiro_debito'] ?? '') . ($item['terceiro_credito'] ?? '');

            if (!isset($agrupado[$chave])) {
                $agrupado[$chave] = $item;

                // Converte de "1.500,00" para 1500.00 para fazer conta matemática
                $valorNumerico = str_replace(['R$', ' ', '.'], '', $item['valor']);
                $valorNumerico = (float) str_replace(',', '.', $valorNumerico);

                $agrupado[$chave]['valor_soma'] = $valorNumerico;
            } else {
                // Se já existe a chave, soma o valor da parcela atual
                $valorNumerico = str_replace(['R$', ' ', '.'], '', $item['valor']);
                $valorNumerico = (float) str_replace(',', '.', $valorNumerico);

                $agrupado[$chave]['valor_soma'] += $valorNumerico;
            }
        }

        // Reconstrói o array formatando o dinheiro de volta para a tela/arquivo
        $resultado = [];
        foreach ($agrupado as $item) {
            if (isset($item['valor_soma'])) {
                $item['valor'] = number_format($item['valor_soma'], 2, ',', '.');
                unset($item['valor_soma']); // Remove a variável temporária
            }
            $resultado[] = $item;
        }

        // Retorna a lista nova, limpa e agrupada
        return array_values($resultado);
    }
}
