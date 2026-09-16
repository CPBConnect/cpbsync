<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use CPBConnect\Infrastructure\Persistence\DatabaseInstaller;
use CPBConnect\Infrastructure\PrestaShop\ModuleAdminShell;
use CPBConnect\Presentation\Admin\AdminActionRouter;

/**
 * Módulo CPB Sync.
 *
 * Sólo mantiene el ciclo de vida del módulo y delega las acciones
 * del administrador en la capa de presentación.
 */
class CpbSync extends Module
{
    private ?AdminActionRouter $actionRouter = null;

    public function __construct()
    {
        $this->name = 'cpbsync';
        $this->tab = 'administration';
        $this->version = '1.2.0';
        $this->author = 'CPBConnect';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->confirmUninstall = true;

        parent::__construct();

        $this->displayName = $this->trans(
            'CPB Sync',
            [],
            'Modules.Cpbsync.Admin'
        );

        $this->description = $this->trans(
            'Synchronize external product catalogs with PrestaShop using configurable mappings and data transformations.',
            [],
            'Modules.Cpbsync.Admin'
        );

        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install(): bool
    {
        $installer = new DatabaseInstaller();

        /*
         * Las tablas se crean antes de registrar el módulo: si algo
         * falla, no queda marcado como instalado sin sus tablas.
         */
        if (!$installer->install()) {
            return false;
        }

        if (!parent::install()) {
            $installer->uninstall();

            return false;
        }

        return true;
    }

    /**
     * El módulo usa el sistema de traducción nuevo (dominios).
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function uninstall(): bool
    {
        $installer = new DatabaseInstaller();

        return $installer->uninstall()
               && parent::uninstall();
    }

    public function getContent()
    {
        return $this->getActionRouter()->handle(
            (string) Tools::getValue('cpbsync_action')
        );
    }

    private function getActionRouter(): AdminActionRouter
    {
        if ($this->actionRouter === null) {
            $this->actionRouter = AdminActionRouter::create(
                new ModuleAdminShell($this, __FILE__),
                $this->name
            );
        }

        return $this->actionRouter;
    }
}
