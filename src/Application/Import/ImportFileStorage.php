<?php

namespace CPBConnect\Application\Import;

use CPBConnect\Application\Source\Reader\SourceReaderRegistry;
use RuntimeException;

class ImportFileStorage
{
    private string $directory;

    public function __construct(
        private SourceReaderRegistry $readers
    ) {
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
                'The uploaded file is not valid.'
            );
        }

        if (
            isset($file['error'])
            && $file['error'] !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'An error occurred while uploading the file.'
            );
        }

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!$this->isAllowedExtension($extension)) {
            throw new RuntimeException(
                'The file type is not allowed.'
            );
        }

        if (!is_dir($this->directory)) {
            if (
                !mkdir($this->directory, 0755, true)
                && !is_dir($this->directory)
            ) {
                throw new RuntimeException(
                    'The imports directory could not be created.'
                );
            }
        }

        $filename = uniqid('import_', true) . '.' . $extension;

        $destination = $this->directory . $filename;

        if (!move_uploaded_file(
            $file['tmp_name'],
            $destination
        )) {
            throw new RuntimeException(
                'The file could not be saved.'
            );
        }

        return $destination;
    }

    private function isAllowedExtension(string $extension): bool
    {
        return in_array(
            $extension,
            $this->readers->fileExtensions(),
            true
        );
    }
}