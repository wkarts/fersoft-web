<?php

namespace Tests\Unit;

use App\Models\VendaCaixa;
use App\Services\NFCeService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class NfceRecuperacaoIdentidadeFiscalTest extends TestCase
{
    private function chave(): string
    {
        $base = '29' . '2610' . '12345678000195' . '65'
            . '012' . '000000347' . '1' . '00041234';

        $soma = 0;
        $peso = 2;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $soma += (int) $base[$i] * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }

        $dv = 11 - ($soma % 11);
        return $base . ($dv >= 10 ? 0 : $dv);
    }

    private function xml(string $chave, array $overrides = []): string
    {
        $cnf = $overrides['cnf'] ?? '00041234';
        $numero = $overrides['numero'] ?? '347';
        $serie = $overrides['serie'] ?? '12';
        $modelo = $overrides['modelo'] ?? '65';
        $cnpj = $overrides['cnpj'] ?? '12345678000195';
        $data = $overrides['data'] ?? '2026-10-10T15:39:00-03:00';
        $assinatura = array_key_exists('assinatura', $overrides)
            ? $overrides['assinatura']
            : '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo/></Signature>';

        return '<NFe xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<infNFe versao="4.00" Id="NFe' . $chave . '"><ide>'
            . '<cUF>29</cUF><cNF>' . $cnf . '</cNF><mod>' . $modelo . '</mod>'
            . '<serie>' . $serie . '</serie><nNF>' . $numero . '</nNF>'
            . '<dhEmi>' . $data . '</dhEmi><tpEmis>1</tpEmis></ide>'
            . '<emit><CNPJ>' . $cnpj . '</CNPJ></emit></infNFe>'
            . $assinatura . '</NFe>';
    }

    private function extrair(string $xml, ?object $config = null): array
    {
        $service = (new ReflectionClass(NFCeService::class))->newInstanceWithoutConstructor();
        $metodo = new ReflectionMethod(NFCeService::class, 'identidadeNfceDeXmlAssinado');

        $venda = new VendaCaixa();
        $venda->empresa_id = 9;
        $venda->estado = 'REJEITADO';
        $venda->NFcNumero = 0;
        $venda->chave = '';

        return $metodo->invoke(
            $service, $venda,
            $config ?? (object)['cnpj' => '12.345.678/0001-95', 'cUF' => 29],
            $xml
        );
    }

    public function test_recupera_numero_e_chave_apenas_do_xml_assinado_original(): void
    {
        $chave = $this->chave();
        $xml = $this->xml($chave);
        $resultado = $this->extrair($xml);

        $this->assertSame($chave, $resultado['chave']);
        $this->assertSame(347, $resultado['numero']);
        $this->assertSame($xml, $resultado['signed_xml']);
    }

    public function test_sem_assinatura_nao_aceita_xml_como_prova_fiscal(): void
    {
        $this->expectException(\DomainException::class);
        $this->extrair($this->xml($this->chave(), ['assinatura' => '']));
    }

    public function test_numero_diferente_da_chave_nao_pode_ser_recuperado(): void
    {
        $this->expectException(\DomainException::class);
        $this->extrair($this->xml($this->chave(), ['numero' => '348']));
    }

    public function test_cnf_diferente_nao_pode_mudar_a_chave(): void
    {
        $this->expectException(\DomainException::class);
        $this->extrair($this->xml($this->chave(), ['cnf' => '00041235']));
    }

    public function test_cnpj_emitente_de_outra_empresa_eh_bloqueado(): void
    {
        $this->expectException(\DomainException::class);
        $this->extrair($this->xml($this->chave(), ['cnpj' => '00000000000199']));
    }

    public function test_modelo_diferente_de_65_eh_bloqueado(): void
    {
        $this->expectException(\DomainException::class);
        $this->extrair($this->xml($this->chave(), ['modelo' => '55']));
    }

    public function test_xml_em_mes_diferente_da_chave_eh_bloqueado(): void
    {
        $this->expectException(\DomainException::class);
        $this->extrair($this->xml($this->chave(), ['data' => '2026-11-01T10:00:00-03:00']));
    }

    public function test_chave_com_digito_verificador_errado_eh_bloqueada(): void
    {
        $chave = $this->chave();
        $outroDv = (string) (((int) substr($chave, -1) + 1) % 10);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Dígito verificador');
        $this->extrair($this->xml(substr($chave, 0, -1) . $outroDv));
    }

    public function test_edicao_da_venda_preserva_estado_numero_chave_e_xml(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/VendaCaixaController.php');

        $this->assertStringContainsString('$preservaIdentidadeFiscal = !$fromPrevenda', $controller);
        $this->assertStringContainsString("in_array((string)\$result->estado, ['REJEITADO', 'APROVADO', 'CANCELADO']", $controller);
        $this->assertStringContainsString('if (!$preservaIdentidadeFiscal) {', $controller);
        $this->assertStringContainsString("VendaCaixa::where('empresa_id', \$this->empresa_id)->findOrFail(", $controller);
    }

    public function test_consulta_na_sefaz_ocorre_depois_da_recuperacao_validada(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/NFCeController.php');
        $service = file_get_contents($root . '/app/Services/NFCeService.php');

        $inicio = strpos($controller, 'public function gerar(Request $request)');
        $fim = strpos($controller, 'public function transmitirContigencia(', $inicio);
        $codigo = substr($controller, $inicio, $fim - $inicio);

        $recuperacao = strpos($codigo, 'recuperarIdentidadeNfceRejeitada(');
        $consulta = strpos($codigo, 'consultarSituacaoDaChave(');
        $transmissao = strpos($codigo, 'transmitirNfce($signed,');
        $this->assertNotFalse($recuperacao);
        $this->assertNotFalse($consulta);
        $this->assertNotFalse($transmissao);
        $this->assertLessThan($consulta, $recuperacao);
        $this->assertLessThan($transmissao, $consulta);

        $this->assertStringContainsString("->where('document_type', 'NFCe')", $service);
        $this->assertStringContainsString("->where('document_id', \$venda->id)", $service);
        $this->assertStringContainsString('sem chave/número recuperáveis de um XML assinado', $service);
        $this->assertStringContainsString("'recuperacao_bloqueada'", $codigo);
        $this->assertStringContainsString('->lockForUpdate()', $codigo);
    }
}
