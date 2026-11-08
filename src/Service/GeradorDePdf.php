<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Renderiza um template Twig e converte o HTML em PDF com o Dompdf.
 */
final class GeradorDePdf
{
    public function __construct(
        private readonly Environment $twig,
        #[Autowire('%kernel.cache_dir%/dompdf')]
        private readonly string $diretorioDeCache,
    ) {
    }

    /**
     * @param array<string, mixed> $contexto
     */
    public function gerar(string $template, array $contexto): string
    {
        $opcoes = new Options();
        $opcoes->setDefaultFont('DejaVu Sans');
        $opcoes->setIsRemoteEnabled(false);
        $opcoes->setFontCache($this->diretorioDeCache);

        if (!is_dir($this->diretorioDeCache)) {
            mkdir($this->diretorioDeCache, 0o775, true);
        }

        $dompdf = new Dompdf($opcoes);
        $dompdf->loadHtml($this->twig->render($template, $contexto));
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
