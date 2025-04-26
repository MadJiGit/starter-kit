<?php

namespace App\Tests\Controller;

use App\Tests\AbstractDatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserControllerTest extends AbstractDatabaseTestCase
{
    protected KernelBrowser $client;

    public function testUserDashboard()
    {
        // Use the shared getOrCreateUser method
        $this->client->loginUser($this->getOrCreateUser('testuser@example.com'));

        $this->client->request('GET', '/en/user/me');

        $this->assertResponseIsSuccessful();
    }

    public function testUserProfilePageLoadsSuccessfully(): void
    {

        $this->client->loginUser($this->getOrCreateUser('testuser@example.com'));

        $this->client->request('GET', '/bg/user/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Заменяш с нещо специфично за твоя шаблон
    }

    public function testEditProfilePostRequest(): void
    {
        $user = $this->getOrCreateUser('testuser@example.com');
        $this->client->loginUser($user);

        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $this->client->request('GET', $urlGenerator->generate('user_edit_profile', ['_locale' => 'bg']));

        $form = $crawler->selectButton('Изпрати')->form([
            'user_profile[username]' => 'newusername',
            'user_profile[oldPassword]' => 'password123', // текущата парола
            'user_profile[newPassword][first]' => 'password123',
            'user_profile[newPassword][second]' => 'password123',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/bg/login');

        $crawler = $this->client->followRedirect();

        // Вземаме съдържанието на data-messages атрибута
        $flashDiv = $crawler->filter('#flash-data')->attr('data-messages');

        // Декодираме JSON-а от атрибута
        $messages = json_decode($flashDiv, true);

        // Проверка дали съобщението съществува
        $this->assertNotEmpty($messages, 'Flash messages should not be empty.');

        $successMessageFound = false;

        foreach ($messages as $message) {
            if ('success' === $message['type'] && str_contains($message['message'], 'Профилът ви беше обновен')) {
                $successMessageFound = true;
                break;
            }
        }

        $this->assertTrue($successMessageFound, 'Expected success flash message was not found.');
    }
}
