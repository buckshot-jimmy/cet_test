<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\Role;
use App\Services\FixturesEntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class AppFixturesTest extends KernelTestCase
{
    /**
     * @covers \App\DataFixtures\AppFixtures::__construct
     * @covers \App\DataFixtures\AppFixtures::load
     */
    public function testItLoadsAppDataFixtures(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        $em->beginTransaction();
        $conn = $em->getConnection();

        try {
            $fixtures = new AppFixtures(new FixturesEntityFactory());
            $fixtures->setReferenceRepository(new ReferenceRepository($em));
            $fixtures->load($em);

            $em->flush();
            $em->clear();

            $role = $em->getRepository(Role::class)->findOneBy([
                'denumire' => 'ROLE_Administrator',
            ]);

            self::assertNotNull($role, 'ROLE_Administrator should be in the database.');
        } finally {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $em->clear();
        }
    }
}
