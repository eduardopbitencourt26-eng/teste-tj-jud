<?php

namespace App\Exception;

use App\Entity\Assunto;

/**
 *  um assunto em uso não pode ser excluído, senão sairia dos livros sem aviso.
 */
final class AssuntoPossuiLivrosException extends \DomainException
{
    public static function comLivros(Assunto $assunto, int $quantidadeDeLivros): self
    {
        return new self(\sprintf(
            'O assunto "%s" não pode ser excluído porque está em %d %s. Remova o assunto desses livros antes.',
            $assunto->getDescricao(),
            $quantidadeDeLivros,
            1 === $quantidadeDeLivros ? 'livro' : 'livros',
        ));
    }

    public static function vinculadoDuranteAExclusao(Assunto $assunto, \Throwable $anterior): self
    {
        return new self(\sprintf(
            'O assunto "%s" não pode ser excluído porque acabou de ser vinculado a um livro.',
            $assunto->getDescricao(),
        ), previous: $anterior);
    }
}
