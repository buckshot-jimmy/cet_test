<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\PreturiFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Services\FixturesEntityFactory;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserFixturesTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
        $this->hasher = self::getContainer()->get('security.password_hasher');
    }

    /**
     * @covers \App\DataFixtures\UserFixtures::__construct
     * @covers \App\DataFixtures\UserFixtures::load
     * @covers \App\DataFixtures\UserFixtures::getDependencies
     */
    public function testItLoadsAppUserFixtures(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $em->beginTransaction();
        $conn = $em->getConnection();

        try {
            $referenceRepo = new ReferenceRepository($em);

            $appFixtures = new AppFixtures(new FixturesEntityFactory());
            $appFixtures->setReferenceRepository($referenceRepo);
            $appFixtures->load($em);

            $fixtures = new UserFixtures($this->hasher, new FixturesEntityFactory());
            $fixtures->setReferenceRepository($referenceRepo);
            $fixtures->load($em);

            $em->flush();
            $em->clear();

            $user = $em->getRepository(User::class)->findOneBy([
                'username' => 'test',
            ]);

            self::assertNotNull($user, 'User "test" should be in the database.');

            $this->assertSame(
                [AppFixtures::class],
                $fixtures->getDependencies()
            );
        } finally {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $em->clear();
        }
    }
}
