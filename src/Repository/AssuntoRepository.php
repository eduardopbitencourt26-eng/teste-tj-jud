<?php

namespace App\Repository;

use App\Entity\Assunto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assunto>
 */
class AssuntoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assunto::class);
    }

    /**
     * COUNT no banco, sem carregar os livros na memória.
     */
    public function contarLivros(Assunto $assunto): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(l.codL) FROM App\Entity\Livro l JOIN l.assuntos ass WHERE ass = :assunto')
            ->setParameter('assunto', $assunto)
            ->getSingleScalarResult();
    }
}
