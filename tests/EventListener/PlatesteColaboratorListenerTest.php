<?php

namespace App\Tests\EventListener;

use App\Entity\Consultatii;
use App\Entity\RapoarteColaboratori;
use App\EventListener\PlatesteColaboratorListener;
use App\Repository\ConsultatiiRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PlatesteColaboratorListenerTest extends TestCase
{
    public function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @covers \App\EventListener\PlatesteColaboratorListener::__construct
     */
    public function testCanBuildListener()
    {
        $listener = new PlatesteColaboratorListener($this->em);
        $this->assertInstanceOf(PlatesteColaboratorListener::class, $listener);
    }

    /**
     * @covers \App\EventListener\PlatesteColaboratorListener::postUpdate
     */
    public function testCanHandlePostUpdate()
    {
        $raportMock = $this->createMock(RapoarteColaboratori::class);

        $consRepo = $this->createMock(ConsultatiiRepository::class);
        $this->em->method('getRepository')->willReturn($consRepo);

        $cons1 = $this->createMock(Consultatii::class);
        $cons2 = $this->createMock(Consultatii::class);

        $consRepo->expects($this->once())->method('getConsultatiiRaportColaborator')
            ->with($raportMock, false)->willReturn([
                ['id' => 10],
                ['id' => 20],
            ]);

        $consRepo->expects($this->exactly(2))
            ->method('find')
            ->with($this->logicalOr($this->equalTo(10), $this->equalTo(20)))
            ->willReturnCallback(static function (int $id) use ($cons1, $cons2) {
                return match ($id) {
                    10 => $cons1,
                    20 => $cons2,
                    default => null,
                };
            });

        $this->em->expects($this->exactly(3))
            ->method('getRepository')
            ->with(Consultatii::class)
            ->willReturn($consRepo);

        $cons1->expects($this->once())
            ->method('setPlatitaColaborator')
            ->with(true);
        $cons2->expects($this->once())
            ->method('setPlatitaColaborator')
            ->with(true);

        $this->em->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr($this->identicalTo($cons1), $this->identicalTo($cons2)));

        $this->em->expects($this->exactly(2))
            ->method('flush');

        $listener = new PlatesteColaboratorListener($this->em);

        $result = $listener->postUpdate($raportMock);

        $this->assertTrue($result);
    }
}
