<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;
class UserManagerTest extends TestCase
{
    public function testValidUser()
    {
        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setNom('Sarra');
        $user->setPassword('password123');

        $manager = new UserManager();

        $this->assertTrue($manager->validate($user));
    }

    public function testInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->setEmail('email_invalide');
        $user->setNom('Sarra');
        $user->setPassword('password123');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testShortPassword()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setNom('Sarra');
        $user->setPassword('123');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testShortName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->setEmail('test@gmail.com');
        $user->setNom('Sa');
        $user->setPassword('password123');

        $manager = new UserManager();
        $manager->validate($user);
    }
}