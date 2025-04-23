<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;
    private array $emails;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, array $emails) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->emails = $emails;
    }

    public function sendEmail(string $to, string $subject, string $textBody, string $fromKey = 'contact'): bool
    {
        $emails = $this->emails;

        if (!isset($emails[$fromKey])) {
            $fromKey = 'contact';
        }

        try {
            $email = (new Email())
                ->from($emails[$fromKey])
                ->to($to)
                ->subject($subject)
                ->text($textBody);

            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Plain email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendTemplatedEmail(string $to, string $subject, string $htmlTemplate, array $context = [], string $fromKey = 'no_reply'): bool
    {
        $emails = $this->emails;

        if (!isset($emails[$fromKey])) {
            $fromKey = 'no_reply';
        }

        try {
            $email = (new TemplatedEmail())
                ->from($emails[$fromKey])
                ->to($to)
                ->subject($subject)
                ->htmlTemplate($htmlTemplate)
                ->context($context);

            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Templated email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}