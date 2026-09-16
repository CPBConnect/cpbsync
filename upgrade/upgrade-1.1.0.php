<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Añade la configuración adicional de las fuentes.
 *
 * La usan los lectores que necesitan parámetros propios, como la ruta
 * de los registros en catálogos XML o JSON.
 */
function upgrade_module_1_1_0($module)
{
    $table = _DB_PREFIX_ . 'cpbsync_source';

    $column = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . $table . "` LIKE 'config'"
    );

    if (!empty($column)) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . $table . '`'
        . ' ADD `config` TEXT NULL AFTER `url`'
    );
}
