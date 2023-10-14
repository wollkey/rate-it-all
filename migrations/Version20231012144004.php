<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231012144004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create telegram_user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE telegram_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE telegram_user (id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, telegram_id INT NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE telegram_user_id_seq CASCADE');
        $this->addSql('DROP TABLE telegram_user');
    }
}
