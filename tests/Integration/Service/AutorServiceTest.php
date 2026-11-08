<?php

namespace App\Tests\Integration\Service;

use App\Entity\Autor;
use App\Entity\Livro;
use App\Exception\AutorPossuiLivrosException;
use App\Service\AutorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Teste de integração (ADR 0009): Service real, Doctrine real e PostgreSQL de teste. Cada teste roda numa
 * transação desfeita ao final (DAMA\DoctrineTestBundle).
 */
final class AutorServiceTest extends KernelTestCase
{
    public function testExcluirAutorComLivrosLancaExcecaoEMantemOVinculo(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $autor = (new Autor())->setNome('Machado de Assis');
        $livro = (new Livro())
            ->setTitulo('Dom Casmurro')
            ->setEditora('Garnier')
            ->setEdicao(1)
            ->setAnoPublicacao('1899')
            ->setValor('49.90')
            ->addAutor($autor);
        $entityManager->persist($autor);
        $entityManager->persist($livro);
        $entityManager->flush();
        $entityManager->clear();

        $autor = $entityManager->find(Autor::class, $autor->getCodAu());

        try {
            $container->get(AutorService::class)->excluir($autor);
            self::fail('Excluir um autor com livros deveria lançar AutorPossuiLivrosException.');
        } catch (AutorPossuiLivrosException $excecao) {
            self::assertStringContainsString('Machado de Assis', $excecao->getMessage());
        }

        $entityManager->clear();
        self::assertCount(1, $entityManager->find(Livro::class, $livro->getCodL())->getAutores());
    }
}
