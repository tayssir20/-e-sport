<?php

namespace App\Form;

use App\Entity\Jeu;
use App\Entity\Tournoi;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournoiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du tournoi',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: CS2 Tunis Major Open 2026']
            ])
            ->add('jeu', EntityType::class, [
                'class' => Jeu::class,
                'choice_label' => 'nom',
                'label' => 'Jeu',
                'placeholder' => '-- Sélectionner un jeu --',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Open, Qualifiers']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'En Attente',
                    'En cours' => 'En cours',
                    'Terminé' => 'Terminé',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_fin', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateInscriptionLimite', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre max de participants',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 16']
            ])
            ->add('cagnotte', NumberType::class, [
                'label' => 'Cagnotte (€)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 12000']
            ])
            ->add('fraisInscription', NumberType::class, [
                'label' => 'Frais d\'inscription (€)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '0 pour gratuit']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournoi::class,
        ]);
    }
}
