<?php
/**
 * MIT License
 *
 * Copyright (c) 2023 Cardinity Payment Gateway
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    DIGITAL RETAIL TECHNOLOGIES SL <mail@simlachat.com>
 *  @copyright 2023 Cardinity Payment Gateway
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

/**
 * Log OAuth requests
 *
 * @version $Id: OAuthRequestLogger.php 98 2010-03-08 12:48:59Z brunobg@corollarium.com $
 *
 * @author Marc Worrell <marcw@pobox.com>
 *
 * @date  Dec 7, 2007 12:22:43 PM
 *
 * The MIT License
 *
 * Copyright (c) 2007-2008 Mediamatic Lab
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class OAuthRequestLogger
{
    private static $logging = 0;
    private static $enable_logging = null;
    private static $store_log = null;
    private static $note = '';
    private static $user_id = null;
    private static $request_object = null;
    private static $sent = null;
    private static $received = null;
    private static $log = [];

    /**
     * Start any logging, checks the system configuration if logging is needed.
     *
     * @param OAuthRequest $request_object
     */
    public static function start($request_object = null)
    {
        if (defined('OAUTH_LOG_REQUEST')) {
            if (null === OAuthRequestLogger::$enable_logging) {
                OAuthRequestLogger::$enable_logging = true;
            }
            if (null === OAuthRequestLogger::$store_log) {
                OAuthRequestLogger::$store_log = true;
            }
        }

        if (OAuthRequestLogger::$enable_logging && !OAuthRequestLogger::$logging) {
            OAuthRequestLogger::$logging = true;
            OAuthRequestLogger::$request_object = $request_object;
            ob_start();

            // Make sure we flush our log entry when we stop the request (eg on an exception)
            register_shutdown_function(['OAuthRequestLogger', 'flush']);
        }
    }

    /**
     * Force logging, needed for performing test connects independent from the debugging setting.
     *
     * @param bool  store_log        (optional) true to store the log in the db
     */
    public static function enableLogging($store_log = null)
    {
        OAuthRequestLogger::$enable_logging = true;
        if (null !== $store_log) {
            OAuthRequestLogger::$store_log = $store_log;
        }
    }

    /**
     * Logs the request to the database, sends any cached output.
     * Also called on shutdown, to make sure we always log the request being handled.
     */
    public static function flush()
    {
        if (OAuthRequestLogger::$logging) {
            OAuthRequestLogger::$logging = false;

            if (null === OAuthRequestLogger::$sent) {
                // What has been sent to the user-agent?
                $data = ob_get_contents();
                if ('' !== $data) {
                    ob_end_flush();
                } elseif (ob_get_level()) {
                    ob_end_clean();
                }
                $hs = headers_list();
                $sent = implode("\n", $hs) . "\n\n" . $data;
            } else {
                // The request we sent
                $sent = OAuthRequestLogger::$sent;
            }

            if (null === OAuthRequestLogger::$received) {
                // Build the request we received
                $hs0 = self::getAllHeaders();
                $hs = [];
                foreach ($hs0 as $h => $v) {
                    $hs[] = "$h: $v";
                }

                $data = '';
                $fh = @fopen('php://input', 'r');
                if ($fh) {
                    while (!feof($fh)) {
                        $s = fread($fh, 1024);
                        if (is_string($s)) {
                            $data .= $s;
                        }
                    }
                    fclose($fh);
                }
                $received = implode("\n", $hs) . "\n\n" . $data;
            } else {
                // The answer we received
                $received = OAuthRequestLogger::$received;
            }

            // The request base string
            if (OAuthRequestLogger::$request_object) {
                $base_string = OAuthRequestLogger::$request_object->signatureBaseString();
            } else {
                $base_string = '';
            }

            // Figure out to what keys we want to log this request
            $keys = [];
            if (OAuthRequestLogger::$request_object) {
                $consumer_key = OAuthRequestLogger::$request_object->getParam('oauth_consumer_key', true);
                $token = OAuthRequestLogger::$request_object->getParam('oauth_token', true);

                switch (get_class(OAuthRequestLogger::$request_object)) {
                    // tokens are access/request tokens by a consumer
                    case 'OAuthServer':
                    case 'OAuthRequestVerifier':
                        $keys['ocr_consumer_key'] = $consumer_key;
                        $keys['oct_token'] = $token;
                        break;

                        // tokens are access/request tokens to a server
                    case 'OAuthRequester':
                    case 'OAuthRequestSigner':
                        $keys['osr_consumer_key'] = $consumer_key;
                        $keys['ost_token'] = $token;
                        break;
                }
            }

            // Log the request
            if (OAuthRequestLogger::$store_log) {
                $store = OAuthStore::instance();
                $store->addLog($keys, $received, $sent, $base_string, OAuthRequestLogger::$note, OAuthRequestLogger::$user_id);
            }

            OAuthRequestLogger::$log[] = [
                'keys' => $keys,
                'received' => $received,
                'sent' => $sent,
                'base_string' => $base_string,
                'note' => OAuthRequestLogger::$note,
            ];
        }
    }

    /**
     * Add a note, used by the OAuthException2 to log all exceptions.
     *
     * @param string note
     */
    public static function addNote($note)
    {
        OAuthRequestLogger::$note .= $note . "\n\n";
    }

    /**
     * Set the OAuth request object being used
     *
     * @param OAuthRequest request_object
     */
    public static function setRequestObject($request_object)
    {
        OAuthRequestLogger::$request_object = $request_object;
    }

    /**
     * Set the relevant user (defaults to the current user)
     *
     * @param int user_id
     */
    public static function setUser($user_id)
    {
        OAuthRequestLogger::$user_id = $user_id;
    }

    /**
     * Set the request we sent
     *
     * @param string request
     */
    public static function setSent($request)
    {
        OAuthRequestLogger::$sent = $request;
    }

    /**
     * Set the reply we received
     *
     * @param string request
     */
    public static function setReceived($reply)
    {
        OAuthRequestLogger::$received = $reply;
    }

    /**
     * Get the the log till now
     *
     * @return array
     */
    public static function getLog()
    {
        return OAuthRequestLogger::$log;
    }

    /**
     * helper to try to sort out headers for people who aren't running apache,
     * or people who are running PHP as FastCGI.
     *
     * @return array of request headers as associative array
     */
    public static function getAllHeaders()
    {
        $retarr = [];
        $headers = [];

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            ksort($headers);

            return $headers;
        } else {
            $headers = array_merge($_ENV, $_SERVER);

            foreach ($headers as $key => $val) {
                // we need this header
                if (false !== strpos(strtolower($key), 'content-type')) {
                    continue;
                }
                if ('HTTP_' != strtoupper(substr($key, 0, 5))) {
                    unset($headers[$key]);
                }
            }
        }

        // Normalize this array to Cased-Like-This structure.
        foreach ($headers as $key => $value) {
            $key = preg_replace('/^HTTP_/i', '', $key);
            $key = str_replace(
                ' ',
                '-',
                ucwords(strtolower(str_replace(['-', '_'], ' ', $key)))
            );
            $retarr[$key] = $value;
        }
        ksort($retarr);

        return $retarr;
    }
}

/* vi:set ts=4 sts=4 sw=4 binary noeol: */
