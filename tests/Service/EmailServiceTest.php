<?php

namespace App\Tests\Service;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailServiceTest extends KernelTestCase
{
    private $emailService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->emailService = self::getContainer()->get(EmailService::class);
    }

    public function testSendEmail()
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->emailService->setMailer($mailerMock); // Инжектираме mock в EmailService

        $result = $this->emailService->sendEmail(
            'test@example.com',
            'Test Subject',
            'emails/test_email.html.twig',
            'value',
            'no-reply'
        );

        $this->assertTrue($result);
    }

    public function testSendTemplatedEmail()
    {
        // Създаване на mock за Symfony\Component\Mailer\MailerInterface
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->emailService->setMailer($mailerMock); // Инжектираме mock в EmailService

        $result = $this->emailService->sendTemplatedEmail(
            'test@example.com',
            'Test Subject',
            'emails/test_email.html.twig',
            ['test_key' => 'value'],
            'no-reply'
        );

        $this->assertTrue($result);
    }
}