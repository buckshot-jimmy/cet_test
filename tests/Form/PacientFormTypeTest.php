<?php

namespace App\Tests\Form;

use App\Form\PacientFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class PacientFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        $type = new PacientFormType();

        return [
            new PreloadedExtension([$type], []),
            new ValidatorExtension($validator),
        ];
    }

    private function createForm()
    {
        return $this->factory->create(PacientFormType::class, null, [
            'tari' => ['Romania', 'Germany'],
            'judete' => ['Cluj', 'Bucuresti'],
            'stariCivile' => ['single' => 'Single', 'married' => 'Married'],
            'varsta' => 30,
            'pacient_id' => 123,
        ]);
    }

    /**
     * @covers \App\Form\PacientFormType::buildForm
     * @covers \App\Form\PacientFormType::configureOptions
     * @covers \App\Form\PacientFormType::buildValueChoices
     */
    public function testBuildFormFieldsExist()
    {
        $form = $this->createForm();

        $expectedFields = ['nume', 'prenume', 'tara', 'pacient_id', 'ciEliberat', 'varsta', 'judet', 'localitate',
            'adresa', 'telefon', 'telefon2', 'email', 'ocupatie', 'locMunca', 'stareCivila', 'observatii', 'ci', 'cnp'];

        foreach ($expectedFields as $field) {
            $this->assertTrue($form->has($field));
        }
    }

    /**
     * @covers \App\Form\PacientFormType::buildForm
     * @covers \App\Form\PacientFormType::configureOptions
     * @covers \App\Form\PacientFormType::buildValueChoices
     */
    public function testFieldTypes()
    {
        $form = $this->createForm();

        $this->assertInstanceOf(TextType::class, $form->get('nume')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('prenume')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('tara')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('cnp')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(HiddenType::class, $form->get('pacient_id')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextareaType::class, $form->get('observatii')->getConfig()->getType()->getInnerType());
    }

    /**
     * @covers \App\Form\PacientFormType::buildForm
     * @covers \App\Form\PacientFormType::configureOptions
     * @covers \App\Form\PacientFormType::buildValueChoices
     */
    public function testRequiredConstraints()
    {
        $form = $this->createForm();

        $this->assertNotEmpty($form->get('nume')->getConfig()->getOption('constraints'));
        $this->assertNotEmpty($form->get('cnp')->getConfig()->getOption('constraints'));
        $this->assertNotEmpty($form->get('telefon')->getConfig()->getOption('constraints'));
        $this->assertNotEmpty($form->get('localitate')->getConfig()->getOption('constraints'));
        $this->assertNotEmpty($form->get('adresa')->getConfig()->getOption('constraints'));
    }

    /**
     * @covers \App\Form\PacientFormType::buildForm
     * @covers \App\Form\PacientFormType::configureOptions
     * @covers \App\Form\PacientFormType::buildValueChoices
     */
    public function testOptionalFields()
    {
        $form = $this->createForm();

        $this->assertFalse($form->get('ci')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('telefon2')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('email')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('ocupatie')->getConfig()->getOption('required'));
    }

    /**
     * @covers \App\Form\PacientFormType::buildForm
     * @covers \App\Form\PacientFormType::configureOptions
     * @covers \App\Form\PacientFormType::buildValueChoices
     */
    public function testMappedAndDataFields()
    {
        $form = $this->createForm();

        $this->assertFalse($form->get('varsta')->getConfig()->getOption('mapped'));
        $this->assertEquals(30, $form->get('varsta')->getConfig()->getOption('data'));

        $this->assertFalse($form->get('pacient_id')->getConfig()->getOption('mapped'));
        $this->assertEquals(123, $form->get('pacient_id')->getConfig()->getOption('data'));
    }

    /**
     * @covers \App\Form\PacientFormType::buildForm
     * @covers \App\Form\PacientFormType::configureOptions
     * @covers \App\Form\PacientFormType::buildValueChoices
     */
    public function testAttributesAreSet()
    {
        $form = $this->createForm();

        $numeAttr = $form->get('nume')->getConfig()->getOption('attr');
        $this->assertEquals('form-control', $numeAttr['class']);
        $this->assertEquals('Nume', $numeAttr['placeholder']);

        $telefonAttr = $form->get('telefon')->getConfig()->getOption('attr');
        $this->assertEquals('form-control', $telefonAttr['class']);
    }
}
