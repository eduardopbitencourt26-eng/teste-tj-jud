<?php

namespace App\Relatorio;

final readonly class RelatorioLivrosPorAutor
{
    /**
     * @param list<GrupoDoAutor> $grupos
     */
    public function __construct(
        public array $grupos,
        public int $quantidadeDeLivros,
        public string $total,
        public \DateTimeImmutable $geradoEm,
    ) {
    }
}
