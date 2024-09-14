<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\BankAccount;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'required' => true,
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('user', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => User::class,
                'required' => true,
                'choice_label' => function (User $user) {
                    return $user->getFirstname() . ' ' . strtoupper($user->getLastname());
                },
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant',
                'html5' => true,
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('bankAccount', EntityType::class, [
                'label' => 'Compte bancaire',
                'class' => BankAccount::class,
                'required' => true,
                'choice_label' => function (BankAccount $bankAccount) {
                    return $bankAccount->getName() . ' - ' . strtoupper($bankAccount->getLastname()) . ' ' . $bankAccount->getFirstname();
                },
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => "Enregistrer",
                'attr' => [
                    'class' => 'btn pi-bg text-white rounded-0 mt-3'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'csrf_protection' => true,
            // 'csrf_field_name' => '_csrf_token_transaction_user',
            'csrf_token_id'   => 'user_deposit_form'
        ]);
    }
}
