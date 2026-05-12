<?php

namespace App\DataFixtures;

use App\Services\FixturesEntityFactory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PreturiFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(private FixturesEntityFactory $entityFactory) {}

    public function load(ObjectManager $manager)
    {
        $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Preturi',
            [
                'medic' => $this->getReference('userPopescu'),
                'serviciu' => $this->getReference('consultGeneral'),
                'owner' => $this->getReference('ownerNeuro')
            ],
            [
                'setPret' => 100, 'setSters' => false, 'setProcentajMedic' => 50,
                'setMedic' => $this->getReference('userPopescu'),
                'setServiciu' => $this->getReference('consultGeneral'),
                'setOwner' => $this->getReference('ownerNeuro')
            ]
        );

        $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Preturi',
            [
                'medic' => $this->getReference('userCasu'),
                'serviciu' => $this->getReference('consultSpecial'),
                'owner' => $this->getReference('ownerCasu')
            ],
            [
                'setPret' => 150, 'setSters' => false, 'setProcentajMedic' => 70,
                'setMedic' => $this->getReference('userCasu'),
                'setServiciu' => $this->getReference('consultSpecial'),
                'setOwner' => $this->getReference('ownerCasu')
            ]
        );

        $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Preturi',
            [
                'medic' => $this->getReference('userSimori'),
                'serviciu' => $this->getReference('investigatieSpeciala'),
                'owner' => $this->getReference('ownerNeuro')
            ],
            [
                'setPret' => 200, 'setSters' => false, 'setProcentajMedic' => 50,
                'setMedic' => $this->getReference('userSimori'),
                'setServiciu' => $this->getReference('investigatieSpeciala'),
                'setOwner' => $this->getReference('ownerNeuro')
            ]
        );

        $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Preturi',
            [
                'medic' => $this->getReference('userMarginean'),
                'serviciu' => $this->getReference('consultGeneral'),
                'owner' => $this->getReference('ownerNeuro')
            ],
            [
                'setPret' => 300, 'setSters' => false, 'setProcentajMedic' => 25,
                'setMedic' => $this->getReference('userMarginean'),
                'setServiciu' => $this->getReference('consultGeneral'),
                'setOwner' => $this->getReference('ownerNeuro')
            ]
        );

        $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Preturi',
            [
                'medic' => $this->getReference('userLupu'),
                'serviciu' => $this->getReference('evaluarePsihologica'),
                'owner' => $this->getReference('ownerLupu')
            ],
            [
                'setPret' => 250, 'setSters' => false, 'setProcentajMedic' => 40,
                'setMedic' => $this->getReference('userLupu'),
                'setServiciu' => $this->getReference('evaluarePsihologica'),
                'setOwner' => $this->getReference('ownerLupu')
            ]
        );

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            UserFixtures::class,
        ];
    }
}
