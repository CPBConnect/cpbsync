<?php

namespace CPBConnect\Infrastructure\PrestaShop;

use CPBConnect\Presentation\Admin\AdminShellInterface;
use Context;
use Module;

/**
 * Adaptador entre la capa de presentación y el Module de PrestaShop.
 *
 * Module::trans() y Module::$context son protected, así que este
 * adaptador sólo utiliza su API pública (getLocalPath, display,
 * getTranslator) y el contexto global de PrestaShop.
 */
class ModuleAdminShell implements AdminShellInterface
{
    private const TEMPLATE_DIRECTORY = 'views/templates/admin/';
    private const TRANSLATION_DOMAIN = 'Modules.Cpbsync.Admin';

    public function __construct(
        private Module $module,
        private string $moduleFile
    ) {
    }

    public function assign(array $data): void
    {
        $this->context()->smarty->assign($data);
    }

    public function fetch(string $template): string
    {
        return (string) $this->context()->smarty->fetch(
            $this->module->getLocalPath()
            . self::TEMPLATE_DIRECTORY
            . $template
        );
    }

    public function display(string $template): string
    {
        return (string) $this->module->display(
            $this->moduleFile,
            self::TEMPLATE_DIRECTORY . $template
        );
    }

    public function translate(
        string $message,
        array $parameters = []
    ): string {
        return (string) $this->module->getTranslator()->trans(
            $message,
            $parameters,
            self::TRANSLATION_DOMAIN,
            null
        );
    }

    public function addError(
        string $message,
        array $parameters = []
    ): void {
        $this->context()->controller->errors[] =
            $this->translate($message, $parameters);
    }

    public function addConfirmation(
        string $message,
        array $parameters = []
    ): void {
        $this->context()->controller->confirmations[] =
            $this->translate($message, $parameters);
    }

    public function translateMessages(array $messages): array
    {
        $translated = [];

        foreach ($messages as $message) {
            $translated[] = $this->translate((string) $message);
        }

        return $translated;
    }

    public function json(array $data): string
    {
        header('Content-Type: application/json; charset=utf-8');

        return (string) json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );
    }

    public function emitJson(array $data): void
    {
        echo $this->json($data);
    }

    private function context(): Context
    {
        return Context::getContext();
    }
}
