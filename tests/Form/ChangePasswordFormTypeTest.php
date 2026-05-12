<?php

namespace App\Tests\Form;

use App\Form\ChangePasswordFormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class ChangePasswordFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $type = new ChangePasswordFormType();

        return [
            new PreloadedExtension([$type], [])
        ];
    }

    /**
     * @covers \App\Form\ChangePasswordFormType::buildForm
     * @covers \App\Form\ChangePasswordFormType::configureOptions
     */
    public function testBuildForm()
    {
        $form = $this->factory->create(ChangePasswordFormType::class);

        $this->assertTrue($form->has('new_password'));
        $this->assertTrue($form->has('confirm_new_password'));
        $this->assertTrue($form->has('confirm_new_password_btn'));

        $this->assertInstanceOf(PasswordType::class,
            $form->get('new_password')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(PasswordType::class,
            $form->get('confirm_new_password')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(SubmitType::class,
            $form->get('confirm_new_password_btn')->getConfig()->getType()->getInnerType());

        $this->assertSame('new_password',
            $form->get('new_password')->getConfig()->getOption('attr')['id']);
        $this->assertSame('Parola',
            $form->get('new_password')->getConfig()->getOption('attr')['placeholder']);

        $this->assertSame('confirm_new_password',
            $form->get('confirm_new_password')->getConfig()->getOption('attr')['id']);
        $this->assertSame('Confirma parola',
            $form->get('confirm_new_password')->getConfig()->getOption('attr')['placeholder']);

        $this->assertSame('change_password_btn',
            $form->get('confirm_new_password_btn')->getConfig()->getOption('attr')['id']);
        $this->assertSame('Confirma',
            $form->get('confirm_new_password_btn')->getConfig()->getOption('label'));
    }
}
