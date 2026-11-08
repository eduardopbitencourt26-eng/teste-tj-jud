<?php

namespace App\Controller;

use App\Entity\Livro;
use App\Form\LivroType;
use App\Service\LivroService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/livros', name: 'livro_')]
final class LivroController extends AbstractController
{
    public function __construct(private readonly LivroService $livroService)
    {
    }

    #[Route('', name: 'listar', methods: ['GET'])]
    public function listar(): Response
    {
        return $this->render('livro/listar.html.twig', [
            'livros' => $this->livroService->listar(),
        ]);
    }

    #[Route('/novo', name: 'novo', methods: ['GET', 'POST'])]
    public function novo(Request $request): Response
    {
        return $this->formulario($request, new Livro(), 'Novo livro', 'Livro cadastrado com sucesso.');
    }

    #[Route('/{codL:livro}/editar', name: 'editar', requirements: ['codL' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function editar(Request $request, Livro $livro): Response
    {
        return $this->formulario($request, $livro, 'Editar livro', 'Livro atualizado com sucesso.');
    }

    #[Route('/{codL:livro}/excluir', name: 'excluir', requirements: ['codL' => Requirement::DIGITS], methods: ['POST'])]
    public function excluir(Request $request, Livro $livro): Response
    {
        if (!$this->isCsrfTokenValid('excluir_livro_'.$livro->getCodL(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Não foi possível confirmar a exclusão. Recarregue a página e tente novamente.');

            return $this->redirectToRoute('livro_listar', status: Response::HTTP_SEE_OTHER);
        }

        $this->livroService->excluir($livro);
        $this->addFlash('success', 'Livro excluído com sucesso.');

        return $this->redirectToRoute('livro_listar', status: Response::HTTP_SEE_OTHER);
    }

    private function formulario(Request $request, Livro $livro, string $titulo, string $mensagemSucesso): Response
    {
        $form = $this->createForm(LivroType::class, $livro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->livroService->salvar($livro);
            $this->addFlash('success', $mensagemSucesso);

            return $this->redirectToRoute('livro_listar', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('livro/formulario.html.twig', [
            'form' => $form,
            'titulo' => $titulo,
        ]);
    }
}
