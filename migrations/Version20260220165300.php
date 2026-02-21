<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220165300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add 2FA fields to User table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` ADD google2fa_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD is_2fa_enabled TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP COLUMN google2fa_secret');
        $this->addSql('ALTER TABLE `user` DROP COLUMN is_2fa_enabled');
    }
}
