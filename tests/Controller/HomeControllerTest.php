<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class HomeControllerTest extends WebTestCase
{
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

    private function loginUser(KernelBrowser $client): void
    {
        $user = $this->getUser();
        $client->loginUser($user);
    }

    public function testPrivacyPolicyPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $this->loginUser($client);
        $client->request('GET', '/bg/home/privacy');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Политика за поверителност');
    }

    public function testContactPageLoadsForm(): void
    {
        $client = static::createClient();
        $this->loginUser($client);
        $client->request('GET', '/bg/home/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // форма трябва да присъства
        $this->assertSelectorTextContains('button[type=submit]', 'Изпрати'); // бутонът
    }

    public function testContactFormSubmission(): void
    {
        $client = static::createClient();
        $this->loginUser($client);
        $crawler = $client->request('GET', '/bg/home/contact');

        $form = $crawler->selectButton('Изпрати')->form([
            'contact_form[name]' => 'Тестов Потребител',
            'contact_form[email]' => 'user@example.com',
            'contact_form[message]' => 'Това е тестово съобщение.',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects(); // очакваме пренасочване след успешна заявка
        $client->followRedirect();

        $this->assertSelectorExists('#flash-data');
    }
}
