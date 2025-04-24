<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserControllerTest extends WebTestCase
{
    public function testUserDashboard()
    {
        // Създаване на тестов клиент
        $client = static::createClient();

        // Логваме потребителя
        $client->loginUser($this->getUser());

        // Изпращаме GET заявка към /user/me
        $client->request('GET', '/en/user/me');

        // Проверяваме дали отговорът е успешен
        $this->assertResponseIsSuccessful();

        // Проверяваме дали съдържа заглавие "User Dashboard"
        //        $this->assertSelectorTextContains('h1', 'User Dashboard');
    }

    // Тук създаваме метода getUser, който ще връща потребителя за тестовете
    private function getUser(): User
    {
        $user = self::getContainer()
            ->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'testuser@example.com']);

        if (!$user) {
            throw new \RuntimeException('Test user not found in database.');
        }

        return $user;
    }

    public function testUserProfilePageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser());

        $client->request('GET', '/bg/user/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Заменяш с нещо специфично за твоя шаблон
    }

    public function testEditProfilePostRequest(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser());
        $urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $client->request('GET', $urlGenerator->generate('user_edit_profile', ['_locale' => 'bg']));

        $form = $crawler->selectButton('Изпрати')->form([
            'user_profile[username]' => 'newusername',
            'user_profile[oldPassword]' => 'password123', // текущата парола
            'user_profile[newPassword][first]' => 'password123',
            'user_profile[newPassword][second]' => 'password123',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/bg/login');

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

        $this->assertStringContainsString('Профилът ви беше обновен.', $client->getResponse()->getContent());
    }
}
