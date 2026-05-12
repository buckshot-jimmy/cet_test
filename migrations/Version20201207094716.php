<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201207094716 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pacienti ADD adaugat_de INT NULL');
        $this->addSql('ALTER TABLE pacienti ADD CONSTRAINT FK_CF7DE57570668C14 FOREIGN KEY (adaugat_de) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_CF7DE57570668C14 ON pacienti (adaugat_de)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pacienti DROP FOREIGN KEY FK_CF7DE57570668C14');
        $this->addSql('DROP INDEX IDX_CF7DE57570668C14 ON pacienti');
        $this->addSql('ALTER TABLE pacienti DROP adaugat_de');
    }
}
