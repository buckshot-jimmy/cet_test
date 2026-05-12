<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private TranslatorInterface $translator
    ) {}

    public function sendEmail($emailData)
    {
        $emailMessage = (new Email())
            ->from('noreply@mindreset.ro')
            ->to($emailData['recipient'])
            ->subject($emailData['subject'] . ' - MIND RESET')
            ->html($this->twig->render($emailData['template'],
                [
                    'token' => $emailData['token'] ?? null,
                    'params' => $emailData['templateParams'] ?? null
                ])
            );

        if (isset($emailData['att'])) {
            $emailMessage->attach($emailData['att']['doc'], $emailData['att']['name'], $emailData['att']['mime']);
        }

        try {
            $this->mailer->send($emailMessage);
        } catch (\Exception $e) {
            return ['message' => $this->translator->trans('Failed sending email'),
                'status' => Response::HTTP_BAD_REQUEST
            ];
        }

        return ['message' => $this->translator->trans('Email sent'), 'status' => Response::HTTP_OK];
    }
}