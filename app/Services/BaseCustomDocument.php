<?php

namespace App\Services;

use ReflectionClass;

class BaseCustomDocument
{
    protected static $rodapeConfig = [];

    /**
     * Define as configurações globais do rodapé.
     */
    public static function setRodapeConfig(array $config)
    {
        self::$rodapeConfig = $config;
    }

    /**
     * Sobrescreve o método rodape dinamicamente.
     */
    protected function rodape($x, $y = null)
    {
        $y = $y ?? $this->maxH - 4;
        $w = $this->orientacao === 'P' ? $this->wPrint : $this->wPrint - $this->wCanhoto;
        $x = $this->orientacao === 'P' ? $x : $this->wCanhoto;

        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => 'I'];

        // Configurações do rodapé
        $rodapeEsquerda = self::$rodapeConfig['dinamic_footer_left'] ?? "Impresso em {DATA_HORA}";
        $rodapeDireita = self::$rodapeConfig['dinamic_footer_right'] ?? "Desenvolvido por WWSoftware's®";
        $siteRodape = self::$rodapeConfig['dinamic_footer_right_site'] ?? null;

        // Substituir data/hora dinamicamente
        $dataHora = date('d/m/Y H:i:s');
        $rodapeEsquerda = str_replace('{DATA_HORA}', $dataHora, $rodapeEsquerda);

        // Adicionar textos ao rodapé
        $this->pdf->textBox($x, $y, $w, 0, $rodapeEsquerda, $aFont, 'T', 'L', false);
        $this->pdf->textBox($x, $y, $w, 0, $rodapeDireita, $aFont, 'T', 'R', false);

        if ($siteRodape) {
            $this->pdf->textBox($x, $y, $w, 0, $siteRodape, $aFont, 'T', 'R', 0, $siteRodape);
        }
    }

    /**
     * Sobrescreve dinamicamente o rodapé customizado para MDFe.
     */
    protected function footerMDFe($x, $y = null)
    {
        $this->rodape($x, $y);
    }

    /**
     * Sobrescreve dinamicamente o rodapé customizado para eventos.
     */
    protected function footer($x, $y = null)
    {
        $y = $y ?? $this->maxH - 4;
        $w = $this->wPrint;

        $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'I'];

        // Configurações específicas para eventos
        $evento110110 = self::$rodapeConfig['mensagem_evento_110110'] ?? null;
        $evento110111 = self::$rodapeConfig['mensagem_evento_110111'] ?? null;

        $texto = '';
        if ($this->tpEvento === '110110' && $evento110110) {
            $texto = $evento110110;
        } elseif ($this->tpEvento === '110111' && $evento110111) {
            $texto = $evento110111;
        }

        if ($texto) {
            $this->pdf->textBox($x, $y, $w, 20, $texto, $aFont, 'T', 'C', 0, '', false);
        }

        // Adicionar data/hora e powered
        $this->rodape($x, $y);
    }

    /**
     * Modifica o método monta.
     */
    public function monta($logo = null)
    {
        if ($logo) {
            $this->logomarca = $this->adjustImage($logo, true);
        }
        $this->rodape(0, $this->maxH - 10);
    }

}
