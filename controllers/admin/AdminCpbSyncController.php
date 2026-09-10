<?php

class AdminCpbSyncController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
                                           'module_name' => $this->module->displayName,
                                       ]);

        $this->setTemplate('sources.tpl');
    }
}