<?php

namespace App\Tests\Functional;

use App\Entity\Autor;
use App\Repository\LivroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

final class LivroControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testListagemRespondeOk(): void
    {
        $this->client->request('GET', '/livros');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Livros');
    }

    public function testCadastroComDoisAutoresRedirecionaComFlashDeSucesso(): void
    {
        $this->cadastrarAutores('Machado de Assis', 'José de Alencar');

        $crawler = $this->client->request('GET', '/livros/novo');
        $form = $crawler->selectButton('Salvar')->form([
            'livro[titulo]' => 'Antologia',
            'livro[editora]' => 'Garnier',
            'livro[edicao]' => '2',
            'livro[anoPublicacao]' => '1900',
            'livro[valor]' => '1.234,56',
        ]);
        /** @var array<ChoiceFormField> $autores */
        $autores = $form['livro[autores]'];
        foreach ($autores as $autor) {
            $autor->tick();
        }

        $this->client->submit($form);

        self::assertResponseRedirects('/livros', 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Livro cadastrado com sucesso.');

        $livro = static::getContainer()->get(LivroRepository::class)->findOneBy(['titulo' => 'Antologia']);
        self::assertNotNull($livro);
        self::assertSame('1234.56', $livro->getValor());
        self::assertCount(2, $livro->getAutores());
    }

    private function cadastrarAutores(string ...$nomes): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        foreach ($nomes as $nome) {
            $entityManager->persist((new Autor())->setNome($nome));
        }
        $entityManager->flush();
    }
}
