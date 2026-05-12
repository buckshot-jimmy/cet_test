<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421154151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE factura_consultatie (id INT AUTO_INCREMENT NOT NULL, factura_id INT NOT NULL, consultatie_id INT NOT NULL, valoare INT NOT NULL, INDEX IDX_67AC007CF04F795F (factura_id), INDEX IDX_67AC007C90A2F215 (consultatie_id), UNIQUE INDEX factura_consultatie (factura_id, consultatie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE facturi (id INT AUTO_INCREMENT NOT NULL, pacient_id INT DEFAULT NULL, client_pj_id INT DEFAULT NULL, stornare_id INT DEFAULT NULL COMMENT \'stornata cu factura cu id sau storneaza factura cu id\', owner_id INT NOT NULL, serie VARCHAR(5) NOT NULL, numar INT NOT NULL, data DATE NOT NULL, scadenta DATE DEFAULT NULL, tip SMALLINT NOT NULL COMMENT \'0-normala,1-storno\', INDEX IDX_F730283B1DF7AA4B (pacient_id), INDEX IDX_F730283B51068020 (client_pj_id), UNIQUE INDEX UNIQ_F730283B90813B9F (stornare_id), INDEX IDX_F730283B7E3C61F9 (owner_id), UNIQUE INDEX factura_data (serie, numar, data), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE factura_consultatie ADD CONSTRAINT FK_67AC007CF04F795F FOREIGN KEY (factura_id) REFERENCES facturi (id)');
        $this->addSql('ALTER TABLE factura_consultatie ADD CONSTRAINT FK_67AC007C90A2F215 FOREIGN KEY (consultatie_id) REFERENCES consultatii (id)');
        $this->addSql('ALTER TABLE facturi ADD CONSTRAINT FK_F730283B1DF7AA4B FOREIGN KEY (pacient_id) REFERENCES pacienti (id)');
        $this->addSql('ALTER TABLE facturi ADD CONSTRAINT FK_F730283B51068020 FOREIGN KEY (client_pj_id) REFERENCES persoane_juridice (id)');
        $this->addSql('ALTER TABLE facturi ADD CONSTRAINT FK_F730283B90813B9F FOREIGN KEY (stornare_id) REFERENCES facturi (id)');
        $this->addSql('ALTER TABLE facturi ADD CONSTRAINT FK_F730283B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE factura_consultatie DROP FOREIGN KEY FK_67AC007CF04F795F');
        $this->addSql('ALTER TABLE factura_consultatie DROP FOREIGN KEY FK_67AC007C90A2F215');
        $this->addSql('ALTER TABLE facturi DROP FOREIGN KEY FK_F730283B1DF7AA4B');
        $this->addSql('ALTER TABLE facturi DROP FOREIGN KEY FK_F730283B51068020');
        $this->addSql('ALTER TABLE facturi DROP FOREIGN KEY FK_F730283B90813B9F');
        $this->addSql('ALTER TABLE facturi DROP FOREIGN KEY FK_F730283B7E3C61F9');
        $this->addSql('DROP TABLE factura_consultatie');
        $this->addSql('DROP TABLE facturi');
    }
}
