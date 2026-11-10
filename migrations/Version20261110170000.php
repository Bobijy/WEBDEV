<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add account status and product creator ownership without changing existing accounts or products.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'This migration requires MySQL.');
        $this->addSql('ALTER TABLE users ADD active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE products ADD created_by_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PRODUCT_CREATED_BY ON products (created_by_id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_PRODUCT_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing account status could reactivate disabled users; removing ownership would discard staff ownership. Restore a verified backup instead.');
    }
}
