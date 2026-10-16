<?php

namespace Tests\Unit;

use App\Models\VendaCaixa;
use App\Services\NFCeService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class NfceRetransmissaoIdentidadeFiscalTest extends TestCase
{
    private function chaveOriginal(): string
    {
        // UF(2) + AAMM(4) + CNPJ(14) + mod(2) + serie(3) +
        // nNF(9) + tpEmis(1) + cNF(8) + cDV(1).
        $base = '29' . '2610' . '12345678000195' . '65'
            . '012' . '000000347' . '1' . '00041234';

        $peso = 2;
        $soma = 0;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $soma += ((int) $base[$i]) * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }
        $dv = 11 - ($soma % 11);
        return $base . ($dv >= 10 ? 0 : $dv);
    }

    private function vendaRejeitada(?string $xml = null): VendaCaixa
    {
        $chave = $this->chaveOriginal();

        $venda = new VendaCaixa();
        $venda->chave = $chave;
        $venda->NFcNumero = 347;
        $venda->estado = 'REJEITADO';
        $venda->signed_xml = $xml ?? $this->xmlAssinado($chave);

        return $venda;
    }

    private function xmlAssinado(string $chave, string $dhEmi = '2026-10-10T15:39:00-03:00'): string
    {
        return '<NFe xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<infNFe versao="4.00" Id="NFe' . $chave . '">'
            . '<ide><dhEmi>' . $dhEmi . '</dhEmi><tpEmis>1</tpEmis></ide>'
            . '</infNFe></NFe>';
    }

    private function identificar(VendaCaixa $venda, ?object $config = null): array
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(NFCeService::class, 'identidadeOriginalNFCe');

        return $method->invoke($service, $venda, $config ?? (object) [
            'cnpj' => '12.345.678/0001-95',
            'cUF' => 29,
        ]);
    }

    public function test_preserva_chave_numero_serie_cnf_com_zeros_e_data_original(): void
    {
        $identidade = $this->identificar($this->vendaRejeitada());

        $this->assertSame($this->chaveOriginal(), $identidade['chave']);
        $this->assertSame(347, $identidade['nNF']);
        $this->assertSame(12, $identidade['serie']);
        $this->assertSame('00041234', $identidade['cNF']);
        $this->assertSame('2026-10-10T15:39:00-03:00', $identidade['dhEmi']);
        $this->assertSame(29, $identidade['cUF']);
        $this->assertSame(1, $identidade['tpEmis']);
    }

    public function test_bloqueia_rejeitada_sem_numero_coerente_com_a_chave(): void
    {
        $venda = $this->vendaRejeitada();
        $venda->NFcNumero = 348;

        $this->expectException(\DomainException::class);
        $this->identificar($venda);
    }

    public function test_bloqueia_rejeitada_sem_chave_original(): void
    {
        $venda = $this->vendaRejeitada();
        $venda->chave = '';

        $this->expectException(\DomainException::class);
        $this->identificar($venda);
    }

    public function test_bloqueia_rejeitada_com_xml_assinado_de_outra_chave(): void
    {
        $venda = $this->vendaRejeitada($this->xmlAssinado(str_repeat('9', 44)));

        $this->expectException(\DomainException::class);
        $this->identificar($venda);
    }

    public function test_bloqueia_rejeitada_quando_falta_xml_original(): void
    {
        $venda = $this->vendaRejeitada();
        $venda->signed_xml = null;

        $this->expectException(\DomainException::class);
        $this->identificar($venda);
    }

    public function test_bloqueia_data_de_emissao_que_mudaria_ano_mes_da_chave(): void
    {
        $venda = $this->vendaRejeitada($this->xmlAssinado(
            $this->chaveOriginal(),
            '2026-11-10T15:39:00-03:00'
        ));

        $this->expectException(\DomainException::class);
        $this->identificar($venda);
    }

    public function test_bloqueia_emitente_que_nao_corresponde_ao_cnpj_da_chave(): void
    {
        $this->expectException(\DomainException::class);
        $this->identificar($this->vendaRejeitada(), (object) [
            'cnpj' => '00.000.000/0001-00',
            'cUF' => 29,
        ]);
    }

    public function test_servico_e_controller_nao_recalculam_numero_chave_ou_contador_na_retransmissao(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root . '/app/Services/NFCeService.php');
        $controller = file_get_contents($root . '/app/Http/Controllers/NFCeController.php');

        $this->assertStringContainsString(
            "\$stdIde->cNF = \$identidadeOriginal['cNF'] ?? rand(",
            $service
        );
        $this->assertStringContainsString(
            "\$stdIde->nNF = \$identidadeOriginal['nNF'] ?? \$this->reservarNumeroNFCe(",
            $service
        );
        $this->assertStringContainsString(
            "\$stdIde->dhEmi = \$dhEmiRetransmissao ?? \$identidadeOriginal['dhEmi'] ?? FiscalDateHelper::nowXml()",
            $service
        );
        $this->assertStringContainsString(
            'hash_equals($identidadeOriginal[\'chave\'], $chaveGerada)',
            $service
        );
        $this->assertStringContainsString('if (!$retransmitindo) {', $controller);
        $this->assertStringContainsString('if ($retransmitindo', $controller);
        $this->assertStringContainsString('&& (int)$config->ultimo_numero_nfce < (int)$nfce[', $controller);
        $this->assertStringNotContainsString('NFcNumero = $config->ultimo_numero_nfce+1', $service);

        // A reserva e o incremento existem somente para a primeira emissão.
        $this->assertStringContainsString("function reservarNumeroNFCe(", $service);
    }
}
