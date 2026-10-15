<?php

namespace Tests\Unit;

use App\Services\NFCeService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Regressão: não inventar data nova nem diagnóstico fiscal para 704/timeout.
 * Não invoca webservice externo nem escreve dados de produção.
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

    public function test_data_original_da_nota_nao_e_substituida_por_limiar_arbitrario(): void
    {
        $service = file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/NFCeService.php'
        );

        $this->assertStringContainsString(
            "\$stdIde->dhEmi = \$identidadeOriginal['dhEmi'] ?? FiscalDateHelper::nowXml()",
            $service
        );
        $this->assertStringNotContainsString('horarioRetransmissaoNFCe', $service);
        $this->assertStringNotContainsString('$diferenca >= 60', $service);
        $this->assertStringContainsString("hash_equals(\$identidadeOriginal['chave'], \$chaveGerada)", $service);
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

        $this->assertNotFalse($cpf);
        $this->assertNotFalse($consulta);
        $this->assertNotFalse($geracao);
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
        $this->assertStringContainsString("'cStat' => $codigoNota", $service);
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
