<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProjectFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Nom du projet'
            ])
            ->add('agemin', NumberType::class, [
                'required' => false,
                'label' => 'Min'
            ])
            ->add('agemax', NumberType::class, [
                'required' => false,
                'label' => 'Max'
            ])
            ->add('yieldmin', NumberType::class, [
                'required' => false,
                'label' => 'Min'
            ])
            ->add('yieldmax', NumberType::class, [
                'required' => false,
                'label' => 'Max'
            ])
            ->add('risk', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '---' => null,
                    'Faible' => 1,
                    'Modéré' => 2,
                    'Elevé' => 3,
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Filtrer']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }
}
