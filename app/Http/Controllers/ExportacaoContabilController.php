<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContabilidadeService;
use App\Models\Filial;

class ExportacaoContabilController extends BaseController
{
    // Apenas para cumprir exigências do BaseController, mesmo não usando CRUD padrão aqui
    protected $model = Filial::class; 
    protected $redirectPage = '/'; 

    public function rules(): array { return []; }
    public function messages(): array { return []; }

    public function index()
    {
        // 1. Define o título da página que o seu layout exige
        $title = 'Exportação Contábil Prosoft';

        // 2. Busca filiais para o select
        $filiais = Filial::where('empresa_id', $this->empresa_id)->get();
        
        // 3. Envia as filiais e o título para a view
        return view('contabilidade.exportacao', compact('filiais', 'title'));
    }

    public function processarPrevia(Request $request)
    {
        $service = new ContabilidadeService();
        
        // Simula um objeto de filial para a Matriz se for selecionado "matriz"
        $filial = ($request->filial_id == 'matriz') ? (object)['id' => 'matriz'] : Filial::findOrFail($request->filial_id);
        
        $dados = $service->processarAuditoria($request->data_inicio, $request->data_fim, $filial);
        $dados['pagar_prov'] = $this->agruparProvisoes($dados['pagar_prov'] ?? []);
        $dados['receber_prov'] = $this->agruparProvisoes($dados['receber_prov'] ?? []);

        return response()->json($dados);
    }
  
  public function gerarArquivo(Request $request, \App\Services\ContabilidadeService $service)
    {
        $filialId = $request->input('filial_id');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        $dados = $service->processarAuditoria($dataInicio, $dataFim, $filialId);

        $dados['pagar_prov'] = $this->agruparProvisoes($dados['pagar_prov'] ?? []);
        $dados['receber_prov'] = $this->agruparProvisoes($dados['receber_prov'] ?? []);

        $linhasTxt = [];
        $ordem = 1;

        // SE O SEU CONTADOR MUDAR O CÓDIGO DA FILIAL, É SÓ ALTERAR AQUI:
        $codigoFilialProsoft = '002';

        $processarAba = function($linhas) use (&$linhasTxt, &$ordem, $codigoFilialProsoft) {
            foreach ($linhas as $item) {
                if ($item['status'] === 'ERRO' || $item['status'] === 'SEM PROVISÃO') {
                    continue; 
                }

                $data = str_replace('/', '', $item['data']);

                $debito = str_pad(substr(preg_replace('/[^0-9]/', '', $item['debito']), 0, 5), 5, '0', STR_PAD_LEFT);
                $credito = str_pad(substr(preg_replace('/[^0-9]/', '', $item['credito']), 0, 5), 5, '0', STR_PAD_LEFT);
                
                // Trata o Terceiro (CNPJ/CPF) adicionando zeros à esquerda até dar 14 posições, ou espaços se for vazio
                $terc_D = !empty($item['terceiro_debito']) 
                          ? str_pad($item['terceiro_debito'], 14, '0', STR_PAD_LEFT) 
                          : str_pad('', 14, ' ');
                          
                $terc_C = !empty($item['terceiro_credito']) 
                          ? str_pad($item['terceiro_credito'], 14, '0', STR_PAD_LEFT) 
                          : str_pad('', 14, ' ');
                
                $valorLimpo = str_replace(['R$', ' ', '.'], '', $item['valor']); 
                $valorLimpo = str_replace(',', '.', $valorLimpo); 
                $valor = str_pad($valorLimpo, 16, '0', STR_PAD_LEFT);
                
                $historico = strtoupper(trim(preg_replace('/\s+/', ' ', $item['historico'])));
                $historico = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $historico);
                $historico = str_pad(substr($historico, 0, 240), 240, ' ', STR_PAD_RIGHT);

                // ==========================================================
                // MONTAGEM DA LINHA PROSOFT
                // ==========================================================
                $linha = 'LC1' .                                       // Posição 1 a 3 (Tipo)
                         str_pad($ordem, 5, '0', STR_PAD_LEFT) .       // Posição 4 a 8 (Ordem)
                         '   ' .                                       // Posição 9 a 11 (Filler)
                         '1' .                                         // Posição 12 (Modo = 1 Simples)
                         $data .                                       // Posição 13 a 20 (Data)
                         str_pad('', 10, ' ') .                        // Posição 21 a 30 (Documento)
                         str_pad('', 5, ' ') .                         // Posição 31 a 35 (Lote)
                         str_pad('', 30, ' ') .                        // Posição 36 a 65 (Origem)
                         str_pad($codigoFilialProsoft, 3, '0', STR_PAD_LEFT) . // Posição 66 a 68 (Código da Filial na Prosoft)
                         $debito .                                     // Posição 69 a 73 (Conta Débito)
                         $terc_D .                                     // Posição 74 a 87 (Terceiro Débito CNPJ/CPF)
                         str_pad('', 5, ' ') .                         // Posição 88 a 92 (C/Custo Débito)
                         $credito .                                    // Posição 93 a 97 (Conta Crédito)
                         $terc_C .                                     // Posição 98 a 111 (Terceiro Crédito CNPJ/CPF)
                         str_pad('', 5, ' ') .                         // Posição 112 a 116 (C/Custo Crédito)
                         $valor .                                      // Posição 117 a 132 (Valor)
                         $historico .                                  // Posição 133 a 372 (Histórico)
                         '  ' .                                        // Posição 373 a 374 (Conciliação D e C)
                         str_pad('', 74, ' ');                         // Posição 375 a 448 (Filler)

                $linhasTxt[] = $linha;
                $ordem++;
            }
        };

        $processarAba($dados['pagar_prov']);
        $processarAba($dados['pagar_baixa']);
        $processarAba($dados['receber_prov']);
        $processarAba($dados['receber_baixa']);
        $processarAba($dados['manual']);
        $processarAba($dados['adiantamentos'] ?? []);

        $conteudo = implode("\r\n", $linhasTxt);
        if (!empty($conteudo)) {
            $conteudo .= "\r\n";
        }

        // Nome do arquivo idêntico ao seu exemplo
        $codigoEmpresa = '0160'; 
        $nomeArquivo = "ctblctos{$codigoEmpresa}.txt";

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

        $dados['pagar_prov'] = $this->agruparProvisoes($dados['pagar_prov'] ?? []);
        $dados['receber_prov'] = $this->agruparProvisoes($dados['receber_prov'] ?? []);

        $linhasCsv = [];
        
        // Cabeçalho das Colunas
        $linhasCsv[] = implode(';', ['DATA', 'HISTORICO', 'TERCEIROS', 'CREDITO', 'DEBITO', 'N DOCUMENTO', 'CATEGORIA', 'VALOR']);

        $processarAba = function($linhas) use (&$linhasCsv) {
            foreach ($linhas as $item) {
                // Pula os lançamentos com erro, igual na Prosoft
                if ($item['status'] === 'ERRO' || $item['status'] === 'SEM PROVISÃO') {
                    continue; 
                }

                $data = $item['data']; // Ex: 15/04/2026
                
                // Limpa o ponto e vírgula do texto para não pular coluna no Excel
                $historico = str_replace(';', ',', $item['historico']);
                $categoria = str_replace(';', ',', $item['categoria']);
                
                // Terceiro: Pega o CNPJ/CPF onde ele estiver (débito ou crédito)
                $terceiro = !empty($item['terceiro_debito']) ? $item['terceiro_debito'] : $item['terceiro_credito'];
                if (!empty($terceiro)) {
                    $terceiro = '="' . $terceiro . '"';
                }

                $credito = $item['credito'] === '---' ? '' : $item['credito'];
                $debito = $item['debito'] === '---' ? '' : $item['debito'];
                
                // Pega o número do documento que adicionaremos no Service
                $documento = $item['documento'] ?? '';
                
                // Valor já vem formatado como 1.500,00
                $valor = $item['valor']; 

                $linhasCsv[] = implode(';', [
                    $data, $historico, $terceiro, $credito, $debito, $documento, $categoria, $valor
                ]);
            }
        };

        $processarAba($dados['pagar_prov']);
        $processarAba($dados['pagar_baixa']);
        $processarAba($dados['receber_prov']);
        $processarAba($dados['receber_baixa']);
        $processarAba($dados['manual']);
        $processarAba($dados['adiantamentos'] ?? []);

        // Converte para ISO-8859-1 (Padrão do Excel no Brasil para não bugar os acentos)
        $conteudo = implode("\r\n", $linhasCsv);
        $conteudo = mb_convert_encoding($conteudo, 'ISO-8859-1', 'UTF-8');

        $nomeArquivo = "exportacao_universal_" . date('Ymd_His') . ".csv";

        return response($conteudo)
            ->header('Content-Type', 'text/csv; charset=iso-8859-1')
            ->header('Content-Disposition', 'attachment; filename="' . $nomeArquivo . '"');
    }

    private function agruparProvisoes($provisoes): array
    {
        $agrupado = [];
        $diretos = [];
        foreach ($provisoes as $item) {
            if (in_array($item['status'] ?? null, ['ERRO', 'SEM PROVISÃO'], true)) {
                $diretos[] = $item;
                continue;
            }
            $chave = implode('|', [$item['documento'] ?? 'SEM_DOC', $item['debito'] ?? '', $item['credito'] ?? '', $item['terceiro_debito'] ?? '', $item['terceiro_credito'] ?? '']);
            $texto = str_replace(['R$', ' ', '.'], '', (string)($item['valor'] ?? 0));
            $valor = (float)str_replace(',', '.', $texto);
            if (!isset($agrupado[$chave])) {
                $agrupado[$chave] = $item;
                $agrupado[$chave]['_valor_soma'] = 0.0;
            }
            $agrupado[$chave]['_valor_soma'] += $valor;
        }
        foreach ($agrupado as &$item) {
            $item['valor'] = number_format($item['_valor_soma'], 2, ',', '.');
            unset($item['_valor_soma']);
        }
        unset($item);
        return array_values(array_merge($diretos, $agrupado));
    }
}