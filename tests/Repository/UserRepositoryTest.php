<?php

namespace App\Tests\Repository;

use App\Entity\Role;
use App\Entity\Specialitate;
use App\Entity\Titulatura;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\SpecialitateRepository;
use App\Repository\TitulaturaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $this->em = $registry->getManager();
        $this->hasher = $container->get(UserPasswordHasherInterface::class);

        $this->repo = new UserRepository($registry, $this->hasher, $this->em);

        $this->em->getConnection()->beginTransaction();

        $this->titulatura = (new Titulatura())->setDenumire('titulatura_test');
        $this->em->persist($this->titulatura);

        $this->specialitate = (new Specialitate())->setDenumire('test_specialitate');
        $this->em->persist($this->specialitate);

        $this->role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($this->role);

        $this->medic = (new User())
            ->setUsername('test_medic')
            ->setPassword('test_password_hash')
            ->setNume('Nume_Test')
            ->setPrenume('Prenume_Test')
            ->setTelefon('0700000000')
            ->setRole($this->role);
        $this->medic->setSters(false);
        $this->medic->setParolaSchimbata(false);
        $this->medic->setTitulatura($this->titulatura);
        $this->medic->setSpecialitate($this->specialitate);
        $this->medic->setCodParafa('A999090');
        $this->em->persist($this->medic);

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
     * @covers \App\Repository\UserRepository::__construct
     */
    public function testCanBuildRoleRepository()
    {
        $this->assertInstanceOf(UserRepository::class, $this->repo);
    }

    /**
     * @covers \App\Repository\UserRepository::getAllUsers
     */
    public function testItCanGetAllUsersWithException()
    {
        $this->expectException(\Exception::class);

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('innerJoin')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('andWhere')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $repoMock = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getAllUsers([]);
    }

    /**
     * @covers \App\Repository\UserRepository::getAllUsers
     * @covers \App\Repository\UserRepository::applyFilters
     * @covers \App\Repository\UserRepository::getTotalUsers
     * @covers \App\Repository\UserRepository::buildSort
     * @dataProvider dataProvider
     */
    public function testItCanGetAllUsersWithWithLoggedUserSuccess($filtedValue, $col)
    {
        $result = $this->repo->getAllUsers([
            'value' => $filtedValue,
            'start' => 0,
            'length' => 10,
            'sort' => ['column' => $col, 'dir' => 'DESC'],
            'loggedUserId' => $filtedValue,
        ]);

        $this->assertArrayHasKey('utilizatori', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\UserRepository::getAllUsers
     * @covers \App\Repository\UserRepository::applyFilters
     * @covers \App\Repository\UserRepository::buildSort
     * @dataProvider dataProvider
     */
    public function testItCanGetAllUsersWithWithoutLoggedUserSuccess($filtedValue)
    {
        $result = $this->repo->getAllUsers([
            'value' => $filtedValue,
            'start' => 0,
            'length' => 10,
            'sort' => ['column' => '1', 'dir' => 'DESC'],
            'loggedUserId' => null,
        ]);

        $this->assertArrayHasKey('utilizatori', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @covers \App\Repository\UserRepository::saveFirstTimeNewPassword
     */
    public function testItCanSaveFirstTimeNewPasswordWithException()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing ID');

        $this->repo->saveFirstTimeNewPassword(['user_id' => -10, 'password' => 'test']);
    }

    /**
     * @covers \App\Repository\UserRepository::saveFirstTimeNewPassword
     */
    public function testItCanSaveFirstTimeNewPasswordWithPersistException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $userMock = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry, $hasher, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [User::class, $userMock]
        ]);

        $userMock->method('find')->with($this->medic->getId())
            ->willReturn($this->medic);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $userMock->saveFirstTimeNewPassword(['user_id' => $this->medic->getId(), 'password' => 'test']);
    }

    /**
     * @covers \App\Repository\UserRepository::saveFirstTimeNewPassword
     */
    public function testItCanSaveFirstTimeNewPasswordWithPersistSUccess()
    {
        $role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($role);

        $user = new User();
        $user->setUsername('test_medic1')
            ->setPassword($this->hasher->hashPassword($user, 'abc'))
            ->setNume('Nume_Test1')
            ->setPrenume('Prenume_Test1')
            ->setTelefon('0700000000')
            ->setRole($role);
        $user->setSters(false);
        $user->setParolaSchimbata(false);

        $this->em->persist($user);
        $this->em->flush();

        $this->repo->saveFirstTimeNewPassword([
            'user_id' => $user->getId(),
            'password' => 'test123'
        ]);

        $this->assertTrue($this->hasher->isPasswordValid($user, 'test123'));
        $this->assertTrue($user->getParolaSchimbata());
    }

    /**
     * @covers \App\Repository\UserRepository::saveUser
     * @covers \App\Repository\UserRepository::saveProfile
     */
    public function testItCanSaveUserWithSaveProfileException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $roleMock = $this->getMockBuilder(RoleRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $userMock = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry, $hasher, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [User::class, $userMock],
            [Role::class, $roleMock]
        ]);

        $userMock->method('find')->with($this->medic->getId())->willReturn($this->medic);
        $roleMock->method('find')->with($this->role->getId())->willReturn($this->role);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $userMock->saveUser([
            'editUserId' => $this->medic->getId(),
            'loggedUserId' => $this->medic->getId(),
            'edit_profile_password' => 'test',
            'edit_profile_nume' => 'Nume_Test',
            'edit_profile_prenume' => 'Prenume_Test',
            'edit_profile_telefon' => '0700000000',
            'edit_profile_cod_parafa' => null,
            'edit_profile_email' => null,
            'edit_profile_titulatura' => null,
            'edit_profile_specialitate' => null,
            'edit_profile_username' => 'test_medic',
            'role' => $this->role->getId(),
            'edit_profile_sters' => false,
            'edit_profile_observatii' => null,
            'edit_profile_parola_schimbata' => false
        ]);
    }

    /**
     * @covers \App\Repository\UserRepository::saveUser
     * @covers \App\Repository\UserRepository::saveProfile
     */
    public function testItCanSaveUserWithSaveProfileSuccess()
    {
        $result = $this->repo->saveUser([
            'editUserId' => $this->medic->getId(),
            'loggedUserId' => $this->medic->getId(),
            'edit_profile_password' => 'test',
            'edit_profile_nume' => 'Nume_Test',
            'edit_profile_prenume' => 'Prenume_Test',
            'edit_profile_telefon' => '0700000000',
            'edit_profile_cod_parafa' => '32',
            'edit_profile_email' => 'a@b.cd',
            'edit_profile_username' => 'test_medic',
            'role' => $this->role->getId(),
            'edit_profile_titulatura' => $this->titulatura->getId(),
            'edit_profile_specialitate' => $this->specialitate->getId(),
        ]);

        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * @covers \App\Repository\UserRepository::saveUser
     */
    public function testItCanSaveUserWithPersistException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $titulatura = $this->getMockBuilder(TitulaturaRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $specialitate = $this->getMockBuilder(SpecialitateRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $roleMock = $this->getMockBuilder(RoleRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $userMock = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry, $hasher, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [User::class, $userMock],
            [Role::class, $roleMock],
            [Titulatura::class, $titulatura],
            [Specialitate::class, $specialitate],
        ]);

        $userMock->method('find')->with($this->medic->getId())->willReturn($this->medic);
        $roleMock->method('find')->with($this->role->getId())->willReturn($this->role);
        $titulatura->method('find')->with($this->titulatura->getId())->willReturn($this->titulatura);
        $specialitate->method('find')->with($this->specialitate->getId())->willReturn($this->specialitate);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $userMock->saveUser([
            'editUserId' => $this->medic->getId(),
            'loggedUserId' => -10,
            'password' => 'test',
            'nume' => 'Nume_Test',
            'prenume' => 'Prenume_Test',
            'username' => 'username_test',
            'telefon' => '0700000000',
            'rol' => $this->role->getId(),
            'titulatura' => $this->titulatura->getId(),
            'specialitate' => $this->specialitate->getId(),
            'parolaSchimbata' => 1,
        ]);
    }

    /**
     * @covers \App\Repository\UserRepository::saveUser
     */
    public function testItCanSaveUserWithPersistSuccess()
    {
        $user = $this->repo->saveUser([
            'editUserId' => $this->medic->getId(),
            'loggedUserId' => -10,
            'password' => 'test',
            'nume' => 'Nume_Test',
            'prenume' => 'Prenume_Test',
            'username' => 'username_test',
            'telefon' => '0700000000',
            'rol' => $this->role->getId(),
            'titulatura' => $this->titulatura->getId(),
            'specialitate' => $this->specialitate->getId(),
            'parolaSchimbata' => 1,
        ]);

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * @covers \App\Repository\UserRepository::getUser
     */
    public function testItCanGetUserWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data collection error');

        $this->repo->getUser(new User());
    }

    /**
     * @covers \App\Repository\UserRepository::getUser
     */
    public function testItCanGetUserWithUserSuccess()
    {
        $user = $this->repo->getUser($this->medic);

        $this->assertArrayHasKey('nume', $user);
        $this->assertSame('Nume_Test', $user['nume']);
    }

    /**
     * @covers \App\Repository\UserRepository::getUser
     */
    public function testItCanGetUserWithoutUserSuccess()
    {
        $user = $this->repo->getUser(-10);

        $this->assertEmpty($user);
    }

    /**
     * @covers \App\Repository\UserRepository::getAllMedici
     */
    public function testItCanGetAllMediciWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $repoMock = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry, $hasher, $em])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willThrowException(new \Exception('Data collection error'));

        $qbMock = $this->createMock(QueryBuilder::class);

        $qbMock->method('select')->willReturnSelf();
        $qbMock->method('innerJoin')->willReturnSelf();
        $qbMock->method('leftJoin')->willReturnSelf();
        $qbMock->method('where')->willReturnSelf();
        $qbMock->method('setParameters')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);

        $exprMock = $this->createMock(Expr::class);
        $exprMock->method('orX')->willReturn('OR_EXPRESSION');
        $qbMock->method('expr')->willReturn($exprMock);

        $repoMock->method('createQueryBuilder')->willReturn($qbMock);

        $repoMock->getAllMedici();
    }

    /**
     * @covers \App\Repository\UserRepository::getAllMedici
     */
    public function testItCanGetAllMediciWithSuccess()
    {
        $medici = $this->repo->getAllMedici();

        $this->assertIsArray($medici);
    }

    /**
     * @covers \App\Repository\UserRepository::deleteUser
     */
    public function testItCanDeleteUserWithException()
    {
        $this->expectException(\Exception::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $userMock = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry, $hasher, $em])
            ->onlyMethods(['find'])
            ->getMock();

        $em->method('getRepository')->willReturnMap([
            [User::class, $userMock]
        ]);

        $userMock->method('find')->with($this->medic->getId())->willReturn($this->medic);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush')->willThrowException(new \Exception());

        $userMock->deleteUser($this->medic->getId());
    }

    /**
     * @covers \App\Repository\UserRepository::deleteUser
     */
    public function testItCanDeleteUserWithSuccess()
    {
        $role = (new Role())->setDenumire('ROLE_TestMedic');
        $this->em->persist($role);

        $user = (new User())
            ->setUsername('test_medic2')
            ->setPassword('test_password_hash2')
            ->setNume('Nume_Test2')
            ->setPrenume('Prenume_Test2')
            ->setTelefon('0700000000')
            ->setRole($role);
        $user->setSters(false);
        $user->setParolaSchimbata(false);
        $this->em->persist($user);

        $this->em->persist($user);
        $this->em->flush();

        $this->repo->deleteUser($user->getId());

        $this->assertSame(true, $user->getSters());
    }

    protected function dataProvider()
    {
        return [
            ['Test', '1'],
            ['Test', '2'],
            ['Test', '3'],
            ['Test', '4'],
            ['Test', '5'],
            ['Test', ''],
            ['', '2']
        ];
    }
}
