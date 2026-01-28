<?php

namespace App\Services;

use NFePHP\DA\NFe\Danfe;

class CustomDanfe extends Danfe
{
    protected static $rodapeConfig = [];

    /**
     * Define as configurações do rodapé.
     */
    public static function setRodapeConfig(array $config)
    {
        self::$rodapeConfig = $config;
    }

    /**
     * Sobrescreve o método de rodapé.
     */
    protected function rodape($x)
    {
        $y = $this->maxH - 4;

        if ($this->orientacao === 'P') {
            $w = $this->wPrint;
        } else {
            $w = $this->wPrint - $this->wCanhoto;
            $x = $this->wCanhoto;
        }

        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'I'];

        // Obter textos configurados
        $rodapeEsquerda = self::$rodapeConfig['rodape_texto_esquerda'] ?? "Impresso em {DATA_HORA}";
        $rodapeDireita = self::$rodapeConfig['rodape_texto_direita'] ?? '';
        $rodapeSite = self::$rodapeConfig['rodape_texto_site'] ?? '';

        // Substituir {DATA_HORA} dinamicamente
        $dataHora = date('d/m/Y H:i:s');
        $rodapeEsquerda = str_replace('{DATA_HORA}', $dataHora, $rodapeEsquerda);

        // Inserir os textos no rodapé
        $this->pdf->textBox($x, $y, $w, 0, $rodapeEsquerda, $aFont, 'T', 'L', false);
        $this->pdf->textBox($x, $y, $w, 0, $rodapeDireita, $aFont, 'T', 'R', false);

        if ($rodapeSite) {
            $this->pdf->textBox($x, $y, $w, 0, $rodapeSite, $aFont, 'T', 'C', false);
        }
    }
}
