<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404221132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locale column to player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD locale VARCHAR(10) DEFAULT \'en\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP locale');
    }
}
