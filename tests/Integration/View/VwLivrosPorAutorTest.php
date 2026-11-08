<?php

namespace App\Tests\Integration\View;

use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Teste de integração da view do relatório (ADR 0009): o SQL da view roda no PostgreSQL de teste, dentro da
 * transação desfeita ao final (DAMA\DoctrineTestBundle).
 */
final class VwLivrosPorAutorTest extends KernelTestCase
{
    public function testLivroComDoisAutoresApareceSobCadaUmDeles(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $machado = (new Autor())->setNome('Machado de Assis');
        $alencar = (new Autor())->setNome('José de Alencar');
        $livro = (new Livro())
            ->setTitulo('Antologia')
            ->setEditora('Garnier')
            ->setEdicao(2)
            ->setAnoPublicacao('1900')
            ->setValor('1234.56')
            ->addAutor($machado)
            ->addAutor($alencar);
        $entityManager->persist($machado);
        $entityManager->persist($alencar);
        $entityManager->persist($livro);
        $entityManager->flush();

        $linhas = $container->get(Connection::class)->fetchAllAssociative(
            'SELECT autor_nome, titulo, valor FROM vw_livros_por_autor WHERE livro_codl = ? ORDER BY autor_nome',
            [$livro->getCodL()],
        );

        self::assertSame([
            ['autor_nome' => 'José de Alencar', 'titulo' => 'Antologia', 'valor' => '1234.56'],
            ['autor_nome' => 'Machado de Assis', 'titulo' => 'Antologia', 'valor' => '1234.56'],
        ], $linhas);
    }
}
