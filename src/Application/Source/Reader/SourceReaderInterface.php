<?php

namespace CPBConnect\Application\Source\Reader;

/**
 * Contrato que debe cumplir cualquier lector de catálogos.
 *
 * La edición gratuita incluye el lector CSV; las ediciones de pago
 * añaden sus propios lectores (XML, JSON, APIs REST...) sin duplicar
 * el motor de sincronización.
 */
interface SourceReaderInterface
{
    /**
     * Identificador técnico que se guarda en cpbsync_source.type.
     */
    public function getType(): string;

    /**
     * Etiqueta que se muestra en el formulario de administración.
     */
    public function getLabel(): string;

    /**
     * Extensiones admitidas en la importación manual de archivos.
     *
     * @return array<int, string>
     */
    public function getFileExtensions(): array;

    /**
     * Registros que se procesan por lote.
     *
     * Los lectores que no pueden leer por lotes desde la URL original
     * usan un lote mayor para no descargar el catálogo en cada paso.
     */
    public function getBatchSize(): int;

    /**
     * Lee el catálogo completo.
     *
     * @param array<string, mixed> $source
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    public function read(array $source): array;

    /**
     * Lee un lote de registros desde la fuente.
     *
     * @param array<string, mixed> $source
     *
     * @return array<int, array<string, mixed>>
     */
    public function readBatch(
        array $source,
        int $offset,
        int $limit
    ): array;

    /**
     * Lee un lote de registros desde un archivo local.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readBatchFromFile(
        string $path,
        int $offset,
        int $limit
    ): array;

    /**
     * Cuenta los registros de la fuente.
     *
     * @param array<string, mixed> $source
     */
    public function countRows(array $source): int;

    /**
     * Cuenta los registros de un archivo local.
     */
    public function countFileRows(string $path): int;
}
