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
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-13
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Helper;

/**
 * Class HttpHelper
 *
 * @package phpMyFAQ\Helper
 */
class HttpHelper extends Helper
{
    /** @var string Content type */
    private $contentType = '';

    /** @var int HTTP status code */
    private $statusCode = 200;

    /** @var string[] Array of HTTP header entries */
    private $headers = [];

    /**
     * Setter for content type.
     *
     * @param string $contentType Content type
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Sets some Header.
     */
    public function addHeader(): void
    {
        header('Expires: Thu, 07 Apr 1977 14:47:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store');
        header('Pragma: no-cache');
        header('Vary: Negotiate,Accept');
        header('Content-type: ' . $this->contentType);
    }

    /**
     * Adds an additional header.
     *
     * @param string $header
     */
    public function addAdditionalHeader(string $header): void
    {
        header($header);
    }

    /**
     *
     */
    public function startCompression(): void
    {
        if (false === $this->config->get('main.enableGzipCompression') || !DEBUG) {
            ob_start('ob_gzhandler');
        }
    }

    /**
     *  Sends the CORS header.
     */
    public function sendCorsHeader(): void
    {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }

    /**
     * Fetch all HTTP request headers.
     */
    public function fetchAllHeaders(): void
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $this->headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
            }
        }
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
     * Returns the HTTP header value for "X-PMF-Token"
     * @return string
     */
    public function getClientApiToken(): string
    {
        return $this->headers['x-pmf-token'] ?? '';
    }

    /**
     * URL to redirect
     *
     * @param string $url
     */
    public function redirect(string $url): void
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
    public function setStatus(int $code): void
    {
        $this->statusCode = $code;
        http_response_code($code);
    }

    /**
     * Sends any kind of data with optional HTTP headers as JSON.
     *
     * @param mixed        $payload What to send
     * @param string|array $headers Which headers to send
     */
    public function sendJsonWithHeaders($payload, $headers = ''): void
    {
        $this->sendWithHeaders($payload, $headers, true);
    }

    /**
     * Sends any kind of data with optional HTTP headers as text or JSON.
     *
     * @param mixed        $payload What to send
     * @param string|array $headers Which headers to send
     * @param bool         $isJson  Send as JSON?
     */
    public function sendWithHeaders($payload, $headers = '', $isJson = false): void
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
