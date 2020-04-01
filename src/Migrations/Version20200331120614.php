<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200331120614 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE records (id BLOB NOT NULL, market SMALLINT UNSIGNED NOT NULL, date_ DATE NOT NULL --(DC2Type:date_immutable)
        , image_id BLOB NOT NULL, description CLOB NOT NULL, link VARCHAR(2083) DEFAULT NULL, hotspots BLOB DEFAULT NULL, messages BLOB DEFAULT NULL, coverstory BLOB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9C9D584699ACA5FD ON records (date_)');
        $this->addSql('CREATE INDEX IDX_9C9D58463DA5256D ON records (image_id)');
        $this->addSql('CREATE INDEX IDX_9C9D58466BAC85CB99ACA5FD ON records (market, date_)');
        $this->addSql('CREATE TABLE images (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, first_appeared_on DATE NOT NULL --(DC2Type:date_immutable)
        , last_appeared_on DATE NOT NULL --(DC2Type:date_immutable)
        , urlbase VARCHAR(255) NOT NULL, copyright VARCHAR(255) NOT NULL, wp BOOLEAN NOT NULL, vid BLOB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E01FBE6A5E237E06 ON images (name)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE records');
        $this->addSql('DROP TABLE images');
    }
}
