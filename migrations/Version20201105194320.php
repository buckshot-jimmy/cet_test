<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201105194320 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE consultatii ADD platita_colaborator TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'0-neplatita,1-platita\', CHANGE zile_cm zile_cm INT DEFAULT NULL COMMENT \'nr. zile c.m.\', CHANGE certif_cm certif_cm VARCHAR(20) DEFAULT NULL COMMENT \'certif. c.m.\', CHANGE medic_trimitator medic_trimitator VARCHAR(30) DEFAULT NULL, CHANGE ahc ahc VARCHAR(500) DEFAULT NULL, CHANGE app app VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE pacienti CHANGE judet judet VARCHAR(50) DEFAULT NULL, CHANGE loc_munca loc_munca VARCHAR(50) DEFAULT NULL, CHANGE ocupatie ocupatie VARCHAR(50) DEFAULT NULL, CHANGE data_inreg data_inreg DATETIME DEFAULT NULL, CHANGE telefon2 telefon2 VARCHAR(20) DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE localitate localitate VARCHAR(50) DEFAULT NULL, CHANGE observatii observatii VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE role_id role_id INT DEFAULT NULL, CHANGE specialitate_id specialitate_id INT DEFAULT NULL, CHANGE titulatura_id titulatura_id INT DEFAULT NULL, CHANGE email email VARCHAR(50) DEFAULT NULL, CHANGE cod_parafa cod_parafa VARCHAR(25) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE consultatii DROP platita_colaborator, CHANGE ahc ahc VARCHAR(500) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE app app VARCHAR(500) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE zile_cm zile_cm INT DEFAULT NULL COMMENT \'nr. zile c.m.\', CHANGE certif_cm certif_cm VARCHAR(20) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci` COMMENT \'certif. c.m.\', CHANGE medic_trimitator medic_trimitator VARCHAR(30) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE pacienti CHANGE telefon2 telefon2 VARCHAR(20) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE email email VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE judet judet VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE localitate localitate VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE loc_munca loc_munca VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE ocupatie ocupatie VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE data_inreg data_inreg DATETIME DEFAULT \'NULL\', CHANGE observatii observatii VARCHAR(500) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE users CHANGE role_id role_id INT DEFAULT NULL, CHANGE specialitate_id specialitate_id INT DEFAULT NULL, CHANGE titulatura_id titulatura_id INT DEFAULT NULL, CHANGE email email VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`, CHANGE cod_parafa cod_parafa VARCHAR(25) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_general_ci`');
    }
}
