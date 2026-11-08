<?php

namespace App\Controller;

use App\Service\AssuntoService;
use App\Service\AutorService;
use App\Service\LivroService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InicioController extends AbstractController
{
    #[Route('/', name: 'inicio', methods: ['GET'])]
    public function inicio(LivroService $livroService, AutorService $autorService, AssuntoService $assuntoService): Response
    {
        return $this->render('inicio.html.twig', [
            'cadastros' => [
                ['titulo' => 'Livros', 'rota' => 'livro_listar', 'quantidade' => $livroService->contar()],
                ['titulo' => 'Autores', 'rota' => 'autor_listar', 'quantidade' => $autorService->contar()],
                ['titulo' => 'Assuntos', 'rota' => 'assunto_listar', 'quantidade' => $assuntoService->contar()],
            ],
        ]);
    }
}
