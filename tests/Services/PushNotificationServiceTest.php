<?php

namespace App\Tests\Services;

use App\Services\PushNotificationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;

class PushNotificationServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $this->mercure = $this->createMock(HubInterface::class);

        $this->service = new PushNotificationService($this->mercure);
    }

    /**
     * @covers \App\Services\PushNotificationService::__construct
     */
    public function testItCanBuildPushNotificationService()
    {
        $this->assertInstanceOf(PushNotificationService::class, $this->service);
    }

    /**
     * @covers \App\Services\PushNotificationService::pushNotificationToMercure
     */
    public function testPushNotificationWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(2001);
        $this->expectExceptionMessage('Successful operation but failed to publish notification');

        $this->mercure->method('publish')
            ->willThrowException(
                new \Exception('Successful operation but failed to publish notification', 2001));

        $this->service->pushNotificationToMercure('event_name');
    }

    /**
     * @covers \App\Services\PushNotificationService::pushNotificationToMercure
     */
    public function testPushNotificationWithSuccess()
    {
        $this->mercure->method('publish')->willReturn('test_push');

        $result = $this->service->pushNotificationToMercure('event_name');

        $this->assertSame($result, 'test_push');
    }
}
