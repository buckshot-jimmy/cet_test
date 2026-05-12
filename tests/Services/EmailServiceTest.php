<?php

namespace App\Tests\Services;

use App\Repository\UserRepository;
use App\Services\EmailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EmailServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->service = new EmailService(
            $this->mailer,
            $this->twig,
            $this->translator
        );

        $this->testUser = static::getContainer()
            ->get(UserRepository::class)
            ->findOneByEmail('ciprianmarta.cm@gmail.com');
    }

    /**
     * @covers \App\Services\EmailService::__construct
     */
    public function testCanBuildService()
    {
        $this->assertInstanceOf(EmailService::class, $this->service);
    }

    /**
     * @covers \App\Services\EmailService::sendEmail
     */
    public function testSendEmailWithException()
    {
        $this->twig->method('render')
            ->willReturn('<html lang="en">Email</html>');

        $this->translator->method('trans')->willReturn('Failed sending email');

        $this->mailer->expects($this->once())->method('send')
            ->willThrowException(new \Exception('Failed sending email'));

        $response = $this->service->sendEmail([
            'recipient' => 'test@test.com', 'subject' => 'subject', 'template' => 't', 'templateParams' => ['a' => 'b']
        ]);

        $this->assertEquals('Failed sending email', $response['message']);
    }

    /**
     * @covers \App\Services\EmailService::sendEmail
     */
    public function testSendEmailSuccess()
    {
        $this->translator->method('trans')->willReturn('Email sent');

        $att = [
            'doc' => 'doc',
            'name' => 'name',
            'mime' => 'application/pdf'
        ];

        $response = $this->service->sendEmail([
            'recipient' => 'test@test.com', 'subject' => 'subject', 'template' => 't', 'templateParams' => ['a' => 'b'],
            'att' => $att
        ]);

        $this->assertEquals('Email sent', $response['message']);
    }
}
