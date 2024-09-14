<?php

namespace App\Form;

use App\Entity\BankAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class BankAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la banque',
                'error_bubbling' => false,
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 30,
                        'minMessage' => 'Le nom de la banque doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom de la banque ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new NotBlank([
                        'message' => 'Le nom de la banque ne peut pas être vide.',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom de banque'
                ]
            ])
            ->add('iban', TextType::class, [
                'label' => 'IBAN',
                'error_bubbling' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom de banque'
                ]
            ])
            ->add('bic', TextType::class, [
                'label' => 'BIC',
                'error_bubbling' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom de banque'
                ]
            ])
            ->add('address', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'cols' => 50,
                ],
                'required' => true
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'error_bubbling' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom de banque'
                ]
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'error_bubbling' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom de banque'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => "Enregistrer",
                'attr' => [
                    'class' => 'btn pi-bg text-white mt-3'
                ]
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token_bank_account_user',
            'csrf_token_id'   => 'user_bank_account_form',
        ]);
    }
}
