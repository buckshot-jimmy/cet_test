<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321095321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE programari (id INT AUTO_INCREMENT NOT NULL, pacient_id INT NOT NULL, pret_id INT NOT NULL, adaugata_de INT NOT NULL, data DATE NOT NULL, ora TIME NOT NULL, anulata TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'1-anulata\', INDEX IDX_C7CA78B71DF7AA4B (pacient_id), INDEX IDX_C7CA78B71B61704B (pret_id), INDEX IDX_C7CA78B7D4A17C09 (adaugata_de), UNIQUE INDEX medic_programare (pret_id, data, ora), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE programari ADD CONSTRAINT FK_C7CA78B71DF7AA4B FOREIGN KEY (pacient_id) REFERENCES pacienti (id)');
        $this->addSql('ALTER TABLE programari ADD CONSTRAINT FK_C7CA78B71B61704B FOREIGN KEY (pret_id) REFERENCES preturi (id)');
        $this->addSql('ALTER TABLE programari ADD CONSTRAINT FK_C7CA78B7D4A17C09 FOREIGN KEY (adaugata_de) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE programari DROP FOREIGN KEY FK_C7CA78B71DF7AA4B');
        $this->addSql('ALTER TABLE programari DROP FOREIGN KEY FK_C7CA78B71B61704B');
        $this->addSql('ALTER TABLE programari DROP FOREIGN KEY FK_C7CA78B7D4A17C09');
        $this->addSql('DROP TABLE programari');
    }
}
