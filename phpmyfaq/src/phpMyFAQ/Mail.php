<?php

/**
 * MUA (Mail User Agent) implementation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-11
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail\Builtin;
use phpMyFAQ\Mail\SMTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class Mail
 *
 * @package phpMyFAQ
 */
class Mail
{
    /**
     * Type of the used MUA. Possible values:
     * - built-in.
     */
    public string $agent;

    /**
     * Attached filed.
     */
    public mixed $attachments = [];

    /**
     * Body of the e-mail.
     */
    public string $body = '';

    /**
     * Boundary.
     */
    public string $boundary = '----------';

    /**
     * Charset.
     */
    public string $charset = 'utf-8';

    /**
     * Content disposition.
     */
    public string $contentDisposition = 'inline';

    /**
     * Content type.
     */
    public string $contentType = 'text/plain';

    /**
     * Content transfer encoding.
     */
    public string $contentTransferEncoding = '8bit';

    /**
     * The one and only valid End Of Line sequence as per RFC 2822:
     * carriage-return followed by line-feed.
     */
    public string $eol = "\r\n";

    /**
     * Array of headers of the e-mail
     *
     * @var array<string|int>
     */
    public array $headers = [];

    /**
     * Message of the e-mail: HTML text allowed.
     */
    public string $message = '';

    /**
     * Alternate message of the e-mail: only plain text allowed.
     */
    public string $messageAlt = '';

    /**
     * Message-ID of the e-mail.
     */
    public string $messageId;

    /**
     * Priorities: 1 (Highest), 2 (High), 3 (Normal), 4 (Low), 5 (Lowest).
     *
     * @var array<string>
     */
    public array $priorities = [
        1 => 'Highest',
        2 => 'High',
        3 => 'Normal',
        4 => 'Low',
        5 => 'Lowest'
    ];

    /**
     * Priority of the e-mail: 1 (Highest), 2 (High), 3 (Normal), 4 (Low), 5 (Lowest).
     *
     *
     * @see priorities
     */
    public int $priority = 3;

    /**
     * Subject of the e-mail.
     */
    public string $subject = '';

    /**
     * Recipients of the e-mail as <BCC>.
     */
    private mixed $bcc = [];

    /**
     * Recipients of the e-mail as <CC>.
     */
    private mixed $cc = [];

    /**
     * Recipients of the e-mail as <From>.
     */
    private mixed $from = [];

    /**
     * Mailer string.
     */
    private readonly string $mailer;

    /**
     * Recipient of the optional notification.
     */
    private mixed $notifyTo = [];

    /**
     * Recipient of the e-mail as <Reply-To>.
     */
    private mixed $replyTo = [];

    /**
     * Recipient of the e-mail as <Return-Path>.
     */
    private mixed $returnPath = [];

    /**
     * Recipient of the e-mail as <Sender>.
     */
    private mixed $sender = [];

    /**
     * Recipients of the e-mail as <TO:>.
     */
    private mixed $to = [];

    private readonly Configuration $config;

    /*
     * Default constructor.
     * Note: any email will be sent from the PMF administrator, use unsetFrom
     *       before using setFrom.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        // Set default value for public properties
        $this->agent = $config->get('mail.remoteSMTP') ? 'SMTP' : 'built-in';
        $this->boundary = self::createBoundary();
        $this->messageId = '<' . $_SERVER['REQUEST_TIME'] . '.' . md5(microtime()) . '@' . self::getServerName() . '>';

        // Set default value for private properties
        $this->config = $config;

        // Set phpMyFAQ related data
        $this->mailer = 'phpMyFAQ/' . $this->config->getVersion();
        try {
            $this->setFrom($this->config->getAdminEmail(), $this->config->getTitle());
        } catch (Exception) {
            // @todo handle exception
        }
    }

    /**
     * Create a string to be used as a valid boundary value.
     *
     * @static
     * @return string The boundary value.
     */
    public static function createBoundary(): string
    {
        return '-----' . md5(microtime());
    }

    /**
     * Returns the server name.
     *
     * @static
     * @return string The server name.
     */
    public static function getServerName(): string
    {
        $hostname = 'localhost.localdomain';
        if (isset($_SERVER['HTTP_HOST'])) {
            $hostname = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $hostname = $_SERVER['SERVER_NAME'];
        }

        return $hostname;
    }

    /**
     * Set the "From" address.
     *
     * @param string      $address User e-mail address.
     * @param string|null $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function setFrom(string $address, string $name = null): bool
    {
        return $this->setEmailTo($this->from, 'From', $address, $name);
    }

    /**
     * Set just one e-mail address into an array.
     *
     * @param array<string> $target Target array.
     * @param string        $targetAlias Alias Target alias.
     * @param string        $address User e-mail address.
     * @param string|null   $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    private function setEmailTo(array &$target, string $targetAlias, string $address, string $name = null): bool
    {
        // Check for the permitted number of items into the $target array
        if (count($target) > 2) {
            $keys = array_keys($target);
            trigger_error(
                "<strong>Mail Class</strong>: a valid e-mail address, $keys[0], " .
                "has been already added as '$targetAlias'!",
                E_USER_ERROR
            );
        }

        return $this->addEmailTo($target, $targetAlias, $address, $name);
    }

    /**
     * Add an e-mail address to an array.
     *
     * @param array<string> $target Target array.
     * @param string        $targetAlias Alias Target alias.
     * @param string        $address User e-mail address.
     * @param string|null   $name Username (optional).
     * @throws Exception
     * @return bool True if successful, false otherwise.
     */
    private function addEmailTo(array &$target, string $targetAlias, string $address, string $name = null): bool
    {
        // Sanity check
        if (!self::validateEmail($address)) {
            throw new Exception('"' . $address . '" is not a valid email address!');
        }

        // Don't allow duplicated addresses
        if (array_key_exists($address, $target)) {
            throw new Exception(
                '<strong>Mail Class</strong>: ' . $address . ' has been already added in ' . $targetAlias . '!'
            );
        }

        if (isset($name)) {
            // Remove CR and LF characters to prevent header injection
            $name = str_replace(["\n", "\r"], '', $name);

            // Encode any special characters in the displayed name
            $name = iconv_mime_encode($targetAlias, $name);

            // Wrap the displayed name in quotes (to fix problems with commas etc.),
            // and escape any existing quotes
            $name = '"' . str_replace('"', '\"', $name) . '"';
        }

        // Add the email address into the target array
        $target[$address] = $name;

        // On Windows, when using PHP built-in mail drop any name, just use the e-mail address
        if (('WIN' === strtoupper(substr(PHP_OS, 0, 3))) && ('built-in' == $this->agent)) {
            $target[$address] = null;
        }

        return true;
    }

    /**
     * Validate an address as an e-mail address.
     *
     * @param string $address E-Mail address
     *
     * @return bool True if the given address is a valid e-mail address, false otherwise.
     */
    public static function validateEmail(string $address): bool
    {
        if (empty($address)) {
            return false;
        }

        $unsafe = ["\r", "\n"];
        if ($address !== str_replace($unsafe, '', $address)) {
            return false;
        }

        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * Add an attachment.
     *
     * @param string      $path File path.
     * @param string|null $name File name. Defaults to the basename.
     * @param string      $mimetype File MIME type. Defaults to 'application/octet-stream'.
     * @param string      $disposition Attachment disposition. Defaults to 'attachment'.
     * @param string      $cid Content ID, required when disposition is 'inline'. Defaults to ''.
     * @return bool True if successful, false otherwise.
     */
    public function addAttachment(
        string $path,
        string $name = null,
        string $mimetype = 'application/octet-stream',
        string $disposition = 'attachment',
        string $cid = ''
    ): bool {
        if (!file_exists($path)) {
            // File not found
            return false;
        } elseif (('inline' == $disposition) && empty($cid)) {
            // Content ID is required
            return false;
        } else {
            if (empty($name)) {
                $name = basename($path);
            }

            $this->attachments[] = [
                'cid' => $cid,
                'disposition' => $disposition,
                'mimetype' => $mimetype,
                'name' => $name,
                'path' => $path
            ];

            return true;
        }
    }

    /**
     * Add a recipient as <BCC>.
     *
     * @param string      $address User e-mail address.
     * @param string|null $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function addBcc(string $address, string $name = null): bool
    {
        return $this->addEmailTo($this->bcc, 'Bcc', $address, $name);
    }

    /**
     * Add a recipient as <CC>.
     *
     * @param string      $address User e-mail address.
     * @param string|null $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function addCc(string $address, string $name = null): bool
    {
        return $this->addEmailTo($this->cc, 'Cc', $address, $name);
    }

    /**
     * Add an address to send a notification to.
     *
     * @param string      $address User e-mail address.
     * @param string|null $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function addNotificationTo(string $address, string $name = null): bool
    {
        return $this->addEmailTo($this->notifyTo, 'Disposition-Notification-To', $address, $name);
    }

    /**
     * Add a recipient as <TO>.
     *
     * @param string $address User e-mail address.
     * @param string|null   $name User name (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function addTo(string $address, ?string $name = null): bool
    {
        return $this->addEmailTo($this->to, 'To', $address, $name);
    }

    /**
     * Send the email according to the current settings.
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function send(): int
    {
        // Sanity check
        if (count($this->to) + count($this->cc) + count($this->bcc) < 1) {
            throw new Exception(
                '<strong>Mail Class</strong>: you need at least to set one recipient among TO, CC and BCC!'
            );
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
            ++$idx;
        }

        if ($hasInlineAttachments) {
            $this->contentType = 'multipart/related';
        }

        // A valid MUA needs to implement the PMF_Mail_IMUA interface
        // i.e. we must prepare recipients, headers, body for the send() method

        // Prepare the recipients
        $to = [];
        foreach ($this->to as $address => $name) {
            $to[] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }
        $recipients = implode(',', $to);
        // Check for the need of undisclosed recipients outlook-like <TO:>
        if (empty($recipients) && (0 == count($this->cc))) {
            $recipients = '<Undisclosed-Recipient:;>';
        }

        // Prepare the headers
        $this->createHeaders();

        // Prepare the body
        $this->createBody();

        // Send the email adopting to the given MUA
        $mua = self::getMUA($this->agent);

        if (method_exists($mua, 'setAuthConfig')) {
            $mua->setAuthConfig(
                $this->config->get('mail.remoteSMTPServer'),
                $this->config->get('mail.remoteSMTPUsername'),
                $this->config->get('mail.remoteSMTPPassword'),
                $this->config->get('mail.remoteSMTPPort'),
                $this->config->get('mail.remoteSMTPDisableTLSPeerVerification')
            );
        }

        return match ($this->agent) {
            'SMTP', 'built-in' => $mua->send($recipients, $this->headers, $this->body),
            default => throw new Exception('<strong>Mail Class</strong>: ' . $this->agent . ' has no implementation!'),
        };
    }

    /**
     * Create the headers of the email.
     */
    private function createHeaders(): void
    {
        // Cleanup headers
        $this->headers = [];

        // Check if the message consists of just a "plain" single item
        if (!str_contains($this->contentType, 'multipart')) {
            // Content-Disposition: inline
            $this->headers['Content-Disposition'] = $this->contentDisposition;
            // Content-Type
            $this->headers['Content-Type'] = $this->contentType . '; format=flowed; charset="' . $this->charset . '"';
            // Content-Transfer-Encoding: 7bit
            $this->headers['Content-Transfer-Encoding'] = '7bit';
        } else {
            // Content-Type
            $this->headers['Content-Type'] = $this->contentType . '; boundary="' . $this->boundary . '"';
        }

        // Date
        $this->headers['Date'] = self::getDate(self::getTime());

        // Disposition-Notification-To, RFC 3798
        $notifyTos = [];
        foreach ($this->notifyTo as $address => $name) {
            $notifyTos[] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }
        $notifyTo = implode(',', $notifyTos);
        if (!empty($notifyTo)) {
            $this->headers['Disposition-Notification-To'] = $notifyTo;
        }

        // From
        foreach ($this->from as $address => $name) {
            $this->headers['From'] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }

        // CC
        foreach ($this->cc as $address => $name) {
            $this->headers['CC'] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }

        // BCC
        foreach ($this->bcc as $address => $name) {
            $this->headers['BCC'] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }

        // Message-Id
        $this->headers['Message-ID'] = $this->messageId;

        // MIME-Version: 1.0
        $this->headers['MIME-Version'] = '1.0';

        // Reply-To
        $this->headers['Reply-To'] = $this->headers['From'];
        foreach ($this->replyTo as $address => $name) {
            $this->headers['Reply-To'] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }

        // Return-Path
        foreach ($this->from as $address => $name) {
            $this->headers['Return-Path'] = '<' . $address . '>';
        }
        foreach ($this->returnPath as $address => $name) {
            $this->headers['Return-Path'] = '<' . $address . '>';
        }

        // Sender
        $this->headers['Sender'] = $this->headers['From'];
        foreach ($this->sender as $address => $name) {
            $this->headers['Sender'] = (empty($name) ? '' : $name . ' ') . '<' . $address . '>';
        }

        // Subject. Note: it must be RFC 2047 compliant
        // TODO: wrap mb_encode_mimeheader() to add other content encodings
        $this->headers['Subject'] = Utils::resolveMarkers(
            html_entity_decode($this->subject, ENT_COMPAT, 'UTF-8'),
            $this->config
        );

        // X-Mailer
        $this->headers['X-Mailer'] = $this->mailer;

        // X-MSMail-Priority
        if (isset($this->priorities[$this->priority])) {
            $this->headers['X-MSMail-Priority'] = $this->priorities[$this->priority];
        }

        // X-Originating-IP
        $this->headers['X-Originating-IP'] = Request::createFromGlobals()->getClientIp();

        // X-Priority
        $this->headers['X-Priority'] = $this->priority;
    }

    /**
     * Returns the date according to RFC 2822.
     *
     * @static
     *
     * @param int $date Unix timestamp.
     *
     * @return string The RFC 2822 date if successful, false otherwise.
     */
    public static function getDate(int $date): string
    {
        return date('r', $date);
    }

    /**
     * Returns the Unix timestamp with preference to the Page Request time.
     *
     * @static
     *
     * @return int Unix timestamp.
     */
    public static function getTime(): int
    {
        if (isset($_SERVER['REQUEST_TIME'])) {
            return $_SERVER['REQUEST_TIME'];
        }

        return time();
    }

    /**
     * Create the body of the email.
     */
    private function createBody(): void
    {
        $lines = [];
        $mainBoundary = $this->boundary;

        // Cleanup body
        $this->body = '';

        // Add lines
        if (str_contains($this->contentType, 'multipart')) {
            $lines[] = 'This is a multi-part message in MIME format.';
            $lines[] = '';
        }

        if (
            in_array(
                $this->contentType,
                ['multipart/mixed', 'multipart/related']
            )
        ) {
            $lines[] = '--' . $mainBoundary;
            $this->boundary = '--=alternative=' . self::createBoundary();
            $lines[] = 'Content-Type: multipart/alternative; boundary="' . $this->boundary . '"';
            $lines[] = '';
        }

        if (str_contains($this->contentType, 'multipart')) {
            // At least we have messageAlt and message
            if (!empty($this->messageAlt)) {
                // 1/2. messageAlt, supposed as plain text
                $lines[] = '--' . $this->boundary;
                $lines[] = 'Content-Type: text/plain; charset="' . $this->charset . '"';
                $lines[] = 'Content-Transfer-Encoding: ' . $this->contentTransferEncoding;
                $lines[] = '';
                $lines[] = self::wrapLines(Utils::resolveMarkers($this->messageAlt, $this->config));
                $lines[] = '';
            }
            // 2/2. message, supposed as, potentially, HTML
            $lines[] = '--' . $this->boundary;
            $lines[] = 'Content-Type: text/html; charset="' . $this->charset . '"';
            $lines[] = 'Content-Transfer-Encoding: ' . $this->contentTransferEncoding;
            $lines[] = '';
            $lines[] = self::wrapLines($this->message);
            // Close the boundary delimiter
            $lines[] = '--' . $this->boundary . '--';
        } else {
            $lines[] = self::wrapLines($this->message);
        }

        if (
            in_array(
                $this->contentType,
                ['multipart/mixed', 'multipart/related']
            )
        ) {
            // Back to the main boundary
            $this->boundary = $mainBoundary;
            // Add the attachments
            foreach ($this->attachments as $attachment) {
                $lines[] = '--' . $this->boundary;
                $lines[] = 'Content-Type: ' . $attachment['mimetype'] . '; name="' . $attachment['name'] . '"';
                $lines[] = 'Content-Transfer-Encoding: base64';
                if ('inline' == $attachment['disposition']) {
                    $lines[] = 'Content-ID: <' . $attachment['cid'] . '>';
                }
                $lines[] =
                    'Content-Disposition: ' . $attachment['disposition'] . '; filename="' . $attachment['name'] . '"';
                $lines[] = '';
                $lines[] = chunk_split(base64_encode(file_get_contents($attachment['path'])));
            }
            // Close the boundary delimiter
            $lines[] = '--' . $this->boundary . '--';
        }

        // Create the final body
        $this->body = '';
        foreach ($lines as $line) {
            $this->body .= $line . $this->eol;
        }
    }

    /**
     * Wraps the lines contained into the given message.
     *
     * @param string $message Message.
     * @param int    $width Column width. Defaults to 72.
     * @param bool   $cut Cutting a word is allowed. Defaults to false.
     * @return string The given message, wrapped as requested.
     */
    public function wrapLines(string $message, int $width = 72, bool $cut = false): string
    {
        $message = $this->fixEOL($message);

        $lines = explode($this->eol, $message);
        $wrapped = '';
        foreach ($lines as $value) {
            $wrapped .= (empty($wrapped) ? '' : $this->eol);
            $wrapped .= wordwrap($value, $width, $this->eol, $cut);
        }

        return $wrapped;
    }

    /**
     * Returns the given text being sure that any CR or LF has been fixed
     * according to RFC 2822 EOL setting.
     *
     * @param string $text Text with a mixed usage of CR, LF, CRLF.
     * @return string The fixed text.
     * @see eol
     */
    public function fixEOL(string $text): string
    {
        // Assure that anything among CRLF, CR will be replaced with just LF
        $text = str_replace(
            [
                "\r\n",
                // CRLF
                "\r",
                // CR
                "\n",
            ],
            "\n", // LF
            $text
        );
        // Set any LF to the RFC 2822 EOL
        return str_replace("\n", $this->eol, $text);
    }

    /**
     * Get the instance of the class implementing the MUA for the given type.
     *
     * @static
     * @param string $mua Type of the MUA.
     */
    public static function getMUA(string $mua): Builtin|SMTP
    {
        $className = ucfirst(
            str_replace(
                '-',
                '',
                $mua
            )
        );
        $class = 'phpMyFAQ\Mail\\' . $className;

        return new $class();
    }

    /**
     * Set an HTML message providing also a plain text alternative message,
     * if not already set using the $messageAlt property.
     * Besides, it is possible to put resources as inline attachments.
     *
     * @param string $message HTML message.
     * @param bool   $sanitize Strip out potentially unsecured HTML tags. Defaults to false.
     * @param bool   $inline Add images as inline attachments. Defaults to false.
     */
    public function setHTMLMessage(string $message, bool $sanitize = false, bool $inline = false): void
    {
        // No Javascript at all
        // 1/2. <script blahblahblah>blahblahblah</tag>
        $message = Strings::preg_replace(
            '/(<script[^>]*>.*<\/script>)|<script[^\/]*\/>|<script[^\/]*>/is',
            '',
            $message
        );

        // Cleanup potentially dangerous HTML tags:
        if ($sanitize && !is_null($message)) {
            // 1/2. <tag blahblahblah>blahblahblah</tag>
            $message = Strings::preg_replace(
                '/<(applet|embed|head|meta|object|style|title)[^>]*>.*<\/\\1>/is',
                '',
                $message
            );
            // 2/2. <tag blahblahblah />
            $message = Strings::preg_replace(
                '/<(applet|embed|head|meta|object|style|title)[^\/]*\/>/is',
                '',
                $message
            );
        }

        if ($inline) {
            trigger_error(
                '<strong>Mail Class</strong>: inline option is not implemented yet.',
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
     * @param string      $address User e-mail address.
     * @param string|null $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function setReplyTo(string $address, string $name = null): bool
    {
        return $this->setEmailTo($this->replyTo, 'Reply-To', $address, $name);
    }

    /**
     * Set the "Return-Path" address.
     *
     * @param string $address User e-mail address.
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function setReturnPath(string $address): bool
    {
        return $this->setEmailTo($this->returnPath, 'Return-Path', $address);
    }

    /**
     * Set the "Sender" address.
     *
     * @param string $address User e-mail address.
     * @param null   $name Username (optional).
     * @return bool True if successful, false otherwise.
     * @throws Exception
     */
    public function setSender(string $address, $name = null): bool
    {
        return $this->setEmailTo($this->sender, 'Sender', $address, $name);
    }

    /**
     * Remove any previous "From" address.
     *
     * @return bool True if successful, false otherwise.
     */
    public function unsetFrom(): bool
    {
        $this->from = [];

        return true;
    }

    /**
     * If the email spam protection has been activated from the general
     * phpMyFAQ configuration this method converts an email address e.g.
     * from "user@example.org" to "user_AT_example_DOT_org". Otherwise,
     * it will return the plain email address.
     *
     * @param string $email E-mail address
     * @static
     */
    public function safeEmail(string $email): string
    {
        if ($this->config->get('spam.enableSafeEmail')) {
            return str_replace(['@', '.'], ['_AT_', '_DOT_'], $email);
        } else {
            return $email;
        }
    }
}
