<?php

namespace App\Tests\EventListener;

use App\Entity\Consultatii;
use App\Entity\MediciTrimitatori;
use App\Entity\RapoarteColaboratori;
use App\EventListener\PlatesteColaboratorListener;
use App\EventListener\SaveConsultatieListener;
use App\Repository\ConsultatiiRepository;
use App\Repository\MediciTrimitatoriRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SaveConsultatieListenerTest extends TestCase
{
    public function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @covers \App\EventListener\SaveConsultatieListener::__construct
     */
    public function testCanBuildListener()
    {
        $listener = new SaveConsultatieListener($this->em);
        $this->assertInstanceOf(SaveConsultatieListener::class, $listener);
    }

    /**
     * @covers \App\EventListener\SaveConsultatieListener::postUpdate
     */
    public function testReturnsTrueAndDoesNothingWhenMedicTrimitatorIsMissing(): void
    {
        $consultatie = $this->createMock(Consultatii::class);
        $consultatie->method('getMedicTrimitator')->willReturn(null);

        $this->em->expects($this->never())->method('getRepository');
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $listener = new SaveConsultatieListener($this->em);

        $this->assertTrue($listener->postUpdate($consultatie));
    }

    /**
     * @covers \App\EventListener\SaveConsultatieListener::postUpdate
     */
    public function testReturnsTrueAndDoesNothingWhenMedicTrimitatorAlreadyExists(): void
    {
        $consultatie = $this->createMock(Consultatii::class);
        $consultatie->method('getMedicTrimitator')->willReturn('exista');

        $repo = $this->createMock(MediciTrimitatoriRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['nume' => 'exista'])
            ->willReturn($this->createMock(MediciTrimitatori::class));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediciTrimitatori::class)
            ->willReturn($repo);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $listener = new SaveConsultatieListener($this->em);

        $this->assertTrue($listener->postUpdate($consultatie));
    }

    /**
     * @covers \App\EventListener\SaveConsultatieListener::postUpdate
     */
    public function testCreatesMedicTrimitatorWhenMissingInDatabase(): void
    {
        $consultatie = $this->createMock(Consultatii::class);
        $consultatie->method('getMedicTrimitator')->willReturn('Dr New');

        $repo = $this->createMock(MediciTrimitatoriRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['nume' => 'Dr New'])
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(MediciTrimitatori::class)
            ->willReturn($repo);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(MediciTrimitatori::class));

        $this->em->expects($this->once())
            ->method('flush');

        $listener = new SaveConsultatieListener($this->em);

        $this->assertTrue($listener->postUpdate($consultatie));
    }
}
