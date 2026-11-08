<?php

namespace App\Exception;

use App\Entity\Autor;

/**
 * Regra de negócio: um autor com livros não pode ser excluído, senão esses livros perderiam o autor.
 */
final class AutorPossuiLivrosException extends \DomainException
{
    public function __construct(Autor $autor, int $quantidadeDeLivros)
    {
        parent::__construct(\sprintf(
            'O autor "%s" não pode ser excluído porque possui %d %s. Remova o autor desses livros antes.',
            $autor->getNome(),
            $quantidadeDeLivros,
            1 === $quantidadeDeLivros ? 'livro' : 'livros',
        ));
    }
}
