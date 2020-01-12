<?php

/**
 * HTTP Helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-13
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Helper;
use phpMyFAQ\Strings;

/**
 * Class HttpHelper
 *
 * @package phpMyFAQ\Helper
 */
class HttpHelper extends Helper
{
    /**
     * Content type
     *
     * @var string
     */
    private $contentType = '';

    /**
     * HTTP status code
     *
     * @var int
     */
    private $statusCode = 200;

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
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Vary: Negotiate,Accept');
        header('Content-type: ' . $this->contentType);
    }

    /**
     * Adds an additional header.
     *
     * @param string $header
     */
    public function addAdditionalHeader(string $header)
    {
        header($header);
    }

    /**
     *
     */
    public function startCompression()
    {
        if (false === $this->config->get('main.enableGzipCompression') || !DEBUG) {
            ob_start('ob_gzhandler');
        }
    }

    /**
     *  Sends the CORS header.
     */
    public function sendCorsHeader()
    {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }

    /**
     * Returns the HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * URL to redirect
     *
     * @param string $url
     */
    public function redirect(string $url)
    {
        // Before a redirection we must force the PHP session update
        // for preventing data loss
        session_write_close();
        $this->setStatus(301);
        header('Location: ' . $url);
    }

    /**
     * Sets a HTTP status header.
     *
     * @param int $code HTTP status code
     */
    public function setStatus(int $code)
    {
        $this->statusCode = $code;
        http_response_code($code);
    }

    /**
     * Sends any kind of data with optional HTTP headers as JSON.
     *
     * @param mixed        $payload What to send
     * @param string|array $headers Which headers to send
     *
     * @return void
     */
    public function sendJsonWithHeaders($payload, $headers = '')
    {
        $this->sendWithHeaders($payload, $headers, true);
    }

    /**
     * Sends any kind of data with optional HTTP headers as text or JSON.
     *
     * @param mixed        $payload What to send
     * @param string|array $headers Which headers to send
     * @param bool         $isJson  Send as JSON?
     *
     * @return void
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
            echo json_encode($payload);
        } else {
            echo $payload;
        }
    }
}
