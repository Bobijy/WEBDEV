<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20261011120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prevent duplicate checkout submissions using a unique nullable request token.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL commits implicitly.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'This migration requires MySQL.');
        $this->addSql('ALTER TABLE orders ADD checkout_token VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CHECKOUT_TOKEN ON orders (checkout_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CHECKOUT_TOKEN ON orders');
        $this->addSql('ALTER TABLE orders DROP checkout_token');
    }
}
