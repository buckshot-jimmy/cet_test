<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201028201845 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE medici_trimitatori (id INT AUTO_INCREMENT NOT NULL, nume VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE consultatii ADD ahc VARCHAR(500) DEFAULT NULL, ADD app VARCHAR(500) DEFAULT NULL, CHANGE zile_cm zile_cm INT DEFAULT NULL COMMENT \'nr. zile c.m.\', CHANGE certif_cm certif_cm VARCHAR(20) DEFAULT NULL COMMENT \'certif. c.m.\', CHANGE medic_trimitator medic_trimitator VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE owner CHANGE denumire denumire VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE pacienti ADD telefon2 VARCHAR(20) DEFAULT NULL, ADD email VARCHAR(50) DEFAULT NULL, ADD localitate VARCHAR(50) DEFAULT NULL, ADD observatii VARCHAR(500) DEFAULT NULL, CHANGE judet judet VARCHAR(25) DEFAULT NULL, CHANGE loc_munca loc_munca VARCHAR(50) DEFAULT NULL, CHANGE ocupatie ocupatie VARCHAR(50) DEFAULT NULL, CHANGE data_inreg data_inreg DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE role_id role_id INT DEFAULT NULL, CHANGE specialitate_id specialitate_id INT DEFAULT NULL, CHANGE titulatura_id titulatura_id INT DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE cod_parafa cod_parafa VARCHAR(25) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE medici_trimitatori');
        $this->addSql('ALTER TABLE consultatii DROP ahc, DROP app, CHANGE zile_cm zile_cm INT DEFAULT NULL COMMENT \'nr. zile c.m.\', CHANGE certif_cm certif_cm VARCHAR(20) CHARACTER SET utf8 DEFAULT \'\'\'NULL\'\'\' COLLATE `utf8_general_ci` COMMENT \'certif. c.m.\', CHANGE medic_trimitator medic_trimitator VARCHAR(30) CHARACTER SET utf8 DEFAULT \'\'\'NULL\'\'\' COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE owner CHANGE denumire denumire VARCHAR(30) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE pacienti DROP telefon2, DROP email, DROP localitate, DROP observatii, CHANGE judet judet VARCHAR(25) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE loc_munca loc_munca VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE ocupatie ocupatie VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE data_inreg data_inreg DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE users CHANGE role_id role_id INT DEFAULT NULL, CHANGE specialitate_id specialitate_id INT DEFAULT NULL, CHANGE titulatura_id titulatura_id INT DEFAULT NULL, CHANGE email email VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE cod_parafa cod_parafa VARCHAR(25) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`');
    }
}
