<?php

namespace App\Service;

use App\Entity\Assunto;
use App\Exception\AssuntoPossuiLivrosException;
use App\Repository\AssuntoRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Casos de uso de Assunto. O controller cuida só de HTTP; gravar e excluir passam por aqui, que é onde
 * entram as regras de negócio (ADR 0012).
 */
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

    /**
     * Serve para cadastro e edição: persist() de um objeto já gerenciado não faz nada.
     */
    public function salvar(Assunto $assunto): void
    {
        $this->entityManager->persist($assunto);
        $this->entityManager->flush();
    }

    /**
     * Mesma situação do AutorService: o Doctrine apagaria os vínculos em livro_assunto antes do DELETE, e o
     * assunto sumiria dos livros sem aviso. A regra é verificada antes do remove().
     *
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
