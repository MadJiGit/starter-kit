<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AbstractDatabaseTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::ensureKernelShutdown(); // 🧹 make sure old kernel is cleaned
        $this->client = static::createClient();         // ✅ boot kernel in a clean way

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Temporarily remove all event listeners to suppress deprecation warnings
        $eventManager = $entityManager->getEventManager();
        $refObject = new \ReflectionObject($eventManager);
        if ($refObject->hasProperty('listeners')) {
            $prop = $refObject->getProperty('listeners');
            $prop->setAccessible(true);
            $listeners = $prop->getValue($eventManager);
            foreach ($listeners as $event => $eventListeners) {
                foreach ($eventListeners as $listener) {
                    if (is_array($listener) && isset($listener[0])) {
                        $eventManager->removeEventListener([$event], $listener[0]);
                    } elseif (is_object($listener) || is_string($listener)) {
                        $eventManager->removeEventListener([$event], $listener);
                    }
                }
            }
        }

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    protected function getOrCreateUser(string $email = 'test@example.com', ?string $username = null): User
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username ?? explode('@', $email)[0]);
            $encoder = $container->get(UserPasswordHasherInterface::class);
            $user->setPassword($encoder->hashPassword($user, 'password123'));
//            $user->setPassword('dummy_hash'); // You can update this later
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(true);

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $user;
    }
}
