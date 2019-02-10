<?php

/**
 * HTTP Helper class for phpMyFAQ.
 *
 * PHP Version 5.5.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-13
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Http.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-13
 */
class PMF_Helper_Http extends PMF_Helper
{
    /**
     * Content type.
     *
     * @var string
     */
    private $contentType = '';

    /**
     * Setter for content type.
     *
     * @param string $contentType Content type
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Returns the Header.
     */
    public function addHeader()
    {
        header('Expires: Thu, 07 Apr 1977 14:47:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Vary: Negotiate,Accept');
        header('Content-type: '.$this->contentType);
    }

    /**
     * Returns a HTTP status header.
     *
     * @param int $code HTTP status code
     */
    public function sendStatus($code)
    {
        switch ($code) {
            case 301:
                header('HTTP/1.1 301 Moved Permanently');
                break;
            case 403:
                header('HTTP/1.1 403 Forbidden');
                break;
            case 404:
                if (('cgi' == PMF_String::substr(PHP_SAPI, 0, 3)) || isset($_SERVER['ALL_HTTP'])) {
                    header('Status: 404 Not Found');
                } else {
                    header('HTTP/1.0 404 Not Found');
                }
                break;
        }

        exit();
    }

    /**
     * @param string $url URL to redirect
     */
    public function redirect($url)
    {
        header('Location: '.$url);
    }

    /**
     * Sends any kind of data with optional HTTP headers as JSON.
     *
     * @param mixed        $payload What to send
     * @param string|array $headers Which headers to send
     *
     * @return string
     */
    public function sendJsonWithHeaders($payload, $headers = '')
    {
        return $this->sendWithHeaders($payload, $headers, true);
    }

    /**
     * Sends any kind of data with optional HTTP headers as text or JSON.
     *
     * @param mixed        $payload What to send
     * @param string|array $headers Which headers to send
     * @param bool         $isJson  Send as JSON?
     *
     * @return string
     */
    public function sendWithHeaders($payload, $headers = '', $isJson = false)
    {
        $validHeaders = [];
        if (is_string($headers) && strlen($headers) > 0) {
            $validHeaders[] = $headers;
        } elseif (is_array($headers)) {
            foreach ($headers as $header) {
                if (strlen($header) > 0) {
                    $validHeaders[] = $header;
                }
            }
        }
        foreach ($validHeaders as $header) {
            header($header);
        }
        if ($isJson) {
            header('Content-Type: application/json');
            print json_encode($payload);
        } else {
            print $payload;
        }
    }
}
