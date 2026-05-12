<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('new_password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'type' => "text",
                    'class' => 'form-control',
                    'id' => 'new_password',
                    'placeholder' => 'Parola'
                ]
            ])
            ->add('confirm_new_password', PasswordType::class, [
                'label' => false,
                'attr' => [
                    'type' => "text",
                    'class' => 'form-control',
                    'id' => 'confirm_new_password',
                    'placeholder' => 'Confirma parola'
                ]
            ])
            ->add('confirm_new_password_btn', SubmitType::class, [
                'label' => 'Confirma',
                'attr' => [
                    'class' => 'btn btn-light',
                    'id' => 'change_password_btn'
                ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}