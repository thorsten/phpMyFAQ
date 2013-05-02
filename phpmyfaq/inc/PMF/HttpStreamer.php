<?php
/**
 * Simple HTTP Streamer
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 *  v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_HttpStreamer
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-11-02
 */

use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_HttpStreamer Class
 *
 * This class manages the stream of a generic content
 * taking into account the correct http headers settings
 *
 * Currently it supports only 4 content (mime) types:
 * - PDF: application/pdf
 * - XHTML: application/xhtml+xml
 * - XML: text/xml
 * - Generic file: application/octet-stream
 *
 * @category  phpMyFAQ
 * @package   PMF_HttpStreamer
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-11-02
 */
class PMF_HttpStreamer
{
    /**
     * HTTP content disposition attachment constant
     *
     * @var string
     */
    const HTTP_CONTENT_DISPOSITION_ATTACHMENT = 'attachment';
    
    /**
     * HTTP content disposition inline constant
     *
     * @var string
     */
    const HTTP_CONTENT_DISPOSITION_INLINE = 'inline';
    
    /**
     * Disposition attachment constant
     *
     * @var string
     */
    const EXPORT_DISPOSITION_ATTACHMENT = 'attachment';
    
    /**
     * Disposition inline constant
     *
     * @var string
     */
    const EXPORT_DISPOSITION_INLINE = 'inline';
    
    /**
     * Enable buffer
     *
     * @var boolean
     */
    const EXPORT_BUFFER_ENABLE = true;
    
    /**
     * PMF export data type
     *
     * @var string
     */
    private $type;

    /**
     * HTTP Content Disposition
     *
     * @var string
     */
    private $disposition;

    /**
     * HTTP streaming data length
     *
     * @var integer
     */
    private $size;

    /**
     * @var Response
     */
    private $response;

    /**
     * Constructor
     *
     * @param Response $response
     * @param string   $type     Type
     * @param string   $content  Content
     */
    public function __construct(Response $response, $type, $content)
    {
        $this->response    = $response;
        $this->type        = $type;
        $this->disposition = self::HTTP_CONTENT_DISPOSITION_INLINE;
        $this->size        = strlen($content);

        $response->setContent($content);
    }

    /**
     * Sends data
     *
     * @param  string $disposition Disposition
     *
     * @return void
     */
    public function send($disposition)
    {
        if (isset($disposition)) {
            $this->disposition = $disposition;
        }

        // Sanity checks
        if (headers_sent()) {
            die("<b>PMF_HttpStreamer Class</b> error: unable to send my headers: someone already sent other headers!");
        }
        if (self::EXPORT_BUFFER_ENABLE) {
            if (ob_get_contents()) {
                die("<b>PMF_HttpStreamer Class</b> error: unable to send my data: someone already sent other data!");
            }
        }

        // Manage output buffering
        if (self::EXPORT_BUFFER_ENABLE) {
            ob_start();
        }
        $this->response->send();
        // Manage output buffer flushing
        if (self::EXPORT_BUFFER_ENABLE) {
            ob_end_flush();
        }
    }

    /**
     * Sends HTTP Headers
     *
     * @return void
     */
    private function _setHttpHeaders()
    {
        $filename = $description = $mimeType = '';

        // Evaluate data upon export type request
        switch ($this->type) {
            case 'pdf':
                $filename    = "phpmyfaq.pdf";
                $description = "phpMyFaq PDF export file";
                $mimeType    = "application/pdf";
                break;
            case 'xhtml':
                $filename    = "phpmyfaq.xhtml";
                $description = "phpMyFaq XHTML export file";
                $mimeType    = "text/html";
                // Why not: text/html or text/xml?
                // See e.g.: http://www.hixie.ch/advocacy/xhtml
                // Unfortunaltelly IE doesn't handle it correctly :(
                // so currenctly we must use text/html as default.
                // See e.g.: http://keystonewebsites.com/articles/mime_type.php
                if (isset($_SERVER["HTTP_ACCEPT"]) && !(strpos($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml") === false)) {
                    $mimeType = "application/xhtml+xml";
                }
                break;
            case 'xml':
                $filename    = "phpmyfaq.xml";
                $description = "phpMyFaq XML export file";
                $mimeType    = "text/xml";
                break;
            case 'csv':
                $filename    = "phpmyfaq.csv";
                $description = "phpMyFaq CSV export file";
                $mimeType    = "text/csv";
                break;
            // In this case no default statement is required:
            // the one above is just for clean coding style
            default:
                $filename    = "phpmyfaq.pmf";
                $description = "Generic file";
                $mimeType    = "application/octet-stream";
                break;
        }

        // Set the correct HTTP headers:
        // 1. Prevent proxies&browsers caching
        $this->response->headers->set("Last-Modified", gmdate("D, d M Y H:i:s") . " GMT");
        $this->response->headers->set("Expires", "0");
        $this->response->headers->set("Cache-Control", "private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        $this->response->headers->set("Pragma", "no-cache");

        // 2. Set the correct values for file streaming
        if (($this->disposition == self::HTTP_CONTENT_DISPOSITION_ATTACHMENT) && 
             isset($_SERVER["HTTP_USER_AGENT"]) && !(strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") === false)) {
            $this->response->headers->set("Content-Type", "application/force-download");
        } else {
            $this->response->headers->set("Content-Type", $mimeType);
        }
        // RFC2616, �19.5.1: $filename must be a quoted-string
        $this->response->headers->set("Content-Disposition", $this->disposition."; filename=\"" . PMF_Export::getExportTimestamp() . "_" . $filename."\"");
        if (!empty($description)) {
            $this->response->headers->set("Content-Description", $description);
        }
        $this->response->headers->set("Content-Transfer-Encoding", "binary");
        $this->response->headers->set("Accept-Ranges", "none");
        $this->response->headers->set("Content-Length", $this->size);
    }
}