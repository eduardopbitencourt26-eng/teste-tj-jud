<?php

namespace App\Service;

use App\Entity\Assunto;
use App\Exception\AssuntoPossuiLivrosException;
use App\Repository\AssuntoRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class AssuntoService
{
    public function __construct(
        private readonly AssuntoRepository $assuntoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
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

    public function salvar(Assunto $assunto): void
    {
        $this->entityManager->persist($assunto);
        $this->entityManager->flush();
    }

    /**
     * @throws AssuntoPossuiLivrosException
     */
    public function excluir(Assunto $assunto): void
    {
        $quantidadeDeLivros = $this->assuntoRepository->contarLivros($assunto);
        if ($quantidadeDeLivros > 0) {
            throw AssuntoPossuiLivrosException::comLivros($assunto, $quantidadeDeLivros);
        }

        try {
            $this->entityManager->remove($assunto);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $excecao) {
            $this->logger->warning('Exclusão de assunto barrada pela chave estrangeira.', [
                'codAs' => $assunto->getCodAs(),
                'erro' => $excecao->getMessage(),
            ]);

            throw AssuntoPossuiLivrosException::vinculadoDuranteAExclusao($assunto, $excecao);
        }
    }
}
