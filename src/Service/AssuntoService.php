<?php

namespace App\Service;

use App\Entity\Assunto;
use App\Repository\AssuntoRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Casos de uso de Assunto. O controller cuida só de HTTP; gravar e excluir passam por aqui, que é onde
 * entram as regras de negócio (ex.: bloquear a exclusão de assunto com livros).
 */
final class AssuntoService
{
    public function __construct(
        private readonly AssuntoRepository $assuntoRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<Assunto>
     */
    public function listar(): array
    {
        return $this->assuntoRepository->findBy([], ['descricao' => 'ASC']);
    }

    public function contar(): int
    {
        return $this->assuntoRepository->count();
    }

    /**
     * Serve para cadastro e edição: persist() de um objeto já gerenciado não faz nada.
     */
    public function salvar(Assunto $assunto): void
    {
        $this->entityManager->persist($assunto);
        $this->entityManager->flush();
    }

    public function excluir(Assunto $assunto): void
    {
        $this->entityManager->remove($assunto);
        $this->entityManager->flush();
    }
}
