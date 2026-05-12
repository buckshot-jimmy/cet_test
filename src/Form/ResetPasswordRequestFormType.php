<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email_forgot_password', EmailType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'email_forgot_password',
                    'placeholder' => 'Email'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email']),
                    new Email(['message' => 'Enter a valid email address']),
                ]
            ])
            ->add('trimite_email_btn', SubmitType::class, [
                'label' => 'Trimite email',
                'attr' => [
                    'id' => 'trimite_email_btn',
                    'class' => 'btn btn-light'
                ]
            ])
            ->add('retrimite_email_btn', SubmitType::class, [
                'label' => 'Retrimite email',
                'attr' => [
                    'id' => 'retrimite_email_btn',
                    'class' => 'btn btn-info',
                    'style' => 'margin-top: 10px;'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}