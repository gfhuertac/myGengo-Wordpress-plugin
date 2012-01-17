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

class myGengo_Client
{
    protected static $instance = null;
    protected $config;
    protected $client;

    protected function __construct()
    {
        $this->config = myGengo_Config::getInstance();
        $config = array('maxredirects' => 5,
                        'useragent' => 'myGengo plugin for Wordpress; Version phpMyGengo 1.0; http://gonzalo.huerta.cl/;',
                        'timeout' => 10,
                        'keepalive' => false);
        $this->client = new Zend_Http_Client(null, $config);
    }

    public function get($url, $format = null, array $params = null)
    {
        $this->client->resetParameters(true);
        return $this->request($url, Zend_Http_Client::GET, $format, $params);
    }

    public function post($url, $format = null, array $params = null)
    {
        $this->client->resetParameters(true);
        return $this->request($url, Zend_Http_Client::POST, $format, $params);
    }

    public function put($url, $format = null, array $params = null)
    {
        $this->client->resetParameters(true);
        return $this->request($url, Zend_Http_Client::PUT, $format, $params);
    }

    public function delete($url, $format = null, array $params = null)
    {
        $this->client->resetParameters(true);
        return $this->request($url, Zend_Http_Client::DELETE, $format, $params);
    }

    protected function request($url, $method, $format = null, $params = null)
    {
        $method = strtoupper($method);
        $methods = array('GET','POST','PUT','DELETE');
        if (! in_array($method, $methods))
        {
            throw new myGengo_Exception("HTTP method: {$method} not supported");
        }
        if (! is_null($format) && is_string($format))
        {
            $format = strtolower($format);
            $formats = array('json', 'xml');
            if (! in_array($format, $formats))
            {
                throw new myGengo_Exception("Invalid response format: {$format}, accepted formats are: json or xml.");
            }
            switch ($format)
            {
            case 'xml':
                $this->client->setHeaders('Accept', 'application/xml');
                break;
            case 'json':
                $this->client->setHeaders('Accept', 'application/json');
                break;
            }
        }
        if (! is_null($params))
        {
            switch ($method)
            {
            case 'DELETE':
            case 'GET':
                $this->client->setParameterGet($params);
                break;
            case 'POST':
                $this->client->setParameterPost($params);
                break;
            case 'PUT':
                if (is_array($params))
                {
                    $params = http_build_query($params);
                }
                $this->client->setRawData($params, Zend_Http_Client::ENC_URLENCODED);
                break;
            }
        }
        try {
            $this->client->setUri($url);
            if ($this->config->get('debug', false))
            {
                $response = $this->client->request($method);
                /*echo $this->client->getUri(true);
                echo "\n";
                echo $this->client->getLastRequest();
                echo "\n";*/
                return $response;
            }
            return $this->client->request($method);
        }
        catch (Exception $ex) {
            throw new myGengo_Exception($ex->getMessage(), $ex->getCode());
        }
    }

    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
