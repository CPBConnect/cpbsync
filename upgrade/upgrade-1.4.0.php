<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Añade las opciones de sincronización a las fuentes.
 *
 * Son decisiones que no dependen del mapeo (sólo crear, rellenar los
 * campos vacíos, no tocar el stock o no importar imágenes), así que se
 * guardan aparte de la configuración del lector.
 */
function upgrade_module_1_4_0($module)
{
    $table = _DB_PREFIX_ . 'cpbsync_source';

    $exists = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . $table . "` LIKE 'options'"
    );

    if (!empty($exists)) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . $table . '`'
        . ' ADD `options` TEXT NULL'
    );
}
