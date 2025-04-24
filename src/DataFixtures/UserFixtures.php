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

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('superadmin');
        $user->setEmail('superadmin@gmail.com');
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, '1234567890'));
        $user->setIsActive(true);
        $user->setRegisteredAt();

        $manager->persist($user);
        $manager->flush();
    }
}
