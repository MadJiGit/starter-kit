<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailService
{
    private MailerInterface $mailer;
    private ParameterBagInterface $params;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, ParameterBagInterface $params, LoggerInterface $logger) {
        $this->mailer = $mailer;
        $this->params = $params;
        $this->logger = $logger;
    }

    public function sendEmail(string $to, string $subject, string $htmlTemplate, array $context = [], string $fromKey = 'no_reply'): bool
    {
        $emails = $this->params->get('app.emails');

        if (!isset($emails[$fromKey])) {
//            throw new \InvalidArgumentException("Email key '$fromKey' not defined in parameters.");
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
            $this->logger->error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}