<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Añade la configuración del calendario a las fuentes.
 *
 * Las programaciones que dependen del calendario (una hora concreta,
 * un día de la semana o del mes) necesitan guardar sus ajustes aparte
 * de la frecuencia.
 */
function upgrade_module_1_3_0($module)
{
    $table = _DB_PREFIX_ . 'cpbsync_source';

    $exists = Db::getInstance()->executeS(
        'SHOW COLUMNS FROM `' . $table . "` LIKE 'schedule'"
    );

    if (!empty($exists)) {
        return true;
    }

    return (bool) Db::getInstance()->execute(
        'ALTER TABLE `' . $table . '`'
        . ' ADD `schedule` TEXT NULL'
    );
}
