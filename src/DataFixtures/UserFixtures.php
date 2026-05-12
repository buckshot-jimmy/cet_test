<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Services\FixturesEntityFactory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(
        private UserPasswordHasherInterface $encoder,
        private FixturesEntityFactory $entityFactory
    ) {}

    public function load(ObjectManager $manager)
    {
        $user = new User();

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'admin'],
            [
                'setUsername' => 'admin',
                'setNume' => 'Admin',
                'setPrenume' => 'Admin',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_1'),
                'setEmail' => 'ciprianmarta.cm@gmail.com',
                'setTelefon' => '0745545689',
                'setRole' => $this->getReference('roleAdmin'),
                'setSters' => false,
                'setCodParafa' => null,
                'setTitulatura' => null,
                'setSpecialitate' => null
            ]
        );
        $this->addReference('userAdmin', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'dpopescu'],
            [
                'setUsername' => 'dpopescu',
                'setNume' => 'Popescu',
                'setPrenume' => 'Damian',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_2'),
                'setEmail' => 'damian.popescu@mindreset.ro',
                'setTelefon' => '0721225583',
                'setRole' => $this->getReference('roleMedic'),
                'setSters' => false,
                'setTitulatura' => $this->getReference('primar'),
                'setSpecialitate' => $this->getReference('neurolog'),
                'setCodParafa' => 'A65508'
            ]
        );
        $this->addReference('userPopescu', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'gsimori'],
            [
                'setUsername' => 'gsimori',
                'setNume' => 'Simori',
                'setPrenume' => 'Gabor',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_3'),
                'setEmail' => 'gabor.simori@mindreset.ro',
                'setTelefon' => '0758067010',
                'setRole' => $this->getReference('roleMedic'),
                'setSters' => false,
                'setTitulatura' => $this->getReference('primar'),
                'setSpecialitate' => $this->getReference('neurolog'),
                'setCodParafa' => 'B42843'
            ]
        );
        $this->addReference('userSimori', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'lmarginean'],
            [
                'setUsername' => 'lmarginean',
                'setNume' => 'Marginean',
                'setPrenume' => 'Laura',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_4'),
                'setEmail' => 'laura.marginean@mindreset.ro',
                'setTelefon' => '0744691392',
                'setRole' => $this->getReference('roleMedic'),
                'setSters' => false,
                'setTitulatura' => $this->getReference('primar'),
                'setSpecialitate' => $this->getReference('neurolog'),
                'setCodParafa' => 'A67907'
            ]
        );
        $this->addReference('userMarginean', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'mcasu'],
            [
                'setUsername' => 'mcasu',
                'setNume' => 'Casu',
                'setPrenume' => 'Mihai',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_5'),
                'setEmail' => 'mihai.casu@mindreset.ro',
                'setTelefon' => '0744611971',
                'setRole' => $this->getReference('roleMedic'),
                'setSters' => false,
                'setTitulatura' => $this->getReference('primar'),
                'setSpecialitate' => $this->getReference('psihiatru'),
                'setCodParafa' => '943522'
            ]
        );
        $this->addReference('userCasu', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'mlupu'],
            [
                'setUsername' => 'mlupu',
                'setNume' => 'Lupu',
                'setPrenume' => 'Mihaela',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_6'),
                'setEmail' => 'mihaela.lupu@mindreset.ro',
                'setTelefon' => '0744489701',
                'setRole' => $this->getReference('rolePsiholog'),
                'setSters' => false,
                'setTitulatura' => $this->getReference('psihologPrincipal'),
                'setSpecialitate' => $this->getReference('psiholog'),
                'setCodParafa' => '04137'
            ]
        );
        $this->addReference('userLupu', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\User',
            ['username' => 'test'],
            [
                'setUsername' => 'test',
                'setNume' => 'Test',
                'setPrenume' => 'Test',
                'setPassword' => $this->encoder->hashPassword($user, 'Admin_7'),
                'setEmail' => 'test@test.ro',
                'setTelefon' => '0745545689',
                'setRole' => $this->getReference('roleTest'),
                'setSters' => false,
                'setTitulatura' => null,
                'setSpecialitate' => null,
                'setCodParafa' => null
            ]
        );
        $this->addReference('userTest', $entity);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
}
