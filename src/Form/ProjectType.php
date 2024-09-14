<?php

namespace App\Form;

use App\Entity\Image;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Uploader une nouvelle image',
                'error_bubbling' => false,
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
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier au format JPEG, PNG, AVIF, WEBP ou GIF',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom du projet',
                'required' => true,
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 30,
                        'minMessage' => 'Le nom du projet doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom du projet ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                    new NotBlank([
                        'message' => 'Le nom de projet ne peut pas être vide.',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisir un nom de projet'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Business en ligne' => 1,
                    'Business physique' => 2,
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('risk', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Faible' => 1,
                    'Modéré' => 2,
                    'Elevé' => 3,
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Actif' => 1,
                    'Inactif' => 2
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('yield', NumberType::class, [
                'scale' => 2, // Nombre de décimales après la virgule
                'html5' => true, // Pour activer l'input de type "number" HTML5
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20
                ],
            ])
            ->add('age', NumberType::class, [
                'html5' => true,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 50
                ],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 50,
                        'notInRangeMessage' => 'Le nombre doit être compris entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10, // Nombre de lignes visibles dans le textarea
                    'cols' => 50, // Nombre de colonnes visibles dans le textarea (optionnel)
                ],
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 3000,
                        'minMessage' => 'La description doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
                'required' => true
            ])
            ->add('submit', SubmitType::class, [
                'label' => "Enregistrer",
                'attr' => [
                    'class' => 'btn pi-bg text-white mt-3'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
