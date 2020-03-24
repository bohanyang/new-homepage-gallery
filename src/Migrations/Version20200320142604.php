<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200320142604 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'oracle', 'Migration can only be executed safely on \'oracle\'.');

        $this->addSql('CREATE TABLE archives (id RAW(12) NOT NULL, market NUMBER(5) NOT NULL, date_ DATE NOT NULL, image_id RAW(12) NOT NULL, description CLOB NOT NULL, link VARCHAR2(2083) DEFAULT NULL NULL, hotspots BLOB DEFAULT NULL NULL, messages BLOB DEFAULT NULL NULL, coverstory BLOB DEFAULT NULL NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E262EC3999ACA5FD ON archives (date_)');
        $this->addSql('CREATE INDEX IDX_E262EC393DA5256D ON archives (image_id)');
        $this->addSql('COMMENT ON COLUMN archives.date_ IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE images (id RAW(12) NOT NULL, name VARCHAR2(255) NOT NULL UNIQUE, first_appeared DATE DEFAULT NULL NULL, last_appeared DATE DEFAULT NULL NULL, urlbase VARCHAR2(255) NOT NULL, copyright VARCHAR2(255) NOT NULL, wp NUMBER(1) NOT NULL, vid BLOB DEFAULT NULL NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN images.first_appeared IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN images.last_appeared IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'oracle', 'Migration can only be executed safely on \'oracle\'.');

        $this->addSql('DROP TABLE archives');
        $this->addSql('DROP TABLE images');
    }
}
