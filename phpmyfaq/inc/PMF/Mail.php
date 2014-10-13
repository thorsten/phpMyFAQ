<?php
/**
 * MUA (Mail User Agent) implementation.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Mail
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-11
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Mail
 *
 * @category  phpMyFAQ
 * @package   Mail
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-11
 */ 
class PMF_Mail
{
    /**
     * Type of the used MUA. Possible values:
     * - built-in.     
     *
     * @var string $agent
     */
    public $agent;

    /**
     * Attached filed.
     *
     * @var mixed $attachments
     */
    public $attachments;

    /**
     * Body of the e-mail.
     *
     * @var string $body
     */
    public $body = '';

    /**
     * Boundary.
     *
     * @var string $boundary
     */
    public $boundary = '----------';

    /**
     * Charset.
     *
     * @var string $charset
     */
    public $charset = 'utf-8';

    /**
     * Content disposition.
     *
     * @var string $contentDisposition
     */
    public $contentDisposition = 'inline';

    /**
     * Content type.
     *
     * @var string $contentType
     */
    public $contentType = 'text/plain';

    /**
     * Content transfer encoding.
     *
     * @var string $contentTransferEncoding
     */
    public $contentTransferEncoding = '8bit';

    /**
     * The one and only valid End Of Line sequence as per RFC 2822:
     * carriage-return followed by line-feed.     
     *
     * @var string $eol
     */
    public $eol = "\r\n";

    /**
     * Headers of the e-mail.
     *
     * @var string $headers
     */
    public $headers;

    /**
     * Message of the e-mail: HTML text allowed.
     *
     * @var string $message
     */
    public $message;

    /**
     * Alternate message of the e-mail: only plain text allowed.
     *
     * @var string $messageAlt
     */
    public $messageAlt;

    /**
     * Message-ID of the e-mail.
     *
     * @var string $messageId
     */
    public $messageId;

    /**
     * Priorities: 1 (Highest), 2 (High), 3 (Normal), 4 (Low), 5 (Lowest).
     *
     * @var mixed $priorities
     */
    public $priorities = array(
        1 => 'Highest',
        2 => 'High',
        3 => 'Normal',
        4 => 'Low',
        5 => 'Lowest');

    /**
     * Priority of the e-mail: 1 (Highest), 2 (High), 3 (Normal), 4 (Low), 5 (Lowest).
     *
     * @var int $priority
     * @see priorities     
     */
    public $priority;

    /**
     * Subject of the e-mail.
     *
     * @var string $subject
     */
    public $subject;

    /**
     * Recipients of the e-mail as <BCC>.
     *
     * @var mixed $_bcc
     */
    private $_bcc;

    /**
     * Recipients of the e-mail as <CC>.
     *
     * @var mixed $_cc
     */
    private $_cc;

    /**
     * Recipients of the e-mail as <From>.
     *
     * @var mixed $_from
     */
    private $_from;

    /**
     * Mailer string.
     *
     * @var string $_mailer
     */
    private $_mailer;

    /**
     * Recipient of the optional notification.
     *
     * @var mixed $_notifyTo
     */
    private $_notifyTo;

    /**
     * Recipient of the e-mail as <Reply-To>.
     *
     * @var mixed $_replyTo
     */
    private $_replyTo;

    /**
     * Recipient of the e-mail as <Return-Path>.
     *
     * @var mixed $_returnPath
     */
    private $_returnPath;

    /**
     * Recipient of the e-mail as <Sender>.
     *
     * @var mixed $_sender
     */
    private $_sender;

    /**
     * Recipients of the e-mail as <TO:>.
     *
     * @var mixed $_to
     */
    private $_to;

    /**
     * @var PMF_Configuration
     */
    private $_config;

    /*
     * Default constructor.
     * Note: any email will be sent from the PMF administrator, use unsetFrom
     *       before using setFrom.     
     *
     * @param Configuration $config
     */     
    function __construct(PMF_Configuration $config)
    {
        // Set default value for public properties
        $this->agent       = $config->get( 'mail.remoteSMTP' ) ? 'SwiftSMTP' : 'built-in';
        $this->attachments = array();
        $this->boundary    = self::createBoundary();
        $this->headers     = array();
        $this->message     = '';
        $this->messageAlt  = '';
        $this->messageId   = '<'.$_SERVER['REQUEST_TIME'] . '.'. md5(microtime()) . '@' . self::getServerName() . '>';
        $this->priority    = 3; // 3 -> Normal
        $this->subject     = '';

        // Set default value for private properties
        $this->_config     = $config;
        $this->_bcc        = array();
        $this->_cc         = array();
        $this->_from       = array();
        $this->_mailer     = 'phpMyFAQ on PHP/' . PHP_VERSION;
        $this->_notifyTo   = array();
        $this->_replyTo    = array();
        $this->_returnPath = array();
        $this->_sender     = array();
        $this->_to         = array();

        // Set phpMyFAQ related data
        $this->_mailer = 'phpMyFAQ/' . $this->_config->get('main.currentVersion');
        $this->setFrom($this->_config->get('main.administrationMail'));
    }

    /**
     * Add an e-mail address to an array.
     *
     * @param  array  $target      Target array.
     * @param  string $targetAlias Alias Target alias.
     * @param  string $address     User e-mail address.
     * @param  string $name        User name (optional).
     *
     * @return bool   True if successful, false otherwise.
     *
     * @todo   Enhance error handling using exceptions
     */          
    private function _addEmailTo(&$target, $targetAlias, $address, $name = null)
    {
        // Sanity check
        if (!self::validateEmail($address)) {
            trigger_error(
                "<strong>Mail Class</strong>: $address is not a valid e-mail address!",
                E_USER_ERROR
            );
            return false;
        }

        // Don't allow duplicated addresses
        if (array_key_exists($address, $target)) {
            trigger_error(
                "<strong>Mail Class</strong>: $address has been already added in '$targetAlias'!",
                E_USER_WARNING
            );
            return false;
        }

        if (!empty($name)) {
            // Remove CR and LF characters to prevent header injection
            $name = str_replace(array("\n", "\r"), '', $name);
            
            if (function_exists('mb_encode_mimeheader')) {
                // Encode any special characters in the displayed name
                $name = mb_encode_mimeheader($name);
            }
            
            // Wrap the displayed name in quotes (to fix problems with commas etc),
            // and escape any existing quotes
            $name = '"' . str_replace('"', '\"', $name) . '"';
        }

        // Add the e-mail address into the target array
        $target[$address] = $name;
        // On Windows, when using PHP built-in mail drop any name, just use the e-mail address
        if (('WIN' === strtoupper(substr(PHP_OS, 0, 3))) && ('built-in' == $this->agent)) {
            $target[$address] = null;
        }

        return true;
    }

    /**
     * Create the body of the email.
     *
     * @return void     
     */          
    private function _createBody()
    {
        $lines        = array();
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
                $lines[] = self::wrapLines(PMF_Utils::resolveMarkers($this->messageAlt, $this->_config));
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

    /**
     * Create the headers of the email.
     *
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
        foreach ($this->_from as $address => $name) {
            $this->headers['From'] = (empty($name) ? '' : $name.' ') . '<' . $address . '>';
        }

        // CC
        foreach ($this->_cc as $address => $name) {
            $this->headers['CC'] = (empty($name) ? '' : $name.' ') . '<' . $address . '>';
        }

        // BCC
        foreach ($this->_bcc as $address => $name) {
            $this->headers['BCC'] = (empty($name) ? '' : $name.' ') . '<' . $address . '>';
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
        // TODO: wrap mb_encode_mimeheader() to add other content encodings
        $this->headers['Subject'] = PMF_Utils::resolveMarkers(
            html_entity_decode($this->subject, ENT_COMPAT, 'UTF-8'),
            $this->_config
        );

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

    /**
     * Set just one e-mail address into an array.
     *
     * @param  array  $target      Target array.
     * @param  string $targetAlias Alias Target alias.
     * @param  string $address     User e-mail address.
     * @param  string $name        User name (optional).
     *
     * @return bool   True if successful, false otherwise.
     */          
    private function _setEmailTo(&$target, $targetAlias, $address, $name = null)
    {
        // Check for the permitted number of items into the $target array
        if (count($target) > 0) {
            $keys = array_keys($target);
            trigger_error(
                "<strong>Mail Class</strong>: a valid e-mail address, $keys[0], has been already added as '$targetAlias'!",
                E_USER_ERROR
            );
            return false;
        }
        
        return $this->_addEmailTo($target, $targetAlias, $address, $name);
    }

    /**
     * Add an attachment.
     *
     * @param  string $path        File path.
     * @param  string $name        File name. Defaults to the basename.
     * @param  string $mimetype    File MIME type. Defaults to 'application/octet-stream'.
     * @param  string $disposition Attachment disposition. Defaults to 'attachment'.
     * @param  string $cid         Content ID, required when disposition is 'inline'. Defaults to ''.
     * @return bool   True if successful, false otherwise.
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

    /**
     * Add a recipient as <BCC>.
     *
     * @param  string $address User e-mail address.
     * @param  string $name    User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addBcc($address, $name = null)
    {
        return $this->_addEmailTo($this->_bcc, 'Bcc', $address, $name);
    }

    /**
     * Add a recipient as <CC>.
     *
     * @param  string $address User e-mail address.
     * @param  string $name    User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addCc($address, $name = null)
    {
        return $this->_addEmailTo($this->_cc, 'Cc', $address, $name);
    }

    /**
     * Add an address to send a notification to.
     *
     * @param  string $address User e-mail address.
     * @param  string $name    User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addNotificationTo($address, $name = null)
    {
        return $this->_addEmailTo($this->_notifyTo, 'Disposition-Notification-To', $address, $name);
    }

    /**
     * Add a recipient as <TO>.
     *
     * @param  string $address User e-mail address.
     * @param  string $name    User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function addTo($address, $name = null)
    {
        return $this->_addEmailTo($this->_to, 'To', $address, $name);
    }

    /**
     * Create a string to be used as a valid boundary value.
     *
     * @static     
     * @return string The boundary value.
     */          
    public static function createBoundary()
    {
        return '-----'  .md5(microtime());
    }

    /**
     * Returns the given text being sure that any CR or LF has been fixed
     * according with RFC 2822 EOL setting.
     *
     * @param  string $text Text with a mixed usage of CR, LF, CRLF.
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

    /**
     * Returns the date according with RFC 2822.
     *
     * @static
     * @param  string $date Unix timestamp.
     * @return string The RFC 2822 date if successful, false otherwise.
     */          
    public static function getDate($date)
    {
        $rfc2822Date = date('r', $date);

        return $rfc2822Date;
    }

    /**
     * Returns the Unix timestamp with preference to the Page Request time.
     *
     * @static
     * @return int Unix timestamp.
     */          
    public static function getTime()
    {
        if (isset($_SERVER['REQUEST_TIME'])) {
            return $_SERVER['REQUEST_TIME'];
        }

        return time();
    }

    /**
     * Get the instance of the class implementing the MUA for the given type.
     *
     * @static
     * @param string $mua Type of the MUA.
     *
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

    /**
     * Returns the server name.
     *
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

    /**
     * Send the e-mail according with the current settings.
     *
     * @return bool True if successful, false otherwise.
     *
     * @todo   Enhance error handling using exceptions
     */          
    public function send()
    {
        // Sanity check
        if (count($this->_to) + count($this->_cc) + count($this->_bcc) < 1) {
            trigger_error(
                "<strong>Mail Class</strong>: you need at least to set one recipient among TO, CC and BCC!",
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
            $hasInlineAttachments = ('inline' == $this->attachments[$idx]['disposition']);
            $idx++;
        }

        if ($hasInlineAttachments) {
            $this->contentType = 'multipart/related';
        }

        // A valid MUA needs to implement the PMF_Mail_IMUA interface
        // i.e. we must prepare recipients, headers, body for the send() method

        // Prepare the recipients
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
        $mua  = self::getMUA($this->agent);
		
		if( is_object( $mua ) && method_exists( $mua, 'setAuthConfig' ) ){
			$mua->setAuthConfig( 
				$this->_config->get( 'mail.remoteSMTPServer' ),
				$this->_config->get( 'mail.remoteSMTPUsername' ),
				$this->_config->get( 'mail.remoteSMTPPassword' )
			);
		}
		
        switch ($this->agent) {
			case 'SwiftSMTP': 
                $sent = $mua->send($this->_to, $this->headers, $this->body);
                break;
            case 'built-in':
                $sent = $mua->send($recipients, $this->headers, $this->body);
                break;
            default:
                trigger_error(
                    "<strong>Mail Class</strong>: $this->agent has no implementation!",
                    E_USER_ERROR
                );
                $sent = false;
        }

        return $sent;
    }

    /**
     * Set the "From" address.
     *
     * @param  string $address User e-mail address.
     * @param  string $name User name (optional).
     * @return bool   True if successful, false otherwise.
     */          
    public function setFrom($address, $name = null)
    {
        return $this->_setEmailTo($this->_from, 'From', $address, $name);
    }

    /**
     * Set an HTML message providing also a plain text alternative message,
     * if not already set using the $messageAlt property.
     * Besides it is possible to put resources as inline attachments
     *
     * @param  string $message     HTML message.
     * @param  bool   $sanitize Strip out potentially unsecured HTML tags. Defaults to false.
     * @param  bool   $inline   Add images as inline attachments. Defaults to false.
     *
     * @return void
     */          
    public function setHTMLMessage($message, $sanitize = false, $inline = false)
    {
        // No Javascript at all
        // 1/2. <script blahblahblah>blahblahblah</tag>
        $message = PMF_String::preg_replace(
            '/(<script[^>]*>.*<\/script>)|<script[^\/]*\/>|<script[^\/]*>/is',
            '',
            $message
        );

        // Cleanup potentially dangerous HTML tags:
        if ($sanitize) {
            // 1/2. <tag blahblahblah>blahblahblah</tag>
            $message = PMF_String::preg_replace(
                '/<(applet|embed|head|meta|object|style|title)[^>]*>.*<\/\\1>/is',
                '',
                $message
            );
            // 2/2. <tag blahblahblah />
            $message = PMF_String::preg_replace(
                '/<(applet|embed|head|meta|object|style|title)[^\/]*\/>/is',
                '',
                $message
            );
        }

        if ($inline) {
            trigger_error(
                "<strong>Mail Class</strong>: inline option is not implemented yet.",
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

    /**
     * Set the "Reply-to" address.
     *
     * @param string $address User e-mail address.
     * @param string $name User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function setReplyTo($address, $name = null)
    {
        return $this->_setEmailTo($this->_replyTo, 'Reply-To', $address, $name);
    }

    /**
     * Set the "Return-Path" address.
     *
     * @param  string $address User e-mail address.
     * @return bool   True if successful, false otherwise.
     */          
    public function setReturnPath($address)
    {
        return $this->_setEmailTo($this->_returnPath, 'Return-Path', $address);
    }

    /**
     * Set the "Sender" address.
     *
     * @param  string $address User e-mail address.
     * @param  string $name    User name (optional).
     * @return bool True if successful, false otherwise.
     */          
    public function setSender($address, $name = null)
    {
        return $this->_setEmailTo($this->_sender, 'Sender', $address, $name);
    }

    /**
     * Remove any previous "From" address.
     *
     * @return bool True if successful, false otherwise.
     */          
    public function unsetFrom()
    {
        $this->_from = array();

        return true;
    }

    /**
     * Validate an address as an e-mail address.
     *
     * @param string $address E-Mail address
     *
     * @return bool True if the given address is a valid e-mail address, false otherwise.     
     */
    public static function validateEmail($address)
    {
        if (empty($address)) {
            return false;
        }
        
        if (PMF_String::strpos($address, '\0') !== false) {
            return false;
        }
        
        $unsafe = array ("\r", "\n");
        if ($address !== str_replace($unsafe, '', $address)) {
            return false;
        }
        
        if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
          return false;
        }
        
        return true;
    }

    /**
     * Wraps the lines contained into the given message.
     *
     * @param  string  $message Message.
     * @param  integer $width   Column width. Defaults to 72.
     * @param  boolean $cut     Cutting a word is allowed. Defaults to false.
     *
     * @return string The given message, wrapped as requested.
     */          
    public function wrapLines($message, $width = 72, $cut = false)
    {
        $message = $this->fixEOL($message);

        if (PMF_String::strpos(strtolower($this->charset), 'utf') !== false) {
            // PHP wordwrap() is not safe with multibyte UTF chars
            return $message;
        } else {
            $lines = explode($this->eol, $message);
            $wrapped = '';
            foreach ($lines as $value) {
                $wrapped .= (empty($wrapped) ? '' : $this->eol);
                $wrapped .= wordwrap($value, $width, $this->eol, $cut);
            }
    
            return $wrapped;
        }
    }
    
    /**
     * If the email spam protection has been activated from the general 
     * phpMyFAQ configuration this method converts an email address e.g. 
     * from "user@example.org" to "user_AT_example_DOT_org". Otherwise 
     * it will return the plain email address.
     *
     * @param  string $email E-mail address
     * @static
     *
     * @return string
     */
    public function safeEmail($email)
    {
        if ($this->_config->get('spam.enableSafeEmail')) {
            return str_replace ( array ('@', '.' ), array ('_AT_', '_DOT_' ), $email );
        } else {
            return $email;
        }
    }
}
