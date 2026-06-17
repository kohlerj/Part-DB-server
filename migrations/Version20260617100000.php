<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\AbstractMultiPlatformMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260617100000 extends AbstractMultiPlatformMigration
{
    public function getDescription(): string
    {
        return 'Add structural tree columns (parent_id, comment, not_selectable, alternative_names, id_preview_attachment) to orders table';
    }

    public function mySQLUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD parent_id INT DEFAULT NULL, ADD comment LONGTEXT NOT NULL DEFAULT \'\', ADD not_selectable TINYINT(1) NOT NULL DEFAULT 0, ADD alternative_names LONGTEXT DEFAULT NULL, ADD id_preview_attachment INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE727ACA70 FOREIGN KEY (parent_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA8A86FA3 FOREIGN KEY (id_preview_attachment) REFERENCES attachments (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E52FFDEE727ACA70 ON orders (parent_id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA8A86FA3 ON orders (id_preview_attachment)');
    }

    public function mySQLDown(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE727ACA70');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA8A86FA3');
        $this->addSql('DROP INDEX IDX_E52FFDEE727ACA70 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEA8A86FA3 ON orders');
        $this->addSql('ALTER TABLE orders DROP parent_id, DROP comment, DROP not_selectable, DROP alternative_names, DROP id_preview_attachment');
    }

    public function sqLiteUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD COLUMN parent_id INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN comment CLOB NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE orders ADD COLUMN not_selectable BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE orders ADD COLUMN alternative_names CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN id_preview_attachment INTEGER DEFAULT NULL');
        // SQLite does not support adding FK constraints via ALTER TABLE; they are enforced at table creation only.
    }

    public function sqLiteDown(Schema $schema): void
    {
        // SQLite does not support DROP COLUMN in older versions; reconstruct table
        $this->addSql('CREATE TEMPORARY TABLE __temp__orders AS SELECT id, name, notes, last_modified, datetime_added FROM orders');
        $this->addSql('DROP TABLE orders');
        $this->addSql('CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, notes CLOB NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, datetime_added DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL)');
        $this->addSql('INSERT INTO orders (id, name, notes, last_modified, datetime_added) SELECT id, name, notes, last_modified, datetime_added FROM __temp__orders');
        $this->addSql('DROP TABLE __temp__orders');
    }

    public function postgreSQLUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD comment TEXT NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE orders ADD not_selectable BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE orders ADD alternative_names TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD id_preview_attachment INT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE727ACA70 FOREIGN KEY (parent_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA8A86FA3 FOREIGN KEY (id_preview_attachment) REFERENCES attachments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E52FFDEE727ACA70 ON orders (parent_id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA8A86FA3 ON orders (id_preview_attachment)');
    }

    public function postgreSQLDown(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE727ACA70');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEEA8A86FA3');
        $this->addSql('DROP INDEX IDX_E52FFDEE727ACA70');
        $this->addSql('DROP INDEX IDX_E52FFDEEA8A86FA3');
        $this->addSql('ALTER TABLE orders DROP parent_id');
        $this->addSql('ALTER TABLE orders DROP comment');
        $this->addSql('ALTER TABLE orders DROP not_selectable');
        $this->addSql('ALTER TABLE orders DROP alternative_names');
        $this->addSql('ALTER TABLE orders DROP id_preview_attachment');
    }
}
