<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\RequestSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestSubscriberTest extends TestCase
{
    public function buildSubscriberWithSession(?Session $session): RequestSubscriber
    {
        $requestStack = new RequestStack();

        $currentRequest = Request::create('http://current-request');
        if ($session !== null) {
            $currentRequest->setSession($session);
        }

        $requestStack->push($currentRequest);

        return new RequestSubscriber($requestStack);
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::__construct
     */
    public function testCanBuildSubscriber(): void
    {
        $this->assertInstanceOf(
            RequestSubscriber::class,
            $this->buildSubscriberWithSession(new Session(new MockArraySessionStorage()))
        );
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::onKernelRequest
     */
    public function testSavesTargetPathOnMainNonAjaxNonLoginRequestWithSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('http://cet_test');
        $request->setSession($session);
        $request->attributes->set('_route', 'some_route');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = $this->buildSubscriberWithSession($session);
        $subscriber->onKernelRequest($event);

        $this->assertSame(
            'http://cet_test/',
            $session->get('_security.main.target_path')
        );
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::onKernelRequest
     */
    public function testDoesNotSaveTargetPathForAjaxRequest(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('http://cet_test');
        $request->setSession($session);
        $request->attributes->set('_route', 'some_route');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = $this->buildSubscriberWithSession($session);
        $subscriber->onKernelRequest($event);

        $this->assertFalse($session->has('_security.main.target_path'));
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::onKernelRequest
     */
    public function testDoesNotSaveTargetPathOnLoginRoute(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('http://cet_test/login');
        $request->setSession($session);
        $request->attributes->set('_route', 'app_login');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = $this->buildSubscriberWithSession($session);
        $subscriber->onKernelRequest($event);

        $this->assertFalse($session->has('_security.main.target_path'));
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::onKernelRequest
     */
    public function testDoesNotSaveTargetPathOnSubRequest(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('http://cet_test');
        $request->setSession($session);
        $request->attributes->set('_route', 'some_route');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $subscriber = $this->buildSubscriberWithSession($session);
        $subscriber->onKernelRequest($event);

        $this->assertFalse($session->has('_security.main.target_path'));
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::onKernelRequest
     */
    public function testDoesNotSaveTargetPathWhenRequestHasNoSession(): void
    {
        $session = null;
        $request = Request::create('http://cet_test');
        $request->attributes->set('_route', 'some_route');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = $this->buildSubscriberWithSession($session);
        $subscriber->onKernelRequest($event);

        $this->assertTrue(true);
    }

    /**
     * @covers \App\EventSubscriber\RequestSubscriber::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $events = RequestSubscriber::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame(['onKernelRequest'], $events[KernelEvents::REQUEST]);
    }
}
