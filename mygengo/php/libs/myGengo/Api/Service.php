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

class myGengo_Api_Service extends myGengo_Api
{
    public function __construct($api_key = null, $private_key = null)
    {
        parent::__construct($api_key, $private_key);
    }

    /**
     * translate/service/quote (POST)
     *
     * Returns credit quote and unit count for text based on content, tier, and language pair for job or jobs submitted
     *
     * @param an array of $jobs to be quoted
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getQuote($jobs, $format = null, $params = null)
    {
        $data = array('jobs' => $jobs);
        // create the query
        $params = array('api_key' => $this->config->get('api_key', null, true), 
                'ts' => gmdate('U'),
                'data' => json_encode($data));
        // sort and sign
        ksort($params);
        $enc_params = json_encode($params);
        $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));

	if (is_null($format))
	        $format = $this->config->get('format', null, true);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/service/quote";
        $this->response = $this->client->post($baseurl, $format, $params);
    }

    /**
     * translate/service/languages (GET)
     *
     * Returns a list of supported languages and their language codes.
     *
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getLanguages($format = null, $params = null)
    {
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/service/languages";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/service/language_pairs (GET)
     *
     * Returns supported translation language pairs, tiers, and credit
     * prices.
     *
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getLanguagePair($format = null, $params = null)
    {
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/service/language_pairs";
        $this->response = $this->client->get($baseurl, $format, $params);
    }
}
