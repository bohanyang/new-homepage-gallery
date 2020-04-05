<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200405121459 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mssql', 'Migration can only be executed safely on \'mssql\'.');

        $this->addSql('CREATE TABLE records (id BINARY(12) NOT NULL, market TINYINT NOT NULL, date_ DATE NOT NULL, image_id BINARY(12) NOT NULL, description NVARCHAR(MAX) NOT NULL, link NVARCHAR(2083), hotspots VARBINARY(MAX), messages VARBINARY(MAX), coverstory VARBINARY(MAX), PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9C9D584699ACA5FD ON records (date_)');
        $this->addSql('CREATE INDEX IDX_9C9D58463DA5256D ON records (image_id)');
        $this->addSql('CREATE INDEX IDX_9C9D58466BAC85CB99ACA5FD ON records (market, date_)');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:object_id)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', id');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:market)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', market');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:app_date)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', date_');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:object_id)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', image_id');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:serialized_binary)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', hotspots');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:serialized_binary)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', messages');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:serialized_binary)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'records\', N\'COLUMN\', coverstory');
        $this->addSql('CREATE TABLE images (id BINARY(12) NOT NULL, name NVARCHAR(255) NOT NULL, first_appeared_on DATE NOT NULL, last_appeared_on DATE NOT NULL, urlbase NVARCHAR(255) NOT NULL, copyright NVARCHAR(255) NOT NULL, wp BIT NOT NULL, vid VARBINARY(MAX), PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E01FBE6A5E237E06 ON images (name) WHERE name IS NOT NULL');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:object_id)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'images\', N\'COLUMN\', id');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:app_date)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'images\', N\'COLUMN\', first_appeared_on');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:app_date)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'images\', N\'COLUMN\', last_appeared_on');
        $this->addSql('EXEC sp_addextendedproperty N\'MS_Description\', N\'(DC2Type:serialized_binary)\', N\'SCHEMA\', \'dbo\', N\'TABLE\', \'images\', N\'COLUMN\', vid');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mssql', 'Migration can only be executed safely on \'mssql\'.');

        $this->addSql('DROP TABLE records');
        $this->addSql('DROP TABLE images');
    }
}
