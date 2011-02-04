<?php
/**
 * myGengo API Client
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that came
 * with this package in the file LICENSE.txt. It is also available 
 * through the world-wide-web at this URL:
 * http://mygengo.com/services/api/dev-docs/mygengo-code-license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@mygengo.com so we can send you a copy immediately.
 *
 * @category   myGengo
 * @package    API Client Library
 * @copyright  Copyright (c) 2009-2010 myGengo, Inc. (http://mygengo.com)
 * @license    http://mygengo.com/services/api/dev-docs/mygengo-code-license  New BSD License
 */

/**
 * This Init class provides some helper functionality for the examples,
 * such as auto-loading and error-handling, 
 * and is not required by the client library.  
 */

define('MYGENGO_BASE', dirname(__FILE__));

class Init
{
    public function __construct()
    {
        // We have created an error handler for convenience.
        // If you wish to use it, uncomment the following line.
        // set_error_handler(array($this, 'error_handler'));

        // include this api classpath
        $include_path = get_include_path() . PATH_SEPARATOR;
        $include_path .= MYGENGO_BASE .'/libs';
        set_include_path($include_path);

        // set our own class autoloader if one doesn't already exist
        if (false === spl_autoload_functions())
        {
            if (function_exists('__autoload')) {
                spl_autoload_register('__autoload');
            }
        }
        spl_autoload_register(array('Init', 'autoload'));

        // set:
        // - internal character encoding
        // - timezone
        mb_internal_encoding('UTF-8');
        if (function_exists('date_default_timezone_set'))
        {
            date_default_timezone_set(myGengo_Config::getInstance()->get('timezone', 'Asia/Tokyo'));
        }
    }

    public function error_handler($errno, $errstr, $errfile = '', $errline = -1, $errctx = null)
    {
        $ex = new myGengo_Exception($errstr, $errno);
        $ex->setFile($errfile);
        $ex->setLine($errline);
        throw $ex;
    }

    /**
     * We use our own autoloader, but restricted to myGengo classes
     **/
    public static function autoload($classname)
    {
        if (false !== strpos($classname, 'myGengo') ||
            false !== strpos($classname, 'Zend_')) 
        {
            $classpath = str_replace('_', '/', $classname) . '.php';
            require_once $classpath;
        }
    }

}
new Init();
