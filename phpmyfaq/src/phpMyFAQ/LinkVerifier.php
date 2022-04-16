<?php

/**
 * The LinkVerifier class provides methods and functions for verifying URLs.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @package   phpMyFAQ
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2022 NetJapan, Inc. and phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-08-01
 */

namespace phpMyFAQ;

/**
 * Class LinkVerifier
 *
 * @package phpMyFAQ
 */
class LinkVerifier
{
    /**
     * Defines number of times link verifier follows 302 response before failing.
     */
    private const LINKVERIFIER_MAX_REDIRECT_COUNT = 10;

    /**
     * Defines the number of seconds to wait for the remote server to respond.
     */
    private const LINKVERIFIER_CONNECT_TIMEOUT = 5;

    /**
     * Defines the number of seconds to wait for the remote server to send data.
     */
    private const LINKVERIFIER_RESPONSE_TIMEOUT = 10;

    /**
     * List of protocol and urls.
     *
     * @var array
     */
    private array $urlPool = [];

    /**
     * List of protocols we do not want to look at.
     *
     * @var array
     */
    private array $invalidProtocols = [];

    /**
     * Last verify results (we might use it later).
     *
     * @var array
     */
    private array $lastResult = [];

    /**
     * List of hosts that are slow to resolve.
     *
     * @var array
     */
    private array $slowHosts = [];

    /**
     * @var Configuration|null
     */
    private ?Configuration $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;

        if (!extension_loaded('openssl')) {
            $this->addIgnoreProtocol('https:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'https'));
        }

        $this->addIgnoreProtocol('ftp:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'ftp'));
        $this->addIgnoreProtocol('gopher:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'gopher'));
        $this->addIgnoreProtocol('mailto:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'mailto'));
        $this->addIgnoreProtocol('telnet:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'telnet'));
        $this->addIgnoreProtocol('feed:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'feed'));

        // Hack: these below are not real scheme for defining protocols like the ones above
        $this->addIgnoreProtocol('file:', sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'file'));
        $this->addIgnoreProtocol(
            'javascript:',
            sprintf(Translation::get('ad_linkcheck_protocol_unsupported'), 'javascript')
        );
    }

    /**
     * Adds protocols we want to ignore to an array, executed in constructor.
     *
     * @param string $protocol
     * @param string $message
     *
     * @return bool true, if successfully added, otherwise false
     */
    protected function addIgnoreProtocol(string $protocol = '', string $message = ''): bool
    {
        if ('' !== $protocol) {
            $this->invalidProtocols[strtolower($protocol)] = $message;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get current urls.
     *
     * @return array
     */
    public function getUrlPool(): array
    {
        return $this->urlPool;
    }

    /**
     * Returns the HTML text that needs to be shown in entry listing.
     *
     * @param int    $id
     * @param string $faqLang
     *
     * @return string
     */
    public function getEntryStateHTML(int $id = 0, string $faqLang = ''): string
    {
        // Check if feature is disabled.
        if ($this->isReady() === false) {
            return sprintf(
                '<span class="fa-stack" aria-hidden="true"><i class="fa fa-link fa-stack-1x"></i>' .
                '<i class="fa fa-ban fa-stack-2x text-danger" title="%s"></i></span>',
                Translation::get('ad_linkcheck_feedback_url-disabled')
            );
        }

        // check if article entry exists (we should not need this)
        $src = $this->getEntryState($id, $faqLang, false);
        if ($src === false) {
            return sprintf(
                '<span class="fa-stack" aria-hidden="true"><i class="fa fa-link fa-stack-1x"></i>' .
                '<i class="fa fa-ban fa-stack-2x text-danger" title="%s"></i></span>',
                Translation::get('ad_linkcheck_feedback_url-disabled')
            );
        }

        if ($src === true) {
            $src = 'noscript';
        }

        // define name for javascripting
        $spanId = 'spanurl_' . $faqLang . '_' . $id;
        $divId = 'divurl_' . $faqLang . '_' . $id;

        return sprintf(
            '<div id="%s" class="url-%s"><span id="%s"><a href="javascript:onDemandVerifyURL(%d,\'%s\');">%s</a>' .
            '</span></div>',
            $divId,
            $src,
            $spanId,
            $id,
            $faqLang,
            Translation::get('ad_linkcheck_feedback_url-' . $src)
        );
    }

    /**
     * Returns whether link verifier is ready to verify URLs.
     *
     * @return bool true if ready to verify URLs, otherwise false
     */
    public function isReady(): bool
    {
        if (is_null($this->config->getDefaultUrl()) || '' !== $this->config->getDefaultUrl()) {
            return false;
        }

        return true;
    }

    /**
     * retrieves stored link state and validates timestamp.
     *
     * @param int    $id
     * @param string $faqLang
     * @param bool   $checkDate
     * @return bool|string
     */
    public function getEntryState(int $id = 0, string $faqLang = '', bool $checkDate = false): bool|string
    {
        $interval = $this->getURLValidateInterval();
        $query = sprintf(
            "
            SELECT 
                links_state, links_check_date 
            FROM 
                %sfaqdata 
            WHERE 
                id = %d 
            AND 
                lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->config->getDb()->escape($faqLang)
        );

        if ($result = $this->config->getDb()->query($query)) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $_linkState = $row->links_state;
                if (trim($_linkState) == '') {
                    $_linkState = true;
                }

                if ($row->links_check_date > $interval) {
                    return $_linkState;
                } else {
                    if ($checkDate == false) {
                        return $_linkState;
                    } else {
                        return true;
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Retrieves the oldest timestamp for stored link validation result.
     *
     * @return int
     */
    public function getURLValidateInterval(): int
    {
        if ($this->config->get('main.urlValidateInterval') != '') {
            $requestTime = $_SERVER['REQUEST_TIME'] - $this->config->get('main.urlValidateInterval');
        } else {
            // default in recheck links once a day unless explicitly requested.
            $requestTime = $_SERVER['REQUEST_TIME'] - 86400;
        }

        return $requestTime;
    }

    /**
     * Verifies specified article content and update links_state database entry.
     *
     * @param string $contents
     * @param int    $id
     * @param string $faqLang
     * @param bool   $cron
     * @return string HTML text, if $cron is false (default)
     */
    public function verifyArticleURL(
        string $contents = '',
        int $id = 0,
        string $faqLang = '',
        bool $cron = false
    ): string {
        if ($this->config->getDefaultUrl() === '') {
            $output = Translation::get('ad_linkcheck_noReferenceURL');

            return ($cron ? '' : sprintf('<p class="alert alert-warning">%s</p>', $output));
        }

        if (trim('' == $this->config->getDefaultUrl())) {
            $output = Translation::get('ad_linkcheck_noReferenceURL');

            return ($cron ? '' : sprintf('<p class="alert alert-warning">%s</p>', $output));
        }

        if ($this->isReady() === false) {
            $output = Translation::get('ad_linkcheck_noAllowUrlOpen');

            return ($cron ? '' : sprintf('<p class="alert alert-warning">%s</p>', $output));
        }

        // Parse contents and verify URLs
        $this->parseString($contents);
        $result = $this->verifyURLs($this->config->getDefaultUrl());
        $this->markEntry($id, $faqLang);

        // If no URLs found
        if ($result == false) {
            $output = sprintf(
                '<h3>%s</h3><p class="alert alert-info">%s</p>',
                Translation::get('ad_linkcheck_checkResult'),
                Translation::get('ad_linkcheck_noLinksFound')
            );

            return ($cron ? '' : $output);
        }

        $failreasons = $inforeasons = [];
        $output = '    <h3>' . Translation::get('ad_linkcheck_checkResult') . "</h3>\n";
        $output .= '    <table class="table align-middle">' . "\n";
        foreach ($result as $type => $_value) {
            $output .= '        <tr><td><strong>' . Strings::htmlspecialchars($type) . "</strong></td></tr>\n";
            foreach ($_value as $value) {
                $_output = '            <td />';
                $_output .= '            <td><a href="' . $value['absurl'] . '" target="_blank">' .
                    Strings::htmlspecialchars($value['absurl']) . "</a></td>\n";
                $_output .= '            <td>';
                if (isset($value['redirects']) && ($value['redirects'] > 0)) {
                    $_redirects = '(' . $value['redirects'] . ')';
                } else {
                    $_redirects = '';
                }
                if ($value['valid'] === true) {
                    $_classname = 'urlsuccess';
                    $_output .= '<td class="' . $_classname . '">' . Translation::get('ad_linkcheck_checkSuccess') .
                        $_redirects . '</td>';
                    if ($value['reason'] != '') {
                        $inforeasons[] = sprintf(
                            Translation::get('ad_linkcheck_openurl_infoprefix'),
                            Strings::htmlspecialchars($value['absurl'])
                        ) . $value['reason'];
                    }
                } else {
                    $_classname = 'urlfail';
                    $_output .= '<td class="' . $_classname . '">' . Translation::get('ad_linkcheck_checkFailed') .
                        '</td>';
                    if ($value['reason'] != '') {
                        $failreasons[] = $value['reason'];
                    }
                }
                $_output .= '</td>';
                $output .= '        <tr class="' . $_classname . '">' . "\n" . $_output . "\n";
                $output .= "        </tr>\n";
            }
        }
        $output .= "    </table>\n";

        if (count($failreasons) > 0) {
            $output .= "    <br>\n    <strong>" . Translation::get('ad_linkcheck_failReason') . "</strong>\n    <ul>\n";
            foreach ($failreasons as $reason) {
                $output .= '        <li>' . $reason . "</li>\n";
            }
            $output .= "    </ul>\n";
        }

        if (count($inforeasons) > 0) {
            $output .= "    <br>\n    <strong>" . Translation::get('ad_linkcheck_infoReason') . "</strong>\n    <ul>\n";
            foreach ($inforeasons as $reason) {
                $output .= '        <li>' . $reason . "</li>\n";
            }
            $output .= "    </ul>\n";
        }

        if ($cron) {
            return '';
        } else {
            return $output;
        }
    }

    /**
     * This function parses HTML and extracts URLs and returns the number of
     * URLs found.
     *
     * @param string $string String
     * @return int
     */
    public function parseString(string $string = ''): int
    {
        $urlCount = 0;
        $types = ['href', 'src'];
        $matches = [];

        // Clean $this->urlpool
        $this->urlPool = [];
        foreach ($types as $type) {
            preg_match_all("|[^?&]$type\=(\"?'?`?)([[:alnum:]\:\#%?=;&@/\ \.\_\-\{\}]+)\\1|i", $string, $matches);
            $sz = sizeof($matches[2]);
            for ($i = 0; $i < $sz; ++$i) {
                $this->urlPool[$type][] = $matches[2][$i];
                ++$urlCount;
            }
        }

        return $urlCount;
    }

    /**
     * Perform link validation to each URLs found.
     *
     * @param string $referenceUri
     * @return array
     */
    public function verifyURLs(string $referenceUri = ''): array
    {
        $this->lastResult = [];

        foreach ($this->urlPool as $_type => $_value) {
            foreach ($_value as $_key => $_url) {
                if (!(isset($result[$_type][$_url]))) {
                    $_result = [];
                    $_result['type'] = $_type;
                    $_result['rawurl'] = $_url;
                    $_result['reference'] = $referenceUri;

                    // Expand uri into absolute URL.
                    $_absurl = $this->makeAbsoluteURL($_url, $referenceUri);
                    $_result['absurl'] = $_absurl;

                    list($_result['valid'], $_result['redirects'], $_result['reason']) = $this->openURL($_absurl);
                    $this->lastResult[$_type][$_url] = $_result;
                }
            }
        }

        return $this->lastResult;
    }

    /**
     * This function converts relative uri into absolute uri using specific reference point.
     * For example:
     *   $relativeUri  = "test/foo.html"
     *   $referenceUri = "http://example.com:8000/sample/index.php"
     * will generate "http://example.com:8000/sample/test/foo.html".
     *
     * @param string $relativeUri
     * @param string $referenceUri
     * @return string $result
     */
    protected function makeAbsoluteURL(string $relativeUri = '', string $referenceUri = ''): string
    {
        // If relative URI is protocol we don't want to handle, don't process it.
        foreach ($this->invalidProtocols as $protocol => $message) {
            if (Strings::strpos($relativeUri, $protocol) === 0) {
                return $relativeUri;
            }
        }

        // If relative URI is absolute URI, don't process it.
        foreach (['https://', 'http://'] as $protocol) {
            if (Strings::strpos($relativeUri, $protocol) === 0) {
                return $relativeUri;
            }
        }

        // Split reference uri into parts.
        $pathParts = parse_url($referenceUri);

        // If port is specified in reference uri, prefix with ":"
        if (isset($pathParts['port']) && $pathParts['port'] !== '') {
            $pathParts['port'] = ':' . $pathParts['port'];
        } else {
            $pathParts['port'] = '';
        }

        // If path is not specified in reference uri, set as blank
        if (isset($pathParts['path'])) {
            $pathParts['path'] = str_replace('\\', '/', $pathParts['path']);
            $pathParts['path'] = preg_replace("/^.*(\/)$/i", '', $pathParts['path']);
        } else {
            $pathParts['path'] = '';
        }

        // Recombine urls
        if ('/' !== Strings::substr($relativeUri, 0, 1)) {
            $relativeUri = $pathParts['path'] . '/' . $relativeUri;
        }

        return sprintf(
            '%s://%s%s%s',
            $pathParts['scheme'],
            $pathParts['host'],
            $pathParts['port'],
            $relativeUri
        );
    }

    /**
     * Checks whether a URL can be opened.
     * if $redirect is specified, will handle Location: redirects.
     *
     * @param string $url
     * @param string $redirect
     * @param int    $redirectCount
     * @return array
     */
    protected function openURL(string $url = '', string $redirect = '', int $redirectCount = 0): array
    {
        // If perquisites fail
        if (false === $this->isReady()) {
            return [false, $redirectCount, Translation::get('ad_linkcheck_openurl_notready')];
        }

        // Recurring too much ?
        if (($redirectCount >= self::LINKVERIFIER_MAX_REDIRECT_COUNT) || ($url == $redirect)) {
            return [
                false,
                $redirectCount,
                sprintf(
                    Translation::get('ad_linkcheck_openurl_maxredirect'),
                    self::LINKVERIFIER_MAX_REDIRECT_COUNT
                ),
            ];
        }

        // If destination is blank, fail.
        if ('' === trim($url)) {
            return [false, $redirectCount, Translation::get('ad_linkcheck_openurl_urlisblank')];
        }

        if ('' !== $redirect) {
            $url = $this->makeAbsoluteURL($redirect, $url);
        }

        // parse URL
        $defaultParts = [
            'scheme' => 'http',
            'host' => $_SERVER['HTTP_HOST'],
            'user' => '',
            'pass' => '',
            'path' => '/',
            'query' => '',
            'fragment' => '',
        ];
        $urlParts = @parse_url($url);
        foreach ($defaultParts as $key => $value) {
            if (!(isset($urlParts[$key]))) {
                $urlParts[$key] = $value;
            }
        }

        if (!(isset($urlParts['port']))) {
            $urlParts['port'] = match ($urlParts['scheme']) {
                'https' => 443,
                default => 80,
            };
        }

        // Hack: fix any unsafe space chars in any component of the path to avoid HTTP 400 status during HEAD crawling
        if ('' !== $urlParts['path']) {
            $urlSubParts = explode('/', $urlParts['path']);
            $num = count($urlSubParts);
            for ($i = 0; $i < $num; ++$i) {
                $urlSubParts[$i] = str_replace(' ', '%20', $urlSubParts[$i]);
            }
            $urlParts['path'] = implode('/', $urlSubParts);
        }

        if ('' !== $urlParts['query']) {
            $urlParts['query'] = '?' . $urlParts['query'];
        }

        if ('' !== $urlParts['fragment']) {
            $urlParts['fragment'] = '#' . $urlParts['fragment'];
        }

        // Check whether we tried the host before
        if (isset($this->slowHosts[$urlParts['host']])) {
            return [
                false,
                $redirectCount,
                sprintf(
                    Translation::get('ad_linkcheck_openurl_tooslow'),
                    Strings::htmlspecialchars($urlParts['host'])
                ),
            ];
        }

        // Check whether the hostname exists
        if (gethostbynamel($urlParts['host']) === false) {
            // mark this host too slow to verify
            $this->slowHosts[$urlParts['host']] = true;

            return [
                false,
                $redirectCount,
                sprintf(
                    Translation::get('ad_linkcheck_openurl_nodns'),
                    Strings::htmlspecialchars($urlParts['host'])
                ),
            ];
        }

        $_response = '';

        // open socket for remote server with timeout (default: 5secs)
        $_host = $urlParts['host'];
        if (@extension_loaded('openssl') && ('https' == $urlParts['scheme'])) {
            $_host = 'ssl://' . $_host;
        }

        $fp = @fsockopen($_host, $urlParts['port'], $errno, $errstr, self::LINKVERIFIER_CONNECT_TIMEOUT);

        if (!$fp) {
            // mark this host too slow to verify
            $this->slowHosts[$urlParts['host']] = true;

            return [
                false,
                $redirectCount,
                sprintf(
                    Translation::get('ad_linkcheck_openurl_tooslow'),
                    Strings::htmlspecialchars($urlParts['host'])
                ),
            ];
        }

        // wait for data with timeout (default: 10secs)
        stream_set_timeout($fp, self::LINKVERIFIER_RESPONSE_TIMEOUT, 0);
        $_url = $urlParts['path'] . $urlParts['query'] . $urlParts['fragment'];
        fputs($fp, 'HEAD ' . $_url . " HTTP/1.0\r\nHost: " . $urlParts['host'] . "\r\n");
        // Be polite: let our probe declares itself
        fputs($fp, "User-Agent: phpMyFAQ Link Checker\r\n");
        fputs($fp, "\r\n");
        while (!feof($fp)) {
            $_response .= fread($fp, 4096);
        }
        fclose($fp);

        // parse response
        $code = 0;
        $allowVerbs = 'n/a';
        $location = $url;
        $response = explode("\r\n", $_response);
        $httpStatusMsg = strip_tags($response[count($response) - 1]);

        foreach ($response as $_response) {
            if (preg_match("/^HTTP\/[^ ]+ ([01-9]+) .*$/", $_response, $matches)) {
                $code = $matches[1];
            }
            if (preg_match('/^Location: (.*)$/', $_response, $matches)) {
                $location = $matches[1];
            }
            if (preg_match('/^[a|A]llow: (.*)$/', $_response, $matches)) {
                $allowVerbs = $matches[1];
            }
        }

        // process response code
        switch ($code) {
            // TODO: Add more explicit http status management
            case '200': // OK
                $_reason = ($redirectCount > 0) ? sprintf(
                    Translation::get('ad_linkcheck_openurl_redirected'),
                    Strings::htmlspecialchars($url)
                ) : '';

                return array(true, $redirectCount, $_reason);
                break;
            case '301': // Moved Permanently (go recursive ?)
            case '302': // Found (go recursive ?)
                return $this->openURL($url, $location, $redirectCount + 1);
                break;
            case 400:   // Bad Request
                return array(
                    false,
                    $redirectCount,
                    sprintf(Translation::get('ad_linkcheck_openurl_ambiguous') . '<br>' . $httpStatusMsg, $code)
                );
                break;
            case 404:   // Not found
                return array(
                    false,
                    $redirectCount,
                    sprintf(Translation::get('ad_linkcheck_openurl_not_found'), $urlParts['host'])
                );
                break;
            case '300': // Multiple choices
            case '401': // Unauthorized (but it's there. right ?)
                return array(true, $redirectCount, sprintf(Translation::get('ad_linkcheck_openurl_ambiguous'), $code));
                break;
            case '405': // Method Not Allowed
                // TODO: Add a fallback to use GET method, otherwise this link should be marked as bad
                return array(
                    true,
                    $redirectCount,
                    sprintf(Translation::get('ad_linkcheck_openurl_not_allowed'), $urlParts['host'], $allowVerbs)
                );
                break;
            default:    // All other statuses
                return array(false, $redirectCount, sprintf(Translation::get('ad_linkcheck_openurl_ambiguous'), $code));
                break;
        }
    }

    /**
     * logs the current state of link to the specified entry.
     *
     * @param int    $id
     * @param string $faqLang
     * @param string $state   (optional)
     * @return bool true if operation successful, otherwise false
     */
    public function markEntry(int $id = 0, string $faqLang = '', string $state = ''): bool
    {
        if (($id < 1) || (trim($faqLang) == '')) {
            return false;
        }

        if ($state == '') {
            $state = $this->getLinkStateString();
        }

        $query = sprintf(
            "
            UPDATE 
                %sfaqdata 
            SET 
                links_state = '%s', links_check_date = %d 
            WHERE 
                id = %d 
            AND 
                lang='%s'",
            Database::getTablePrefix(),
            $state,
            $_SERVER['REQUEST_TIME'],
            $id,
            $faqLang
        );

        if ($this->config->getDb()->query($query)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * gets the current status string for link check result.
     *
     * "nolinks" - no links were found in contents
     * "linkok"  - link(s) were found and were all ok
     * "linkbad" - link(s) were found and at least one link was broken
     *
     * @result string
     */
    public function getLinkStateString(): string
    {
        $linkCount = $errorCount = 0;

        foreach ($this->lastResult as $_type => $_value) {
            foreach ($_value as $_url => $value) {
                ++$linkCount;
                if ($value['valid'] == false) {
                    ++$errorCount;
                }
            }
        }

        if (0 === $linkCount) {
            return 'nolinks';
        } else {
            if (0 === $errorCount) {
                return 'linkok';
            } else {
                return 'linkbad';
            }
        }
    }
}
