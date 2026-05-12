<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323081532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultatii ADD programare_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE consultatii ADD CONSTRAINT FK_A924FD3770D14A8A FOREIGN KEY (programare_id) REFERENCES programari (id)');
        $this->addSql('CREATE INDEX IDX_A924FD3770D14A8A ON consultatii (programare_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultatii DROP FOREIGN KEY FK_A924FD3770D14A8A');
        $this->addSql('DROP INDEX IDX_A924FD3770D14A8A ON consultatii');
        $this->addSql('ALTER TABLE consultatii DROP programare_id');
    }
}
