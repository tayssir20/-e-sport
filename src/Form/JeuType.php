<?php

namespace App\Form;

use App\Entity\Jeu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JeuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Game Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. League of Legends']
            ])
            ->add('genre', TextType::class, [
                'label' => 'Genre',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. MOBA']
            ])
            ->add('plateforme', TextType::class, [
                'label' => 'Platform',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. PC']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Status',
                'choices'  => [
                    'Active' => 'ACTIVE',
                    'Inactive' => 'INACTIVE',
                    'Coming Soon' => 'COMING_SOON',
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jeu::class,
        ]);
    }
}
