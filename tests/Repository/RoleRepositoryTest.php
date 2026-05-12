<?php

namespace App\Tests\Repository;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoleRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();

        $this->repo = new RoleRepository($registry);

        $this->em->getConnection()->beginTransaction();

        $this->role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($this->role);

        $this->em->flush();
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->em) && null !== $this->em) {
                $conn = $this->em->getConnection();

                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                $this->em->clear();
                $this->em->close();
            }
        } finally {
            $this->em = null;
            $this->repo = null;

            self::ensureKernelShutdown();
            parent::tearDown();
        }
    }

    /**
     * @covers \App\Repository\RoleRepository::__construct
     */
    public function testCanBuildRoleRepository()
    {
        $this->assertInstanceOf(RoleRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\RoleRepository::getAllRoles
     */
    public function testItCanGetAllRolesWithException()
    {
        $this->expectException(\Exception::class);

        $registry = $this->createMock(ManagerRegistry::class);

        $repoMock = $this->getMockBuilder(RoleRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getAllRoles();
    }

    /**
     * @covers \App\Repository\RoleRepository::getAllRoles
     */
    public function testItCanGetAllRolesWithSuccess()
    {
       $result = $this->repo->getAllRoles();

       $this->assertSame(substr($this->role->getDenumire(), 5, 20), end($result)['denumire']);
    }
}
