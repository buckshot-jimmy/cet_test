<?php

namespace App\Tests\Form;

use App\Form\PatientCancelAppointmentFormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class PatientCancelAppointmentFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $type = new PatientCancelAppointmentFormType();

        return [
            new PreloadedExtension([$type], [])
        ];
    }

    /**
     * @covers \App\Form\PatientCancelAppointmentFormType::buildForm
     * @covers \App\Form\PatientCancelAppointmentFormType::configureOptions
     */
    public function testBuildForm()
    {
        $form = $this->factory->create(PatientCancelAppointmentFormType::class);

        $this->assertTrue($form->has('pacient_anuleaza_programare'));

        $this->assertInstanceOf(SubmitType::class,
            $form->get('pacient_anuleaza_programare')->getConfig()->getType()->getInnerType());
        $this->assertSame('anuleaza_programare_btn',
            $form->get('pacient_anuleaza_programare')->getConfig()->getOption('attr')['id']);
    }
}
