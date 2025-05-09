<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    /** @var array<string, string> */
    private array $emails;
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    /**
     * @param array<string, string> $emails
     */
    public function __construct(MailerInterface $mailer, LoggerInterface $logger, array $emails)
    {
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
            $this->logger->error('Plain email sending failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
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
            $this->logger->error('Templated email sending failed: '.$e->getMessage());

            return false;
        }
    }

    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }
}
