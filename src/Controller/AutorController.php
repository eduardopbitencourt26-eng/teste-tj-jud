<?php

namespace App\Controller;

use App\Entity\Autor;
use App\Exception\AutorPossuiLivrosException;
use App\Form\AutorType;
use App\Service\AutorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/autores', name: 'autor_')]
final class AutorController extends AbstractController
{
    public function __construct(private readonly AutorService $autorService)
    {
    }

    #[Route('', name: 'listar', methods: ['GET'])]
    public function listar(): Response
    {
        return $this->render('autor/listar.html.twig', [
            'autores' => $this->autorService->listar(),
        ]);
    }

    #[Route('/novo', name: 'novo', methods: ['GET', 'POST'])]
    public function novo(Request $request): Response
    {
        return $this->formulario($request, new Autor(), 'Novo autor', 'Autor cadastrado com sucesso.');
    }

    #[Route('/{codAu:autor}/editar', name: 'editar', requirements: ['codAu' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function editar(Request $request, Autor $autor): Response
    {
        return $this->formulario($request, $autor, 'Editar autor', 'Autor atualizado com sucesso.');
    }

    #[Route('/{codAu:autor}/excluir', name: 'excluir', requirements: ['codAu' => Requirement::DIGITS], methods: ['POST'])]
    public function excluir(Request $request, Autor $autor): Response
    {
        if (!$this->isCsrfTokenValid('excluir_autor_'.$autor->getCodAu(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Não foi possível confirmar a exclusão. Recarregue a página e tente novamente.');

            return $this->redirectToRoute('autor_listar', status: Response::HTTP_SEE_OTHER);
        }

        try {
            $this->autorService->excluir($autor);
            $this->addFlash('success', 'Autor excluído com sucesso.');
        } catch (AutorPossuiLivrosException $excecao) {
            $this->addFlash('danger', $excecao->getMessage());
        }

        return $this->redirectToRoute('autor_listar', status: Response::HTTP_SEE_OTHER);
    }

    private function formulario(Request $request, Autor $autor, string $titulo, string $mensagemSucesso): Response
    {
        $form = $this->createForm(AutorType::class, $autor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->autorService->salvar($autor);
            $this->addFlash('success', $mensagemSucesso);

            return $this->redirectToRoute('autor_listar', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('autor/formulario.html.twig', [
            'form' => $form,
            'titulo' => $titulo,
        ]);
    }
}
