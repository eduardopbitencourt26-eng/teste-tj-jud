<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Autor;
use App\Entity\Livro;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class LivroValidacaoTest extends TestCase
{
    /**
     * Sem 'assunto': o campo assunto é opcional
     */
    private static function livroValido(): Livro
    {
        return new Livro()
            ->setTitulo('Dom Casmurro')
            ->setEditora('Garnier')
            ->setEdicao(1)
            ->setAnoPublicacao('1899')
            ->setValor('49.90')
            ->addAutor(new Autor()->setNome('Machado de Assis'));
    }

    /**
     * @return list<string> propriedades com ao menos uma violação
     */
    private static function propriedadesInvalidas(Livro $livro): array
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $propriedades = [];
        foreach ($validator->validate($livro) as $violacao) {
            $propriedades[] = $violacao->getPropertyPath();
        }

        return array_values(array_unique($propriedades));
    }

    public function testLivroValido(): void
    {
        self::assertSame([], self::propriedadesInvalidas(self::livroValido()));
    }

    /**
     * @return iterable<string, array{string, \Closure(Livro): void}>
     */
    public static function livrosInvalidos(): iterable
    {
        yield 'título vazio' => ['titulo', fn (Livro $livro) => $livro->setTitulo('')];
        yield 'título com 41 caracteres' => ['titulo', fn (Livro $livro) => $livro->setTitulo(str_repeat('a', 41))];
        yield 'sem autor' => ['autores', function (Livro $livro): void {
            foreach ($livro->getAutores()->toArray() as $autor) {
                $livro->removeAutor($autor);
            }
        }];
        yield 'valor negativo' => ['valor', fn (Livro $livro) => $livro->setValor('-0.01')];
        yield 'ano com 2 dígitos' => ['anoPublicacao', fn (Livro $livro) => $livro->setAnoPublicacao('99')];
        yield 'ano com letra' => ['anoPublicacao', fn (Livro $livro) => $livro->setAnoPublicacao('20a4')];
    }

    /**
     * @param \Closure(Livro): void $quebraRegra
     */
    #[DataProvider('livrosInvalidos')]
    public function testLivroInvalido(string $propriedadeInvalida, \Closure $quebraRegra): void
    {
        $livro = self::livroValido();
        $quebraRegra($livro);

        self::assertSame([$propriedadeInvalida], self::propriedadesInvalidas($livro));
    }
}
