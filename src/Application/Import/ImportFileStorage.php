<?php

namespace CPBConnect\Application\Import;

use RuntimeException;

class ImportFileStorage
{
    private string $directory;

    public function __construct()
    {
        $this->directory = _PS_MODULE_DIR_
                           . 'cpbsync/var/imports/';
    }

    public function store(array $file): string
    {
        if (
            !isset($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name'])
        ) {
            throw new RuntimeException(
                'El archivo subido no es válido.'
            );
        }

        if (
            isset($file['error'])
            && $file['error'] !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Ocurrió un error al subir el archivo.'
            );
        }

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if ($extension !== 'csv') {
            throw new RuntimeException(
                'Solo se permiten archivos CSV.'
            );
        }

        if (!is_dir($this->directory)) {
            if (
                !mkdir($this->directory, 0755, true)
                && !is_dir($this->directory)
            ) {
                throw new RuntimeException(
                    'No se pudo crear el directorio de importaciones.'
                );
            }
        }

        $filename = uniqid('import_', true) . '.csv';

        $destination = $this->directory . $filename;

        if (!move_uploaded_file(
            $file['tmp_name'],
            $destination
        )) {
            throw new RuntimeException(
                'No se pudo guardar el archivo CSV.'
            );
        }

        return $destination;
    }
}