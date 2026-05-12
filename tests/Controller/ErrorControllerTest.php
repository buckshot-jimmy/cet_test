<?php

namespace App\Tests\Controller;

use App\Controller\ErrorController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErrorControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->controller = new ErrorController($this->translator);
    }

    /**
     * @covers \App\Controller\ErrorController::__construct
     */
    public function testCanBuildController()
    {
        $controller = new ErrorController($this->translator);

        $this->assertInstanceOf(ErrorController::class, $controller);
    }

    /**
     * @dataProvider dataProviderException
     */
    public function testAjaxRequestReturnsJsonResponse(string $message, int $code, int $responseCode): void
    {
        $this->translator->method('trans')
            ->with($message)
            ->willReturn($message);

        $exception = new \Exception(message: $message, code: $code);

        $request = new Request();
        $request->attributes->set('exception', $exception);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->controller->show($request);

        $data = json_decode($response->getContent(), true);

        $this->assertSame($responseCode, $data['status_code']);
        $this->assertSame($message, $data['message']);
    }

    public function testWithNonAjaxRequest()
    {
        $client = static::createClient();

        $client->request('GET', '/non_existing_route');

        $response = $client->getResponse();

        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('A aparut o eroare.', $response);
    }

    public function dataProviderException()
    {
        yield ['Test error', 4041, Response::HTTP_NOT_FOUND];
        yield ['An error occured', 9009, Response::HTTP_BAD_REQUEST];
        yield ['Internal Server Error', 5001, Response::HTTP_INTERNAL_SERVER_ERROR];
        yield ['Bad Request', 4001, Response::HTTP_BAD_REQUEST];
        yield ['Created', 2001, Response::HTTP_CREATED];
        yield ['Conflict', 4009, Response::HTTP_CONFLICT];
    }
}
