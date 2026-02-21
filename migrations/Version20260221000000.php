<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OAuth fields to User table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD google_oauth_id VARCHAR(255) DEFAULT NULL UNIQUE');
        $this->addSql('ALTER TABLE `user` ADD oauth_provider VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN google_oauth_id');
        $this->addSql('ALTER TABLE `user` DROP COLUMN oauth_provider');
    }
}
