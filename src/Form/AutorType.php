<?php

namespace App\Form;

use App\Entity\Autor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * sem o campo 'livros': o vínculo com livros é editado pelo formulário do livro, que é o lado dono da relação.
 */
final class AutorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nome', TextType::class, [
            'label' => 'Nome',
            'attr' => ['maxlength' => 40],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Autor::class,
        ]);
    }
}
