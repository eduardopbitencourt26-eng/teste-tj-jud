<?php

namespace App\Controller;

use App\Entity\Assunto;
use App\Form\AssuntoType;
use App\Service\AssuntoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/assuntos', name: 'assunto_')]
final class AssuntoController extends AbstractController
{
    public function __construct(private readonly AssuntoService $assuntoService)
    {
    }

    #[Route('', name: 'listar', methods: ['GET'])]
    public function listar(): Response
    {
        return $this->render('assunto/listar.html.twig', [
            'assuntos' => $this->assuntoService->listar(),
        ]);
    }

    #[Route('/novo', name: 'novo', methods: ['GET', 'POST'])]
    public function novo(Request $request): Response
    {
        return $this->formulario($request, new Assunto(), 'Novo assunto', 'Assunto cadastrado com sucesso.');
    }

    /**
     * {codAs:assunto} carrega o Assunto cujo campo codAs é o valor da URL; se não existir, responde 404.
     */
    #[Route('/{codAs:assunto}/editar', name: 'editar', requirements: ['codAs' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function editar(Request $request, Assunto $assunto): Response
    {
        return $this->formulario($request, $assunto, 'Editar assunto', 'Assunto atualizado com sucesso.');
    }

    #[Route('/{codAs:assunto}/excluir', name: 'excluir', requirements: ['codAs' => Requirement::DIGITS], methods: ['POST'])]
    public function excluir(Request $request, Assunto $assunto): Response
    {
        if (!$this->isCsrfTokenValid('excluir_assunto_'.$assunto->getCodAs(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Não foi possível confirmar a exclusão. Recarregue a página e tente novamente.');

            return $this->redirectToRoute('assunto_listar', status: Response::HTTP_SEE_OTHER);
        }

        $this->assuntoService->excluir($assunto);
        $this->addFlash('success', 'Assunto excluído com sucesso.');

        return $this->redirectToRoute('assunto_listar', status: Response::HTTP_SEE_OTHER);
    }

    /**
     * Fluxo comum de cadastro e edição. Formulário inválido volta renderizado com os erros (status 422).
     */
    private function formulario(Request $request, Assunto $assunto, string $titulo, string $mensagemSucesso): Response
    {
        $form = $this->createForm(AssuntoType::class, $assunto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->assuntoService->salvar($assunto);
            $this->addFlash('success', $mensagemSucesso);

            return $this->redirectToRoute('assunto_listar', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('assunto/formulario.html.twig', [
            'form' => $form,
            'titulo' => $titulo,
        ]);
    }
}
