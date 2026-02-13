<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213190227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CDAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA157E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE equipe_user ADD CONSTRAINT FK_84DA47B76D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_user ADD CONSTRAINT FK_84DA47B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_tournoi ADD CONSTRAINT FK_C0AFC5636D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_tournoi ADD CONSTRAINT FK_C0AFC563F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inscription_tournoi ADD CONSTRAINT FK_317E2F33F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE inscription_tournoi ADD CONSTRAINT FK_317E2F336D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_game ADD CONSTRAINT FK_424480FE4265900C FOREIGN KEY (equipe1_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_game ADD CONSTRAINT FK_424480FE50D03FE2 FOREIGN KEY (equipe2_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_game ADD CONSTRAINT FK_424480FEF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DF8C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7A76ED395');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25274584665A');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CDAE07E97');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA157E3C61F9');
        $this->addSql('ALTER TABLE equipe_tournoi DROP FOREIGN KEY FK_C0AFC5636D861B89');
        $this->addSql('ALTER TABLE equipe_tournoi DROP FOREIGN KEY FK_C0AFC563F607770A');
        $this->addSql('ALTER TABLE equipe_user DROP FOREIGN KEY FK_84DA47B76D861B89');
        $this->addSql('ALTER TABLE equipe_user DROP FOREIGN KEY FK_84DA47B7A76ED395');
        $this->addSql('ALTER TABLE inscription_tournoi DROP FOREIGN KEY FK_317E2F33F607770A');
        $this->addSql('ALTER TABLE inscription_tournoi DROP FOREIGN KEY FK_317E2F336D861B89');
        $this->addSql('ALTER TABLE match_game DROP FOREIGN KEY FK_424480FE4265900C');
        $this->addSql('ALTER TABLE match_game DROP FOREIGN KEY FK_424480FE50D03FE2');
        $this->addSql('ALTER TABLE match_game DROP FOREIGN KEY FK_424480FEF607770A');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DF8C9E392E');
    }
}
