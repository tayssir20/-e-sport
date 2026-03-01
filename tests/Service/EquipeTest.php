<?php

namespace App\Tests\Service;

use App\Entity\Equipe;
use App\Entity\User;
use App\Service\EquipeManager;
use PHPUnit\Framework\TestCase;

class EquipeTest extends TestCase
{
    private function createValidEquipe(): Equipe
    {
        $owner = new User();

        $equipe = new Equipe();
        $equipe->setNom('Team Alpha');
        $equipe->setOwner($owner);
        $equipe->setMaxMembers(5);

        return $equipe;
    }

    public function testValidEquipe(): void
    {
        $manager = new EquipeManager();
        $result = $manager->validate($this->createValidEquipe());
        $this->assertTrue($result);
    }

    public function testEquipeAvecNomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom de l'équipe ne peut pas être vide");

        $equipe = $this->createValidEquipe();
        $equipe->setNom('');
        (new EquipeManager())->validate($equipe);
    }

    public function testEquipeSansProprietaire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Une équipe doit avoir un propriétaire');

        $equipe = $this->createValidEquipe();
        $equipe->setOwner(null);
        (new EquipeManager())->validate($equipe);
    }

    public function testEquipeAvecNomTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne peut pas dépasser 100 caractères');

        $equipe = $this->createValidEquipe();
        $equipe->setNom(str_repeat('A', 101));
        (new EquipeManager())->validate($equipe);
    }

    public function testEquipeAvecMaxMembersInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre maximum de membres doit être entre 1 et 100');

        $equipe = $this->createValidEquipe();
        $equipe->setMaxMembers(0);
        (new EquipeManager())->validate($equipe);
    }
}
