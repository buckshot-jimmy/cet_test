<?php

namespace App\DataFixtures;

use App\Services\FixturesEntityFactory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends AbstractFixture
{
    public function __construct(private FixturesEntityFactory $entityFactory) {}

    public function load(ObjectManager $manager)
    {
        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Role',
            ['denumire' => 'ROLE_Administrator'],
            ['setDenumire' => 'ROLE_Administrator']
        );
        $this->addReference('roleAdmin', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Role',
            ['denumire' => 'ROLE_Medic'],
            ['setDenumire' => 'ROLE_Medic']
        );
        $this->addReference('roleMedic', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Role',
            ['denumire' => 'ROLE_Psiholog'],
            ['setDenumire' => 'ROLE_Psiholog']
        );
        $this->addReference('rolePsiholog', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Role',
            ['denumire' => 'ROLE_Asistent'],
            ['setDenumire' => 'ROLE_Asistent']
        );
        $this->addReference('roleAsistent', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Role',
            ['denumire' => 'ROLE_Receptioner'],
            ['setDenumire' => 'ROLE_Receptioner']
        );
        $this->addReference('roleReceptie', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Role',
            ['denumire' => 'ROLE_Test'],
            ['setDenumire' => 'ROLE_Test']
        );
        $this->addReference('roleTest', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Titulatura',
            ['denumire' => 'Medic rezident'],
            ['setDenumire' => 'Medic rezident']
        );
        $this->addReference('rezident', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Titulatura',
            ['denumire' => 'Medic specialist'],
            ['setDenumire' => 'Medic specialist']
        );
        $this->addReference('specialist', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Titulatura',
            ['denumire' => 'Psiholog clinician principal'],
            ['setDenumire' => 'Psiholog clinician principal']
        );
        $this->addReference('psihologPrincipal', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Titulatura',
            ['denumire' => 'Medic primar'],
            ['setDenumire' => 'Medic primar']
        );
        $this->addReference('primar', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Specialitate',
            ['denumire' => 'Neurologie'],
            ['setDenumire' => 'Neurologie']
        );
        $this->addReference('neurolog', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Specialitate',
            ['denumire' => 'Neurologie pediatrica'],
            ['setDenumire' => 'Neurologie pediatrica']
        );
        $this->addReference('neurologPediatru', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Specialitate',
            ['denumire' => 'Psihiatrie'],
            ['setDenumire' => 'Psihiatrie']
        );
        $this->addReference('psihiatru', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Specialitate',
            ['denumire' => 'Psihologie'],
            ['setDenumire' => 'Psihologie']
        );
        $this->addReference('psiholog', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Owner',
            ['denumire' => 'Neuro Review S.R.L.'],
            ['setDenumire' => 'Neuro Review S.R.L.', 'setCui' => '42475870', 'setSters' => false]
        );
        $this->addReference('ownerNeuro', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Owner',
            ['denumire' => 'CASU A. MIHAI - MEDIC SPECIALIST PSIHIATRIE'],
            ['setDenumire' => 'CASU A. MIHAI - MEDIC SPECIALIST PSIHIATRIE', 'setCui' => '32830479',
                'setSters' => false]
        );
        $this->addReference('ownerCasu', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Owner',
            ['denumire' => 'CABINET INDIVIDUAL PSIHOLOGIE LUPU MIHAELA ADRIANA'],
            ['setDenumire' => 'CABINET INDIVIDUAL PSIHOLOGIE LUPU MIHAELA ADRIANA', 'setCui' => '23779776',
                'setSters' => false]
        );
        $this->addReference('ownerLupu', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Servicii',
            ['denumire' => 'Consult general'],
            ['setDenumire' => 'Consult general', 'setTip' => 0, 'setSters' => false]
        );
        $this->addReference('consultGeneral', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Servicii',
            ['denumire' => 'Consult special'],
            ['setDenumire' => 'Consult special', 'setTip' => 0, 'setSters' => false]
        );
        $this->addReference('consultSpecial', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Servicii',
            ['denumire' => 'Investigatie speciala'],
            ['setDenumire' => 'Investigatie speciala', 'setTip' => 1, 'setSters' => false]
        );
        $this->addReference('investigatieSpeciala', $entity);

        $entity = $this->entityFactory->ensureEntity(
            $manager, 'App\Entity\Servicii',
            ['denumire' => 'Evaluare psihologica'],
            ['setDenumire' => 'Evaluare psihologica', 'setTip' => 2, 'setSters' => false]
        );
        $this->addReference('evaluarePsihologica', $entity);

        $manager->flush();
    }
}