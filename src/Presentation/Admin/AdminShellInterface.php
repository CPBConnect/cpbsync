<?php

namespace CPBConnect\Presentation\Admin;

/**
 * Salida de la capa de presentación hacia PrestaShop.
 *
 * Los handlers de administración no conocen la clase Module: sólo
 * usan esta interfaz para asignar variables, renderizar plantillas,
 * notificar mensajes y emitir respuestas JSON.
 */
interface AdminShellInterface
{
    /**
     * Asigna variables para la siguiente plantilla.
     */
    public function assign(array $data): void;

    /**
     * Renderiza una plantilla de views/templates/admin/.
     */
    public function fetch(string $template): string;

    /**
     * Renderiza con Module::display(), necesario para las plantillas
     * que dependen de module_dir.
     */
    public function display(string $template): string;

    public function translate(
        string $message,
        array $parameters = []
    ): string;

    public function addError(
        string $message,
        array $parameters = []
    ): void;

    public function addConfirmation(
        string $message,
        array $parameters = []
    ): void;

    /**
     * Traduce una lista de mensajes (por ejemplo, los errores de un
     * producto en el Dry Run o en el resultado de una sincronización).
     *
     * @param array<int, string> $messages
     *
     * @return array<int, string>
     */
    public function translateMessages(array $messages): array;

    /**
     * Serializa una respuesta JSON con las cabeceras adecuadas.
     */
    public function json(array $data): string;

    /**
     * Envía una respuesta JSON al navegador.
     */
    public function emitJson(array $data): void;
}
