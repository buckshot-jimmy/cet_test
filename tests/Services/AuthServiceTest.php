<?php

namespace App\Tests\Services;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Twig\Environment;

class AuthServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->resetHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->codeGenerator = $this->createMock(CodeGenerator::class);
        $this->twoFactor = $this->createMock(TwoFactorInterface::class);

        $this->service = new AuthService(
            $this->mailer,
            $this->twig,
            $this->hasher,
            $this->resetHelper,
            $this->em,
            $this->codeGenerator
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');
    }

    /**
     * @covers \App\Services\AuthService::__construct
     */
    public function testCanBuildService()
    {
        $this->assertInstanceOf(AuthService::class, $this->service);
    }

    /**
     * @covers \App\Services\AuthService::sendAuthCode
     */
    public function testSendEmailSuccess()
    {
        self::bootKernel();

        $container = static::getContainer();

        $service = $container->get(AuthService::class);

        $user = $this->createMock(TwoFactorInterface::class);
        $user->method('getEmailAuthRecipient')->willReturn($this->testUser->getEmail());
        $user->method('getEmailAuthCode')->willReturn('123456');

        $service->sendAuthCode($user);

        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();

        $this->assertStringContainsString('123456', $email->getHtmlBody());
    }

    /**
     * @covers \App\Services\AuthService::processRequestReset
     */
    public function testItCanProcessResetTwoFactor()
    {
        $user = new User();
        $user->setPassword('Admin_1');
        $token = 'valid-reset-token';

        $this->hasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'Admin_1')
            ->willReturn('hashed-password');

        $this->resetHelper
            ->expects($this->once())
            ->method('removeResetRequest')
            ->with($token);

        $this->em
            ->expects($this->once())
            ->method('flush');

        $session = $this->createMock(SessionInterface::class);
        $expectedRemovedKeys = ['reset-password-token', '_security.main.target_path'];
        $removeCallIndex = 0;
        $session
            ->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function (string $key) use (&$removeCallIndex, $expectedRemovedKeys): void {
                $this->assertSame($expectedRemovedKeys[$removeCallIndex], $key);
                ++$removeCallIndex;
            });

        $this->service->processRequestReset($session, $user, 'Admin_1', $token);

        $this->assertSame('hashed-password', $user->getPassword());
    }

    /**
     * @covers \App\Services\AuthService::generateResetToken
     */
    public function testgenerateResetTokenReturnsTokenWhenHelperSucceeds(): void
    {
        $user = $this->createMock(User::class);
        $token = new ResetPasswordToken('selector.verifier', new \DateTimeImmutable('+1 hour'), time());

        $this->resetHelper
            ->expects($this->once())
            ->method('generateResetToken')
            ->with($user)
            ->willReturn($token);

        $this->assertSame($token, $this->service->generateResetToken($user));
    }

    /**
     * @covers \App\Services\AuthService::generateResetToken
     */
    public function testgenerateResetTokenReturnsNullWhenTooManyRequests(): void
    {
        $user = $this->createMock(User::class);

        $this->resetHelper
            ->expects($this->once())
            ->method('generateResetToken')
            ->with($user)
            ->willThrowException(new TooManyPasswordRequestsException(new \DateTimeImmutable('+1 hour')));

        $this->assertNull($this->service->generateResetToken($user));
    }

    /**
     * @covers \App\Services\AuthService::validateResetTokenAndGetUser
     */
    public function testValidateResetTokenAndGetUserReturnsUserWhenHelperSucceeds(): void
    {
        $token = 'valid-token';
        $user = $this->createMock(User::class);

        $this->resetHelper
            ->expects($this->once())
            ->method('validateTokenAndFetchUser')
            ->with($token)
            ->willReturn($user);

        $this->assertSame($user, $this->service->validateResetTokenAndGetUser($token));
    }

    /**
     * @covers \App\Services\AuthService::validateResetTokenAndGetUser
     */
    public function testValidateResetTokenAndGetUserReturnsNullWhenHelperThrowsException(): void
    {
        $token = 'invalid-token';

        $this->resetHelper
            ->expects($this->once())
            ->method('validateTokenAndFetchUser')
            ->with($token)
            ->willThrowException(new \Exception('invalid token'));

        $this->assertNull($this->service->validateResetTokenAndGetUser($token));
    }

    /**
     * @covers \App\Services\AuthService::resendTwoFactorCode
     */
    public function testItCanResendTwoFactorCodeSuccess(): void
    {
        $this->codeGenerator
            ->expects($this->once())
            ->method('reSend')
            ->with($this->twoFactor);

        $result = $this->service->resendTwoFactorCode($this->twoFactor);

        $this->assertTrue($result);
    }

    /**
     * @covers \App\Services\AuthService::resendTwoFactorCode
     */
    public function testItCanResendTwoFactorCodeThrowsException(): void
    {
        $this->codeGenerator
            ->expects($this->once())
            ->method('reSend')
            ->willThrowException(new \Exception());

        $result = $this->service->resendTwoFactorCode($this->twoFactor);

        $this->assertFalse($result);
    }

    public function testRemoveResetToken()
    {
        $email = 'test@example.com';

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $resetToken = $this->createMock(ResetPasswordRequest::class);

        $userRepository = $this->createMock(EntityRepository::class);
        $tokenRepository = $this->createMock(EntityRepository::class);

        $userRepository
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $tokenRepository
            ->method('findOneBy')
            ->with(['user' => 1])
            ->willReturn($resetToken);

        $this->em->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [ResetPasswordRequest::class, $tokenRepository],
            ]);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($resetToken);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->removeResetToken($email);
    }
}
