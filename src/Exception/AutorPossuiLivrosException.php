<?php

namespace App\Exception;

use App\Entity\Autor;

/**
 * Regra de negócio: um autor com livros não pode ser excluído, senão esses livros perderiam o autor.
 */
final class AutorPossuiLivrosException extends \DomainException
{
    public static function comLivros(Autor $autor, int $quantidadeDeLivros): self
    {
        return new self(\sprintf(
            'O autor "%s" não pode ser excluído porque possui %d %s. Remova o autor desses livros antes.',
            $autor->getNome(),
            $quantidadeDeLivros,
            1 === $quantidadeDeLivros ? 'livro' : 'livros',
        ));
    }

    /**
     * Um livro foi vinculado ao autor entre a verificação e a exclusão; quem barrou foi a chave estrangeira.
     */
    public static function vinculadoDuranteAExclusao(Autor $autor, \Throwable $anterior): self
    {
        return new self(\sprintf(
            'O autor "%s" não pode ser excluído porque acabou de ser vinculado a um livro.',
            $autor->getNome(),
        ), previous: $anterior);
    }
}
