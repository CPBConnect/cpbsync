<?php

/**
 * Comprueba que todos los textos traducibles del módulo están en los
 * catálogos XLIFF.
 *
 * Uso:
 *   php tools/check-translations.php
 *
 * Devuelve código 1 si falta algún texto o si alguna traducción quedó
 * vacía, de modo que pueda usarse en integración continua.
 */

$root = dirname(__DIR__);
$domain = 'Modules.Cpbsync.Admin';
$fileBase = 'ModulesCpbsyncAdmin';
$locales = ['en-US', 'es-ES'];

/**
 * Extrae los textos traducibles del código.
 *
 * @return array<string, array<int, string>> texto => archivos
 */
function extractWordings(string $root, string $domain): array
{
    $found = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $relative = str_replace(
            $root . DIRECTORY_SEPARATOR,
            '',
            $path
        );

        if (str_contains(
                $path,
                DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
            )
            || str_starts_with($relative, 'tests' . DIRECTORY_SEPARATOR)
            || str_starts_with($relative, 'tools' . DIRECTORY_SEPARATOR)
            || str_starts_with($relative, 'translations' . DIRECTORY_SEPARATOR)
            || str_starts_with($relative, 'build' . DIRECTORY_SEPARATOR)
        ) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        $content = file_get_contents($path);

        if ($extension === 'tpl') {
            /*
             * Entre el texto y el dominio puede haber parámetros
             * (sprintf=, js=), así que no se exige que sean
             * contiguos.
             */
            preg_match_all(
                "/\{l\s+s='((?:[^'\\\\]|\\\\.)*)'[^}]*?d='"
                . preg_quote($domain, '/') . "'/",
                $content,
                $matches
            );

            foreach ($matches[1] as $value) {
                $found[$value][] = $relative;
            }

            continue;
        }

        if ($extension !== 'php') {
            continue;
        }

        $patterns = [
            "/->translate\(\s*'((?:[^'\\\\]|\\\\.)+)'/s",
            "/->trans\(\s*'((?:[^'\\\\]|\\\\.)+)'/s",
            "/->addError\(\s*'((?:[^'\\\\]|\\\\.)+)'/s",
            "/->addConfirmation\(\s*'((?:[^'\\\\]|\\\\.)+)'/s",
            "/new ValidationError\(\s*'((?:[^'\\\\]|\\\\.)+)'/s",
            "/throw new \\\\?[A-Za-z\\\\]*Exception\(\s*'((?:[^'\\\\]|\\\\.)+)'/s",
            // Campos y opciones de las transformaciones: las etiquetas
            // son textos de interfaz aunque vivan en un array.
            "/'label' => '((?:[^'\\\\]|\\\\.)+)'/s",
            "/'hint' => '((?:[^'\\\\]|\\\\.)+)'/s",
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);

            foreach ($matches[1] as $value) {
                $value = str_replace(
                    ["\\\\", "\\'"],
                    ["\\", "'"],
                    $value
                );

                $found[$value][] = $relative;
            }
        }
    }

    return $found;
}

/**
 * Lee los textos de un catálogo XLIFF.
 *
 * @return array<string, string> texto => traducción
 */
function readCatalogue(string $path): array
{
    $catalogue = [];

    if (!is_file($path)) {
        return $catalogue;
    }

    $document = new DOMDocument();
    $document->load($path);

    foreach ($document->getElementsByTagName('trans-unit') as $unit) {
        $source = $unit->getElementsByTagName('source')->item(0);
        $target = $unit->getElementsByTagName('target')->item(0);

        if ($source === null) {
            continue;
        }

        $catalogue[$source->nodeValue] = $target === null
            ? ''
            : $target->nodeValue;
    }

    return $catalogue;
}

$wordings = extractWordings($root, $domain);
ksort($wordings);

echo 'Translatable wordings found: ' . count($wordings) . "\n";

$failed = false;

foreach ($locales as $locale) {
    $catalogue = readCatalogue(
        $root . '/translations/' . $locale . '/'
        . $fileBase . '.' . $locale . '.xlf'
    );

    $missing = array_diff(
        array_keys($wordings),
        array_keys($catalogue)
    );

    $empty = [];

    foreach ($catalogue as $source => $target) {
        if (trim($target) === '') {
            $empty[] = $source;
        }
    }

    echo "\n[" . $locale . '] entries=' . count($catalogue)
         . ' missing=' . count($missing)
         . ' empty=' . count($empty) . "\n";

    foreach ($missing as $wording) {
        echo '  MISSING: ' . $wording . "\n";
        $failed = true;
    }

    foreach ($empty as $wording) {
        echo '  EMPTY: ' . $wording . "\n";
        $failed = true;
    }
}

$unused = [];

foreach ($locales as $locale) {
    $catalogue = readCatalogue(
        $root . '/translations/' . $locale . '/'
        . $fileBase . '.' . $locale . '.xlf'
    );

    foreach (array_keys($catalogue) as $source) {
        if (!isset($wordings[$source])) {
            $unused[$source] = true;
        }
    }
}

if ($unused !== []) {
    echo "\nCatalogue entries no longer used: "
         . count($unused) . "\n";

    foreach (array_keys($unused) as $wording) {
        echo '  UNUSED: ' . $wording . "\n";
    }
}

if ($failed) {
    echo "\nResult: MISSING TRANSLATIONS\n";

    exit(1);
}

echo "\nResult: all wordings are translated.\n";

exit(0);
