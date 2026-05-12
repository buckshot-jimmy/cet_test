<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientCancelAppointmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pacient_anuleaza_programare', SubmitType::class, [
                'label' => 'Anuleaza programarea',
                'attr' => [
                    'id' => 'anuleaza_programare_btn',
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