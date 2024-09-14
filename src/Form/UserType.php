<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Votre Nom',
                
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 30,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new NotBlank([
                        'message' => 'Le nom ne peut pas être vide.',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom'
                ]
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Votre prénom',
                
                'constraints' => new Length([
                    'min' => 2,
                    'max' => 30
                ]),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un prénom'
                ]
            ])
            ->add('email', EmailType::class, [ 
                'label' => 'Votre email',
                
                'constraints' => new Length([
                    'min' => 2,
                    'max' => 30
                ]),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un email'
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => 'Votre téléphone',
                
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 15,
                        'minMessage' => 'Le téléphone doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new Regex([
                        'pattern' => '/^\+?[0-9\s\-()]*$/',
                        'message' => 'Veuillez entrer un numéro de téléphone valide.'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un numéro de téléphone'
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Le mot de passe et la confirmation doivent être identique',
                'mapped' => false,
                'required' => false,
                
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Merci de saisir votre mot de passe'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Merci de confirmer votre mot de passe'
                    ]
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Uploader une nouvelle image',
                
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/avif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier au format JPEG, PNG, AVIF ou GIF',
                    ])
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => "Enregistrer"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
