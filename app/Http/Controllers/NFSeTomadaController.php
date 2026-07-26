<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NFSeNacionalService;
use App\Models\ManifestaNfseTomada;
use App\Models\Fornecedor;
use App\Models\Compra;

class NFSeTomadaController extends BaseController
{
    protected $model;
    protected $formTitle;
    protected $redirectPage = '/nfse-tomadas';

    public function __construct() {
        $this->model = Compra::class;
        $this->formTitle = 'NFS-e Tomada Nacional';
        parent::__construct();
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $empresaId = (int) $this->empresa_id;
        $numeroNota = trim((string) $request->input('numero_nota', ''));
        $fornecedor = trim((string) $request->input('fornecedor', ''));
        $nomePrestador = trim((string) $request->input('nome_prestador', $fornecedor));
        $cnpjPrestador = trim((string) $request->input('cnpj_prestador', ''));
        $filialFiltro = $request->input('filial_id');
        $dataInicial = $request->input('data_inicial') ?: now()->startOfMonth()->format('d/m/Y');
        $dataFinal = $request->input('data_final') ?: now()->endOfMonth()->format('d/m/Y');

        $query = ManifestaNfseTomada::query()
            ->where('manifesta_nfse_tomadas.empresa_id', $empresaId)
            ->where('manifesta_nfse_tomadas.numero_nota', '!=', '0')
            ->where('manifesta_nfse_tomadas.valor_servico', '>', 0);

        if ($numeroNota !== '') {
            $numeroSemZeros = ltrim($numeroNota, '0');
            $query->where(function ($builder) use ($numeroNota, $numeroSemZeros): void {
                $builder->where('manifesta_nfse_tomadas.numero_nota', $numeroNota);
                if ($numeroSemZeros !== '') {
                    $builder->orWhereRaw(
                        'CAST(manifesta_nfse_tomadas.numero_nota AS UNSIGNED) = ?',
                        [(int) $numeroSemZeros]
                    );
                }
            });
        }

        if ($nomePrestador !== '' || $cnpjPrestador !== '') {
            $cnpjLimpo = preg_replace('/\D+/', '', $cnpjPrestador !== '' ? $cnpjPrestador : $fornecedor);
            $query->where(function ($builder) use ($nomePrestador, $cnpjLimpo): void {
                if ($nomePrestador !== '') {
                    $builder->where('manifesta_nfse_tomadas.prestador_nome', 'like', '%' . $nomePrestador . '%');
                }
                if ($cnpjLimpo !== '') {
                    $metodo = $nomePrestador !== '' ? 'orWhereRaw' : 'whereRaw';
                    $builder->{$metodo}(
                        "REPLACE(REPLACE(REPLACE(manifesta_nfse_tomadas.prestador_cnpj_cpf, '.', ''), '/', ''), '-', '') LIKE ?",
                        ['%' . $cnpjLimpo . '%']
                    );
                }
            });
        }

        if ($filialFiltro === 'matriz') {
            $query->whereNull('manifesta_nfse_tomadas.filial_id');
        } elseif (is_numeric($filialFiltro) && (int) $filialFiltro > 0) {
            $query->where('manifesta_nfse_tomadas.filial_id', (int) $filialFiltro);
        }

        try {
            $inicio = \Carbon\Carbon::createFromFormat('d/m/Y', $dataInicial)->startOfDay();
            $fim = \Carbon\Carbon::createFromFormat('d/m/Y', $dataFinal)->endOfDay();
            if ($inicio->greaterThan($fim)) {
                [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->endOfDay()];
            }
            $query->whereBetween('manifesta_nfse_tomadas.data_emissao', [$inicio, $fim]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Informe um período válido no formato dd/mm/aaaa.');
        }

        $query->leftJoin('filials', function ($join) use ($empresaId): void {
            $join->on('filials.id', '=', 'manifesta_nfse_tomadas.filial_id')
                ->where('filials.empresa_id', '=', $empresaId);
        });

        $docs = $query->select([
            'manifesta_nfse_tomadas.*',
            'filials.descricao as nome_filial',
            DB::raw("IF(manifesta_nfse_tomadas.fatura_salva = 1, 1, EXISTS(
                SELECT 1
                FROM conta_pagars
                INNER JOIN fornecedors
                    ON fornecedors.id = conta_pagars.fornecedor_id
                    AND fornecedors.empresa_id = conta_pagars.empresa_id
                WHERE conta_pagars.empresa_id = {$empresaId}
                  AND CAST(conta_pagars.numero_nota_fiscal AS UNSIGNED) = CAST(manifesta_nfse_tomadas.numero_nota AS UNSIGNED)
                  AND REPLACE(REPLACE(REPLACE(fornecedors.cpf_cnpj, '.', ''), '/', ''), '-', '') =
                      REPLACE(REPLACE(REPLACE(manifesta_nfse_tomadas.prestador_cnpj_cpf, '.', ''), '/', ''), '-', '')
            )) as ja_no_pagar"),
            DB::raw("IF(COALESCE(manifesta_nfse_tomadas.compra_servico_id, 0) > 0, 1, EXISTS(
                SELECT 1
                FROM compras
                INNER JOIN fornecedors
                    ON fornecedors.id = compras.fornecedor_id
                    AND fornecedors.empresa_id = compras.empresa_id
                WHERE compras.empresa_id = {$empresaId}
                  AND CAST(compras.nf AS UNSIGNED) = CAST(manifesta_nfse_tomadas.numero_nota AS UNSIGNED)
                  AND REPLACE(REPLACE(REPLACE(fornecedors.cpf_cnpj, '.', ''), '/', ''), '-', '') =
                      REPLACE(REPLACE(REPLACE(manifesta_nfse_tomadas.prestador_cnpj_cpf, '.', ''), '/', ''), '-', '')
            )) as ja_comprado"),
        ])
            ->orderByDesc('manifesta_nfse_tomadas.data_emissao')
            ->paginate(20)
            ->appends($request->query());

        $filiais = \App\Models\Filial::where('empresa_id', $empresaId)->orderBy('descricao')->get();

        return view('nfse_tomadas.index', [
            'docs' => $docs,
            'filiais' => $filiais,
            'data_inicial' => $dataInicial,
            'data_final' => $dataFinal,
            'numero_nota' => $numeroNota,
            'fornecedor' => $fornecedor,
            'filial_filtro' => $filialFiltro,
            'title' => 'NFS-e Tomadas',
        ]);
    }
public function sincronizarManual(Request $request)
    {
        $empresaId = (int) $this->empresa_id;
        $local = (string) $request->input('local', 'matriz');
        $filialId = null;

        try {
            if ($local === 'matriz') {
                $certificado = DB::table('certificados')
                    ->where('empresa_id', $empresaId)
                    ->first();
                $configuracao = DB::table('config_notas')
                    ->where('empresa_id', $empresaId)
                    ->first();

                if (!$certificado || !$configuracao) {
                    throw new \RuntimeException('Configuração fiscal ou certificado da matriz não encontrado.');
                }

                $servico = new NFSeNacionalService(
                    preg_replace('/\D+/', '', (string) $configuracao->cnpj),
                    (string) $certificado->arquivo,
                    (string) $certificado->senha
                );
            } else {
                if (!ctype_digit($local) || (int) $local <= 0) {
                    throw new \InvalidArgumentException('Unidade informada é inválida.');
                }

                $filial = DB::table('filials')
                    ->where('empresa_id', $empresaId)
                    ->where('id', (int) $local)
                    ->first();

                if (!$filial) {
                    throw new \RuntimeException('Filial não encontrada para a empresa atual.');
                }

                $filialId = (int) $filial->id;
                $servico = new NFSeNacionalService(
                    preg_replace('/\D+/', '', (string) $filial->cnpj),
                    (string) $filial->arquivo_certificado,
                    (string) $filial->senha_certificado
                );
            }

            $nsuAtual = (int) (ManifestaNfseTomada::query()
                ->where('empresa_id', $empresaId)
                ->where(function ($query) use ($filialId): void {
                    $filialId === null
                        ? $query->whereNull('filial_id')
                        : $query->where('filial_id', $filialId);
                })
                ->max('nsu') ?? 0);

            $totalProcessado = 0;
            $limiteConsultas = 30;

            for ($consulta = 0; $consulta < $limiteConsultas; $consulta++) {
                $resultado = $servico->consultar($nsuAtual);
                if (!$resultado) {
                    break;
                }

                $documentos = isset($resultado['LoteDFe']) && is_array($resultado['LoteDFe'])
                    ? $resultado['LoteDFe']
                    : [];
                $processadosNoLote = 0;
                $maiorNsu = $nsuAtual;

                foreach ($documentos as $documento) {
                    $chave = trim((string) ($documento['ChaveAcesso'] ?? ''));
                    $nsu = (int) ($documento['NSU'] ?? $documento['nsu'] ?? $documento['numNSU'] ?? 0);
                    if ($chave === '' || $nsu <= 0) {
                        continue;
                    }

                    $maiorNsu = max($maiorNsu, $nsu);
                    $conteudoCompactado = base64_decode((string) ($documento['ArquivoXml'] ?? ''), true);
                    if ($conteudoCompactado === false) {
                        \Log::warning('NFS-e recebida com conteúdo Base64 inválido.', compact('empresaId', 'chave', 'nsu'));
                        continue;
                    }

                    $xmlString = @gzdecode($conteudoCompactado);
                    if ($xmlString === false) {
                        $xmlString = $conteudoCompactado;
                    }

                    $xml = $this->carregarXmlNfse($xmlString);
                    if (!$xml) {
                        \Log::warning('NFS-e recebida com XML inválido.', compact('empresaId', 'chave', 'nsu'));
                        continue;
                    }

                    $dados = $this->extrairDadosBasicosNfse($xml, $chave, $documento);
                    $dados['situacao'] = $this->detectarSituacaoNfse($xml);

                    $diretorio = public_path('xml_servico');
                    if (!is_dir($diretorio) && !@mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
                        \Log::warning('Não foi possível criar o diretório de XML de NFS-e.', ['diretorio' => $diretorio]);
                    } elseif (@file_put_contents($diretorio . DIRECTORY_SEPARATOR . $chave . '.xml', $xmlString, LOCK_EX) === false) {
                        \Log::warning('Não foi possível persistir o XML físico da NFS-e.', compact('empresaId', 'chave'));
                    }

                    $this->garantirFornecedorNfse($dados, $xml, $empresaId);

                    ManifestaNfseTomada::query()->updateOrCreate(
                        ['empresa_id' => $empresaId, 'chave' => $chave],
                        [
                            'filial_id' => $filialId,
                            'usuario_id' => $this->usuario_id,
                            'nsu' => $nsu,
                            'numero_nota' => $dados['numero_nota'],
                            'prestador_nome' => $dados['prestador_nome'],
                            'prestador_cnpj_cpf' => $dados['prestador_cnpj_cpf'],
                            'valor_servico' => $dados['valor_servico'],
                            'valor_liquido' => $dados['valor_liquido'],
                            'data_emissao' => $dados['data_emissao'],
                            'situacao' => $dados['situacao'],
                        ]
                    );

                    $processadosNoLote++;
                    $totalProcessado++;
                }

                if ($processadosNoLote > 0) {
                    $nsuAtual = $maiorNsu;
                } else {
                    $nsuAtual++;
                    ManifestaNfseTomada::query()->updateOrCreate(
                        ['empresa_id' => $empresaId, 'chave' => 'AVANCO_COMPAT_FILA_' . $filialId . '_' . $nsuAtual],
                        [
                            'filial_id' => $filialId,
                            'usuario_id' => $this->usuario_id,
                            'nsu' => $nsuAtual,
                            'numero_nota' => '0',
                            'prestador_nome' => 'SINC_AVANCO_MECANICO',
                            'prestador_cnpj_cpf' => '00000000000000',
                            'valor_servico' => 0,
                            'valor_liquido' => 0,
                            'data_emissao' => now(),
                            'fatura_salva' => 0,
                            'situacao' => 'CONTROLE',
                        ]
                    );
                }

                if ($processadosNoLote > 0 && $processadosNoLote < 50) {
                    break;
                }
            }

            $mensagem = $totalProcessado > 0
                ? "Sincronização concluída: {$totalProcessado} NFS-e processada(s)."
                : 'Sincronização concluída sem novas NFS-e. A fila de NSU foi atualizada.';

            return redirect($this->redirectPage)->with('mensagem_sucesso', $mensagem);
        } catch (\Throwable $e) {
            \Log::error('Erro na sincronização manual de NFS-e tomada.', [
                'empresa_id' => $empresaId,
                'filial_id' => $filialId,
                'erro' => $e->getMessage(),
            ]);

            return redirect($this->redirectPage)
                ->with('mensagem_erro', 'Não foi possível sincronizar as NFS-e. Consulte os logs para obter os detalhes.');
        }
    }
public function detalhesLançamento($id)
    {
        $nota = ManifestaNfseTomada::query()
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail((int) $id);

        $veiculos = \App\Models\Veiculo::query()
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('placa')
            ->get();
        $categoriasDeConta = \App\Models\CategoriaConta::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome')
            ->get();

        $xml = $this->obterXmlNfse($nota);
        $descricaoServico = $xml
            ? $this->extrairDescricaoServicoNfse($xml)
            : 'Prestação de serviços gerais discriminada no documento fiscal.';

        return view('nfse_tomadas.importar_painel', [
            'nota' => $nota,
            'veiculos' => $veiculos,
            'categoriasDeConta' => $categoriasDeConta,
            'descricao_servico' => $descricaoServico,
            'title' => $this->formTitle,
        ]);
    }
public function salvarImportacaoPainel(Request $request, $id)
    {
        $request->validate([
            'categoria_conta_id' => ['required', 'integer'],
            'prazo_pagamento' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'quantidade_parcelas' => ['nullable', 'integer', 'min:1', 'max:120'],
            'veiculo_id' => ['nullable', 'integer'],
            'fatura_json' => ['nullable', 'string'],
        ]);

        $empresaId = (int) $this->empresa_id;
        $usuarioId = (int) $this->usuario_id;

        try {
            $numeroNota = DB::transaction(function () use ($request, $id, $empresaId, $usuarioId): string {
                $nota = ManifestaNfseTomada::query()
                    ->where('empresa_id', $empresaId)
                    ->whereKey((int) $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $nota->fatura_salva === 1 || !empty($nota->compra_servico_id)) {
                    throw new \DomainException('Esta NFS-e já foi importada para compras e contas a pagar.');
                }
                if (strtoupper((string) $nota->situacao) === 'CANCELADA') {
                    throw new \DomainException('Uma NFS-e cancelada não pode ser provisionada.');
                }

                $categoria = \App\Models\CategoriaConta::query()
                    ->where('empresa_id', $empresaId)
                    ->whereKey((int) $request->categoria_conta_id)
                    ->firstOrFail();

                $veiculoId = null;
                if ($request->filled('veiculo_id')) {
                    $veiculoId = \App\Models\Veiculo::query()
                        ->where('empresa_id', $empresaId)
                        ->whereKey((int) $request->veiculo_id)
                        ->value('id');
                    if (!$veiculoId) {
                        throw new \InvalidArgumentException('Veículo informado não pertence à empresa atual.');
                    }
                }

                $fornecedor = $this->obterOuCriarFornecedorDaNfse($nota, $empresaId);
                $numeroNota = ltrim((string) $nota->numero_nota, '0') ?: '0';
                $dataEmissao = $nota->data_emissao ?: now();
                $valorBruto = round((float) $nota->valor_servico, 2);
                $valorLiquido = round((float) ($nota->valor_liquido > 0 ? $nota->valor_liquido : $valorBruto), 2);

                $compraExistente = Compra::query()
                    ->where('empresa_id', $empresaId)
                    ->where('chave', $nota->chave)
                    ->lockForUpdate()
                    ->first();
                if ($compraExistente) {
                    throw new \DomainException('Já existe uma compra vinculada à chave desta NFS-e.');
                }

                $compra = Compra::create([
                    'fornecedor_id' => $fornecedor->id,
                    'usuario_id' => $usuarioId,
                    'nf' => $numeroNota,
                    'data_emissao' => $dataEmissao,
                    'valor' => $valorBruto,
                    'veiculo_id' => $veiculoId,
                    'estado' => 'IMPORTADO',
                    'xml_importado' => 1,
                    'xml_path' => $nota->chave . '.xml',
                    'categoria_conta_id' => $categoria->id,
                    'chave' => $nota->chave,
                    'empresa_id' => $empresaId,
                    'filial_id' => $nota->filial_id,
                    'observacao' => 'NFS-e Nacional Tomada Nº ' . $nota->numero_nota,
                    'numero_emissao' => 0,
                ]);

                $produtoServico = \App\Models\Produto::query()
                    ->where('empresa_id', $empresaId)
                    ->where('referencia', '140101')
                    ->first();

                if (!$produtoServico) {
                    $produtoServico = \App\Models\Produto::create([
                        'nome' => 'SERVIÇO TOMADO - NFS-E NACIONAL',
                        'referencia' => '140101',
                        'valor_compra' => $valorBruto,
                        'valor_venda' => $valorBruto,
                        'gerenciar_estoque' => 0,
                        'empresa_id' => $empresaId,
                        'locais' => '["-1"]',
                        'tipo_item' => '09',
                        'unidade_compra' => 'UN',
                        'unidade_venda' => 'UN',
                        'conversao_unitaria' => 1,
                    ]);
                }

                \App\Models\ItemCompra::create([
                    'compra_id' => $compra->id,
                    'produto_id' => $produtoServico->id,
                    'quantidade' => 1,
                    'valor_unitario' => $valorBruto,
                    'unidade_compra' => 'UN',
                    'cfop_entrada' => '1933',
                ]);

                $xml = $this->obterXmlNfse($nota);
                $retencoes = $this->calcularRetencoesNfse($xml, $valorBruto, $valorLiquido);
                $parcelas = $this->normalizarParcelasNfse($request, $dataEmissao, $valorLiquido, $veiculoId, $empresaId);
                $totalRetencoes = round(array_sum($retencoes), 2);
                $descricao = $nota->prestador_nome
                    ? 'Prestação de Serviço - ' . $nota->prestador_nome
                    : 'Serviço Tomado';

                foreach ($parcelas as $indice => $parcela) {
                    $retencoesParcela = $indice === 0 ? $retencoes : array_fill_keys(array_keys($retencoes), 0.0);
                    $valorOriginal = round($parcela['valor'] + ($indice === 0 ? $totalRetencoes : 0), 2);

                    \App\Models\ContaPagar::create([
                        'compra_id' => $compra->id,
                        'fornecedor_id' => $fornecedor->id,
                        'data_vencimento' => $parcela['vencimento'],
                        'data_emissao' => $dataEmissao,
                        'data_emissao_nfe' => $dataEmissao,
                        'valor_integral' => $parcela['valor'],
                        'valor_original' => $valorOriginal,
                        'valor_pago' => 0,
                        'status' => false,
                        'referencia' => 'NFS-e ' . $nota->numero_nota . ' - ' . mb_substr($descricao, 0, 50) . ' (' . ($indice + 1) . '/' . count($parcelas) . ')',
                        'categoria_id' => $categoria->id,
                        'empresa_id' => $empresaId,
                        'filial_id' => $nota->filial_id,
                        'veiculo_id' => $parcela['veiculo_id'],
                        'numero_nota_fiscal' => $numeroNota,
                        'usuario_id' => $usuarioId,
                        ...$retencoesParcela,
                    ]);
                }

                $nota->compra_servico_id = $compra->id;
                $nota->fatura_salva = 1;
                $nota->usuario_id = $usuarioId;
                $nota->save();

                return (string) $nota->numero_nota;
            }, 3);

            return redirect($this->redirectPage)
                ->with('mensagem_sucesso', "NFS-e Nº {$numeroNota} importada e provisionada com sucesso.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Erro ao importar NFS-e tomada para o financeiro.', [
                'empresa_id' => $empresaId,
                'nota_id' => $id,
                'erro' => $e->getMessage(),
            ]);

            $mensagem = $e instanceof \DomainException || $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : 'Não foi possível processar a NFS-e. Consulte os logs para obter os detalhes.';

            return redirect()->back()->withInput()->with('mensagem_erro', $mensagem);
        }
    }
public function imprimirEspelho($id)
    {
        try {
            $nota = ManifestaNfseTomada::query()
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail((int) $id);
            $xml = $this->obterXmlNfse($nota);

            if (!$xml) {
                return view('nfse_tomadas.espelho_layout', [
                    'nota' => $nota,
                    'descricao_servico' => 'Serviço tomado — XML não localizado.',
                    'title' => 'DANFSE - Nota ' . $nota->numero_nota,
                ]);
            }

            $dados = $this->extrairDadosApresentacaoNfse($xml, $nota);

            return view('nfse.visualizar', [
                'xml' => $xml,
                'nota' => $nota,
                'dados' => $dados,
                'descricao_servico' => $dados['descricao_servico'],
                'title' => 'DANFSE - Nota ' . $nota->numero_nota,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao renderizar espelho da NFS-e.', [
                'empresa_id' => $this->empresa_id,
                'nota_id' => $id,
                'erro' => $e->getMessage(),
            ]);

            return redirect($this->redirectPage)
                ->with('mensagem_erro', 'Não foi possível abrir o espelho da NFS-e.');
        }
    }

    public function downloadXml($id)
    {
        $nota = ManifestaNfseTomada::query()
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail((int) $id);
        $caminho = public_path('xml_servico/' . basename((string) $nota->chave) . '.xml');

        if (!is_file($caminho)) {
            abort(404, 'XML da NFS-e não encontrado.');
        }

        return response()->download($caminho, 'NFSE-' . ($nota->numero_nota ?: $nota->id) . '.xml', [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function detalhes($id)
    {
        return $this->imprimirEspelho($id);
    }

    public function sincronizarPorData(Request $request)
    {
        return $this->sincronizarManual($request);
    }

    private function carregarXmlNfse(string $xmlString): ?\SimpleXMLElement
    {
        if (trim($xmlString) === '') {
            return null;
        }
        $anterior = libxml_use_internal_errors(true);
        $xmlLimpo = preg_replace('/\sxmlns(?::[^=]+)?="[^"]*"/i', '', $xmlString);
        $xml = simplexml_load_string((string) $xmlLimpo, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);
        return $xml instanceof \SimpleXMLElement ? $xml : null;
    }

    private function obterXmlNfse(ManifestaNfseTomada $nota): ?\SimpleXMLElement
    {
        $caminho = public_path('xml_servico/' . basename((string) $nota->chave) . '.xml');
        $conteudo = is_file($caminho) ? @file_get_contents($caminho) : false;
        if ($conteudo === false && !empty($nota->xml_base64)) {
            $conteudo = base64_decode((string) $nota->xml_base64, true);
        }
        if (($conteudo === false || $conteudo === null || $conteudo === '') && !empty($nota->xml_puro)) {
            $conteudo = (string) $nota->xml_puro;
        }
        return is_string($conteudo) ? $this->carregarXmlNfse($conteudo) : null;
    }

    private function extrairDadosBasicosNfse(\SimpleXMLElement $xml, string $chave, array $documento): array
    {
        $inf = $xml->infNFSe ?? $xml->NFSe->infNFSe ?? $xml->compNFSe->NFSe->infNFSe ?? $xml;
        $numero = (string) ($inf->nNFSe ?? $inf->identificacaoNFSe->numero ?? substr($chave, 25, 9));
        $nome = (string) ($inf->emit->xNome ?? $inf->prestador->razSocial ?? $inf->prestador->xNome ?? 'Fornecedor sem nome');
        $cnpj = (string) ($inf->emit->CNPJ ?? $inf->prestador->CNPJ ?? $inf->prestador->identificacaoPrestador->cnpj ?? substr($chave, 6, 14));
        $bruto = (float) ($inf->DPS->infDPS->valores->vServPrest->vServ ?? $inf->valores->vServPrest->vServ ?? $inf->valores->vServ ?? 0);
        $liquido = (float) ($inf->valores->vLiq ?? $bruto);
        if ($bruto <= 0 && $liquido > 0) $bruto = $liquido;
        $data = (string) ($inf->dhEmi ?? $inf->dCompet ?? $inf->dtEmit ?? ($documento['DataHoraGeracao'] ?? ''));
        try { $dataEmissao = $data !== '' ? \Carbon\Carbon::parse($data) : now(); }
        catch (\Throwable $e) { $dataEmissao = now(); }
        return [
            'numero_nota' => $numero !== '' ? $numero : '0',
            'prestador_nome' => trim($nome) ?: 'Fornecedor sem nome',
            'prestador_cnpj_cpf' => preg_replace('/\D+/', '', $cnpj),
            'valor_servico' => round($bruto, 2),
            'valor_liquido' => round($liquido > 0 ? $liquido : $bruto, 2),
            'data_emissao' => $dataEmissao,
        ];
    }

    private function detectarSituacaoNfse(\SimpleXMLElement $xml): string
    {
        $texto = strtoupper($xml->asXML() ?: '');
        $codigos = [(string) ($xml->infNFSe->cStat ?? ''), (string) ($xml->cStat ?? ''), (string) ($xml->evento->infEvento->cStat ?? '')];
        if (in_array('101', $codigos, true) || str_contains($texto, '<CSTAT>101</CSTAT>') || str_contains($texto, 'CANCELAD')) {
            return 'CANCELADA';
        }
        return 'NORMAL';
    }

    private function garantirFornecedorNfse(array $dados, \SimpleXMLElement $xml, int $empresaId): void
    {
        $cnpj = preg_replace('/\D+/', '', (string) $dados['prestador_cnpj_cpf']);
        if (!in_array(strlen($cnpj), [11, 14], true)) return;
        $existe = Fornecedor::query()->where('empresa_id', $empresaId)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'/',''),'-','') = ?", [$cnpj])->exists();
        if ($existe) return;
        $inf = $xml->infNFSe ?? $xml->NFSe->infNFSe ?? $xml;
        $end = $inf->emit->enderNac ?? $inf->prestador->endereco ?? null;
        Fornecedor::create([
            'empresa_id' => $empresaId,
            'razao_social' => preg_replace('/^[0-9.\-\/]+\s+/', '', (string) $dados['prestador_nome']),
            'nome_fantasia' => preg_replace('/^[0-9.\-\/]+\s+/', '', (string) $dados['prestador_nome']),
            'cpf_cnpj' => $this->formatarDocumento($cnpj),
            'ie_rg' => 'ISENTO',
            'rua' => (string) ($end->xLgr ?? $end->endereco ?? 'Não informada'),
            'numero' => (string) ($end->nro ?? $end->numero ?? 'S/N'),
            'bairro' => (string) ($end->xBairro ?? $end->bairro ?? 'Não informado'),
            'cidade_id' => 1,
            'cep' => preg_replace('/\D+/', '', (string) ($end->CEP ?? $end->cep ?? '00000000')),
            'contribuinte' => 1,
            'cod_pais' => '1058',
            'ativo' => 1,
        ]);
    }

    private function obterOuCriarFornecedorDaNfse(ManifestaNfseTomada $nota, int $empresaId): Fornecedor
    {
        $documento = preg_replace('/\D+/', '', (string) $nota->prestador_cnpj_cpf);
        if (!in_array(strlen($documento), [11, 14], true)) {
            throw new \DomainException('O documento do prestador da NFS-e é inválido.');
        }
        $fornecedor = Fornecedor::query()->where('empresa_id', $empresaId)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'/',''),'-','') = ?", [$documento])->first();
        if ($fornecedor) return $fornecedor;
        return Fornecedor::create([
            'empresa_id' => $empresaId,
            'razao_social' => $nota->prestador_nome ?: 'Prestador NFS-e ' . $documento,
            'nome_fantasia' => $nota->prestador_nome ?: 'Prestador NFS-e ' . $documento,
            'cpf_cnpj' => $this->formatarDocumento($documento),
            'cidade_id' => 1,
            'ie_rg' => 'ISENTO',
            'ativo' => 1,
        ]);
    }

    private function formatarDocumento(string $documento): string
    {
        if (strlen($documento) === 14) return substr($documento,0,2).'.'.substr($documento,2,3).'.'.substr($documento,5,3).'/'.substr($documento,8,4).'-'.substr($documento,12,2);
        if (strlen($documento) === 11) return substr($documento,0,3).'.'.substr($documento,3,3).'.'.substr($documento,6,3).'-'.substr($documento,9,2);
        return $documento;
    }

    private function calcularRetencoesNfse(?\SimpleXMLElement $xml, float $bruto, float $liquido): array
    {
        $iss = 0.0;
        $pis = $cofins = $ir = $csll = $inss = 0.0;
        if ($xml) {
            $tpRet = (string) ($xml->infNFSe->DPS->infDPS->valores->trib->tribMun->tpRetISSQN ?? $xml->DPS->infDPS->valores->trib->tribMun->tpRetISSQN ?? '');
            $issRetidoAbrasf = (string) ($xml->Nfse->InfNfse->Servico->Valores->IssRetido ?? $xml->infNFSe->servico->valores->issRetido ?? '');
            if ($tpRet === '2' || $issRetidoAbrasf === '1') {
                $iss = (float) ($xml->infNFSe->valores->vISSQN ?? $xml->valores->vISSQN ?? $xml->Nfse->InfNfse->Servico->Valores->ValorIssRetido ?? $xml->Nfse->InfNfse->Servico->Valores->ValorIss ?? 0);
            }
            $pis = (float) ($xml->infNFSe->valores->vPIS ?? $xml->Nfse->InfNfse->Servico->Valores->ValorPis ?? 0);
            $cofins = (float) ($xml->infNFSe->valores->vCOFINS ?? $xml->Nfse->InfNfse->Servico->Valores->ValorCofins ?? 0);
            $ir = (float) ($xml->infNFSe->valores->vIR ?? $xml->Nfse->InfNfse->Servico->Valores->ValorIr ?? 0);
            $csll = (float) ($xml->infNFSe->valores->vCSLL ?? $xml->Nfse->InfNfse->Servico->Valores->ValorCsll ?? 0);
            $inss = (float) ($xml->infNFSe->valores->vINSS ?? $xml->Nfse->InfNfse->Servico->Valores->ValorInss ?? 0);
        }
        $diferenca = max(0, round($bruto - $liquido - $iss - $pis - $cofins - $ir - $csll - $inss, 2));
        if ($diferenca > 0 && ($pis + $cofins + $ir + $csll + $inss) <= 0) {
            $pis = round($bruto * 0.0065, 2);
            $cofins = round($bruto * 0.03, 2);
            $csll = round($bruto * 0.01, 2);
            $ir = max(0, round($diferenca - $pis - $cofins - $csll, 2));
        }
        return ['valor_iss'=>round($iss,2),'valor_pis'=>round($pis,2),'valor_cofins'=>round($cofins,2),'valor_ir'=>round($ir,2),'valor_csll'=>round($csll,2),'valor_inss'=>round($inss,2)];
    }

    private function normalizarParcelasNfse(Request $request, $dataEmissao, float $valorLiquido, ?int $veiculoPadrao, int $empresaId): array
    {
        $faturas = json_decode((string) $request->input('fatura_json', ''), true);
        $parcelas = [];
        if (is_array($faturas) && $faturas !== []) {
            foreach ($faturas as $fatura) {
                $valor = $this->parseMoedaNfse($fatura['valor_parcela'] ?? $fatura['valor'] ?? 0);
                if ($valor <= 0) throw new \InvalidArgumentException('Todas as parcelas devem possuir valor maior que zero.');
                $vencimentoBruto = trim((string) ($fatura['vencimento'] ?? ''));
                try {
                    $vencimento = str_contains($vencimentoBruto, '/')
                        ? \Carbon\Carbon::createFromFormat('d/m/Y', $vencimentoBruto)->format('Y-m-d')
                        : \Carbon\Carbon::parse($vencimentoBruto)->format('Y-m-d');
                } catch (\Throwable $e) { throw new \InvalidArgumentException('Existe uma parcela com vencimento inválido.'); }
                $veiculo = $veiculoPadrao;
                if (!empty($fatura['veiculo_id'])) {
                    $veiculo = \App\Models\Veiculo::query()->where('empresa_id', $empresaId)->whereKey((int)$fatura['veiculo_id'])->value('id');
                    if (!$veiculo) throw new \InvalidArgumentException('Uma parcela informa veículo inválido.');
                }
                $parcelas[] = ['vencimento'=>$vencimento,'valor'=>round($valor,2),'veiculo_id'=>$veiculo];
            }
            $soma = round(array_sum(array_column($parcelas, 'valor')), 2);
            if (abs($soma - $valorLiquido) > 0.02) throw new \InvalidArgumentException('A soma das parcelas deve corresponder ao valor líquido da NFS-e.');
            $parcelas[array_key_last($parcelas)]['valor'] = round($parcelas[array_key_last($parcelas)]['valor'] + ($valorLiquido - $soma), 2);
            return $parcelas;
        }
        $quantidade = max(1, min(120, (int) $request->input('quantidade_parcelas', 1)));
        $prazo = max(0, min(3650, (int) $request->input('prazo_pagamento', 30)));
        $base = round($valorLiquido / $quantidade, 2); $acumulado = 0.0;
        $emissao = \Carbon\Carbon::parse($dataEmissao);
        for ($i=1; $i<=$quantidade; $i++) {
            $valor = $i === $quantidade ? round($valorLiquido-$acumulado,2) : $base;
            $acumulado += $valor;
            $parcelas[]=['vencimento'=>$emissao->copy()->addDays($prazo*$i)->format('Y-m-d'),'valor'=>$valor,'veiculo_id'=>$veiculoPadrao];
        }
        return $parcelas;
    }

    private function parseMoedaNfse($valor): float
    {
        if (is_numeric($valor)) return round((float)$valor, 2);
        $texto=preg_replace('/[^0-9,.-]/','',(string)$valor);
        if (str_contains($texto, ',')) $texto=str_replace(['. ','.'],['',''],$texto); // mantém compatibilidade com formato BR
        $texto=str_replace(',','.',$texto);
        return round((float)$texto,2);
    }

    private function extrairDescricaoServicoNfse(\SimpleXMLElement $xml): string
    {
        $valor = (string) ($xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ ?? $xml->DPS->infDPS->serv->cServ->xDescServ ?? $xml->infNFSe->serv->cServ->xDescServ ?? $xml->infNFSe->servico->Discriminacao ?? $xml->Nfse->InfNfse->Servico->Discriminacao ?? '');
        return trim($valor) !== '' ? trim($valor) : 'Descrição não informada no XML.';
    }

    private function extrairDadosApresentacaoNfse(\SimpleXMLElement $xml, ManifestaNfseTomada $nota): array
    {
        $inf = $xml->infNFSe ?? $xml->NFSe->infNFSe ?? $xml;
        $tomador = $inf->DPS->infDPS->toma ?? $xml->DPS->infDPS->toma ?? $inf->toma ?? $xml->Nfse->InfNfse->TomadorServico ?? null;
        $retencoes = $this->calcularRetencoesNfse($xml, (float)$nota->valor_servico, (float)$nota->valor_liquido);
        return [
            'numero' => (string) ($inf->nNFSe ?? $nota->numero_nota),
            'data_emissao' => (string) ($inf->dhProc ?? $inf->dhEmi ?? $nota->data_emissao),
            'prestador_nome' => (string) ($inf->emit->xNome ?? $inf->prestador->razSocial ?? $nota->prestador_nome),
            'prestador_documento' => (string) ($inf->emit->CNPJ ?? $inf->prestador->CNPJ ?? $nota->prestador_cnpj_cpf),
            'prestador_endereco' => trim((string)($inf->emit->enderNac->xLgr ?? $inf->prestador->endereco->endereco ?? '') . ', ' . (string)($inf->emit->enderNac->nro ?? $inf->prestador->endereco->numero ?? 'S/N') . ' - ' . (string)($inf->emit->enderNac->xBairro ?? $inf->prestador->endereco->bairro ?? '')),
            'tomador_nome' => (string) ($tomador->xNome ?? $tomador->RazaoSocial ?? 'Não informado'),
            'tomador_documento' => (string) ($tomador->CNPJ ?? $tomador->CPF ?? $tomador->IdentificacaoTomador->CpfCnpj->Cnpj ?? $tomador->IdentificacaoTomador->CpfCnpj->Cpf ?? ''),
            'descricao_servico' => $this->extrairDescricaoServicoNfse($xml),
            'valor_bruto' => (float)($nota->valor_servico),
            'valor_liquido' => (float)($nota->valor_liquido > 0 ? $nota->valor_liquido : $nota->valor_servico),
            'retencoes' => $retencoes,
            'valor_iss_apurado' => (float)($inf->valores->vISSQN ?? 0),
            'tributacao_municipal' => (string)($inf->xTribMun ?? ''),
            'codigo_tributacao' => (string)($inf->DPS->infDPS->serv->cServ->cTribNac ?? ''),
        ];
    }

    public function buscarPagamentosManuais()
    {
        $vinculos = 0;
        try {
            ManifestaNfseTomada::query()
                ->where('empresa_id', $this->empresa_id)
                ->where('fatura_salva', 0)
                ->where('numero_nota', '!=', '0')
                ->orderBy('id')->chunkById(100, function ($notas) use (&$vinculos): void {
                    foreach ($notas as $nota) {
                        $numero = (int)ltrim((string)$nota->numero_nota, '0');
                        $cnpj = preg_replace('/\D/', '', (string)$nota->prestador_cnpj_cpf);
                        if (!$cnpj && strlen((string)$nota->chave) === 50) $cnpj = substr((string)$nota->chave, 9, 14);
                        if (!$cnpj && $nota->prestador_nome) {
                            $possivel = preg_replace('/\D/', '', (string)$nota->prestador_nome);
                            if (in_array(strlen($possivel), [11,14], true)) $cnpj = $possivel;
                        }
                        if (!$numero || !$cnpj) continue;

                        $pagamento = DB::table('conta_pagars')
                            ->join('fornecedors', function ($join): void {
                                $join->on('fornecedors.id','=','conta_pagars.fornecedor_id')->on('fornecedors.empresa_id','=','conta_pagars.empresa_id');
                            })
                            ->where('conta_pagars.empresa_id', $this->empresa_id)
                            ->whereRaw('CAST(conta_pagars.numero_nota_fiscal AS UNSIGNED) = ?', [$numero])
                            ->whereRaw("REPLACE(REPLACE(REPLACE(fornecedors.cpf_cnpj,'.',''),'/',''),'-','') = ?", [$cnpj])
                            ->select('conta_pagars.id','fornecedors.cpf_cnpj')->first();
                        if (!$pagamento) continue;
                        $nota->prestador_cnpj_cpf = $pagamento->cpf_cnpj;
                        $nota->fatura_salva = 1;
                        $nota->save();
                        $vinculos++;
                    }
                });
            return redirect()->back()->with('mensagem_sucesso', "Varredura concluída: {$vinculos} nota(s) vinculada(s).");
        } catch (\Throwable $e) {
            \Log::error('Erro ao vincular NFS-e tomadas a pagamentos manuais', ['empresa_id'=>$this->empresa_id,'erro'=>$e->getMessage()]);
            return redirect()->back()->with('mensagem_erro', 'Erro na varredura: ' . $e->getMessage());
        }
    }
}
