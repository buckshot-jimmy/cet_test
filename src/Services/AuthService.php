<?php

namespace App\Services;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Twig\Environment;

class AuthService implements AuthCodeMailerInterface
{
    private const RESET_PASSWORD_TOKEN_SESSION_KEY = 'reset-password-token';

    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UserPasswordHasherInterface $passwordHasher,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $em,
        private CodeGeneratorInterface $codeGenerator,
    ) {}

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $email = (new Email())
            ->from('noreply@mindreset.ro')
            ->to($user->getEmailAuthRecipient())
            ->subject('Codul tau de autentificare - MIND RESET')
            ->html($this->twig->render('emails/2fa_email.html.twig', [
                'code' => $user->getEmailAuthCode(),
            ]));

        $this->mailer->send($email);
    }

    public function processRequestReset(SessionInterface $session, $user, $password, $token)
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->resetPasswordHelper->removeResetRequest($token);
        $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
        $this->em->flush();

        $session->remove('_security.main.target_path');
    }

    public function generateResetToken($user): ?ResetPasswordToken
    {
        try {
            return $this->resetPasswordHelper->generateResetToken($user);
        } catch (TooManyPasswordRequestsException $e) {
            return null;
        }
    }

    public function validateResetTokenAndGetUser($token)
    {
        try {
            return $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function resendTwoFactorCode(TwoFactorInterface $user): bool
    {
        try {
            $this->codeGenerator->reSend($user);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeResetToken($email)
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        $resetToken = $this->em->getRepository(ResetPasswordRequest::class)->findOneBy(['user' => $user->getId()]);

        if ($resetToken) {
            $this->em->remove($resetToken);
            $this->em->flush();
        }
    }
}
