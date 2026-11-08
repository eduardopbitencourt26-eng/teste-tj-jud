<?php

namespace App\Service;

use App\Entity\Livro;
use App\Repository\LivroRepository;
use Doctrine\ORM\EntityManagerInterface;


final class LivroService
{
    public function __construct(
        private readonly LivroRepository $livroRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<Livro>
     */
    public function listar(): array
    {
        return $this->livroRepository->listarComAutoresEAssuntos();
    }

    public function contar(): int
    {
        return $this->livroRepository->count();
    }

    public function salvar(Livro $livro): void
    {
        $this->entityManager->persist($livro);
        $this->entityManager->flush();
    }

    public function excluir(Livro $livro): void
    {
        $this->entityManager->remove($livro);
        $this->entityManager->flush();
    }
}
