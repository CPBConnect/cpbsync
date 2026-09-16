<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Añade las métricas de ejecución al historial y el total de items.
 *
 * El historial guarda como mucho una parte de los items (el resto se
 * resume en items_total), así que también hace falta saber cuántos
 * había en realidad.
 */
function cpbsync_add_column(
    string $table,
    string $column,
    string $definition
): bool {
    $exists = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . $table . "` LIKE '" . $column . "'"
    );

    if (!empty($exists)) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . $table . '`'
        . ' ADD `' . $column . '` ' . $definition
    );
}

function upgrade_module_1_2_0($module)
{
    $table = _DB_PREFIX_ . 'cpbsync_sync_log';

    $items = cpbsync_add_column(
        $table,
        'items_total',
        'INT UNSIGNED NOT NULL DEFAULT 0'
    );

    $duration = cpbsync_add_column(
        $table,
        'duration_ms',
        'INT UNSIGNED NULL'
    );

    $memory = cpbsync_add_column(
        $table,
        'memory_kb',
        'INT UNSIGNED NULL'
    );

    $phases = cpbsync_add_column(
        $table,
        'phases',
        'TEXT NULL'
    );

    return $items && $duration && $memory && $phases;
}
