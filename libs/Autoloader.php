<?php
/**
 * Smarty Autoloader
 *
 * @package    Smarty
 */

/**
 * Smarty Autoloader
 *
 * @package    Smarty
 * @author     Uwe Tews
 *             Usage:
 *                  require_once '...path/Autoloader.php';
 *                  Smarty_Autoloader::register();
 *             or
 *                  include '...path/bootstrap.php';
 *
 *                  $smarty = new Smarty();
 */
class Database_Autoloader
{
   /**
     * Filepath to Smarty root
     *
     * @var string
     */
    public static $SMARTY_DIR = null;

    /**
     * Filepath to Smarty internal plugins
     *
     * @var string
     */
    public static $SMARTY_SYSPLUGINS_DIR = null;

    /**
     * Array with Smarty core classes and their filename
     *
     * @var array
     */
    public static $rootClasses = array('database' => 'database.class.php',);

    /**
     * Registers Smarty_Autoloader backward compatible to older installations.
     *
     * @param bool $prepend Whether to prepend the autoloader or not.
     */
    public static function registerBC($prepend = false)
    {
        /**
         * register the class autoloader
         */
        if (!defined('DATABASE_SPL_AUTOLOAD')) {
            define('DATABASE_SPL_AUTOLOAD', 0);
        }

      self::register($prepend);
        
    }

    /**
     * Registers Smarty_Autoloader as an SPL autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not.
     */
    public static function register($prepend = false)
    {
        self::$DATABASE_DIR = defined('DATABASE_DIR') ? DATABASE_DIR : dirname(__FILE__) . DIRECTORY_SEPARATOR;
        
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            spl_autoload_register(array(__CLASS__, 'autoload'), true, $prepend);
        } else {
            spl_autoload_register(array(__CLASS__, 'autoload'));
        }
    }

    /**
     * Handles auto loading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoload($class)
    {
        if ($class[ 0 ] !== 'S' && strpos($class, 'Smarty') !== 0) {
            return;
        }
        $_class = strtolower($class);
        if (isset(self::$rootClasses[ $_class ])) {
            $file = self::$DATABASE_DIR . self::$rootClasses[ $_class ];
            if (is_file($file)) {
                include $file;
            }
        } else {
            $file = self::$DATABASE_DIR . $_class . '.php';
            if (is_file($file)) {
                include $file;
            }
        }
        return;
    }
}
