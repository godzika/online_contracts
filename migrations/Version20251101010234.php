<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251101010234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract ADD signed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_by_id created_by_id INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E98F28595647652B ON contract (unique_token)');
        $this->addSql('ALTER TABLE signature ADD contract_id INT NOT NULL, DROP contract, CHANGE ip_address ip_address VARCHAR(45) DEFAULT NULL, CHANGE signed_at signed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE sugnature_data signature_data LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE signature ADD CONSTRAINT FK_AE8801412576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AE8801412576E0FD ON signature (contract_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_E98F28595647652B ON contract');
        $this->addSql('ALTER TABLE contract DROP signed_at, CHANGE created_by_id created_by_id INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE `signature` DROP FOREIGN KEY FK_AE8801412576E0FD');
        $this->addSql('DROP INDEX UNIQ_AE8801412576E0FD ON `signature`');
        $this->addSql('ALTER TABLE `signature` ADD contract VARCHAR(255) NOT NULL, DROP contract_id, CHANGE ip_address ip_address VARCHAR(255) NOT NULL, CHANGE signed_at signed_at DATETIME NOT NULL, CHANGE signature_data sugnature_data LONGTEXT NOT NULL');
    }
}
