<?php

// src/Form/StreamType.php
namespace App\Form;

use App\Entity\Stream;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StreamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('videoFile', VichFileType::class, [
                'label' => 'Choisir une vidéo (MP4)',
                'required' => true,
                'allow_delete' => false,
                'download_uri' => false,
                'attr' => ['accept' => 'video/mp4'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stream::class,
        ]);
    }
}