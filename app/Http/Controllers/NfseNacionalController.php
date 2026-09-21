<?php

namespace App\Http\Controllers;

use App\Models\ConfigNota;
use App\Models\FaturaEngenharia;
use App\Services\Nfse\NfseServiceFactory;
use App\Services\NfseNacionalTools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NFePHP\Common\Certificate;

class NfseNacionalController extends BaseController
{
    protected $redirectPage = '/contratos/medicoes';
    protected $formTitle = 'NFS-e de Medição';

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function emitir($id)
    {
        try {
            $fatura = $this->fatura((int) $id);

            if ($fatura->chave_nfse) {
                return redirect()->back()->with(
                    'mensagem_erro',
                    'Esta medição já possui uma NFS-e emitida. Consulte ou visualize o documento existente.'
                );
            }

            $servico = $fatura->servico ?: optional($fatura->itens->first())->servico;
            if (!$servico) {
                throw new \RuntimeException('Nenhum serviço vinculado a esta medição.');
            }

            if (!$fatura->cliente) {
                throw new \RuntimeException('Nenhum cliente vinculado a esta medição.');
            }

            $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            if (!$configNota) {
                throw new \RuntimeException(
                    'Configurações de nota fiscal não encontradas para esta empresa.'
                );
            }

            if ((bool) ($servico->exige_obra ?? false) && !$fatura->codigo_obra) {
                throw new \RuntimeException(
                    "O serviço '{$servico->nome}' exige Código de Obra/ART na medição."
                );
            }

            [$serieDps, $numeroDps] = $this->reservarNumeroDps($configNota);

            $config = (array) $configNota->getAttributes();
            $config['serie_dps'] = $serieDps;
            $config['numero_dps'] = $numeroDps;
            $config['tpAmb'] = (int) ($configNota->ambiente ?? 2);
            $config['usuario_saatri'] = config('services.nfse_saatri.username');
            $config['senha_saatri'] = config('services.nfse_saatri.password');

            $certificado = $this->certificado();
            $provedor = $this->provedor((string) ($configNota->codMun ?? ''));

            $tools = NfseServiceFactory::criar(
                $provedor,
                $config,
                $certificado
            );

            $resultado = $tools->emitir(
                $this->montarDps(
                    $fatura,
                    $servico,
                    $configNota,
                    $serieDps,
                    $numeroDps
                )
            );

            $numeroOficial = is_array($resultado)
                ? ($resultado['numero_nfse'] ?? null)
                : null;

            $xmlRetornado = is_array($resultado)
                ? ($resultado['xml_retorno'] ?? null)
                : $resultado;

            $chave = $tools->getChave();
            if (!$chave) {
                throw new \RuntimeException(
                    'O emissor não retornou a chave da NFS-e.'
                );
            }

            $fatura->update([
                'numero_nfse' => $numeroOficial ?: $numeroDps,
                'serie_nfse' => $serieDps,
                'chave_nfse' => $chave,
                'protocolo_nfse' => $tools->getProtocolo(),
                'xml_nfse' => is_array($xmlRetornado)
                    ? json_encode($xmlRetornado, JSON_UNESCAPED_UNICODE)
                    : $xmlRetornado,
                'status' => 'Finalizado',
            ]);

            return redirect()->back()->with(
                'mensagem_sucesso',
                'NFS-e enviada e processada com sucesso!'
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao emitir NFS-e de medição.', [
                'medicao_id' => $id,
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'mensagem_erro',
                'Falha ao emitir NFS-e: ' . $e->getMessage()
            );
        }
    }

    public function consultar($id)
    {
        try {
            $fatura = $this->fatura((int) $id);

            if (!$fatura->chave_nfse) {
                throw new \RuntimeException(
                    'A medição ainda não possui chave de acesso NFS-e.'
                );
            }

            $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->firstOrFail();
            $provedor = $this->provedor((string) ($configNota->codMun ?? ''));

            if ($provedor !== 'nacional') {
                return redirect()->back()->with(
                    'mensagem_sucesso',
                    'NFS-e municipal registrada localmente. Status atual: '
                        . ($fatura->status ?: 'N/D')
                        . '.'
                );
            }

            $config = (array) $configNota->getAttributes();
            $config['tpAmb'] = (int) ($configNota->ambiente ?? 2);

            $tools = NfseServiceFactory::criar(
                $provedor,
                $config,
                $this->certificado()
            );

            if (!$tools instanceof NfseNacionalTools) {
                throw new \RuntimeException(
                    'Emissor selecionado não suporta consulta pela SEFIN Nacional.'
                );
            }

            $resultado = $tools->consultar((string) $fatura->chave_nfse);

            $xml = $resultado['nfseXmlGZipB64'] ?? null;
            if ($xml) {
                $decoded = base64_decode((string) $xml, true);
                $xml = $decoded !== false ? @gzdecode($decoded) : null;
            }

            $fatura->update([
                'protocolo_nfse' => $tools->getProtocolo() ?: $fatura->protocolo_nfse,
                'xml_nfse' => $xml ?: $fatura->xml_nfse,
            ]);

            return redirect()->back()->with(
                'mensagem_sucesso',
                'Consulta da NFS-e concluída com sucesso.'
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao consultar NFS-e de medição.', [
                'medicao_id' => $id,
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'mensagem_erro',
                'Falha ao consultar NFS-e: ' . $e->getMessage()
            );
        }
    }

    public function cancelar(Request $request, $id)
    {
        try {
            $fatura = $this->fatura((int) $id);

            if (!$fatura->chave_nfse) {
                throw new \RuntimeException(
                    'Medição não possui chave NFS-e para cancelamento.'
                );
            }

            $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->firstOrFail();
            $config = (array) $configNota->getAttributes();
            $config['tpAmb'] = (int) ($configNota->ambiente ?? 2);
            $config['usuario_saatri'] = config('services.nfse_saatri.username');
            $config['senha_saatri'] = config('services.nfse_saatri.password');

            $tools = NfseServiceFactory::criar(
                $this->provedor((string) ($configNota->codMun ?? '')),
                $config,
                $this->certificado()
            );

            $tools->cancelar(
                (string) $fatura->chave_nfse,
                (string) $request->input(
                    'motivo',
                    'Cancelamento solicitado via ERP'
                )
            );

            $fatura->update(['status' => 'Cancelado']);

            return redirect()->back()->with(
                'mensagem_sucesso',
                'NFS-e cancelada com sucesso.'
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao cancelar NFS-e de medição.', [
                'medicao_id' => $id,
                'empresa_id' => $this->empresa_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'mensagem_erro',
                'Falha ao cancelar NFS-e: ' . $e->getMessage()
            );
        }
    }

    public function downloadXml($id)
    {
        $fatura = $this->fatura((int) $id);

        if (!$fatura->xml_nfse) {
            return redirect()->back()->with(
                'mensagem_erro',
                'XML da NFS-e não disponível para esta medição.'
            );
        }

        $nome = 'NFSe_' . ($fatura->chave_nfse ?: $fatura->id) . '.xml';

        return response((string) $fatura->xml_nfse, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nome . '"',
        ]);
    }

    public function imprimir($id)
    {
        $fatura = $this->fatura((int) $id);

        if (!$fatura->chave_nfse) {
            return redirect()->back()->with(
                'mensagem_erro',
                'NFS-e ainda não foi emitida para esta medição.'
            );
        }

        $configNota = ConfigNota::where(
            'empresa_id',
            $this->empresa_id
        )->first();

        return view(
            'contratos.medicoes.nfse_imprimir',
            compact('fatura', 'configNota')
        );
    }

    private function fatura(int $id): FaturaEngenharia
    {
        return FaturaEngenharia::where('empresa_id', $this->empresa_id)
            ->with([
                'cliente.cidade',
                'cidadePrestacao',
                'servico',
                'itens.servico',
            ])
            ->findOrFail($id);
    }

    private function certificado(): Certificate
    {
        $registro = DB::table('certificados')
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$registro || empty($registro->arquivo)) {
            throw new \RuntimeException(
                'Certificado digital A1 não encontrado para esta empresa.'
            );
        }

        return Certificate::readPfx(
            $registro->arquivo,
            (string) $registro->senha
        );
    }

    private function reservarNumeroDps(ConfigNota $configNota): array
    {
        return DB::transaction(function () use ($configNota) {
            $row = DB::table('config_notas')
                ->where('id', $configNota->id)
                ->where('empresa_id', $this->empresa_id)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                throw new \RuntimeException(
                    'Configuração fiscal não encontrada durante reserva da DPS.'
                );
            }

            $serie = (string) (
                $row->numero_serie_nfse
                ?? $row->serie_nfse
                ?? '1'
            );

            $numero = ((int) ($row->ultimo_numero_nfse ?? 0)) + 1;

            DB::table('config_notas')
                ->where('id', $row->id)
                ->update([
                    'ultimo_numero_nfse' => $numero,
                    'updated_at' => now(),
                ]);

            return [$serie, $numero];
        });
    }

    private function montarDps(
        FaturaEngenharia $fatura,
        $servico,
        ConfigNota $configNota,
        string $serie,
        int $numero
    ): array {
        $cliente = $fatura->cliente;

        $docTomador = preg_replace(
            '/\D+/',
            '',
            (string) (
                $cliente->cpf_cnpj
                ?? $cliente->cnpj
                ?? $cliente->cpf
                ?? ''
            )
        );

        if (!in_array(strlen($docTomador), [11, 14], true)) {
            throw new \RuntimeException(
                'CPF/CNPJ do tomador é inválido para emissão da NFS-e.'
            );
        }

        $codigoEmitente = preg_replace(
            '/\D+/',
            '',
            (string) ($configNota->codMun ?? '')
        );

        if (strlen($codigoEmitente) !== 7) {
            throw new \RuntimeException(
                'Código IBGE do município emitente não está configurado corretamente.'
            );
        }

        $codigoCliente = preg_replace(
            '/\D+/',
            '',
            (string) (
                optional($cliente->cidade)->codigo
                ?? $cliente->codMun
                ?? $codigoEmitente
            )
        );

        $codigoPrestacao = preg_replace(
            '/\D+/',
            '',
            (string) (
                optional($fatura->cidadePrestacao)->codigo
                ?? $codigoEmitente
            )
        );

        $codigoServico = preg_replace(
            '/\D+/',
            '',
            (string) (
                $servico->codigo_tributacao_nacional
                ?? $servico->codigo_servico
                ?? ''
            )
        );

        if ($codigoServico === '') {
            throw new \RuntimeException(
                'Código de tributação do serviço não está configurado.'
            );
        }

        $tomador = [
            strlen($docTomador) === 11 ? 'CPF' : 'CNPJ' => $docTomador,
            'xNome' => (string) (
                $cliente->razao_social
                ?? $cliente->nome
                ?? ''
            ),
            'ender' => [
                'xLgr' => (string) (
                    $cliente->logradouro
                    ?? $cliente->rua
                    ?? ''
                ),
                'nro' => (string) ($cliente->numero ?? 'S/N'),
                'xBairro' => (string) ($cliente->bairro ?? ''),
                'cMun' => $codigoCliente,
                'UF' => (string) (
                    $cliente->uf
                    ?? optional($cliente->cidade)->uf
                    ?? $configNota->UF
                    ?? 'BA'
                ),
                'CEP' => preg_replace(
                    '/\D+/',
                    '',
                    (string) ($cliente->cep ?? '')
                ),
            ],
        ];

        $cNbs = preg_replace(
            '/\D+/',
            '',
            (string) ($servico->codigo_nbs ?? '')
        );

        $cServ = [
            'cTribNac' => $codigoServico,
            'xDescServ' => (string) (
                $fatura->observacao
                ?? $servico->descricao_padrao
                ?? $servico->nome
                ?? 'PRESTAÇÃO DE SERVIÇO'
            ),
        ];

        if ($cNbs !== '') {
            $cServ['cNBS'] = $cNbs;
        }

        $dados = [
            'infDPS' => [
                'dhEmi' => date('Y-m-d\TH:i:sP'),
                'dCompet' => optional($fatura->data_faturamento)->format('Y-m-d')
                    ?: date('Y-m-d'),
                'tpEmit' => 1,
                'cLocEmi' => $codigoEmitente,
                'serie' => $serie,
                'nDPS' => $numero,
                'prest' => [
                    'CNPJ' => preg_replace(
                        '/\D+/',
                        '',
                        (string) $configNota->cnpj
                    ),
                    'regTrib' => [
                        'opSimpNac' => (int) (
                            $configNota->opcao_simples_nacional
                            ?? 1
                        ),
                        'regApTribSN' => (int) (
                            $configNota->regime_apuracao_sn
                            ?? 1
                        ),
                        'regEspTrib' => 0,
                    ],
                ],
                'toma' => $tomador,
                'serv' => [
                    'locPrest' => [
                        'cLocPrest' => $codigoPrestacao,
                    ],
                    'cServ' => $cServ,
                ],
                'valores' => [
                    'vServPrest' => [
                        'vServ' => number_format(
                            (float) $fatura->valor_total,
                            2,
                            '.',
                            ''
                        ),
                    ],
                    'trib' => [
                        'tribMun' => [
                            'tribISSQN' => 1,
                            'tpRetISSQN' => (bool) ($fatura->iss_retido ?? false)
                                ? 2
                                : 1,
                            'pAliq' => number_format(
                                (float) ($servico->aliquota_iss ?? 0),
                                2,
                                '.',
                                ''
                            ),
                        ],
                    ],
                ],
            ],
        ];

        if ($fatura->codigo_obra) {
            $dados['infDPS']['serv']['obra'] = [
                'cObra' => (string) $fatura->codigo_obra,
            ];
        }

        return $dados;
    }

    private function provedor(string $codigoIbge): string
    {
        return match (preg_replace('/\D+/', '', $codigoIbge)) {
            '2910057' => 'diasdavila',
            '2927408' => 'salvador',
            default => 'nacional',
        };
    }
}
