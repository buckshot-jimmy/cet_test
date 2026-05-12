<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409075814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pacienti ADD ci VARCHAR(10) DEFAULT NULL, ADD ci_eliberat VARCHAR(50) DEFAULT NULL, DROP sex, DROP data_nasterii');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pacienti ADD sex VARCHAR(1) NOT NULL, ADD data_nasterii DATE NOT NULL, DROP ci, DROP ci_eliberat');
    }
}
