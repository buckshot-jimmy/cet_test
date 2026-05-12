<?php

namespace App\Tests\Security;

use App\Security\AccessDeniedHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AccessDeniedHandlerTest extends KernelTestCase
{
    public function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->handler = new AccessDeniedHandler($this->translator, $this->twig);
    }

    /**
     * @covers \App\Security\AccessDeniedHandler::__construct
     */
    public function testItCanBuildHandler()
    {
        $this->assertInstanceOf(AccessDeniedHandler::class, $this->handler);
    }

    /**
     * @covers \App\Security\AccessDeniedHandler::handle
     */
    public function testHandleAjaxRequest()
    {
        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(true);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Access denied');

        $twig = $this->createMock(Environment::class);

        $handler = new AccessDeniedHandler($translator, $twig);

        $response = $handler->handle(
            $request,
            new AccessDeniedException()
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Access denied', $data['message']);
    }

    /**
     * @covers \App\Security\AccessDeniedHandler::handle
     */
    public function testHandleNormalRequest()
    {
        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(false);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Access denied');

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<html lang="en">error page</html>');

        $handler = new AccessDeniedHandler($translator, $twig);

        $response = $handler->handle(
            $request,
            new AccessDeniedException()
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('error page', $response->getContent());
    }
}
