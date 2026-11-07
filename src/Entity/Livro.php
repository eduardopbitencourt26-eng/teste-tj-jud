<?php

namespace App\Entity;

use App\Repository\LivroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivroRepository::class)]
#[ORM\Table(name: 'livro')]
class Livro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codl', type: Types::INTEGER)]
    private ?int $codL = null;

    #[ORM\Column(name: 'titulo', type: Types::STRING, length: 40)]
    private ?string $titulo = null;

    #[ORM\Column(name: 'editora', type: Types::STRING, length: 40)]
    private ?string $editora = null;

    #[ORM\Column(name: 'edicao', type: Types::INTEGER)]
    private ?int $edicao = null;

    #[ORM\Column(name: 'anopublicacao', type: Types::STRING, length: 4)]
    private ?string $anoPublicacao = null;

    /**
     * DECIMAL chega ao PHP como string ("1234.56") para não perder precisão em float.
     */
    #[ORM\Column(name: 'valor', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $valor = null;

    /**
     * Excluir o livro apaga seus vínculos (CASCADE); excluir um autor que ainda tem livros
     * é bloqueado pela chave estrangeira.
     *
     * @var Collection<int, Autor>
     */
    #[ORM\ManyToMany(targetEntity: Autor::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'livro_autor')]
    #[ORM\JoinColumn(name: 'livro_codl', referencedColumnName: 'codl', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'autor_codau', referencedColumnName: 'codau', nullable: false)]
    private Collection $autores;

    /**
     * @var Collection<int, Assunto>
     */
    #[ORM\ManyToMany(targetEntity: Assunto::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'livro_assunto')]
    #[ORM\JoinColumn(name: 'livro_codl', referencedColumnName: 'codl', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'assunto_codas', referencedColumnName: 'codas', nullable: false)]
    private Collection $assuntos;

    public function __construct()
    {
        $this->autores = new ArrayCollection();
        $this->assuntos = new ArrayCollection();
    }

    public function getCodL(): ?int
    {
        return $this->codL;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(?string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getEditora(): ?string
    {
        return $this->editora;
    }

    public function setEditora(?string $editora): static
    {
        $this->editora = $editora;

        return $this;
    }

    public function getEdicao(): ?int
    {
        return $this->edicao;
    }

    public function setEdicao(?int $edicao): static
    {
        $this->edicao = $edicao;

        return $this;
    }

    public function getAnoPublicacao(): ?string
    {
        return $this->anoPublicacao;
    }

    public function setAnoPublicacao(?string $anoPublicacao): static
    {
        $this->anoPublicacao = $anoPublicacao;

        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): static
    {
        $this->valor = $valor;

        return $this;
    }

    /**
     * @return Collection<int, Autor>
     */
    public function getAutores(): Collection
    {
        return $this->autores;
    }

    public function addAutor(Autor $autor): static
    {
        if (!$this->autores->contains($autor)) {
            $this->autores->add($autor);
            $autor->getLivros()->add($this);
        }

        return $this;
    }

    public function removeAutor(Autor $autor): static
    {
        if ($this->autores->removeElement($autor)) {
            $autor->getLivros()->removeElement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Assunto>
     */
    public function getAssuntos(): Collection
    {
        return $this->assuntos;
    }

    public function addAssunto(Assunto $assunto): static
    {
        if (!$this->assuntos->contains($assunto)) {
            $this->assuntos->add($assunto);
            $assunto->getLivros()->add($this);
        }

        return $this;
    }

    public function removeAssunto(Assunto $assunto): static
    {
        if ($this->assuntos->removeElement($assunto)) {
            $assunto->getLivros()->removeElement($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->titulo;
    }
}
