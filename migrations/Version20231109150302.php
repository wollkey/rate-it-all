<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231109150302 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ALTER telegram_id TYPE BIGINT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ALTER telegram_id TYPE INT');
    }
}
