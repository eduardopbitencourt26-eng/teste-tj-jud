<?php

namespace App\Service;

use App\Entity\Autor;
use App\Repository\AutorRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Casos de uso de Autor. Mesmo molde do AssuntoService (ADR 0012).
 */
final class AutorService
{
    public function __construct(
        private readonly AutorRepository $autorRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<Autor>
     */
    public function listar(): array
    {
        return $this->autorRepository->findBy([], ['nome' => 'ASC']);
    }

    public function contar(): int
    {
        return $this->autorRepository->count();
    }

    public function salvar(Autor $autor): void
    {
        $this->entityManager->persist($autor);
        $this->entityManager->flush();
    }

    public function excluir(Autor $autor): void
    {
        $this->entityManager->remove($autor);
        $this->entityManager->flush();
    }
}
