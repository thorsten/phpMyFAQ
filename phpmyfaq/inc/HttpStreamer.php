<?php
/**
* $Id: HttpStreamer.php,v 1.1 2006-07-20 21:44:45 matteo Exp $
*
* Simple HTTP Streamer
*
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2005-11-02
* @copyright    (c) 2005-2006 phpMyFAQ Team
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License"); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

// {{{ Constants
/**#@+
  * See RFC 1806: http://www.faqs.org/rfcs/rfc1806.html
  */
define("HTTP_CONTENT_DISPOSITION_ATTACHMENT", "attachment");
define("HTTP_CONTENT_DISPOSITION_INLINE", "inline");
/**#@-*/
/**#@+
  * HTTP GET Parameters EXPORT accepted values definitions
  */
define("EXPORT_DISPOSITION_ATTACHMENT", "attachment");
define("EXPORT_DISPOSITION_INLINE", "inline");
/**#@-*/
/**#@+
  * Output buffering management: set it to true to improve perfomances
  */
define("EXPORT_BUFFER_ENABLE", true);
/**#@-*/
// }}}

// {{{ Classes
/**
 * PMF_HttpStreamer Class
 *
 * This class manages the stream of a generic content
 * taking into account the correct http headers settings
 *
 * Currently it supports only 5 content (mime) types:
 * - DocBook: application/docbook+xml
 * - PDF: application/pdf
 * - XHTML: application/xhtml+xml
 * - XML: text/xml
 * - Generic file: application/octet-stream
 */
class PMF_HttpStreamer
{
    // {{{ PMF_HttpStreamer Properties
    /**
    * PMF export data type
    *
    * @var  $type
    */
    var $type;

    /**
    * HTTP Content Disposition
    *
    * @var  $disposition
    */
    var $disposition;

    /**
    * HTTP streaming data
    *
    * @var  $content
    */
    var $content;

    /**
    * HTTP streaming data length
    *
    * @var  $size
    */
    var $size;
    // }}}

    function PMF_HttpStreamer($type, $content)
    {
        $this->type        = $type;
        $this->disposition = HTTP_CONTENT_DISPOSITION_INLINE;
        $this->content     = $content;
        $this->size        = strlen($this->content);
    }

    function send($disposition)
    {
        if (isset($disposition)) {
            $this->disposition = $disposition;
        }

        // Sanity checks
        if(headers_sent()) {
            die("<b>PMF_HttpStreamer Class</b> error: unable to send my headers: someone already sent other headers!");
        }
        if (EXPORT_BUFFER_ENABLE) {
            if (ob_get_contents()) {
                die("<b>PMF_HttpStreamer Class</b>error: unable to send my data: someone already sent other data!");
            }
        }

        // Manage output buffering
        if (EXPORT_BUFFER_ENABLE) {
            ob_start();
        }
        // Send the right HTTP headers
        $this->_setHttpHeaders();
        // Send the raw content
        $this->_streamContent();
        // Manage output buffer flushing
        if (EXPORT_BUFFER_ENABLE) {
            ob_end_flush();
        }
    }

    function _setHttpHeaders()
    {
        $filename = "";
        $description = "";
        $mimeType = "";

        // Evaluate data upon export type request
        switch ($this->type) {
            case EXPORT_TYPE_DOCBOOK:
                $filename    = "phpmyfaq_docbook.xml";
                $description = "phpMyFaq DocBook export file";
                // http://www.docbook.org/specs/wd-docbook-docbook-4.5CR1.html#a.mimetype
                $mimeType    = "application/docbook+xml";
                break;
            case EXPORT_TYPE_PDF:
                $filename    = "phpmyfaq.pdf";
                $description = "phpMyFaq PDF export file";
                $mimeType    = "application/pdf";
                break;
            case EXPORT_TYPE_XHTML:
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
            case EXPORT_TYPE_XML:
                $filename    = "phpmyfaq.xml";
                $description = "phpMyFaq XML export file";
                $mimeType    = "text/xml";
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
        header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
        header("Expires: 0");
        header("Cache-Control: private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");

        // 2. Set the correct values for file streaming
        header("Content-Type: ".$mimeType);
        if (($this->disposition == HTTP_CONTENT_DISPOSITION_ATTACHMENT) && isset($_SERVER["HTTP_USER_AGENT"]) && !(strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") === false)) {
            header("Content-Type: application/force-download");
        }
        // RFC2616, §19.5.1: $filename must be a quoted-string
        header("Content-Disposition: ".$this->disposition."; filename=\"".PMF_Export::getExportTimestamp()."_".$filename."\"");
        if (!empty($description)) {
            header("Content-Description: ".$description);
        }
        header("Content-Transfer-Encoding: binary");
        // Deny partial downloads (opposite to: "Accept-Ranges: bytes")
        header("Accept-Ranges: none");
        header("Content-Length: ".$this->size);
    }

    function _streamContent()
    {
        print($this->content);
    }
}
// }}}
