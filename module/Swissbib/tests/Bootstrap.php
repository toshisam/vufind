<?php
/**
 * Bootstrap
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 1/2/13
 * Time: 4:09 PM
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category Swissbib_VuFind2
 * @package  SwissbibTest
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace SwissbibTest;

use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Bootstrap
 *
 * @category Swissbib_VuFind2
 * @package  SwissbibTest
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class Bootstrap
{
    /**
     * ServiceManager
     *
     * @var ServiceManager
     */
    protected static $serviceManager;

    /**
     * Config
     *
     * @var Config
     */
    protected static $config;

    /**
     * Bootstrap
     *
     * @var
     */
    protected static $bootstrap;

    /**
     * Init
     *
     * @return void
     */
    public static function init()
    {
        // Load the user-defined test configuration file, if it exists;
        // otherwise, load
        if (is_readable(__DIR__ . '/TestConfig.php')) {
            $testConfig = include __DIR__ . '/TestConfig.php';
        } else {
            $testConfig = include __DIR__ . '/TestConfig.php.dist';
        }

        $zf2ModulePaths = [];

        if (isset($testConfig['module_listener_options']['module_paths'])) {
            $modulePaths = $testConfig['module_listener_options']['module_paths'];
            foreach ($modulePaths as $modulePath) {
                if (($path = static::findParentPath($modulePath))) {
                    $zf2ModulePaths[] = $path;
                }
            }
        }

        $zf2ModulePaths = implode(PATH_SEPARATOR, $zf2ModulePaths) . PATH_SEPARATOR;
        $zf2ModulePaths .= getenv('ZF2_MODULES_TEST_PATHS') ?: (defined('ZF2_MODULES_TEST_PATHS') ? ZF2_MODULES_TEST_PATHS : '');


        echo PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
        echo "init" . PHP_EOL;

        static::initAutoloader();

        // use ModuleManager to load this module and it's dependencies
        $baseConfig = [
            'module_listener_options' => [
                'module_paths' => explode(PATH_SEPARATOR, $zf2ModulePaths),
            ],
        ];

        self::initVuFind();

        $config = ArrayUtils::merge($baseConfig, $testConfig);

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();

        static::$serviceManager = $serviceManager;
        static::$config         = $config;

        echo PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
    }

    /**
     * InitVuFind
     *
     * @return void
     */
    public static function initVuFind()
    {
        define('APPLICATION_ENV', 'development');
        define('SWISSBIB_TEST_FIXTURES', realpath(__DIR__ . '/fixtures'));


        echo "SWISSBIB_TEST_FIXTURES: " . realpath(__DIR__ . '/fixtures') . PHP_EOL;

        // Setup autoloader for VuFindTest classes
        $loader = \Zend\Loader\AutoloaderFactory::getRegisteredAutoloader(
            \Zend\Loader\AutoloaderFactory::STANDARD_AUTOLOADER
        );

        $loader->registerNamespace('VuFindTest', __DIR__ . '/../../VuFind/src/VuFindTest');

        echo "VuFindTest: " . __DIR__ . '/../../VuFind/src/VuFindTest' . PHP_EOL;
    }

    /**
     * GetServiceManager
     *
     * @return ServiceManager
     */
    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    /**
     * GetConfig
     *
     * @return Config
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * InitAutoloader
     *
     * @return void
     */
    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');

        echo "vendorPath: {$vendorPath}" . PHP_EOL;

        if (is_readable($vendorPath . '/autoload.php')) {

            echo "is_readable: true" . PHP_EOL;

            $loader = include $vendorPath . '/autoload.php';
        } else {
            $zf2Path = getenv('ZF2_PATH') ?: (defined('ZF2_PATH') ? ZF2_PATH : (is_dir($vendorPath . '/ZF2/library') ? $vendorPath . '/ZF2/library' : false));

            echo "zf2Path: {$zf2Path}" . PHP_EOL;


            if (!$zf2Path) {
                throw new RuntimeException('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
            }

            include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
        }


        echo "dir namespace: " . __DIR__ . '/' . __NAMESPACE__ . PHP_EOL;

        AutoloaderFactory::factory(
            [
                'Zend\Loader\StandardAutoloader' => [
                    'autoregister_zf' => true,
                    'namespaces'      => [
                        __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
                    ],
                ],
            ]
        );
    }

    /**
     * FindParentPath
     *
     * @param String $path Path
     *
     * @return bool|string
     */
    protected static function findParentPath($path)
    {
        $dir         = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);

            if ($previousDir === $dir) {
                return false;
            }

            $previousDir = $dir;
        }

        return $dir . '/' . $path;
    }
}


// Set flag that we're in test mode
define('VUFIND_PHPUNIT_RUNNING', 1);

// Set path to this module
define('VUFIND_PHPUNIT_MODULE_PATH', __DIR__);

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__DIR__) . '/../..'));

// Define path to local override directory
defined('LOCAL_OVERRIDE_DIR')
|| define('LOCAL_OVERRIDE_DIR', (getenv('VUFIND_LOCAL_DIR') ? getenv('VUFIND_LOCAL_DIR') : ''));

chdir(APPLICATION_PATH);

echo APPLICATION_PATH;

// Ensure vendor/ is on include_path; some PEAR components may not load correctly
// otherwise (i.e. File_MARC may cause a "Cannot redeclare class" error by pulling
// from the shared PEAR directory instead of the local copy):
$pathParts = [];
$pathParts[] = APPLICATION_PATH . '/vendor';
$pathParts[] = get_include_path();
set_include_path(implode(PATH_SEPARATOR, $pathParts));

// Composer autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
    $loader = new \Composer\Autoload\ClassLoader();
    $loader->add('VuFindTest', __DIR__ . '/unit-tests/src');
    $loader->add('VuFindTest', __DIR__ . '/../src');
    // Dynamically discover all module src directories:
    $modules = opendir(__DIR__ . '/../..');
    while ($mod = readdir($modules)) {
        $mod = trim($mod, '.'); // ignore . and ..
        $dir = empty($mod) ? false : realpath(__DIR__ . "/../../{$mod}/src");
        if (!empty($dir) && is_dir($dir . '/' . $mod)) {
            $loader->add($mod, $dir);
        }
    }
    $loader->register();
}

define('PHPUNIT_SEARCH_FIXTURES', realpath(__DIR__ . '/../../VuFindSearch/tests/unit-tests/fixtures'));

Bootstrap::init();
