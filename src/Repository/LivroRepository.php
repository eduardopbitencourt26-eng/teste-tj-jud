<?php

namespace App\Repository;

use App\Entity\Livro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<Livro>
 */
class LivroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livro::class);
    }

    /**
     * @return array<Livro>
     */
    public function listarComAutoresEAssuntos(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.autores', 'au')
            ->addSelect('au')
            ->leftJoin('l.assuntos', 'ass')
            ->addSelect('ass')
            ->orderBy('l.titulo', SortDirection::Ascending)
            ->addOrderBy('au.nome', SortDirection::Ascending)
            ->addOrderBy('ass.descricao', SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }
}
