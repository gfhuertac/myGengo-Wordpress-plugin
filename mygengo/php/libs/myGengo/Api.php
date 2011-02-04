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

abstract class myGengo_Api
{
    protected $config;
    protected $response;
    protected $client;

    public function __construct($api_key = null, $private_key = null)
    {
        $this->config = myGengo_Config::getInstance();
        if (! is_null($api_key))
        {
            $this->config->api_key = $api_key;
        }
        if (! is_null($private_key))
        {
            $this->config->private_key = $private_key;
        }
        $this->client = myGengo_Client::getInstance();
        $this->response = null;
    }

    /**
     * @param string $api_key Overwrite or set the api_key
     * @return void
     */
    public function setApiKey($api_key)
    {
        $this->config->set('api_key', $api_key);
    }

    /**
     * @param string $private_key Overwrite or set the private_key
     * @return void
     */
    public function setPrivateKey($private_key)
    {
        $this->config->set('private_key', $private_key);
    }

    /**
     * @param string $format Overwrite or set the requested response
     * format (xml or json)
     * @return void
     */
    public function setResponseFormat($format)
    {
        $format = strtolower($format);
        $valid = array('xml', 'json');
        if (! in_array($format, $valid))
        {
            throw new myGengo_Exception("Invalid response format: {$format}, accepted formats are: xml or json.");
        }
        $this->config->format = $format;
    }

    /**
     * @param string $url Overwrite or set the api base url
     * @return void
     */
    public function setBaseUrl($url)
    {
        // make sure it ends with forward slash
        $this->config->baseurl = rtrim($url, '\//') . '/';
    }

    /**
     * @param bool $raw True or False (false by default)
     * @return string If $raw is true the raw body (as transfered on
     * wire) will be returned, if false the decoded body
     */
    public function getResponseBody($raw = false)
    {
        $this->checkResponse();
        if ($raw)
        {
            return $this->response->getRawBody();
        }
        return $this->response->getBody();
    }

    /**
     * @return int The HTTP response status code
     */
    public function getResponseCode()
    {
        $this->checkResponse();
        return $this->response->getStatus();
    }

    /**
     * @param string $key The HTTP header to return
     * @return string The requested HTTP header or null if header does
     * not exists
     */
    public function getResponseHeader($key)
    {
        $this->checkResponse();
        return $this->response->getHeader($key);
    }

    /**
     * @param bool $as_array True or false (false by default)
     */
    public function getResponseHeaders($as_array = false)
    {
        $this->checkResponse();
        if ($as_array)
        {
            return $this->response->getHeaders();
        }
        return $this->response->getHeadersAsString();
    }

    protected function checkResponse()
    {
        if (is_null($this->response))
        {
            throw new myGengo_Exception("A valid response is not yet available, please make a request first.");
        }
    }

    public function __toString()
    {
        if (is_null($this->response))
        {
            return '';
        }
        return $this->response->__toString();
    }

    /**
     * @param string $client The name of the clinet to instantiate (job, jobs, account or service)
     * @param string $api_key user api key
     * @param string $private_key user secret key
     * @return myGengo_Api A myGengo Api client
     */
    public static function factory($client, $api_key = null, $private_key = null)
    {
        switch ($client)
        {
        case 'job':
            return new myGengo_Api_Job($api_key, $private_key);
        case 'jobs':
            return new myGengo_Api_Jobs($api_key, $private_key);
        case 'account':
            return new myGengo_Api_Account($api_key, $private_key);
        case 'service':
            return new myGengo_Api_Service($api_key, $private_key);
        }
        throw new myGengo_Exception("Invalid client: {$client}, accepted clients are: job,jobs,account and service.");
    }

    /**
     * Set the passed parameters that are null with default
     * configuration values
     */
    protected function setParams(&$id, &$format, &$params)
    {
        if (is_null($id))
        {
            $id = $this->config->get('job_id', null, true);
        }
        if (is_null($format))
        {
            $format = $this->config->get('format', null, true);
        }
        if (is_null($params))
        {
            $private_key = $this->config->get('private_key', null, true);
            $params = array();
            $params['ts'] = gmdate('U');
            $params['api_key'] = $this->config->get('api_key', null, true);
            ksort($params);
            $query = http_build_query($params);
            $params['api_sig'] = myGengo_Crypto::sign($query, $private_key);
        }
    }

    /**
     * Set the passed parameters that are null with default
     * configuration values
     */
    protected function setParamsNotId(&$format, &$params)
    {
        $hack = 0;
        $this->setParams($hack, $format, $params);
    }
}
