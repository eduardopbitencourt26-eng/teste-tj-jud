<?php

namespace App\Service;

use App\Entity\Autor;
use App\Exception\AutorPossuiLivrosException;
use App\Repository\AutorRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class AutorService
{
    public function __construct(
        private readonly AutorRepository $autorRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
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

    /**
     * @throws AutorPossuiLivrosException
     */
    public function excluir(Autor $autor): void
    {
        $quantidadeDeLivros = $this->autorRepository->contarLivros($autor);
        if ($quantidadeDeLivros > 0) {
            throw AutorPossuiLivrosException::comLivros($autor, $quantidadeDeLivros);
        }

        try {
            $this->entityManager->remove($autor);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $excecao) {
            // Um livro foi vinculado entre a contagem e o DELETE. O EntityManager fica fechado depois de um erro de banco
            $this->logger->warning('Exclusão de autor barrada pela chave estrangeira.', [
                'codAu' => $autor->getCodAu(),
                'erro' => $excecao->getMessage(),
            ]);

            throw AutorPossuiLivrosException::vinculadoDuranteAExclusao($autor, $excecao);
        }
    }
}
