<?php

namespace App\Form;

use App\Entity\Pacient;
use App\Validator\PacientConstraints;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PacientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nume', TextType::class, [
                'label' => 'Nume',
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nume'],
            ])
            ->add('prenume', TextType::class, [
                'label' => 'Prenume',
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prenume'],
            ])
            ->add('tara', ChoiceType::class, [
                'label' => 'Tara',
                'choices' => $this->buildValueChoices($options['tari']),
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('cnp', TextType::class, [
                'label' => 'CNP / ID',
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'CNP / ID'],
            ])
            ->add('ci', TextType::class, [
                'label' => 'C.I.',
                'required' => false,
                'attr' => ['class' => 'form-control date-or-time'],
            ])
            ->add('ciEliberat', TextType::class, [
                'label' => 'Eliberata de',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('varsta', TextType::class, [
                'label' => 'Varsta',
                'mapped' => false,
                'required' => false,
                'data' => $options['varsta'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Varsta', 'readonly' => 'readonly'],
            ])
            ->add('judet', ChoiceType::class, [
                'label' => 'Judet',
                'choices' => ['---' => ''] + $this->buildValueChoices($options['judete']),
                'required' => false,
                'attr' => ['class' => 'form-control select2-search'],
            ])
            ->add('localitate', TextType::class, [
                'label' => 'Localitate *',
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Localitate'],
            ])
            ->add('adresa', TextType::class, [
                'label' => 'Adresa *',
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresa'],
            ])
            ->add('telefon', TextType::class, [
                'label' => 'Telefon *',
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Telefon'],
            ])
            ->add('telefon2', TextType::class, [
                'label' => 'Telefon 2',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Telefon 2'],
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email'],
            ])
            ->add('ocupatie', TextType::class, [
                'label' => 'Ocupatie',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ocupatie'],
            ])
            ->add('locMunca', TextType::class, [
                'label' => 'Loc de munca',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Loc de munca'],
            ])
            ->add('stareCivila', ChoiceType::class, [
                'label' => 'Stare civila',
                'choices' => array_flip($options['stariCivile']),
                'constraints' => [new NotBlank()],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('observatii', TextareaType::class, [
                'label' => 'Observatii',
                'required' => false,
                'attr' => ['class' => 'form-control twig-style-104', 'placeholder' => 'Observatii'],
            ])
            ->add('pacient_id', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => $options['pacient_id'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pacient::class,
            'constraints' => [new PacientConstraints()],
            'csrf_token_id' => 'pacient',
            'tari' => [],
            'judete' => [],
            'stariCivile' => [],
            'varsta' => '',
            'pacient_id' => '',
        ]);

        $resolver->setAllowedTypes('tari', 'array');
        $resolver->setAllowedTypes('judete', 'array');
        $resolver->setAllowedTypes('stariCivile', 'array');
        $resolver->setAllowedTypes('varsta', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('pacient_id', ['string', 'int', 'null']);
    }

    private function buildValueChoices(array $values): array
    {
        return array_combine($values, $values) ?: [];
    }
}
