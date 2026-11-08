<?php

namespace App\Controller;

use App\Service\GeradorDePdf;
use App\Service\RelatorioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/relatorio', name: 'relatorio_')]
final class RelatorioController extends AbstractController
{
    public function __construct(private readonly RelatorioService $relatorioService)
    {
    }

    #[Route('', name: 'tela', methods: ['GET'])]
    public function tela(): Response
    {
        return $this->render('relatorio/tela.html.twig', [
            'relatorio' => $this->relatorioService->gerar(),
        ]);
    }

    #[Route('/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(GeradorDePdf $geradorDePdf): Response
    {
        $relatorio = $this->relatorioService->gerar();
        $nomeDoArquivo = \sprintf('livros-por-autor-%s.pdf', $relatorio->geradoEm->format('Y-m-d'));

        return new Response($geradorDePdf->gerar('relatorio/pdf.html.twig', ['relatorio' => $relatorio]), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $nomeDoArquivo),
        ]);
    }
}
