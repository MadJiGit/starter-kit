<?php

namespace App\Tests\Controller;

use App\Service\EmailService;
use App\Tests\AbstractDatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class SecurityControllerTest extends AbstractDatabaseTestCase
{
    protected KernelBrowser $client;

    public function testLoginPageRenders(): void
    {
        $this->client->request('GET', '/en/logout');

        // Ensure the user is logged out
        $crawler = $this->client->request('GET', '/en/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[method="post"]');
        $this->assertSelectorTextContains('h1, h2, h3', 'Login'); // Add more specific checks for the template
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/en/login');

        $form = $crawler->selectButton('Login')->form([
            '_email' => 'wrong@example.com',
            '_password' => 'incorrectpassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/en/login');
        $crawler = $this->client->followRedirect();

        $this->showFlashMessages('danger', 'Invalid credentials.', $crawler);
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->client->loginUser($this->getOrCreateUser('test_validuser@example.com'));
        $this->client->request('GET', '/en/logout');
        $crawler = $this->client->request('GET', '/en/login');

        $form = $crawler->selectButton('Login')->form([
            '_email' => 'test_validuser@example.com',
            '_password' => 'password123',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/en/user/me');
        $crawler = $this->client->followRedirect();
        $this->assertSelectorTextContains('h1, h2, h3', 'Dashboard'); // or whatever page you expect after login
    }

    public function testForgottenPasswordForm(): void
    {
        $crawler = $this->client->request('GET', '/en/forgotten_pass');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[method="post"]');
        $this->assertSelectorTextContains('h1, h2, h3', "Click to reset your password");
    }

    public function testForgottenPasswordWithInvalidEmail(): void
    {
        $this->client->loginUser($this->getOrCreateUser('testuser@example.com'));
        $crawler = $this->client->request('GET', '/en/forgotten_pass');

        $form = $crawler->selectButton('Submit')->form([
            'forgotten_password[email]' => 'invalid@example.com',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/en/forgotten_pass');
        $crawler = $this->client->followRedirect();

        $this->showFlashMessages('danger', 'Email address not found.', $crawler);
//        $this->assertSelectorTextContains('.flash-error', 'Email address not found.');
    }

    public function testForgottenPasswordEmailSent(): void
    {
        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())
            ->method('sendTemplatedEmail')
            ->with(
                $this->equalTo('test@example.com'),
                $this->stringContains('Reset your password'),
                $this->stringContains('confirmation_reset_password.html.twig')
            );

        $this->client->getContainer()->set(EmailService::class, $emailService);

        $this->client->loginUser($this->getOrCreateUser('testuser@example.com'));
        $crawler = $this->client->request('GET', '/bg/forgotten_pass');

        $form = $crawler->selectButton('Submit')->form([
            'forgotten_password[email]' => 'test@example.com',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/bg/login');
    }

    public function testNewPasswordPage(): void
    {
        $user = $this->getOrCreateUser('testuser@example.com', 'validpassword123');
        $token = 'valid-token';

        $crawler = $this->client->request('GET', "/bg/new_password?token=$token");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="reset_password"]');

        // Now, submit the form with the new password and confirmation
        $form = $crawler->selectButton('Submit')->form([
            'reset_password[plainPassword][first]' => 'newpassword123',
            'reset_password[plainPassword][second]' => 'newpassword123',
        ]);

        $this->client->submit($form);

        // After submitting, assert that the user is redirected to the login page
        $this->assertResponseRedirects('/en/login');
        $this->client->followRedirect();

        // Ensure the flash success message appears
//        $this->assertSelectorTextContains('.flash-success', 'Your password has been changed successfully.');
        $this->showFlashMessages('success', 'Your password has been changed successfully.', $crawler);
    }

    public function testConfirmNewPassword(): void
    {
        $this->client->loginUser($this->getOrCreateUser('testuser@example.com'));
        $token = 'valid-token';
        $this->client->request('GET', "/bg/confirm_new_password?token=$token");

        $crawler = $this->client->submitForm('Submit', [
            'reset_password[plainPassword]' => 'newpassword123',
        ]);

        $this->assertResponseRedirects('/en/login');
        $crawler = $this->client->followRedirect();
        //        $this->assertSelectorTextContains('.flash-success', 'Your password has been changed successfully.');
        $this->showFlashMessages('success', 'Your password has been changed successfully.', $crawler);
//        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
//        $messages = json_decode($flashDiv, true);
//
//        $this->assertNotEmpty($messages, 'Паролата ви беше променена успешно. Можете да влезете с новата парола.');
//        $this->assertSame('success', $messages[0]['type']);
    }

    public function testLogout(): void
    {
        $this->client->loginUser($this->getOrCreateUser('testuser@example.com'));
        $this->client->request('GET', '/en/logout');

        $this->assertResponseRedirects('/en/login');
    }

    private function showFlashMessages(string $type, string $message, $crawler)
    {
        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, $message);
        $this->assertSame($type, $messages[0]['type']);
    }
}
