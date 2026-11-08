<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Executa database/03_carga_inicial.sql, para que a carga pelo Doctrine e a carga pelo psql sejam a mesma.
 */
final class CargaInicialFixtures extends Fixture
{
    public function __construct(
        private readonly Connection $conexao,
        #[Autowire('%kernel.project_dir%/database/03_carga_inicial.sql')]
        private readonly string $script,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $sql = file_get_contents($this->script);

        if (false === $sql) {
            throw new \RuntimeException(sprintf('Não foi possível ler o script de carga "%s".', $this->script));
        }

        $this->conexao->executeStatement($sql);
    }
}
