<?php

/**
 * Bootstrap de las pruebas del módulo.
 *
 * Sustituye el mínimo de PrestaShop necesario para poder ejecutar
 * la lógica de aplicación y de presentación sin una tienda instalada.
 *
 * El stub de Module respeta la visibilidad real de PrestaShop 8:
 * trans() y $context son protected, getLocalPath(), display() y
 * getTranslator() son public.
 */

define('_PS_VERSION_', '8.1.0');
define('_DB_PREFIX_', 'ps_');
define('_MYSQL_ENGINE_', 'InnoDB');
define('_PS_MODULE_DIR_', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require_once dirname(__DIR__) . '/vendor/autoload.php';

class Tools
{
    public static array $values = [];

    public static function getValue($key, $default = false)
    {
        return self::$values[$key] ?? $default;
    }

    public static function set(array $values): void
    {
        self::$values = $values;
    }

    public static function reset(): void
    {
        self::$values = [];
    }
}

class Link
{
    public array $calls = [];

    public function getAdminLink(
        $controller,
        $withToken = true,
        $sfRoutes = [],
        $params = []
    ) {
        $this->calls[] = [
            'controller' => $controller,
            'withToken' => $withToken,
            'params' => $params,
        ];

        $url = 'http://shop.test/admin/index.php?controller='
               . $controller;

        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }
}

class TranslatorStub
{
    public array $calls = [];

    public function trans(
        $id,
        array $parameters = [],
        $domain = null,
        $locale = null
    ) {
        $this->calls[] = [
            'id' => $id,
            'parameters' => $parameters,
            'domain' => $domain,
            'locale' => $locale,
        ];

        return $parameters === [] ? $id : strtr($id, $parameters);
    }
}

class SmartyStub
{
    public array $vars = [];
    public array $fetched = [];

    public function assign($data): void
    {
        $this->vars = array_merge($this->vars, (array) $data);
    }

    public function fetch($template): string
    {
        $this->fetched[] = $template;

        return 'smarty:' . $template;
    }
}

class ControllerStub
{
    public array $errors = [];
    public array $confirmations = [];
}

class Context
{
    public $link;
    public $smarty;
    public $controller;

    private ?TranslatorStub $translator = null;
    private static ?Context $instance = null;

    public static function getContext(): Context
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->link = new Link();
            self::$instance->smarty = new SmartyStub();
            self::$instance->controller = new ControllerStub();
        }

        return self::$instance;
    }

    public function getTranslator(): TranslatorStub
    {
        if ($this->translator === null) {
            $this->translator = new TranslatorStub();
        }

        return $this->translator;
    }
}

/**
 * Stub de ModuleCore con la visibilidad real de PrestaShop.
 */
class Module
{
    public $name;
    public $displayName;
    public string $lastFile = '';
    public string $lastTemplate = '';
    public bool $lastUsedDisplay = false;

    protected $local_path = null;
    protected $context;

    public function __construct()
    {
        $this->name = 'cpbsync';
        $this->displayName = 'CPB Sync';
        $this->local_path = _PS_MODULE_DIR_ . 'cpbsync/';
        $this->context = Context::getContext();
    }

    public function getLocalPath()
    {
        return $this->local_path;
    }

    public function getTranslator()
    {
        return Context::getContext()->getTranslator();
    }

    public function display(
        $file,
        $template,
        $cache_id = null,
        $compile_id = null
    ) {
        $this->lastFile = $file;
        $this->lastTemplate = $template;
        $this->lastUsedDisplay = true;

        $this->context->smarty->assign([
            'module_dir' => '/modules/'
                             . basename($file, '.php')
                             . '/',
        ]);

        return 'module-display:' . $template;
    }

    protected function trans(
        $id,
        array $parameters = [],
        $domain = null,
        $locale = null
    ) {
        return $this->getTranslator()->trans(
            $id,
            $parameters,
            $domain,
            $locale
        );
    }
}
