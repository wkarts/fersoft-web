<?php

namespace Tests\Unit;

use App\Services\NFCeService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Regressão: dhEmi só muda após 704, consulta 217 e confirmação explícita.
 * Os testes de diagnóstico não invocam webservice nem escrevem em produção.
 */
class NfceRejeicao704TimeoutTest extends TestCase
{
    private function chave(): string
    {
        // Chave ilustrativa: UF=29, AAMM=2610, mod=65, número=347.
        return '29261012345678000195650120000003471000412340';
    }

    private function consultar(string $xml): array
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();

        $tools = new class($xml) {
            public function __construct(private string $xml) {}
            public function sefazConsultaChave(string $chave): string
            {
                return $this->xml;
            }
        };

        (new ReflectionProperty(NFCeService::class, 'tools'))->setValue($service, $tools);
        return (new ReflectionMethod(NFCeService::class, 'consultarSituacaoDaChave'))
            ->invoke($service, $this->chave());
    }

    private function xmlConsulta(
        int $cStat,
        string $motivo,
        ?string $chaveProtocolo = null,
        int $cStatProtocolo = 100
    ): string {
        $protocolo = '';
        if ($chaveProtocolo !== null) {
            $protocolo = '<protNFe versao="4.00"><infProt>'
                . '<tpAmb>2</tpAmb><verAplic>TESTE</verAplic>'
                . '<chNFe>' . $chaveProtocolo . '</chNFe>'
                . '<dhRecbto>2026-10-15T10:00:00-03:00</dhRecbto>'
                . '<nProt>129260000000000</nProt>'
                . '<digVal>AA==</digVal>'
                . '<cStat>' . $cStatProtocolo . '</cStat><xMotivo>' . $motivo . '</xMotivo>'
                . '</infProt></protNFe>';
        }

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<retConsSitNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>2</tpAmb><verAplic>TESTE</verAplic>'
            . '<cStat>' . $cStat . '</cStat><xMotivo>' . $motivo . '</xMotivo>'
            . '<cUF>29</cUF><chNFe>' . $this->chave() . '</chNFe>'
            . $protocolo . '</retConsSitNFe>';
    }

    public function test_dh_emi_original_so_muda_apos_704_confirmado_e_consulta_217(): void
    {
        $service = file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/NFCeService.php'
        );

        $this->assertStringContainsString('public function prepararReassinaturaNfce704', $service);
        $this->assertStringContainsString('private function substituirDhEmiNoXmlAssinado704', $service);
        $this->assertStringContainsString('public function validarXmlReassinadoNfce704', $service);
        $this->assertStringContainsString(
            "\$stdIde->dhEmi = \$identidadeOriginal['dhEmi'] ?? FiscalDateHelper::nowXml();",
            $service
        );
        $this->assertStringContainsString('dadosCorrecaoDhEmiNfce704', $service);
        $this->assertStringContainsString("'retorno_codigo'", $service);
        $this->assertStringContainsString("tpEmis'] !== 1", $service);
        $this->assertStringContainsString("hash_equals(\$identidadeOriginal['chave'], \$chaveGerada)", $service);

        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/NFCeController.php'
        );
        $this->assertStringContainsString("NFCE_704_CONFIRMAR_DHEMI", $controller);
        $this->assertStringContainsString('prepararReassinaturaNfce704', $controller);
        $this->assertStringContainsString("consulta_sefaz_cStat' => 217", $controller);
        $this->assertStringContainsString("'dhSaiEnt' => \$dhSaiEnt !== '' ? \$dhSaiEnt : null", $service);
        $this->assertStringContainsString("\$stdIde->dhSaiEnt = \$identidadeOriginal['dhSaiEnt']", $service);
    }

    public function test_reassinatura_reutiliza_xml_original_e_altera_somente_dhEmi(): void
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $chave = $this->chave();
        $dhEmiOriginal = '2026-10-15T09:07:37-03:00';
        $dhEmiNovo = '2026-10-16T01:20:49-03:00';
        $xmlOriginal = '<NFe xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<infNFe versao="4.00" Id="NFe' . $chave . '"><ide>'
            . '<dhEmi>' . $dhEmiOriginal . '</dhEmi>'
            . '<dhSaiEnt>2026-10-15T09:10:00-03:00</dhSaiEnt>'
            . '<tpEmis>1</tpEmis></ide><det nItem="1"><prod><xProd>Produto A</xProd></prod></det>'
            . '</infNFe><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo/>'
            . '<SignatureValue>AA==</SignatureValue></Signature></NFe>';

        $transform = (new ReflectionMethod(NFCeService::class, 'substituirDhEmiNoXmlAssinado704'))
            ->invoke($service, $xmlOriginal, $chave, $dhEmiOriginal, $dhEmiNovo);

        $domOriginal = new \DOMDocument('1.0', 'UTF-8');
        $this->assertTrue(@$domOriginal->loadXML($xmlOriginal, LIBXML_NONET));
        $xpathOriginal = new \DOMXPath($domOriginal);
        $xpathOriginal->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $this->assertSame($dhEmiOriginal, $xpathOriginal->evaluate('string(//nfe:ide/nfe:dhEmi)'));

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $this->assertTrue(@$dom->loadXML($transform['xml'], LIBXML_NONET));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $this->assertSame($dhEmiNovo, $xpath->evaluate('string(//nfe:ide/nfe:dhEmi)'));
        $this->assertSame('2026-10-15T09:10:00-03:00', $xpath->evaluate('string(//nfe:ide/nfe:dhSaiEnt)'));
        $this->assertSame('Produto A', $xpath->evaluate('string(//nfe:prod/nfe:xProd)'));
        $this->assertSame(0, $dom->getElementsByTagNameNS(
            'http://www.w3.org/2000/09/xmldsig#',
            'Signature'
        )->length);

        $dom->documentElement->appendChild(
            $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')
        );
        $xmlAssinado = $dom->saveXML();
        $service->validarXmlReassinadoNfce704([
            'chave' => $chave,
            'dhEmi' => $dhEmiNovo,
            'tpEmis' => 1,
            'fingerprint_infNFe_sem_dhEmi' => $transform['fingerprint_infNFe_sem_dhEmi'],
        ], $xmlAssinado);
    }

    public function test_reassinatura_bloqueia_dhEmi_que_altera_mes_da_chave(): void
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $this->expectException(\\DomainException::class);
        (new ReflectionMethod(NFCeService::class, 'substituirDhEmiNoXmlAssinado704'))
            ->invoke(
                $service,
                '<xml/>',
                $this->chave(),
                '2026-10-15T09:07:37-03:00',
                '2026-11-01T00:00:00-03:00'
            );
    }

    public function test_diagnostico_temporal_separa_emissao_saida_e_tipo_de_emissao(): void
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $xml = '<NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe><ide>'
            . '<dhEmi>2026-10-15T09:07:37-03:00</dhEmi>'
            . '<dhSaiEnt>2026-10-15T14:11:52-03:00</dhSaiEnt>'
            . '<tpEmis>1</tpEmis><dhCont></dhCont><xJust></xJust>'
            . '</ide></infNFe></NFe>';

        $dados = (new ReflectionMethod(NFCeService::class, 'diagnosticoTemporalXmlNfce'))
            ->invoke($service, $xml);

        $this->assertSame('2026-10-15T09:07:37-03:00', $dados['dhEmi_xml']);
        $this->assertSame('2026-10-15T14:11:52-03:00', $dados['dhSaiEnt_xml']);
        $this->assertSame(1, $dados['tpEmis_xml']);
        $this->assertNull($dados['dhCont_xml']);
        $this->assertFalse($dados['xJust_contingencia_informado']);
        $this->assertTrue($dados['xml_temporal_legivel']);
    }

    public function test_calculo_temporal_considera_os_offsets_do_xml(): void
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();

        $diferenca = (new ReflectionMethod(NFCeService::class, 'diferencaTemporalEmSegundos'))
            ->invoke(
                $service,
                '2026-10-15T09:07:37-03:00',
                '2026-10-15T14:11:52-03:00'
            );

        $this->assertSame(18255, $diferenca);
    }

    public function test_feedback_704_explica_emissao_e_transmissao_separadamente(): void
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $diagnostico = [
            'dhEmi_xml' => '2026-10-15T09:07:37-03:00',
            'dhSaiEnt_xml' => '2026-10-15T14:11:52-03:00',
            'tpEmis_xml' => 1,
            'venda_created_at_db' => '2026-10-15 09:07:30',
            'venda_data_emissao_db' => null,
            'timezone_aplicacao' => 'America/Bahia',
            'transmissao_iniciada_em' => '2026-10-15T14:11:53-03:00',
            'dhRecbto_sefaz' => '2026-10-15T14:11:54-03:00',
        ];

        $mensagem = (new ReflectionMethod(NFCeService::class, 'mensagemRejeicao704'))
            ->invoke($service, 'Rejeicao: NFC-e com Data-Hora de emissao atrasada', $diagnostico);

        $this->assertStringContainsString('Emissão fiscal do XML (dhEmi): 2026-10-15T09:07:37-03:00', $mensagem);
        $this->assertStringContainsString('Início do envio pelo sistema: 2026-10-15T14:11:53-03:00', $mensagem);
        $this->assertStringContainsString('Recebimento informado pela SEFAZ (dhRecbto): 2026-10-15T14:11:54-03:00', $mensagem);
        $this->assertStringContainsString('Tipo de emissão no XML (tpEmis): 1 (normal)', $mensagem);
        $this->assertStringContainsString('created_at bruto no banco; app.timezone=America/Bahia): 2026-10-15 09:07:30', $mensagem);
        $this->assertStringContainsString('Campo venda.data_emissao no banco: NULL/não preenchido', $mensagem);
        $this->assertStringContainsString('são horários distintos', $mensagem);
        $this->assertStringContainsString('Registro da venda', $mensagem);
    }

    public function test_704_nao_usa_inicio_envio_como_recebimento_sefaz(): void
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $diagnostico = [
            'dhEmi_xml' => '2026-10-15T09:07:37-03:00',
            'transmissao_iniciada_em' => '2026-10-15T14:11:53-03:00',
        ];

        $mensagem = (new ReflectionMethod(NFCeService::class, 'mensagemRejeicao704'))
            ->invoke($service, 'Rejeicao: NFC-e com Data-Hora de emissao atrasada', $diagnostico);

        $this->assertStringContainsString('dhRecbto não veio no protocolo', $mensagem);
        $this->assertStringNotContainsString('Diferença de dhEmi até dhRecbto', $mensagem);
    }

    public function test_consulta_217_informa_nao_consta_sem_inventar_rejeicao(): void
    {
        $dados = $this->consultar(
            $this->xmlConsulta(217, 'NF-e não consta na base de dados')
        );

        $this->assertSame('nao_consta', $dados['situacao']);
        $this->assertSame(217, $dados['codigo']);
    }

    public function test_autorizacao_100_confirmada_na_consulta_eh_diferente_de_timeout(): void
    {
        $dados = $this->consultar(
            $this->xmlConsulta(100, 'Autorizado o uso da NFC-e', $this->chave())
        );

        $this->assertSame('autorizada', $dados['situacao']);
        $this->assertSame(100, $dados['codigo']);
    }

    public function test_autorizacao_fora_do_prazo_150_eh_tratada_como_autorizacao(): void
    {
        $dados = $this->consultar(
            $this->xmlConsulta(150, 'Autorizado fora do prazo', $this->chave(), 150)
        );

        $this->assertSame('autorizada', $dados['situacao']);
        $this->assertSame(150, $dados['codigo']);
    }

    public function test_protocolo_de_outra_nota_nao_pode_ser_conciliado(): void
    {
        $this->expectException(\DomainException::class);
        $this->consultar(
            $this->xmlConsulta(100, 'Autorizado', str_repeat('9', 44))
        );
    }

    public function test_indisponibilidade_da_sefaz_nao_eh_rejeicao_confirmada(): void
    {
        $dados = $this->consultar(
            $this->xmlConsulta(108, 'Serviço paralisado momentaneamente')
        );

        $this->assertSame('indeterminada', $dados['situacao']);
        $this->assertSame(108, $dados['codigo']);
    }

    public function test_cpf_eh_persistido_antes_da_consulta_e_da_geracao_do_xml(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/NFCeController.php'
        );

        $inicio = strpos($controller, 'public function gerar(Request $request)');
        $fim = strpos($controller, 'public function transmitirContigencia(', $inicio);
        $metodo = substr($controller, $inicio, $fim - $inicio);

        $cpf = strpos($metodo, '$venda->cpf = $request->doc');
        $consulta = strpos($metodo, 'consultarSituacaoDaChave(');
        $geracao = strpos($metodo, 'gerarNFCe($vendaId)');
        $reassinaturaOriginal = strpos($metodo, 'prepararReassinaturaNfce704($venda, $config,');

        $this->assertNotFalse($cpf);
        $this->assertNotFalse($consulta);
        $this->assertNotFalse($geracao);
        $this->assertNotFalse($reassinaturaOriginal);
        $this->assertLessThan($consulta, $cpf);
        $this->assertLessThan($geracao, $cpf);
        $this->assertSame(1, substr_count($metodo, '$venda->cpf = $request->doc'));
    }

    public function test_fluxo_registra_diagnosticos_internos_e_no_laravel(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/NFCeController.php');
        $service = file_get_contents($root . '/app/Services/NFCeService.php');

        $this->assertStringContainsString('new LogService(', $controller);
        $this->assertStringContainsString("->registrar('nfce_' . \$etapa", $controller);
        $this->assertStringContainsString("Log::log(\$nivel, 'NFC-e: ' . \$etapa", $controller);
        $this->assertStringContainsString("'consulta_falhou'", $controller);
        $this->assertStringContainsString("'geracao_recusada'", $controller);
        $this->assertStringContainsString("'transmissao_resultado'", $controller);

        $this->assertStringContainsString('new EmissionLogger(', $service);
        $this->assertStringContainsString("'document_type' => 'NFCe'", $service);
        $this->assertStringContainsString("'NFC-e: SEFAZ retornou código de não autorização.'", $service);
        $this->assertStringContainsString("Log::error('NFC-e: rejeição de documento informada pela SEFAZ.'", $service);
        $this->assertStringContainsString("'cStat' => \$codigoNota", $service);
        $this->assertStringContainsString("Log::error('NFC-e: falha técnica/comunicação SOAP-SSL.'", $service);
        $this->assertStringContainsString("'retorno_codigo' => \$codigo", $service);
        $this->assertStringContainsString("'retorno_mensagem' => \$mensagem", $service);
        $this->assertStringContainsString('tipoUltimoResultadoTransmissao()', $service);
    }

    public function test_assinatura_e_chave_sao_preservadas_antes_do_envio_soap(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/NFCeController.php'
        );

        $inicio = strpos($controller, 'public function gerar(Request $request)');
        $fim = strpos($controller, 'public function transmitirContigencia(', $inicio);
        $metodo = substr($controller, $inicio, $fim - $inicio);

        $persistir = strpos($metodo, '$venda->signed_xml = $signed;');
        $transmitir = strpos($metodo, 'transmitirNfce($signed,');
        $this->assertNotFalse($persistir);
        $this->assertNotFalse($transmitir);
        $this->assertLessThan($transmitir, $persistir);
        $this->assertStringContainsString("hash_equals((string) \$venda->chave, (string) \$nfce['chave'])", $metodo);
        $this->assertStringContainsString("'sha256_xml_assinado'", $metodo);
    }
}
