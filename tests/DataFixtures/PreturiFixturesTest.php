<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\PreturiFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Preturi;
use App\Services\FixturesEntityFactory;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PreturiFixturesTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        $this->hasher = self::getContainer()->get('security.password_hasher');
    }

    /**
     * @covers \App\DataFixtures\PreturiFixtures::__construct
     * @covers \App\DataFixtures\PreturiFixtures::load
     * @covers \App\DataFixtures\PreturiFixtures::getDependencies
     */
    public function testItLoadsPreturiFixtures(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $em->beginTransaction();
        $conn = $em->getConnection();

        try {
            $referenceRepo = new ReferenceRepository($em);

            $appFixtures = new AppFixtures(new FixturesEntityFactory());
            $appFixtures->setReferenceRepository($referenceRepo);
            $appFixtures->load($em);

            $userFixtures = new UserFixtures($this->hasher, new FixturesEntityFactory());
            $userFixtures->setReferenceRepository($referenceRepo);
            $userFixtures->load($em);

            $preturiFixtures = new PreturiFixtures(new FixturesEntityFactory());
            $preturiFixtures->setReferenceRepository($referenceRepo);
            $preturiFixtures->load($em);

            $em->flush();
            $em->clear();

            $pret = $em->getRepository(Preturi::class)->findOneBy([
                'procentajMedic' => '50',
            ]);

            self::assertNotNull($pret, 'Pret should be in the database.');

            $this->assertSame(
                [AppFixtures::class, UserFixtures::class],
                $preturiFixtures->getDependencies()
            );
        } finally {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $em->clear();
        }
    }
}
