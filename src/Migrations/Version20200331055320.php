<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200331055320 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE records (id BYTEA NOT NULL, market SMALLINT NOT NULL, date_ DATE NOT NULL, image_id BYTEA NOT NULL, description TEXT NOT NULL, link VARCHAR(2083) DEFAULT NULL, hotspots BYTEA DEFAULT NULL, messages BYTEA DEFAULT NULL, coverstory BYTEA DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9C9D584699ACA5FD ON records (date_)');
        $this->addSql('CREATE INDEX IDX_9C9D58463DA5256D ON records (image_id)');
        $this->addSql('COMMENT ON COLUMN records.date_ IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE images (id BYTEA NOT NULL, name VARCHAR(255) NOT NULL, first_appeared_on DATE DEFAULT NULL, last_appeared_on DATE DEFAULT NULL, urlbase VARCHAR(255) NOT NULL, copyright VARCHAR(255) NOT NULL, wp BOOLEAN NOT NULL, vid BYTEA DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E01FBE6A5E237E06 ON images (name)');
        $this->addSql('COMMENT ON COLUMN images.first_appeared_on IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN images.last_appeared_on IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE records');
        $this->addSql('DROP TABLE images');
    }
}
