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
 * @license    http://mygengo.com/services/api/dev-docs/mygengo-code-license   New BSD License
 */

class myGengo_Config
{
    protected static $instance = null;
    protected $data;
    protected $do_not_write = array("api_key","private_key");

    protected function __construct()
    {
        $configpath = MYGENGO_BASE . '/config.ini';
        if (file_exists($configpath))
        {
            $this->data = parse_ini_file($configpath);
        }
        else {
            $this->data = array();
        }
    }

    public function __get($name)
    {
        return $this->get($name, null);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __isset($name)
    {
        if (array_key_exists($name, $this->data))
        {
            return !empty($this->data[$name]);
        }
        return false;
    }

    public function __unset($name)
    {
        if (array_key_exists($name, $this->data))
        {
            unset($this->data[$name]);
        }
    }

    public function get($name, $default = null, $must_exists = false)
    {
        if (array_key_exists($name, $this->data))
        {
            if ($must_exists && empty($this->data[$name]))
            {
                throw new myGengo_Exception("Configuration field: {$name} is missing or empty.");
            }
            return $this->data[$name];
        }
        if ($must_exists)
        {
            throw new myGengo_Exception("Configuration field: {$name} is missing or empty.");
        }
        return $default;
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;
	$configpath = MYGENGO_BASE . '/config.ini';
	$this->write_php_ini($this->data, $configpath);
    }

    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

	private function write_php_ini($array, $file) {
		$res = array();
		foreach($array as $key => $val) {
			if (!in_array ( $key , $this->do_not_write )) {
				if(is_array($val)) {
					$res[] = "[$key]";
					foreach($val as $skey => $sval) 
						$res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
				}
				else 
					$res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
			}
		}
		$this->safefilerewrite($file, implode("\r\n", $res));
	}

	private function safefilerewrite($fileName, $dataToSave) {
		if ($fp = fopen($fileName, 'w')) {
			$startTime = microtime();
			do {
				$canWrite = flock($fp, LOCK_EX);
				if(!$canWrite) usleep(round(rand(0, 100)*1000));
			} while ((!$canWrite)and((microtime()-$startTime) < 1000));
			if ($canWrite) {            
				fwrite($fp, $dataToSave);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}

	}
}
