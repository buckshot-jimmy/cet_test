<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201019182850 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE consultatii (id INT AUTO_INCREMENT NOT NULL, pret_id INT NOT NULL, pacient_id INT NOT NULL, diagnostic VARCHAR(500) NOT NULL, consultatie VARCHAR(500) NOT NULL, tratament VARCHAR(500) NOT NULL, nr_inreg VARCHAR(10) NOT NULL, data_consultatie DATETIME NOT NULL, tarif INT NOT NULL, loc VARCHAR(1) NOT NULL COMMENT \'C-cabinet,D-domiciliu\', zile_cm INT DEFAULT NULL COMMENT \'nr. zile c.m.\', certif_cm VARCHAR(20) DEFAULT NULL COMMENT \'certif. c.m.\', inchisa TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-deschisa,1-inchisa\', stearsa TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-nestearsa,1-stearsa\', incasata TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-neincasata,1-incasata\', medic_trimitator VARCHAR(30) DEFAULT NULL, INDEX IDX_A924FD371B61704B (pret_id), INDEX IDX_A924FD371DF7AA4B (pacient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE owner (id INT AUTO_INCREMENT NOT NULL, denumire VARCHAR(30) NOT NULL, cui VARCHAR(10) NOT NULL, sters TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-nesters,1-sters\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pacienti (id INT AUTO_INCREMENT NOT NULL, nume VARCHAR(50) NOT NULL, prenume VARCHAR(50) NOT NULL, cnp BIGINT NOT NULL, telefon VARCHAR(20) NOT NULL, adresa VARCHAR(250) NOT NULL, judet VARCHAR(25) DEFAULT NULL, tara VARCHAR(30) NOT NULL, sex VARCHAR(1) NOT NULL, data_nasterii DATE NOT NULL, loc_munca VARCHAR(50) DEFAULT NULL, ocupatie VARCHAR(50) DEFAULT NULL, data_inreg DATETIME DEFAULT NULL, sters TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-nesters,1-sters\', UNIQUE INDEX UNIQ_CF7DE5751EAB9B7E (cnp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE preturi (id INT AUTO_INCREMENT NOT NULL, medic_id INT NOT NULL, serviciu_id INT NOT NULL, owner_id INT NOT NULL, pret INT NOT NULL, procentaj_medic INT NOT NULL, sters TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-nesters,1-sters\', INDEX IDX_F93C045A409615FE (medic_id), INDEX IDX_F93C045A4E6E141C (serviciu_id), INDEX IDX_F93C045A7E3C61F9 (owner_id), UNIQUE INDEX medic_serviciu (medic_id, serviciu_id, owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, denumire VARCHAR(25) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE servicii (id INT AUTO_INCREMENT NOT NULL, denumire VARCHAR(50) NOT NULL, tip SMALLINT NOT NULL COMMENT \'0-consultatie,1-investigatie\', sters TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-nesters,1-sters\', UNIQUE INDEX UNIQ_22E5571F455A422D (denumire), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialitati (id INT AUTO_INCREMENT NOT NULL, denumire VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE titulaturi (id INT AUTO_INCREMENT NOT NULL, denumire VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, role_id INT DEFAULT NULL, specialitate_id INT DEFAULT NULL, titulatura_id INT DEFAULT NULL, username VARCHAR(25) NOT NULL, password VARCHAR(100) NOT NULL, nume VARCHAR(25) NOT NULL, prenume VARCHAR(25) NOT NULL, email VARCHAR(25) DEFAULT NULL, telefon VARCHAR(20) NOT NULL, cod_parafa VARCHAR(25) DEFAULT NULL, sters TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-nesters,1-sters\', UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), INDEX IDX_1483A5E9D60322AC (role_id), INDEX IDX_1483A5E944E7ACCA (specialitate_id), INDEX IDX_1483A5E9E20FEC90 (titulatura_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE consultatii ADD CONSTRAINT FK_A924FD371B61704B FOREIGN KEY (pret_id) REFERENCES preturi (id)');
        $this->addSql('ALTER TABLE consultatii ADD CONSTRAINT FK_A924FD371DF7AA4B FOREIGN KEY (pacient_id) REFERENCES pacienti (id)');
        $this->addSql('ALTER TABLE preturi ADD CONSTRAINT FK_F93C045A409615FE FOREIGN KEY (medic_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE preturi ADD CONSTRAINT FK_F93C045A4E6E141C FOREIGN KEY (serviciu_id) REFERENCES servicii (id)');
        $this->addSql('ALTER TABLE preturi ADD CONSTRAINT FK_F93C045A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E944E7ACCA FOREIGN KEY (specialitate_id) REFERENCES specialitati (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9E20FEC90 FOREIGN KEY (titulatura_id) REFERENCES titulaturi (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE preturi DROP FOREIGN KEY FK_F93C045A7E3C61F9');
        $this->addSql('ALTER TABLE consultatii DROP FOREIGN KEY FK_A924FD371DF7AA4B');
        $this->addSql('ALTER TABLE consultatii DROP FOREIGN KEY FK_A924FD371B61704B');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9D60322AC');
        $this->addSql('ALTER TABLE preturi DROP FOREIGN KEY FK_F93C045A4E6E141C');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E944E7ACCA');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9E20FEC90');
        $this->addSql('ALTER TABLE preturi DROP FOREIGN KEY FK_F93C045A409615FE');
        $this->addSql('DROP TABLE consultatii');
        $this->addSql('DROP TABLE owner');
        $this->addSql('DROP TABLE pacienti');
        $this->addSql('DROP TABLE preturi');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE servicii');
        $this->addSql('DROP TABLE specialitati');
        $this->addSql('DROP TABLE titulaturi');
        $this->addSql('DROP TABLE users');
    }
}
