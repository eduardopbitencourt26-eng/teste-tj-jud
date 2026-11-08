<?php

namespace App\Repository;

use App\Entity\Autor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Autor>
 */
class AutorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Autor::class);
    }

    /**
     * COUNT no banco, sem carregar os livros na memória.
     */
    public function contarLivros(Autor $autor): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(l.codL) FROM App\Entity\Livro l JOIN l.autores au WHERE au = :autor')
            ->setParameter('autor', $autor)
            ->getSingleScalarResult();
    }
}
