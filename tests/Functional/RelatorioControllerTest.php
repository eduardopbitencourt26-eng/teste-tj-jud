<?php

namespace App\Tests\Functional;

use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RelatorioControllerTest extends WebTestCase
{
    public function testRelatorioRespondeComPdf(): void
    {
        $client = static::createClient();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $autor = (new Autor())->setNome('Machado de Assis');
        $entityManager->persist($autor);
        $entityManager->persist((new Livro())
            ->setTitulo('Dom Casmurro')
            ->setEditora('Garnier')
            ->setEdicao(1)
            ->setAnoPublicacao('1899')
            ->setValor('49.90')
            ->addAutor($autor));
        $entityManager->flush();

        $client->request('GET', '/relatorio/pdf');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', (string) $client->getInternalResponse()->getContent());
    }
}
