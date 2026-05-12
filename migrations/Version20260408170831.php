<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408170831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE persoane_juridice (id INT AUTO_INCREMENT NOT NULL, denumire VARCHAR(100) NOT NULL, cui VARCHAR(10) NOT NULL, adresa VARCHAR(255) NOT NULL, sters TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'0-nesters,1-sters\', UNIQUE INDEX UNIQ_799D861C455A422D (denumire), UNIQUE INDEX UNIQ_799D861CD3F6F824 (cui), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE owner ADD adresa VARCHAR(255) NOT NULL, ADD cont_bancar VARCHAR(34) DEFAULT NULL, ADD banca VARCHAR(50) DEFAULT NULL, ADD serie_factura VARCHAR(10) NOT NULL, ADD reg_com VARCHAR(25) DEFAULT NULL, ADD capital_social INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE persoane_juridice');
        $this->addSql('ALTER TABLE owner DROP adresa, DROP cont_bancar, DROP banca, DROP serie_factura, DROP reg_com, DROP capital_social');
    }
}
