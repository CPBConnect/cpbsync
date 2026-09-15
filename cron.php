<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/cpbsync.php';

$lockFile = fopen(
    sys_get_temp_dir() . '/cpbsync_cron.lock',
    'c'
);

if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
    echo "CPB Sync Cron ya está ejecutándose.\n";
    exit(0);
}

try {

    $module = Module::getInstanceByName('cpbsync');

    if (!$module) {
        throw new \RuntimeException(
            'CPB Sync no está instalado.'
        );
    }

    $runner =
        new \CPBConnect\Application\Cron\CronRunner();

    $results = $runner->run();

    foreach ($results as $result) {

        if ($result['status'] === 'error') {
            echo sprintf(
                "Fuente %d: ERROR - %s\n",
                $result['id_source'],
                $result['error']
            );

            continue;
        }

        $sync = $result['result'];

        echo sprintf(
            "Fuente %d: total=%d created=%d updated=%d skipped=%d errors=%d\n",
            $result['id_source'],
            $sync['total'],
            $sync['created'],
            $sync['updated'],
            $sync['skipped'],
            $sync['errors']
        );
    }

    $exitCode = 0;

} catch (\Throwable $e) {

    fwrite(
        STDERR,
        'CPB Sync Cron ERROR: '
        . $e->getMessage()
        . PHP_EOL
    );

    $exitCode = 1;

} finally {

    if ($lockFile) {
        flock($lockFile, LOCK_UN);
        fclose($lockFile);
    }
}

exit($exitCode);