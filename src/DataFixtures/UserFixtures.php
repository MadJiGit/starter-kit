<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    private function createUser(string $username, string $email, array $roles, string $plainPassword): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setIsActive(true);
        $user->setRegisteredAt();

        return $user;
    }

    public function load(ObjectManager $manager): void
    {
//        $user = $this->createUser('superadmin', 'superadmin@gmail.com', ['ROLE_SUPER_ADMIN'], '1234567890');
//        $manager->persist($user);

        $regularUser = $this->createUser('testuser', 'testuser@example.com', ['ROLE_USER'], 'password123');
        $manager->persist($regularUser);

        $manager->flush();
    }
}
