<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class RelatorioRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return list<array{autor_codau: int, autor_nome: string, livro_codl: int, titulo: string, editora: string, edicao: int, ano_publicacao: string, valor: string, assuntos: ?string}>
     */
    public function linhasPorAutor(): array
    {
        /** @var list<array{autor_codau: int, autor_nome: string, livro_codl: int, titulo: string, editora: string, edicao: int, ano_publicacao: string, valor: string, assuntos: ?string}> */
        return $this->connection->fetchAllAssociative(
            'SELECT autor_codau, autor_nome, livro_codl, titulo, editora, edicao, ano_publicacao, valor, assuntos
             FROM vw_livros_por_autor
             ORDER BY autor_nome, autor_codau, titulo, livro_codl',
        );
    }
}
