<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200320115615 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mssql', 'Migration can only be executed safely on \'mssql\'.');

        $this->addSql('CREATE TABLE archives (id BINARY(12) NOT NULL, market SMALLINT NOT NULL, date_ DATE NOT NULL, image_id BINARY(12) NOT NULL, description NVARCHAR(MAX) NOT NULL, link NVARCHAR(2083), hotspots VARBINARY(MAX), messages VARBINARY(MAX), coverstory VARBINARY(MAX), PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E262EC3999ACA5FD ON archives (date_)');
        $this->addSql('CREATE INDEX IDX_E262EC393DA5256D ON archives (image_id)');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:date_immutable)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'archives\', N\'COLUMN\', date_');
        $this->addSql('CREATE TABLE images (id BINARY(12) NOT NULL, name NVARCHAR(255) NOT NULL UNIQUE, first_appeared DATE, last_appeared DATE, urlbase NVARCHAR(255) NOT NULL, copyright NVARCHAR(255) NOT NULL, wp BIT NOT NULL, vid VARBINARY(MAX), PRIMARY KEY (id))');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:date_immutable)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'images\', N\'COLUMN\', first_appeared');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:date_immutable)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'images\', N\'COLUMN\', last_appeared');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mssql', 'Migration can only be executed safely on \'mssql\'.');

        $this->addSql('DROP TABLE archives');
        $this->addSql('DROP TABLE images');
    }
}
