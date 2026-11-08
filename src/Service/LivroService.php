<?php

namespace App\Service;

use App\Entity\Livro;
use App\Repository\LivroRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Casos de uso de Livro. Mesmo molde do AssuntoService (ADR 0012).
 */
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

    /**
     * Livro é o lado dono das duas relações: o flush grava também livro_autor e livro_assunto.
     */
    public function salvar(Livro $livro): void
    {
        $this->entityManager->persist($livro);
        $this->entityManager->flush();
    }

    /**
     * Os vínculos em livro_autor e livro_assunto saem junto (ON DELETE CASCADE); autores e assuntos ficam.
     */
    public function excluir(Livro $livro): void
    {
        $this->entityManager->remove($livro);
        $this->entityManager->flush();
    }
}
