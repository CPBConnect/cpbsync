<?php

namespace CPBConnect\Application\Source\Reader;

use CPBConnect\Infrastructure\Source\HttpSourceReader;
use RuntimeException;

/**
 * Base común de los lectores.
 *
 * Implementa por defecto la lectura por lotes, el conteo y la lectura
 * de archivos locales en función de read(). Los lectores que puedan
 * hacerlo de forma más eficiente (como el CSV) los sobrescriben.
 */
abstract class AbstractSourceReader implements SourceReaderInterface
{
    private const LOCAL_FILE_PREFIX = 'var/imports/';

    /**
     * Evita volver a descargar la fuente en cada lote cuando el
     * procesado por lotes ocurre dentro del mismo proceso (cron).
     */
    private ?string $cachedUrl = null;
    private ?string $cachedContent = null;

    public function getFileExtensions(): array
    {
        return [];
    }

    public function getBatchSize(): int
    {
        return 50;
    }

    public function readBatch(
        array $source,
        int $offset,
        int $limit
    ): array {
        return array_slice(
            $this->read($source)['rows'],
            $offset,
            $limit
        );
    }

    public function readBatchFromFile(
        string $path,
        int $offset,
        int $limit
    ): array {
        return $this->readBatch(
            $this->localSource($path),
            $offset,
            $limit
        );
    }

    public function countRows(array $source): int
    {
        return count($this->read($source)['rows']);
    }

    public function countFileRows(string $path): int
    {
        return $this->countRows($this->localSource($path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function localSource(string $path): array
    {
        return [
            'type' => $this->getType(),
            'url' => $path,
        ];
    }

    /**
     * Lee un valor de la configuración adicional de la fuente.
     *
     * @param array<string, mixed> $source
     */
    protected function configValue(
        array $source,
        string $key,
        string $default = ''
    ): string {
        $config = $source['config'] ?? null;

        if (!is_string($config) || $config === '') {
            return $default;
        }

        $decoded = json_decode($config, true);

        if (!is_array($decoded) || !isset($decoded[$key])) {
            return $default;
        }

        return is_scalar($decoded[$key])
            ? (string) $decoded[$key]
            : $default;
    }

    /**
     * Obtiene el contenido de la fuente.
     *
     * Sólo se admiten archivos locales dentro del directorio de
     * importaciones del módulo, para que una URL configurada no pueda
     * leer archivos arbitrarios del servidor.
     */
    protected function fetchContent(string $url): string
    {
        if ($this->cachedUrl === $url && $this->cachedContent !== null) {
            return $this->cachedContent;
        }

        $content = $this->isLocalImportFile($url)
            ? $this->readLocalFile($url)
            : $this->httpReader()->read($url);

        $this->cachedUrl = $url;
        $this->cachedContent = $content;

        return $content;
    }

    private function readLocalFile(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(
                'The source file could not be read.'
            );
        }

        return $content;
    }

    private function isLocalImportFile(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $directory = realpath(
            _PS_MODULE_DIR_ . 'cpbsync/' . self::LOCAL_FILE_PREFIX
        );

        if ($directory === false) {
            return false;
        }

        $real = realpath($path);

        return $real !== false
               && str_starts_with($real, $directory);
    }

    private function httpReader(): HttpSourceReader
    {
        return new HttpSourceReader();
    }
}
