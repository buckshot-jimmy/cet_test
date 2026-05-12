<?php

namespace App\Tests\Security;

use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticatorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->authenticator = new LoginFormAuthenticator(
            $this->em,
            $this->urlGenerator,
            $this->csrfTokenManager,
            $this->hasher,
            $this->router
        );

        $this->token = $this->createMock(TokenInterface::class);
    }

    /**
     * @covers \App\Security\LoginFormAuthenticator::__construct
     */
    public function testCanBeConstructed()
    {
        $this->assertInstanceOf(LoginFormAuthenticator::class, $this->authenticator);
    }

    /**
     * @covers \App\Security\LoginFormAuthenticator::onAuthenticationSuccess
     */
    public function testOnAuthenticationReturnsNullWithTwoFactorToken()
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);

        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();
        $session->set('_security.main.target_path', '/admin');

        $request = $this->client->getRequest();
        $request->setSession($session);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertNull($response);
    }

    /**
     * @covers \App\Security\LoginFormAuthenticator::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccessRedirectsToTargetPath()
    {
        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();
        $session->set('_security.main.target_path', '/admin');

        $request = $this->client->getRequest();
        $request->setSession($session);

        $response = $this->authenticator->onAuthenticationSuccess($request, $this->token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin', $response->getTargetUrl());
    }

    /**
     * @covers \App\Security\LoginFormAuthenticator::onAuthenticationSuccess
     */
    public function testRedirectsToAdminWhenNoTargetPath()
    {
        $this->client->request('GET', '/');
        $session = $this->client->getRequest()->getSession();

        $request = $this->client->getRequest();
        $request->setSession($session);

        $this->router->method('generate')->with('admin')->willReturn('/admin');

        $response = $this->authenticator->onAuthenticationSuccess($request, $this->token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @covers \App\Security\LoginFormAuthenticator::authenticate
     */
    public function testAuthenticateReturnsPassport()
    {
        $auth = $this->authenticator->authenticate(new Request([], ['username' => 'user', 'password' => 'pass']));

        $this->assertInstanceOf(Passport::class, $auth);
    }
}
