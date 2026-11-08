<?php

namespace App\Relatorio;


final readonly class GrupoDoAutor
{
    /**
     * @param list<array{livro_codl: int, titulo: string, editora: string, edicao: int, ano_publicacao: string, valor: string, assuntos: ?string}> $livros linhas da view
     */
    public function __construct(
        public string $autor,
        public array $livros,
        public string $subtotal,
    ) {
    }

    public function quantidade(): int
    {
        return \count($this->livros);
    }
}
