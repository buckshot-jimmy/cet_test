<?php

namespace App\Tests\EventListener;

use App\Entity\Consultatie;
use App\Entity\MedicTrimitator;
use App\EventListener\ConsultatieListener;
use App\Repository\MedicTrimitatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ConsultatieListenerTest extends TestCase
{
    public function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @covers \App\EventListener\ConsultatieListener::__construct
     */
    public function testCanBuildListener()
    {
        $listener = new ConsultatieListener($this->em);
        $this->assertInstanceOf(ConsultatieListener::class, $listener);
    }

    /**
     * @covers \App\EventListener\ConsultatieListener::postUpdate
     */
    public function testReturnsTrueAndDoesNothingWhenMedicTrimitatorIsMissing(): void
    {
        $consultatie = $this->createMock(Consultatie::class);
        $consultatie->method('getMedicTrimitator')->willReturn(null);

        $this->em->expects($this->never())->method('getRepository');
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $listener = new ConsultatieListener($this->em);

        $this->assertTrue($listener->postUpdate($consultatie));
    }

    /**
     * @covers \App\EventListener\ConsultatieListener::postUpdate
     */
    public function testReturnsTrueAndDoesNothingWhenMedicTrimitatorAlreadyExists(): void
    {
        $consultatie = $this->createMock(Consultatie::class);
        $consultatie->method('getMedicTrimitator')->willReturn('exista');

        $repo = $this->createMock(MedicTrimitatorRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['nume' => 'exista'])
            ->willReturn($this->createMock(MedicTrimitator::class));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MedicTrimitator::class)
            ->willReturn($repo);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $listener = new ConsultatieListener($this->em);

        $this->assertTrue($listener->postUpdate($consultatie));
    }

    /**
     * @covers \App\EventListener\ConsultatieListener::postUpdate
     */
    public function testCreatesMedicTrimitatorWhenMissingInDatabase(): void
    {
        $consultatie = $this->createMock(Consultatie::class);
        $consultatie->method('getMedicTrimitator')->willReturn('Dr New');

        $repo = $this->createMock(MedicTrimitatorRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['nume' => 'Dr New'])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MedicTrimitator::class)
            ->willReturn($repo);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(MedicTrimitator::class));

        $this->em->expects($this->once())
            ->method('flush');

        $listener = new ConsultatieListener($this->em);

        $this->assertTrue($listener->postUpdate($consultatie));
    }
}
