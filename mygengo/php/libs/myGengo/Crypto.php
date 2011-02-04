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

class myGengo_Crypto
{
    const HMAC_ALGO = 'sha1';

    private function __construct() {}

    /**
     * @param string $data The data to sign
     * @param string $private_key The key used to sign the data
     *
     * @return string Base64 signature of the data
     */
    public static function sign($data, $private_key)
    {
        return hash_hmac(self::HMAC_ALGO, $data, $private_key);
    }
}
