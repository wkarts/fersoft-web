<?php

namespace Tests\Unit;

use App\Services\NFCeService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class NfceRejeicao704TimeoutTest extends TestCase
{
    private function chave(): string
    {
        // Chave apenas de teste: cUF=29, AAMM=2610, mod=65, nNF=347.
        return '29261012345678000195650120000003471000412340';
    }

    private function horario(array $identidade, string $agora): string
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($service, 'horarioRetransmissaoNFCe');
        return $method->invoke($service, $identidade, $agora);
    }

    private function identidade(string $emissao): array
    {
        return ['chave' => $this->chave(), 'dhEmi' => $emissao];
    }

    private function consulta(string $xml): array
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
        return $service->consultarSituacaoDaChave($this->chave());
    }

    private function xmlConsulta(int $cStat, string $motivo, ?string $chaveProt = null): string
    {
        $prot = '';
        if ($chaveProt !== null) {
            $prot = '<protNFe versao="4.00"><infProt>'
                . '<tpAmb>2</tpAmb><verAplic>TESTE</verAplic>'
                . '<chNFe>' . $chaveProt . '</chNFe>'
                . '<dhRecbto>2026-10-15T10:00:00-03:00</dhRecbto>'
                . '<nProt>129260000000000</nProt>'
                . '<digVal>AA==</digVal>'
                . '<cStat>100</cStat><xMotivo>Autorizado o uso da NF-e</xMotivo>'
                . '</infProt></protNFe>';
        }

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<retConsSitNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            . '<tpAmb>2</tpAmb><verAplic>TESTE</verAplic>'
            . '<cStat>' . $cStat . '</cStat><xMotivo>' . $motivo . '</xMotivo>'
            . '<cUF>29</cUF><chNFe>' . $this->chave() . '</chNFe>'
            . $prot . '</retConsSitNFe>';
    }

    public function test_rejeicao_704_renova_apenas_hora_no_mes_da_chave(): void
    {
        $identidade = $this->identidade('2026-10-15T09:00:00-03:00');
        $this->assertSame(
            '2026-10-15T10:08:00-03:00',
            $this->horario($identidade, '2026-10-15T10:08:00-03:00')
        );
        $this->assertSame($this->chave(), $identidade['chave']);
    }

    public function test_emissao_recente_nao_e_alterada_sem_necessidade(): void
    {
        $this->assertSame(
            '2026-10-15T10:07:50-03:00',
            $this->horario(
                $this->identidade('2026-10-15T10:07:50-03:00'),
                '2026-10-15T10:08:20-03:00'
            )
        );
    }

    public function test_mudanca_de_dia_no_mes_preserva_identidade_fiscal(): void
    {
        $this->assertSame(
            '2026-10-15T00:01:00-03:00',
            $this->horario(
                $this->identidade('2026-10-14T23:55:00-03:00'),
                '2026-10-15T00:01:00-03:00'
            )
        );
    }

    public function test_virada_de_mes_nao_pode_criar_outra_chave_implicitamente(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('alteraria a chave de acesso');
        $this->horario(
            $this->identidade('2026-10-31T23:55:00-03:00'),
            '2026-11-01T00:06:00-03:00'
        );
    }

    public function test_consulta_sefaz_217_libera_avaliacao_para_reenvio(): void
    {
        $resultado = $this->consulta($this->xmlConsulta(217, 'NF-e não consta na base de dados'));
        $this->assertSame('nao_consta', $resultado['situacao']);
        $this->assertSame(217, $resultado['codigo']);
    }

    public function test_autorizacao_ocorrida_durante_timeout_impede_novo_envio(): void
    {
        $resultado = $this->consulta($this->xmlConsulta(100, 'Autorizado o uso da NF-e', $this->chave()));
        $this->assertSame('autorizada', $resultado['situacao']);
        $this->assertSame(100, $resultado['codigo']);
        $this->assertStringContainsString('<protNFe', $resultado['resposta']);
    }

    public function test_protocolo_de_outra_chave_nao_serve_para_consiliar(): void
    {
        $this->expectException(\DomainException::class);
        $this->consulta($this->xmlConsulta(100, 'Autorizado', str_repeat('9', 44)));
    }

    public function test_sefaz_fora_do_ar_nao_libera_retransmissao(): void
    {
        $resultado = $this->consulta($this->xmlConsulta(108, 'Serviço paralisado momentaneamente'));
        $this->assertSame('indeterminada', $resultado['situacao']);
        $this->assertSame(108, $resultado['codigo']);
    }

    public function test_controller_consulta_antes_de_gerar_nova_versao_do_xml(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/NFCeController.php');
        $service = file_get_contents($root . '/app/Services/NFCeService.php');
        $inicio = strpos($controller, 'public function gerar(Request $request)');
        $fim = strpos($controller, 'public function transmitirContigencia(', $inicio);
        $gerar = substr($controller, $inicio, $fim - $inicio);

        $this->assertNotFalse($inicio);
        $this->assertNotFalse($fim);
        $consulta = strpos($gerar, 'consultarSituacaoDaChave(');
        $criacao = strpos($gerar, 'gerarNFCe($vendaId)');
        $this->assertNotFalse($consulta);
        $this->assertNotFalse($criacao);
        $this->assertLessThan($criacao, $consulta);
        $this->assertStringContainsString("if ($" . "situacao['situacao'] === 'autorizada')", $gerar);
        $this->assertStringContainsString("if ($" . "situacao['situacao'] !== 'nao_consta')", $gerar);
        $this->assertStringContainsString("hash_equals($" . "identidadeOriginal['chave'], $" . "chaveGerada)", $service);
        $this->assertStringContainsString('horarioRetransmissaoNFCe(', $service);
        $this->assertStringContainsString('conciliarAutorizacaoConsultada(', $service);
        $this->assertStringContainsString('LIBXML_NONET', $service);
    }
}
