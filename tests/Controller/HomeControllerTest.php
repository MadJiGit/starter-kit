<?php

namespace App\Tests\Controller;

use App\Tests\AbstractDatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class HomeControllerTest extends AbstractDatabaseTestCase
{
    protected KernelBrowser $client;

    private function loginUser(KernelBrowser $client): void
    {
        $user = $this->getOrCreateUser('test@example.com', 'testuser');
        $client->loginUser($user);
    }

    public function testPrivacyPolicyPageLoadsSuccessfully(): void
    {

        $this->loginUser($this->client);
        $this->client->request('GET', '/bg/home/privacy');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Политика за поверителност');
    }

    public function testContactPageLoadsForm(): void
    {

        $this->loginUser($this->client);
        $this->client->request('GET', '/bg/home/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // форма трябва да присъства
        $this->assertSelectorTextContains('button[type=submit]', 'Изпрати'); // бутонът
    }

    public function testContactFormSubmission(): void
    {

        $this->loginUser($this->client);
        $crawler = $this->client->request('GET', '/bg/home/contact');

        $form = $crawler->selectButton('Изпрати')->form([
            'contact_form[name]' => 'Тестов Потребител',
            'contact_form[email]' => 'user@example.com',
            'contact_form[message]' => 'Това е тестово съобщение.',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects(); // очакваме пренасочване след успешна заявка
        $this->client->followRedirect();

        $this->assertSelectorExists('#flash-data');
    }
}
