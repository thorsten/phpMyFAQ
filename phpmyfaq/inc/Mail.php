<?php
/**
 * MUA (Mail User Agent) implementation.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since     2009-09-11
 * @version   SVN: $Id$
 * @copyright 2009 phpMyFAQ Team
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

/**
 * PHP 6 script encoding
 *
 */
declare(encoding='latin1');

/**
 * MUA (Mail User Agent) implementation.
 *
 * @package     phpMyFAQ 
 * @access      public
 */ 
class PMF_Mail
{
    /**
     * Type of the used MUA. Possible values:
     * - built-in.     
     *
     * @access public
     * @var string $agent
     */
    public $agent;

    /**
     * Attached filed.
     *
     * @access public
     * @var mixed $attachments
     */
    public $attachments;

    /**
     * Body of the e-mail.
     *
     * @access public
     * @var string $body
     */
    public $body = '';

    /**
     * Boundary.
     *
     * @access public
     * @var string $boundary
     */
    public $boundary = '----------';

    /**
     * Charset.
     *
     * @access public
     * @var string $charset
     */
    public $charset = 'iso-8859-1';

    /**
     * Content disposition.
     *
     * @access public
     * @var string $contentDisposition
     */
    public $contentDisposition = 'inline';

    /**
     * Content type.
     *
     * @access public
     * @var string $contentType
     */
    public $contentType = 'text/plain';

    /**
     * Content transfer encoding.
     *
     * @access public
     * @var string $contentTransferEncoding
     */
    public $contentTransferEncoding = '8bit';

    /**
     * The one and only valid End Of Line sequence as per RFC 2822:
     * carriage-return followed by line-feed.     
     *
     * @access public
     * @var string $eol
     */
    public $eol = "\r\n";

    /**
     * Headers of the e-mail.
     *
     * @access public
     * @var string $headers
     */
    public $headers;

    /**
     * Message of the e-mail: HTML text allowed.
     *
     * @access public
     * @var string $message
     */
    public $message;

    /**
     * Alternate message of the e-mail: only plain text allowed.
     *
     * @access public
     * @var string $messageAlt
     */
    public $messageAlt;

    /**
     * Message-ID of the e-mail.
     *
     * @access public
     * @var string $messageId
     */
    public $messageId;

    /**
     * Priorities: 1 (Highest), 2 (High), 3 (Normal), 4 (Low), 5 (Lowest).
     *
     * @access public
     * @var mixed $priorities
     */
    public $priorities = array(
        1 => 'Highest',
        2 => 'High',
        3 => 'Normal',
        4 => 'Low',
        5 => 'Lowest'
    );

    /**
     * Priority of the e-mail: 1 (Highest), 2 (High), 3 (Normal), 4 (Low), 5 (Lowest).
     *
     * @access public
     * @var int $priority
     * @see priorities     
     */
    public $priority;

    /**
     * Subject of the e-mail.
     *
     * @access public
     * @var string $subject
     */
    public $subject;

    /**
     * Recipients of the e-mail as <BCC>.
     *
     * @access private
     * @var mixed $_bcc
     */
    private $_bcc;

    /**
     * Recipients of the e-mail as <CC>.
     *
     * @access private
     * @var mixed $_cc
     */
    private $_cc;

    /**
     * Recipients of the e-mail as <From>.
     *
     * @access private
     * @var mixed $_from
     */
    private $_from;

    /**
     * Mailer string.
     *
     * @access private
     * @var string $_mailer
     */
    private $_mailer;

    /**
     * Recipient of the optional notification.
     *
     * @access private
     * @var mixed $_notifyTo
     */
    private $_notifyTo;

    /**
     * Recipient of the e-mail as <Reply-To>.
     *
     * @access private
     * @var mixed $_replyTo
     */
    private $_replyTo;

    /**
     * Recipient of the e-mail as <Return-Path>.
     *
     * @access private
     * @var mixed $_returnPath
     */
    private $_returnPath;

    /**
     * Recipient of the e-mail as <Sender>.
     *
     * @access private
     * @var mixed $_sender
     */
    private $_sender;

    /**
     * Recipients of the e-mail as <TO:>.
     *
     * @access private
     * @var mixed $_to
     */
    private $_to;

    /*
     * Default constructor.
     *      
     * @param string $agent Type of agent. Default: built-in.
     */     
    function PMF_Mail($agent = 'built-in')
    {
        // Set default value for public properties
        $this->agent = $agent;
        $this->attachments = array();
        $this->boundary = self::createBoundary();
        $this->headers = array();
        $this->message = '';
        $this->messageAlt = '';
        $this->messageId = '<'.time().'.'.md5(microtime()).'@'.self::getServerName().'>';
        $this->priority = 3; // 3 -> Normal
        $this->subject = '';

        // Set default value for private properties
        $this->_bcc = array();
        $this->_cc = array();
        $this->_from = array();
        $this->_mailer = 'phpMyFAQ on PHP/'.phpversion();
        $this->_notifyTo = array();
        $this->_replyTo = array();
        $this->_returnPath = array();
        $this->_sender = array();
        $this->_to = array();

        // Set phpMyFAQ related data
        global $PMF_CONF, $PMF_LANG;
        if (isset($PMF_CONF)) {
            $this->_mailer = 'phpMyFAQ/'.$PMF_CONF['main.currentVersion'];
            $this->setFrom($PMF_CONF['main.administrationMail']);
        }
        if (isset($PMF_LANG)) {
            $this->charset = $PMF_LANG['metaCharset'];
        }
    }

    /*
     * Add an e-mail address to an array.
     *
     * @access private
     * @param array $target Target array.
     * @param string $target Alias Target alias.
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.     
     */          
    private function _addEmailTo(&$target, $targetAlias, $address, $name = null)
    {
        // Sanity check
        if (!self::validateEmail($address)) {
            trigger_error(
                "<b>PMF_Mail Class</b>: $address is not a valid e-mail address!",
                E_USER_ERROR
            );
            return false;
        }

        // Don't allow duplicated addresses
        if (array_key_exists($address, $target)) {
            trigger_error(
                "<b>PMF_Mail Class</b>: $address has been already added in '$targetAlias'!",
                E_USER_WARNING
            );
            return false;
        }

        // phpMyFAQ has adopted a class to manage Internationalized Domain Names
        global $IDN;
        if (isset($IDN)) {
            $address = $IDN->encode($address);
        }

        // Add the e-mail address into the target array
        $target[$address] = $name;
        // On Windows, when using PHP built-in mail drop any name, just use the e-mail address
        if (   ('WIN' === strtoupper(substr(PHP_OS, 0, 3)))
            && ('built-in' == $this->agent)
            ) {
            $target[$address] = null;
        }

        return true;
    }

    /*
     * Create the body of the email.
     *
     * @access private
     * @return void     
     */          
    private function _createBody()
    {
        $lines = array();
        $mainBoundary = $this->boundary;

        // Cleanup body
        $this->body = array();

        // Add lines
        if (strpos($this->contentType, 'multipart') !== false) {
            $lines[] = 'This is a multi-part message in MIME format.';
            $lines[] = '';
        }

        if (in_array($this->contentType,
                    array(
                        'multipart/mixed',
                        'multipart/related'
                    )
                )
            ) {
            $lines[] = '--'.$mainBoundary;
            $this->boundary = "--=alternative=".self::createBoundary();
            $lines[] = 'Content-Type: multipart/alternative; boundary="'.$this->boundary.'"';
            $lines[] = '';
        }

        if (strpos($this->contentType, 'multipart') !== false) {
            // At least we have messageAlt and message
            if (!empty($this->messageAlt)) {
                // 1/2. messageAlt, supposed as plain text
                $lines[] = '--'.$this->boundary;
                $lines[] = 'Content-Type: text/plain; charset="'.$this->charset.'"';
                $lines[] = 'Content-Transfer-Encoding: '.$this->contentTransferEncoding;
                $lines[] = '';
                $lines[] = self::wrapLines(PMF_Utils::resolveMarkers($this->messageAlt));
                $lines[] = '';
            }
            // 2/2. message, supposed as, potentially, HTML
            $lines[] = '--'.$this->boundary;
            $lines[] = 'Content-Type: text/html; charset="'.$this->charset.'"';
            $lines[] = 'Content-Transfer-Encoding: '.$this->contentTransferEncoding;
            $lines[] = '';
            $lines[] = self::wrapLines($this->message);
            // Close the boundary delimiter
            $lines[] = '--'.$this->boundary.'--';
        } else {
            $lines[] = self::wrapLines($this->message);
        }

        if (in_array($this->contentType,
                    array(
                        'multipart/mixed',
                        'multipart/related'
                    )
                )
            ) {
            // Back to the main boundary
            $this->boundary = $mainBoundary;
            // Add the attachments
            foreach ($this->attachments as $attachment) {
                $lines[] = '--'.$this->boundary;
                $lines[] = 'Content-Type: '.$attachment['mimetype'].'; name="'.$attachment['name'].'"'; 
                $lines[] = 'Content-Transfer-Encoding: base64';
                if ('inline' == $attachment['disposition']) {
                    $lines[] = 'Content-ID: <'.$attachment['cid'].'>';
                }
                $lines[] = 'Content-Disposition: '.$attachment['disposition'].'; filename="'.$attachment['name'].'"';
                $lines[] = '';
                $lines[] = chunk_split(base64_encode(file_get_contents($attachment['path'])));
            }
            // Close the boundary delimiter
            $lines[] = '--'.$this->boundary.'--';
        }

        // Create the final body
        $this->body = '';
        foreach ($lines as $line) {
            $this->body .= $line.$this->eol;
        }
    }

    /*
     * Create the headers of the email.
     *
     * @access private
     * @return void     
     */          
    private function _createHeaders()
    {
        // Cleanup headers
        $this->headers = array();

        // Check if the message consists of just a "plain" single item
        if (false === strpos($this->contentType, 'multipart')) {
            // Content-Disposition: inline
            $this->headers['Content-Disposition'] = $this->contentDisposition;
            // Content-Type
            $this->headers['Content-Type'] = $this->contentType.'; format=flowed; charset="'.$this->charset.'"';
            // Content-Transfer-Encoding: 7bit
            $this->headers['Content-Transfer-Encoding'] = '7bit';
        } else {
            // Content-Type
            $this->headers['Content-Type'] = $this->contentType.'; boundary="'.$this->boundary.'"';
        }

        // Date
        $this->headers['Date'] = self::getDate(self::getTime());

        // Disposition-Notification-To, RFC 3798
        $notifyTos = array();
        foreach($this->_notifyTo as $address => $name) {
            $notifyTos[] = (empty($name) ? '' : $name.' ').'<'.$address.'>';
        }
        $notifyTo = implode(',', $notifyTos);
        if (!empty($notifyTo)) {
            $this->headers['Disposition-Notification-To'] = $notifyTo;
        }

        // From
        foreach($this->_from as $address => $name) {
            $this->headers['From'] = (empty($name) ? '' : $name.' ').'<'.$address.'>';
        }

        // Message-Id
        $this->headers['Message-ID'] = $this->messageId;

        // MIME-Version: 1.0
        $this->headers['MIME-Version'] = '1.0';

        // Reply-To
        $this->headers['Reply-To'] = $this->headers['From'];
        foreach($this->_replyTo as $address => $name) {
            $this->headers['Reply-To'] = (empty($name) ? '' : $name.' ').'<'.$address.'>';
        }

        // Return-Path
        foreach($this->_from as $address => $name) {
            $this->headers['Return-Path'] = '<'.$address.'>';
        }
        foreach($this->_returnPath as $address => $name) {
            $this->headers['Return-Path'] = '<'.$address.'>';
        }

        // Sender
        $this->headers['Sender'] = $this->headers['From'];
        foreach($this->_sender as $address => $name) {
            $this->headers['Sender'] = (empty($name) ? '' : $name.' ').'<'.$address.'>';
        }

        // Subject. Note: it must be RFC 2047 compliant
        $this->headers['Subject'] = PMF_Utils::resolveMarkers($this->subject);

        // X-Mailer
        $this->headers['X-Mailer'] = $this->_mailer;

        // X-MSMail-Priority
        if (isset($this->priorities[(int)$this->priority])) {
            $this->headers['X-MSMail-Priority'] = $this->priorities[(int)$this->priority];
        }

        // X-Originating-IP
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->headers['X-Originating-IP'] = $_SERVER['REMOTE_ADDR'];
        }

        // X-Priority
        $this->headers['X-Priority'] = $this->priority;
    }

    /*
     * Set just one e-mail address into an array.
     *
     * @access private
     * @param array $target Target array.
     * @param string $target Alias Target alias.
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    private function _setEmailTo(&$target, $targetAlias, $address, $name = null)
    {
        // Check for the permitted number of items into the $target array
        if (count($target) > 0) {
            $keys = array_keys($target);
            trigger_error(
                "<b>PMF_Mail Class</b>: a valid e-mail address, $keys[0], has been already added as '$targetAlias'!",
                E_USER_ERROR
            );
            return false;
        }
        
        return $this->_addEmailTo($target, $targetAlias, $address, $name);
    }

    /*
     * Add an attachment.
     *
     * @access public
     * @param string $path File path.
     * @param string $name File name. Defaults to the basename.
     * @param string $mimetype File MIME type. Defaults to 'application/octet-stream'.
     * @param string $disposition Attachment disposition. Defaults to 'attachment'.
     * @param string $cid Content ID, required when disposition is 'inline'. Defaults to ''.
     * @return bool True if successful, false otherwise.
     */          
    public function addAttachment($path, $name = null, $mimetype = 'application/octet-stream', $disposition = 'attachment', $cid = '')
    {
        if (!file_exists($path)) {
            // File not found
            return false;
        } else if (('inline' == $disposition) && empty($cid)) {
            // Content ID is required
            return false;
        } else {
            if (empty($name)) {
                $name = basename($path);
            }

            $this->attachments[] = array(
                "cid"           => $cid,
                "disposition"   => $disposition,
                "mimetype"      => $mimetype,
                "name"          => $name,
                "path"          => $path
            );

            return true;
        }
    }

    /*
     * Add a recipient as <BCC>.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addBcc($address, $name = null)
    {
        return $this->_addEmailTo($this->_bcc, 'Bcc', $address, $name);
    }

    /*
     * Add a recipient as <CC>.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addCc($address, $name = null)
    {
        return $this->_addEmailTo($this->_cc, 'Cc', $address, $name);
    }

    /*
     * Add an address to send a notification to.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addNotificationTo($address, $name = null)
    {
        return $this->_addEmailTo($this->_notifyTo, 'Disposition-Notification-To', $address, $name);
    }

    /*
     * Add a recipient as <TO>.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addTo($address, $name = null)
    {
        return $this->_addEmailTo($this->_to, 'To', $address, $name);
    }

    /*
     * Create a string to be used as a valid boundary value.
     *
     * @access public
     * @static     
     * @return string The boundary value.
     */          
    public static function createBoundary()
    {
        return '-----'.md5(microtime());
    }

    /*
     * Returns the given text being sure that any CR or LF has been fixed
     * according with RFC 2822 EOL setting.
     *
     * @access public
     * @param string $text Text with a mixed usage of CR, LF, CRLF.
     * @return string The fixed text.
     * @see eol
     */          
    public function fixEOL($text)
    {
        // Assure that anything among CRLF, CR will be replaced with just LF
        $text = str_replace(
            array(
                "\r\n",// CRLF
                "\r", // CR
                "\n",// LF
            ),
            "\n", // LF
            $text
        );
        // Set any LF to the RFC 2822 EOL
        $text = str_replace("\n", $this->eol, $text);

        return $text;
    }

    /*
     * Returns the date according with RFC 2822.
     *
     * @access public
     * @static
     * @param string $date Unix timestamp.
     * @return string The RFC 2822 date if successful, false otherwise.
     */          
    public static function getDate($date)
    {
        $rfc2822Date = date('r', $date);

        return $rfc2822Date;
    }

    /*
     * Returns the Unix timestamp with preference to the Page Request time.
     *
     * @access public
     * @static
     * @param string $date .
     * @return int Unix timestamp.
     */          
    public static function getTime()
    {
        if (isset($_SERVER['REQUEST_TIME'])) {
            return $_SERVER['REQUEST_TIME'];
        }

        return time();
    }

    /*
     * Get the instance of the class implementing the MUA for the given type.
     *
     * @access public
     * @static
     * @param string $type Type of the MUA.
     * @return mixed The class instance if successful, false otherwise.
     */          
    public static function getMUA($mua)
    {
        $impl = ucfirst(
            str_replace(
                '-',
                '',
                $mua
            )
        );
        $class = 'PMF_Mail_'.$impl;

        return new $class;
    }

    /*
     * Returns the server name.
     *
     * @access public
     * @static
     * @return string The server name.
     */          
    public static function getServerName()
    {
        $hostname = 'localhost.localdomain';
        if (isset($_SERVER['HTTP_HOST'])) {
            $hostname = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME'])) {
            $hostname = $_SERVER['SERVER_NAME'];
        }

        return $hostname;
    }

    /*
     * Send the e-mail according with the current settings.
     *
     * @access public
     * @return bool True if successful, false otherwise.
     */          
    public function send()
    {
        // Sanity check
        if (count($this->_to) + count($this->_cc) + count($this->_bcc) < 1) {
            trigger_error(
                "<b>PMF_Mail Class</b>: you need at least to set one recipient among TO, CC and BCC!",
                E_USER_ERROR
            );
            return false;
        }

        // Has any alternative message been provided?
        if (!empty($this->messageAlt)) {
            $this->contentType = 'multipart/alternative';
        }

        // Has any attachment been provided?
        if (!empty($this->attachments)) {
            $this->contentType = 'multipart/mixed';
        }

        // Has any in-line attachment been provided?
        $hasInlineAttachments = false;
        $idx = 0;
        while (!$hasInlineAttachments && ($idx < count($this->attachments))) {
            $hasInlineAttachments = ('inline' == $this->attachments[i]['disposition']);
            $idx++;
        }

        if ($hasInlineAttachments) {
            $this->contentType = 'multipart/related';
        }

        // A valid MUA needs to implement the PMF_Mail_IMUA interface
        // i.e. we must prepare recipients, headers, body for the send() method

        // Prepare the recipients
        $recipients = '';
        $to = array();
        foreach($this->_to as $address => $name) {
            $to[] = (empty($name) ? '' : $name.' ').'<'.$address.'>';
        }
        $recipients = implode(',', $to);
        // Check for the need of undisclosed recipients outlook-like <TO:>
        if (empty($recipients) && (0 == count($this->_cc))) {
            $recipients = '<Undisclosed-Recipient:;>';
        }

        // Prepare the headers
        $this->_createHeaders();

        // Prepare the body
        $this->_createBody();

        // Send the email adopting to the given MUA
        $sent = false;
        $mua = self::getMUA($this->agent);
        switch($this->agent) {
            case 'built-in':
                $sent = $mua->send($recipients, $this->headers, $this->body);
                break;
            default:
                trigger_error(
                    "<b>PMF_Mail Class</b>: $this->agent has no implementation!",
                    E_USER_ERROR
                );
                $sent = false;
        }

        return $sent;
    }

    /*
     * Set the "From" address.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function setFrom($address, $name = null)
    {
        return $this->_setEmailTo($this->_from, 'From', $address, $name);
    }

    /*
     * Set an HTML message providing also a plain text alternative message,
     * if not already set using the $messageAlt property.
     * Besides it is possible to put resources as inline attachments
     *
     * @access public
     * @param string $html HTML message.
     * @param bool $sanitize Strip out potentially unsecured HTML tags. Defaults to false.
     * @param bool $inline Add images as inline attachments. Defaults to false.
     * @return void.
     */          
    public function setHTMLMessage($message, $sanitize = false, $inline = false)
    {
        // No Javascript at all
        // 1/2. <script blahblahblah>blahblahblah</tag>
        $message = preg_replace(
            '/(<script[^>]*>.*<\/script>)|<script[^\/]*\/>|<script[^\/]*>/is',
            '',
            $message
        );

        // Cleanup potentially dangerous HTML tags:
        if ($sanitize) {
            // 1/2. <tag blahblahblah>blahblahblah</tag>
            $message = preg_replace(
                '/<(applet|embed|head|meta|object|style|title)[^>]*>.*<\/\\1>/is',
                '',
                $message
            );
            // 2/2. <tag blahblahblah />
            $message = preg_replace(
                '/<(applet|embed|head|meta|object|style|title)[^\/]*\/>/is',
                '',
                $message
            );
        }

        if ($inline) {
            trigger_error(
                "<b>PMF_Mail Class</b>: inline option is not implemented yet.",
                E_USER_ERROR
            );
        }

        // Set the HTML text as the main message
        $this->message = trim($message);

        // If no alternative text has been provided yet, use just
        // the HTML message stripping any HTML tag
        if (empty($this->messageAlt)) {
            $this->messageAlt = trim(strip_tags($this->message));
        }
    }

    /*
     * Set the "Reply-to" address.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function setReplyTo($address, $name = null)
    {
        return $this->_setEmailTo($this->_replyTo, 'Reply-To', $address, $name);
    }

    /*
     * Set the "Return-Path" address.
     *
     * @access public
     * @param string $address User e-mail address.
     * @return bool True if successful, false otherwise.
     */          
    public function setReturnPath($address, $name = null)
    {
        return $this->_setEmailTo($this->_returnPath, 'Return-Path', $address);
    }

    /*
     * Set the "Sender" address.
     *
     * @access public
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function setSender($address, $name = null)
    {
        return $this->_setEmailTo($this->_sender, 'Sender', $address, $name);
    }

    /*
     * Remove any previous "From" address.
     *
     * @access public
     * @return bool True if successful, false otherwise.
     */          
    public function unsetFrom()
    {
        $this->_from = array();

        return true;
    }

    /*
     * Validate an address as an e-mail address.
     *
     * @access public
     * @static
     * @param string $address
     * @return bool True if the given address is a valid e-mail address, false otherwise.     
     */          
    public static function validateEmail($address)
    {
        // Sanity checks
        if (empty($address)) {
            return false;
        }
        if (strpos($address, '\0') !== false) {
            return false;
        }

        // Always sanitize!
        // http://www.php-security.org/MOPB/PMOPB-45-2007.html
        $unsafe = array ("\r", "\n");
        if ($address !== str_replace($unsafe, '', $address)) {
            return false;
        }

        // phpMyFAQ has adopted a class to manage Internationalized Domain Names
        global $IDN;
        if (isset($IDN)) {
            $address = $IDN->decode($address);
        }

        // Validate the address as an e-mail address: syntax validation
        if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
          // Invalid e-mail address
          return false;
        }

        return true;
    }

    /*
     * Wraps the lines contained into the given message.
     *
     * @access public
     * @param string $message Message.
     * @param string $width Column width. Defaults to 72. 
     * @param string $cut Cutting a word is allowed. Defaults to false.
     * @return string The given message, wrapped as requested.
     */          
    public function wrapLines($message, $width = 72, $cut = false)
    {
        $message = $this->fixEOL($message);

        if (strpos(strtolower($this->charset), 'utf') !== false) {
            // PHP wordwrap() is not safe with multibyte UTF chars
            return $message;
        } else {
            $lines = explode($this->eol, $message);
            $wrapped = '';
            foreach ($lines as $key => $value) {
                $wrapped .= (empty($wrapped) ? '' : $this->eol);
                $wrapped .= wordwrap($value, $width, $this->eol, $cut);
            }
    
            return $wrapped;
        }
    }
}
