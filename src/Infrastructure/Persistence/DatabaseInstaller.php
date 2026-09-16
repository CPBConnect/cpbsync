<?php

namespace CPBConnect\Infrastructure\Persistence;

use Db;

class DatabaseInstaller
{
    public function install(): bool
    {
        return $this->createSyncLogTable()
               && $this->createSourceTable()
               && $this->createMappingTable()
               && $this->createProductMetaTable()
               && $this->createImportTable();
    }

    public function uninstall(): bool
    {
        return $this->dropSyncLogTable()
               && $this->dropProductMetaTable()
               && $this->dropMappingTable()
               && $this->dropSourceTable()
               && $this->dropImportTable();
    }

    private function createSyncLogTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'
               . _DB_PREFIX_
               . 'cpbsync_sync_log` (
                `id_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_source` INT UNSIGNED NOT NULL,
                `status` VARCHAR(32) NOT NULL,
                `execution_type` VARCHAR(16) NOT NULL DEFAULT "manual",
                `total` INT UNSIGNED NOT NULL DEFAULT 0,
                `created` INT UNSIGNED NOT NULL DEFAULT 0,
                `updated` INT UNSIGNED NOT NULL DEFAULT 0,
                `skipped` INT UNSIGNED NOT NULL DEFAULT 0,
                `errors` INT UNSIGNED NOT NULL DEFAULT 0,
                `details` LONGTEXT NULL,
                `items_total` INT UNSIGNED NOT NULL DEFAULT 0,
                `duration_ms` INT UNSIGNED NULL,
                `memory_kb` INT UNSIGNED NULL,
                `phases` TEXT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_log`),
                KEY `idx_cpbsync_sync_log_source` (`id_source`),
                KEY `idx_cpbsync_sync_log_date` (`date_add`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function createSourceTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'
               . _DB_PREFIX_
               . 'cpbsync_source` (
                `id_source` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `type` VARCHAR(32) NOT NULL,
                `url` TEXT NOT NULL,
                `config` TEXT NULL,
                `frequency` VARCHAR(32) NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_source`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function createMappingTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'
               . _DB_PREFIX_
               . 'cpbsync_mapping` (
                `id_mapping` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_source` INT UNSIGNED NOT NULL,
                `source_field` VARCHAR(255) NOT NULL,
                `target_field` VARCHAR(255) NOT NULL,
                `transform` VARCHAR(64) NOT NULL DEFAULT "none",
                `transform_config` TEXT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_mapping`),
                KEY `idx_cpbsync_mapping_source` (`id_source`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function createProductMetaTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'
               . _DB_PREFIX_
               . 'cpbsync_product_meta` (
                `id_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_product` INT UNSIGNED NOT NULL,
                `field` VARCHAR(64) NOT NULL,
                `source_value` TEXT NOT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_meta`),
                UNIQUE KEY `uniq_cpbsync_product_field`
                    (`id_product`, `field`),
                KEY `idx_cpbsync_product` (`id_product`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function createImportTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpbsync_import` (
        `id_import` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_source` INT UNSIGNED NOT NULL,
        `file_path` VARCHAR(500) NULL,
        `execution_type` VARCHAR(16) NOT NULL DEFAULT "manual",
        `status` VARCHAR(20) NOT NULL,
        `total` INT UNSIGNED NOT NULL DEFAULT 0,
        `processed` INT UNSIGNED NOT NULL DEFAULT 0,
        `success` INT UNSIGNED NOT NULL DEFAULT 0,
        `errors` INT UNSIGNED NOT NULL DEFAULT 0,
        `current_position` INT UNSIGNED NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id_import`),
        KEY `idx_source` (`id_source`),
        KEY `idx_status` (`status`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function dropSyncLogTable(): bool
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `'
            . _DB_PREFIX_
            . 'cpbsync_sync_log`'
        );
    }

    private function dropProductMetaTable(): bool
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `'
            . _DB_PREFIX_
            . 'cpbsync_product_meta`'
        );
    }

    private function dropMappingTable(): bool
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `'
            . _DB_PREFIX_
            . 'cpbsync_mapping`'
        );
    }

    private function dropSourceTable(): bool
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `'
            . _DB_PREFIX_
            . 'cpbsync_source`'
        );
    }

    private function dropImportTable(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'cpbsync_import`';

        return Db::getInstance()->execute($sql);
    }
}