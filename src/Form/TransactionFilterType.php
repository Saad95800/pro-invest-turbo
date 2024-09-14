<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TransactionFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('datemin', DateType::class, [
                'widget' => 'single_text', // Utilise un élément de type 'date' HTML5
                'label' => 'Date début',
                'help' => 'Choisissez une date de debut.',
                // Les options ci-dessous sont facultatives et à adapter selon tes besoins
                'format' => 'yyyy-MM-dd',  // Format de la date
                'html5' => true,           // Utiliser l'entrée de type date HTML5 où c'est supporté
                'required' => false,       // Rendre le champ optionnel
            ])
            ->add('datemax', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date fin',
                'help' => 'Choisissez une date de fin.',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Utilisateur'
            ])
            ->add('amountmin', NumberType::class, [
                'required' => false,
                'label' => 'Montant Min'
            ])
            ->add('amountmax', NumberType::class, [
                'required' => false,
                'label' => 'Montant Max'
            ])
            ->add('submit', SubmitType::class, ['label' => 'Filtrer'])
        ;
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
