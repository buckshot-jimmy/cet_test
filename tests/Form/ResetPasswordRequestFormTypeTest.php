<?php

namespace App\Tests\Form;

use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ResetPasswordRequestFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        $type = new ChangePasswordFormType();

        return [
            new PreloadedExtension([$type], []),
            new ValidatorExtension($validator),
        ];
    }

    /**
     * @covers \App\Form\ResetPasswordRequestFormType::buildForm
     * @covers \App\Form\ResetPasswordRequestFormType::configureOptions
     */
    public function testBuildForm()
    {
        $form = $this->factory->create(ResetPasswordRequestFormType::class);

        $this->assertTrue($form->has('email_forgot_password'));
        $this->assertTrue($form->has('trimite_email_btn'));

        $this->assertInstanceOf(EmailType::class,
            $form->get('email_forgot_password')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class,
            $form->get('trimite_email_btn')->getConfig()->getType()->getInnerType());

        $this->assertSame('email_forgot_password',
            $form->get('email_forgot_password')->getConfig()->getOption('attr')['id']);
        $this->assertSame('Email',
            $form->get('email_forgot_password')->getConfig()->getOption('attr')['placeholder']);

        $this->assertSame('trimite_email_btn',
            $form->get('trimite_email_btn')->getConfig()->getOption('attr')['id']);
        $this->assertSame('Trimite email',
            $form->get('trimite_email_btn')->getConfig()->getOption('label'));
    }
}
