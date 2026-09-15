<?php

namespace CPBConnect\Infrastructure\Source;

use RuntimeException;

class CsvSourceReader
{
    public function parse(string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException(
                'El archivo CSV está vacío.'
            );
        }

        $lines = preg_split(
            '/\r\n|\r|\n/',
            $content
        );

        if (empty($lines)) {
            throw new RuntimeException(
                'El archivo CSV está vacío.'
            );
        }

        $delimiter = $this->detectDelimiter($lines[0]);

        $headers = str_getcsv(
            $lines[0],
            $delimiter
        );

        $headers = array_map(
            static fn ($header) => trim($header),
            $headers
        );

        $this->validateHeaders($headers);

        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv(
                $line,
                $delimiter
            );

            $values = array_slice(
                $values,
                0,
                count($headers)
            );

            $values = array_pad(
                $values,
                count($headers),
                null
            );

            $rows[] = array_combine(
                $headers,
                $values
            );
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function validateHeaders(array $headers): void
    {
        if (empty($headers)) {
            throw new RuntimeException(
                'El archivo CSV no contiene encabezados.'
            );
        }

        foreach ($headers as $header) {
            if ($header === '') {
                throw new RuntimeException(
                    'El archivo CSV contiene un encabezado vacío.'
                );
            }
        }

        if (count($headers) !== count(array_unique($headers))) {
            throw new RuntimeException(
                'El archivo CSV contiene encabezados duplicados.'
            );
        }
    }

    public function readBatch(
        string $url,
        int $offset,
        int $limit
    ): array {
        $handle = fopen($url, 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'No se pudo abrir la fuente CSV.'
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $rows = [];
        $position = 0;
        $end = $offset + $limit;

        while (($data = fgetcsv($handle)) !== false) {
            if ($position < $offset) {
                $position++;
                continue;
            }

            if ($position >= $end) {
                break;
            }

            $rows[] = array_combine($headers, $data);

            $position++;
        }

        fclose($handle);

        return $rows;
    }

    public function countRows(string $url): int
    {
        $handle = fopen($url, 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'No se pudo abrir la fuente CSV.'
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return 0;
        }

        $total = 0;

        while (fgetcsv($handle) !== false) {
            $total++;
        }

        fclose($handle);

        return $total;
    }

    public function readBatchFromFile(
        string $path,
        int $offset,
        int $limit
    ): array {
        if (!is_readable($path)) {
            throw new \RuntimeException(
                'No se pudo leer el archivo CSV.'
            );
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'No se pudo abrir el archivo CSV.'
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(
            'trim',
            $headers
        );

        $position = 0;

        while ($position < $offset) {
            if (fgetcsv($handle) === false) {
                fclose($handle);

                return [];
            }

            $position++;
        }

        $rows = [];

        while (count($rows) < $limit) {
            $data = fgetcsv($handle);

            if ($data === false) {
                break;
            }

            if (count($data) !== count($headers)) {
                continue;
            }

            $rows[] = array_combine(
                $headers,
                $data
            );
        }

        fclose($handle);

        return $rows;
    }

    public function countFileRows(string $path): int
    {
        if (!is_readable($path)) {
            throw new \RuntimeException(
                'No se pudo leer el archivo CSV.'
            );
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'No se pudo abrir el archivo CSV.'
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return 0;
        }

        $total = 0;

        while (fgetcsv($handle) !== false) {
            $total++;
        }

        fclose($handle);

        return $total;
    }
}