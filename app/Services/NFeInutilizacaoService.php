<?php

namespace App\Services;

use App\Models\NFeInutilizacao;
use App\Models\ConfigNota;
use App\Models\Filial;
use App\Models\Certificado;
use Illuminate\Support\Facades\Log;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use RuntimeException;

class NFeInutilizacaoService
{
    /**
     * Inutiliza faixa diretamente na SEFAZ via NFePHP
     * e registra o resultado (incluindo XML) na tabela nfe_inutilizacoes.
     *
     * Espera um array com:
     * - empresa_id, filial_id (nullable), usuario_id (nullable)
     * - modelo, serie, numero_inicial, numero_final, ano, justificativa, ambiente (opcional), origem
     */
    public function inutilizarFaixa(array $dados): NFeInutilizacao
    {
        $empresaId      = (int) $dados['empresa_id'];
        $filialId       = $dados['filial_id'] ?? null;
        $usuarioId      = $dados['usuario_id'] ?? null;

        $modelo         = $dados['modelo'] ?? '55';
        $serie          = (int) $dados['serie'];
        $numeroInicial  = (int) $dados['numero_inicial'];
        $numeroFinal    = (int) $dados['numero_final'];

        // Você pode manter 2 dígitos ou 4; aqui deixei compatível com o que você já usa.
        // Se não vier no payload, usa o ano atual (padrão NFePHP é 2 dígitos mesmo).
        $ano            = $dados['ano'] ?? date('y');

        $justificativa  = $dados['justificativa'];
        $origem         = $dados['origem'] ?? 'salto_numeracao';

        // 1) Resolve emitente (ConfigNota ou Filial)
        $emitente = $this->resolveEmitente($empresaId, $filialId);

        // 1.1) Normaliza ambiente com base no emitente (1 = produção, 2 = homologação)
        $tpAmbEmitente = (int) ($emitente->ambiente ?? 1);
        if (! in_array($tpAmbEmitente, [1, 2], true)) {
            $tpAmbEmitente = 1;
        }
        $ambienteTexto = $tpAmbEmitente === 1 ? 'producao' : 'homologacao';

        // 2) Monta config NFePHP + carrega certificado e instancia Tools
        $tools = $this->makeTools($emitente, $empresaId);

        // Garante que o modelo utilizado (55 / 65) seja o mesmo do payload
        $tools->model((int) $modelo);

        // Série usada na inutilização:
        $serieNFe = $serie;

        try {
            // 3) Chama SEFAZ para inutilizar a faixa
            $responseXml = $tools->sefazInutiliza($serieNFe, $numeroInicial, $numeroFinal, $justificativa);

            $stdCl = new Standardize($responseXml);
            $std   = $stdCl->toStd();
            $arr   = $stdCl->toArray();

        } catch (\Throwable $e) {
            Log::error('Erro técnico na inutilização de faixa de NFe', [
                'empresa_id' => $empresaId,
                'filial_id'  => $filialId,
                'dados'      => $dados,
                'exception'  => $e,
            ]);

            // Mesmo em caso de erro técnico, registra a intenção de inutilização
            $registro = NFeInutilizacao::create([
                'empresa_id'      => $empresaId,
                'filial_id'       => $filialId,
                'usuario_id'      => $usuarioId,
                'modelo'          => $modelo,
                'serie'           => $serie,
                'numero_inicial'  => $numeroInicial,
                'numero_final'    => $numeroFinal,
                'ano'             => $ano,
                'justificativa'   => $justificativa,
                'ambiente'        => $ambienteTexto,
                'origem'          => $origem,
                'status'          => 'erro',
                'protocolo'       => null,
                'mensagem'        => $e->getMessage(),
                'nfserver_id'     => null,
                'xml_solicitacao' => null,
                'xml_retorno'     => null,
                // novos campos
                'ignorar_gaps'    => false,
                'motivo_ignoracao'=> null,
            ]);

            throw new RuntimeException('Falha técnica ao comunicar com a SEFAZ para inutilização.');
        }

        // 4) Interpreta retorno da SEFAZ (mais robusto)
        $cStat     = null;
        $xMotivo   = null;
        $protocolo = null;

        // Preferência pelo objeto std
        if (isset($std->infInut)) {
            $cStat     = isset($std->infInut->cStat) ? (int) $std->infInut->cStat : null;
            $xMotivo   = $std->infInut->xMotivo ?? null;
            $protocolo = $std->infInut->nProt ?? null;

            // Fallback usando array, se necessário
        } elseif (isset($arr['infInut']) && is_array($arr['infInut'])) {
            $infInut   = $arr['infInut'];
            $cStat     = isset($infInut['cStat']) ? (int) $infInut['cStat'] : null;
            $xMotivo   = $infInut['xMotivo'] ?? null;
            $protocolo = $infInut['nProt'] ?? null;

            // Último fallback, caso o Standardize tenha "achatado" os campos
        } else {
            $cStat   = isset($arr['cStat']) ? (int) $arr['cStat'] : null;
            $xMotivo = $arr['xMotivo'] ?? null;
        }

        // 5) Mapeia cStat da inutilização para um status interno simples
        //
        //  - autorizada -> inutilização homologada (cStat 102)
        //  - pendente   -> problemas temporários de serviço (108, 109)
        //  - rejeitada  -> qualquer outra rejeição de regra de negócio (ex.: 256)
        //  - erro       -> sem cStat identificável (já tratamos também no catch)
        if ($cStat === 102) {
            $status = 'autorizada';
        } elseif (in_array($cStat, [108, 109], true)) {
            // Serviço da SEFAZ paralisado / indisponível
            $status = 'pendente';
        } elseif ($cStat !== null) {
            // Qualquer outro cStat retornado pela SEFAZ é tratado como rejeição
            // Inclui, por exemplo, 241/256 etc.
            $status = 'rejeitada';
        } else {
            $status = 'erro';
        }

        // 5.1) Lógica de ignorar gaps automaticamente para alguns cStat
        // Aqui você pode colocar todos os códigos que "não fazem sentido ficar vendo no relatório de gaps".
        // Exemplo clássico:
        // 256 - "Rejeicao: Uma NF-e da faixa ja esta inutilizada na Base de dados da SEFAZ"
        $ignorarGaps      = false;
        $motivoIgnoracao  = null;

        if (in_array($cStat, [256], true)) {
            $ignorarGaps = true;
            $motivoIgnoracao = 'Ignorado em gaps automaticamente: [' . $cStat . '] ' .
                ($xMotivo ?: 'Rejeição SEFAZ indicando faixa já inutilizada anteriormente.');
        }

        // XML da requisição (se NFePHP expõe via lastRequest)
        $xmlSolicitacao = property_exists($tools, 'lastRequest') ? $tools->lastRequest : null;
        $xmlRetorno     = $responseXml;

        $mensagem = $xMotivo;
        if ($cStat !== null) {
            $mensagem = '[' . $cStat . '] ' . $mensagem;
        }

        // 6) Persiste registro completo da inutilização
        $registro = NFeInutilizacao::create([
            'empresa_id'      => $empresaId,
            'filial_id'       => $filialId,
            'usuario_id'      => $usuarioId,
            'modelo'          => $modelo,
            'serie'           => $serie,
            'numero_inicial'  => $numeroInicial,
            'numero_final'    => $numeroFinal,
            'ano'             => $ano,
            'justificativa'   => $justificativa,
            'ambiente'        => $ambienteTexto,
            'origem'          => $origem,
            'status'          => $status,
            'protocolo'       => $protocolo,
            'mensagem'        => $mensagem,
            'nfserver_id'     => null,
            'xml_solicitacao' => $xmlSolicitacao,
            'xml_retorno'     => $xmlRetorno,
            // novos campos
            'ignorar_gaps'    => $ignorarGaps,
            'motivo_ignoracao'=> $motivoIgnoracao,
        ]);

        // 7) Guarda também o XML em disco (acervo), seguindo padrão dos outros XMLs
        $this->salvarXmlEmDisco(
            $empresaId,
            $modelo,
            $serie,
            $numeroInicial,
            $numeroFinal,
            $xmlRetorno
        );

        // Se não autorizou, lança exceção para o controller exibir mensagem amigável
        if ($cStat !== 102) {
            throw new RuntimeException($mensagem ?: 'Inutilização rejeitada pela SEFAZ.');
        }

        return $registro;
    }

    /**
     * Retorna o emitente a ser usado:
     * - Se tiver filial_id, usa Filial
     * - Caso contrário, usa ConfigNota (matriz)
     */
    protected function resolveEmitente(int $empresaId, ?int $filialId)
    {
        if (!empty($filialId)) {
            return Filial::where('empresa_id', $empresaId)
                ->where('id', $filialId)
                ->firstOrFail();
        }

        return ConfigNota::where('empresa_id', $empresaId)->firstOrFail();
    }

    /**
     * Monta a configuração NFePHP + certificado e devolve uma instância de Tools
     * semelhante ao que você já usa no NFService.
     */
    protected function makeTools($emitente, int $empresaId): Tools
    {
        // Ambiente NFePHP (1 = produção, 2 = homologação)
        $tpAmb = (int) ($emitente->ambiente ?? 1);
        if (! in_array($tpAmb, [1, 2], true)) {
            $tpAmb = 1;
        }

        $config = [
            'atualizacao' => date('Y-m-d h:i:s'),
            'tpAmb'       => $tpAmb,
            'razaosocial' => $emitente->razao_social ?? $emitente->nome_fantasia ?? 'Empresa',
            'siglaUF'     => $emitente->UF ?? $emitente->uf ?? '',
            'cnpj'        => preg_replace('/\D/', '', $emitente->cnpj ?? ''),
            'schemes'     => 'PL_009_V4',
            'versao'      => '4.00',
            'tokenIBPT'   => $emitente->token_ibpt ?? '',
            'CSC'         => $emitente->csc ?? '',
            'CSCid'       => $emitente->csc_id ?? '',
            'aProxyConf'  => [
                'proxyIp'   => '',
                'proxyPort' => '',
                'proxyUser' => '',
                'proxyPass' => '',
            ],
        ];

        // Certificado: se filial tem seu próprio PFX, usa; senão usa Certificado da empresa
        if ($emitente instanceof Filial && !empty($emitente->arquivo_certificado)) {
            $certPath = $emitente->arquivo_certificado;
            $certPass = $emitente->senha_certificado;
        } else {
            $cert = Certificado::where('empresa_id', $empresaId)->firstOrFail();
            $certPath = $cert->arquivo;
            $certPass = $cert->senha;
        }

        $certificate = Certificate::readPfx($certPath, $certPass);

        $tools = new Tools(json_encode($config), $certificate);
        // Modelo padrão 55; será sobrescrito em inutilizarFaixa() se necessário
        $tools->model(55);

        return $tools;
    }

    /**
     * Guarda o XML de inutilização em pasta própria.
     */
    protected function salvarXmlEmDisco(
        int $empresaId,
        string $modelo,
        int $serie,
        int $numeroInicial,
        int $numeroFinal,
        ?string $xml
    ): void {
        if (empty($xml)) {
            return;
        }

        try {
            $dir = public_path('xml_nfe_inutilizada');

            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $fileName = sprintf(
                'inutilizacao_%s_%s_S%03d_%09d-%09d.xml',
                $empresaId,
                $modelo,
                $serie,
                $numeroInicial,
                $numeroFinal
            );

            @safe_file_put_contents($dir . DIRECTORY_SEPARATOR . $fileName, $xml);
        } catch (\Throwable $e) {
            Log::warning('Falha ao salvar XML de inutilização em disco', [
                'empresa_id' => $empresaId,
                'exception'  => $e,
            ]);
        }
    }
}
