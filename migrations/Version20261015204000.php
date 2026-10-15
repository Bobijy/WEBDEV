<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015204000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories table and link category_id to products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_3AF346685E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE products DROP COLUMN category, ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B3BA5A5A12469DE2 ON products (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A12469DE2');
        $this->addSql('DROP INDEX IDX_B3BA5A5A12469DE2 ON products');
        $this->addSql('ALTER TABLE products DROP category_id, ADD category VARCHAR(100) DEFAULT \'Uncategorized\'');
        $this->addSql('DROP TABLE categories');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement("INSERT INTO categories (name, description) VALUES
            ('Parfum', 'Highest fragrance concentration (20-30%), longest lasting.'),
            ('Eau de Parfum', 'Rich and long-lasting concentration (15-20%).'),
            ('Eau de Toilette', 'Light and refreshing concentration (5-15%), ideal for everyday wear.'),
            ('Eau de Cologne', 'Very light and subtle concentration (2-4%), refreshing splash.')
            ON DUPLICATE KEY UPDATE name=name");
    }
}
