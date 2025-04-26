<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\AbstractDatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RegistrationControllerTest extends AbstractDatabaseTestCase
{
    protected KernelBrowser $client;

    public function testGetRegisterFormRenders(): void
    {
        $crawler = $this->client->request('GET', '/en/register/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[method="post"]');
        $this->assertSelectorTextContains('h1, h2, h3', 'Registration');
    }

    public function testPostRegisterWithEmptyData(): void
    {
        $this->client->request('POST', '/en/register/new', [
            'email' => '',
            'username' => '',
            'password' => '',
        ]);

        $this->assertResponseRedirects('/en/register/new');
        $crawler = $this->client->followRedirect();

        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');
        $this->assertSame('danger', $messages[0]['type']);
    }

    public function testPostRegisterWithInvalidEmail(): void
    {
        $this->client->request('POST', '/en/register/new', [
            'email' => 'not-an-email',
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertResponseRedirects('/en/register/new');
        $crawler = $this->client->followRedirect();

        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');
        $this->assertSame('danger', $messages[0]['type']);
    }

    public function testPostRegisterWithShortPassword(): void
    {
        $this->client->request('POST', '/en/register/new', [
            'email' => 'shortpass@example.com',
            'username' => 'shortuser',
            'password' => '123',
        ]);

        $this->assertResponseRedirects('/en/register/new');
        $crawler = $this->client->followRedirect();

        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');
        $this->assertSame('danger', $messages[0]['type']);
    }

    public function testPostRegisterWithExistingEmail(): void
    {

        $email = 'existing@example.com';
        $this->getOrCreateUser($email, 'existing_user');

        $this->client->request('POST', '/en/register/new', [
            'email' => $email,
            'username' => 'existing',
            'password' => 'password123',
        ]);

        $this->assertResponseRedirects('/en/register/new');
        $crawler = $this->client->followRedirect();

        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');
        $this->assertSame('danger', $messages[0]['type']);
    }

    public function testPostRegisterWithExistingUsername(): void
    {
        // Create a user with the username
        $username = 'duplicate_user';
        $email = 'newuser@example.com';
        $this->getOrCreateUser($email, $username);

        $this->client->request('POST', '/en/register/new', [
            'email' => $email,
            'username' => $username,
            'password' => 'password123',
        ]);

        $this->assertResponseRedirects('/en/register/new');
        $crawler = $this->client->followRedirect();

        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');
        $this->assertSame('danger', $messages[0]['type']);
    }

    public function testSuccessfulRegistrationRedirectsToLogin(): void
    {
        $this->client->request('POST', '/en/register/new', [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'securepassword',
        ]);

        $this->assertResponseRedirects('/en/login');
        $crawler = $this->client->followRedirect();

        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');
        $messages = json_decode($flashDiv, true);

        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');
        $this->assertSame('success', $messages[0]['type']);
    }
}
