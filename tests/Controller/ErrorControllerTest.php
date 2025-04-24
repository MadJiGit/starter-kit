<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ErrorControllerTest extends WebTestCase
{
    public function testShowErrorWithTranslatedMessage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/bg/error?message=error.unexpected');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('p', 'Възникна неочаквана грешка.'); // очакваме в шаблона да има такъв елемент
    }

    public function testShowErrorWithCustomMessage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/bg/error?message=Грешка%20при%20зареждане');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('p');
    }
}
