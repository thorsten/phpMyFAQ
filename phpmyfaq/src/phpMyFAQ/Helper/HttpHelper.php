<?php

/**
 * HTTP Helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-13
 */

namespace phpMyFAQ\Helper;

use JsonException;
use phpMyFAQ\Helper;

/**
 * Class HttpHelper
 *
 * @package phpMyFAQ\Helper
 * @deprecated
 */
class HttpHelper extends Helper
{
    /** @var string Content type */
    private string $contentType = '';

    /** @var int HTTP status code */
    private int $statusCode = 200;

    /** @var string[] Array of HTTP header entries */
    private array $headers = [];

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
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Powered-By: phpMyFAQ');
    }

    /**
     * Adds an extra header.
     */
    public function addExtraHeader(string $header): void
    {
        header($header);
    }

    /**
     * Starts Gzip compression.
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
     * Returns the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * URL to redirect
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
     * @param mixed           $payload What to send
     * @param string[]|string $headers Which headers to send
     * @throws JsonException
     */
    public function sendJsonWithHeaders(mixed $payload, array|string $headers = ''): void
    {
        $this->sendWithHeaders($payload, $headers, true);
    }

    /**
     * Sends any kind of data with optional HTTP headers as text or JSON.
     *
     * @param mixed           $payload What to send
     * @param string|string[] $headers Which headers to send
     * @param bool            $isJson Send as JSON?
     * @throws JsonException
     */
    public function sendWithHeaders(mixed $payload, array|string $headers = '', bool $isJson = false): void
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
            echo json_encode($payload, JSON_THROW_ON_ERROR);
        } else {
            echo $payload;
        }
    }
}
