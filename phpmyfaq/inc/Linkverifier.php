<?php
/**
 * The PMF_Linkverifier class provides methods and functions for verifying URLs
 * 
 * PHP Version 5.2
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
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @category  phpMyFAQ
 * @package   PMF_Linkverifier
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2010 NetJapan, Inc. and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-08-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/* Defines number of times linkverifier follows 302 response before failing.
 *
 * Suggested value is above 10 redirects
 */

if (!defined('LINKVERIFIER_MAX_REDIRECT_COUNT')) {
    define('LINKVERIFIER_MAX_REDIRECT_COUNT', 10);
}

/* Defines the number of seconds to wait for the remote server to respond
 *
 * Suggested value is 5 seconds
 */
if (!defined('LINKVERIFIER_CONNECT_TIMEOUT')) {
    define('LINKVERIFIER_CONNECT_TIMEOUT', 5);
}

/* Defines the number of seconds to wait for the remote server to send data
 *
 * Suggested value is 10 seconds
 */
if (!defined('LINKVERIFIER_RESPONSE_TIMEOUT')) {
    define('LINKVERIFIER_RESPONSE_TIMEOUT', 10);
}

/* Defines the behaviour when a user click "Edit FAQs" in the backend.
 * Do you want an automatic links verification
 * with live update of each links verification status?
 *
 * Suggested value is:
 * a. false, if you don't use a cron/at entry to call 'cron.verifyurls.php' during each night.
 *           This will avoid browser high load (100% CPU)
 * b. true, if you use a cron/at entry to call 'cron.verifyurls.php' during each night
 */
if (!defined('LINKVERIFIER_AUTOMATIC_CALL_ON_EDIT_FAQ')) {
    define('LINKVERIFIER_AUTOMATIC_CALL_ON_EDIT_FAQ', false);
}

/**
 * PMF_LinkVerifier
 *
 * @category  phpMyFAQ
 * @package   PMF_Linkverifier
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2010 NetJapan, Inc. and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-08-01
 */
class PMF_Linkverifier
{
    /* List of protocol and urls
     *
     * @var mixed
     */
    protected $urlpool = array();

    /* List of prohibited prefixes and messages
     *
     * @var mixed
     */
    protected $warnlists = array();

    /* List of URLs not to probe
     *
     * @var mixed
     */
    protected $ignorelists = array();

    /* List of protocols we do not want to look at
     *
     * @var mixed
     */
    protected $invalid_protocols = array();

    /* Last verify results (we might use it later)
     *
     * @var mixed
     */
    protected $lastResult = array();

    /* List of hosts that are slow to resolve.
     *
     * @var mixed
     */
    protected $slow_hosts = array();

    /**
    * DB handle
    *
    * @var  object
    */
    private $db = null;

    /**
     * User
     *
     * @var integer
     */
    protected $user = null;

    /**
     * Constructor
     *
     * @param  string $user User
     * @author Minoru TODA <todam@netjapan.co.jp>
     * @author Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since  2005-08-01
     */
    public function __construct($user = null)
    {
        global $PMF_LANG;

        $this->db   = PMF_Db::getInstance();
        $this->user = $user;

        if (!@extension_loaded('openssl')) { // PHP 4.3.0+: fsockopen needs OpenSSL
            $this->addIgnoreProtocol("https:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "https"));
        }
        $this->addIgnoreProtocol("ftp:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "ftp"));

        $this->addIgnoreProtocol("gopher:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "gopher"));
        $this->addIgnoreProtocol("mailto:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "mailto"));
        $this->addIgnoreProtocol("telnet:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "telnet"));
        $this->addIgnoreProtocol("feed:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "feed"));

        // Hack: these below are not real scheme for defining protocols like the ones above
        $this->addIgnoreProtocol("file:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "file"));
        $this->addIgnoreProtocol("javascript:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "javascript"));

        // load list of URLs to ignore / fail
        $this->loadConfigurationFromDB();
    }

    
	/**
	 * Get current urls
	 * 
     * @return array $urlpool url list
     */
    function getUrlpool()
    {
        return $this->urlpool;
    }


    /**
     * Returns whether linkverifier is ready to verify URLs.
     *
     * @return boolean true if ready to verify URLs, otherwise false
     */
    public function isReady()
    {
        if (PMF_Configuration::getInstance()->get('main.referenceURL') == '') {
            return false;
        }

        return true;
    }



    /**
     * Resets url pool for next batch of processing.
     *
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function resetPool()
    {
        $this->urlpool = array();
        $this->lastResult = array();
    }

    /**
     * This function adds entry to the internal warnlists.
     * Use this if you want to mark certain URLs unsable (like internal links of a dev-site).
     *
     * @param   string $urlprefix
     * @param   string $message
     * @return  boolean true, if successfully added, otherwise false
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function addWarnlist($urlprefix = "", $message = "")
    {
        if ($urlprefix != "") {
            $this->warnlists[$urlprefix] = $message;
            return true;
        } else {
            return false;
        }
    }

    /**
     * This function adds entry to the internal ignore list.
     * Some URL/sites makes PHP report 'connection failed', even when browsers can access them.
     * URLs on ignore lists always reports as successful connect.
     *
     * @param   string $url
     * @param   string $message
     * @return  boolean true, if successfully added, otherwise false
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function addIgnorelist($url = "", $message = "")
    {
        if ($url != "") {
            $this->ignorelists[strtolower($url)] = $message;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param   string $protocol
     * @param   string $message
     * @return  boolean true, if successfully added, otherwise false
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function addIgnoreProtocol($protocol = "", $message = "")
    {
        if ($protocol != "") {
            $this->invalid_protocols[strtolower($protocol)] = $message;
            return true;
        } else {
            return false;
        }
    }

    /**
     * load ignore/warnlists from database
     *
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-09-29
    */
    function loadConfigurationFromDB()
    {
        $query = "SELECT type, url, reason FROM ".SQLPREFIX."faqlinkverifyrules WHERE enabled = 'y'";
        $result = $this->db->query($query);
        while ($row = @$this->db->fetch_object($result)) {
            switch (strtolower($row->type)) {
            case 'ignore':      $this->addIgnoreList($row->url, $row->reason);
                                break;
            case 'warn':        $this->addWarnlist($row->url, $row->reason);
                                break;
            }
        }
    }

    /**
     * This function verifies whether a URL is in IgnoreList.
     *
     * @param   string $url
     * @return  mixed  $result false if URL should NOT be ignored. !false if ignored.
     * @access  private
     * @*author Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function checkIfIgnoreLink($url = "")
    {
        $url = strtolower($url);
        foreach ($this->invalid_protocols as $_protocol => $_message) {
            if (strpos($url, $_protocol) === 0) {
                return $_message;
            }
        }

        foreach ($this->ignorelists as $_url => $_message) {
            if ($url == $_url) {
                return $_message;
            }
        }

        return false;
    }

    /**
     * This function verifies whether a URL should be forced as error (warnlist).
     *
     * @param   string $url
     * @return  mixed  $result false if URL should NOT be failed.. !false if qualifies as error.
     * @access  private
     * @*author Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function checkIfForceErrorLink($url = "")
    {
        $url = strtolower($url);

        foreach ($this->warnlists as $_protocol => $_message) {
            if (strpos($url, $_protocol) === 0) {
                return $_message;
            }
        }

        return false;
    }


    /**
     * This function converts relative uri into absolute uri using specific reference point.
     * For example,
     *   $relativeuri = "test/foo.html"
     *   $referenceuri = "http://example.com:8000/sample/index.php"
     * will generate "http://example.com:8000/sample/test/foo.html"
     *
     * @param   string $relativeuri
     * @param   string $message
     * @return  string $result
     * @access  private
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function makeAbsoluteURL($relativeuri = "", $referenceuri = "")
    {
        // If relativeuri is protocol we don't want to handle, don't process it.
        foreach ($this->invalid_protocols as $_protocol => $_message) {
            if (PMF_String::strpos($relativeuri, $_protocol) === 0) {
                return $relativeuri;
            }
        }

        // If relativeuri is absolute URI, don't process it.
        foreach (array("http://", "https://") as $_protocol) {
            if (PMF_String::strpos($relativeuri, $_protocol) === 0) {
                return $relativeuri;
            }
        }

        // Split reference uri into parts.
        $pathparts = parse_url($referenceuri);

        // If port is specified in reference uri, prefix with ":"
        if (isset($pathparts['port']) && $pathparts['port'] != "") {
            $pathparts['port'] = ":".$pathparts['port'];
        } else {
            $pathparts['port'] = "";
        }

        // If path is not specified in reference uri, set as blank
        if (isset($pathparts['path'])) {
            $pathparts['path'] = str_replace("\\","/",$pathparts['path']);
            $pathparts['path'] = preg_replace("/^.*(\/)$/i","", $pathparts['path']);
        } else {
            $pathparts['path'] = "";
        }

        // Recombine urls
        if (PMF_String::substr($relativeuri,0,1) == "/") {
            return $pathparts['scheme']."://".$pathparts['host'].$pathparts['port'].$relativeuri;
        } else {
            return $pathparts['scheme']."://".$pathparts['host'].$pathparts['port'].$pathparts['path']."/".$relativeuri;
        }
    }

    /**
     * This function parses HTML and extracts urls
     *
     * @param string $string String
     * 
     * @return mixed  false if URL is not found, otherwise returns the number of URLs found.
     */
    public function parse_string($string = "")
    {
        $urlcount = 0;
        $types    = array('href', 'src', 'url');
        $matches  = array();

        // Clean $this->urlpool
        $this->urlpool = array();
        while(list(,$type) = each($types)) {
            preg_match_all("|[^?&]$type\=(\"?'?`?)([[:alnum:]\:\#%?=;&@/\ \.\_\-\{\}]+)\\1|i", $string, $matches); 
            $sz = sizeof($matches[2]);
            for ($i = 0;$i < $sz; $i++) {
                $this->urlpool[$type][] = $matches[2][$i];
                $urlcount++;
            }
        }

        return ($urlcount == 0) ? false : $urlcount;
    }

    /**
     * Checks whether a URL can be opened.
     *
     * if $redirect is specified, will handle Location: redirects.
     *
     * @param   string $url
     * @param   string $redirect
     * @param   string $loopsLeft
     * @result  boolean true if connect successful. otherwise false
     * @access  private
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2005-08-01
     */

    function openURL($url = "", $redirect = "", $redirectCount = 0)
    {
        global $PMF_LANG;

        // If prequisites fail
        if ($this->isReady() == false) {
            return array(false, $redirectCount, $PMF_LANG['ad_linkcheck_openurl_notready']);
        }

        // Recursing too much ?
        if (($redirectCount >= LINKVERIFIER_MAX_REDIRECT_COUNT) || ($url == $redirect)) {
            return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_maxredirect'], LINKVERIFIER_MAX_REDIRECT_COUNT));
        }

        // If destination is blank, fail.
        if (trim($url) == "") {
            return array(false, $redirectCount, $PMF_LANG['ad_linkcheck_openurl_urlisblank']);
        }

        if ($redirect != "") {
            $url = $this->makeAbsoluteURL($redirect, $url);
        }

        // parse URL
        $urlParts = @parse_url($url);
        foreach(array(
                    'scheme' => 'http',
                    'host' => $_SERVER['HTTP_HOST'],
                    'user' => '',
                    'pass' => '',
                    'path' => '/',
                    'query' => '',
                    'fragment' => '') as $_key => $_value) {
            if (!(isset($urlParts[$_key]))) {
                $urlParts[$_key] = $_value;
            }
        }

        if (!(isset($urlParts['port']))) {
            switch ($urlParts['scheme']) {
                case 'https': $urlParts['port'] = '443'; break;
                case 'http': $urlParts['port'] = '80'; break;
                default: $urlParts['port'] = '80'; break;
            }
        }

        // Hack: fix any unsafe space chars in any component of the path to avoid HTTP 400 status during HEAD crawling
        if ($urlParts['path'] != '') {
            $urlSubParts = explode('/', $urlParts['path']);
            for ($i = 0; $i < count($urlSubParts); $i++) {
                $urlSubParts[$i] = str_replace(' ', '%20', $urlSubParts[$i]);
            }
            $urlParts['path'] = implode('/', $urlSubParts);
        }

        if ($urlParts['query'] != "") {
            $urlParts['query'] = "?".$urlParts['query'];
        }

        if ($urlParts['fragment'] != "") {
            $urlParts['fragment'] = "#".$urlParts['fragment'];
        }

        // Check whether we tried the host before
        if (isset($this->slow_hosts[$urlParts['host']])) {
            return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_tooslow'],PMF_String::htmlspecialchars($urlParts['host'])));
        }

        // Check whether the hostname exists
        if (gethostbynamel($urlParts['host']) === false) {
            // mark this host too slow to verify
            $this->slow_hosts[$urlParts['host']] = true;
            return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_nodns'], PMF_String::htmlspecialchars($urlParts['host'])));
        }

        $_response = "";
        // open socket for remote server with timeout (default: 5secs)
        // PHP 4.3.0+: when compiled w/ OpenSSL support, fsockopen can connect to the remote host using SSL
        $_host = $urlParts['host'];
        if (@extension_loaded('openssl') && ('https' == $urlParts['scheme'])) {
            $_host = 'ssl://'.$_host;
        }
        $fp = @fsockopen($_host, $urlParts['port'], $errno, $errstr, LINKVERIFIER_CONNECT_TIMEOUT);
        if (!$fp) {
            // mark this host too slow to verify
            $this->slow_hosts[$urlParts['host']] = true;
            return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_tooslow'],PMF_String::htmlspecialchars($urlParts['host'])));
        }

        // wait for data with timeout (default: 10secs)
        stream_set_timeout($fp, LINKVERIFIER_RESPONSE_TIMEOUT, 0);
        $_url = $urlParts['path'].$urlParts['query'].$urlParts['fragment'];
        fputs($fp, "HEAD ".$_url." HTTP/1.0\r\nHost: ".$urlParts['host']."\r\n");
        // Be polite: let our probe declares itself
        fputs($fp, "User-Agent: phpMyFAQ Link Checker\r\n");
        fputs($fp, "\r\n");
        while (!feof($fp)) { $_response .= fread($fp, 4096); }
        fclose($fp);

        // parse response
        $code = 0;
        $allowVerbs = 'n/a';
        $httpStatusMsg = '';
        $location = $url;
        $response = explode("\r\n", $_response);
        $httpStatusMsg = strip_tags($response[count($response) - 1]);

        foreach ($response as $_response) {
            if (preg_match("/^HTTP\/[^ ]+ ([01-9]+) .*$/", $_response, $matches)) {
                $code = $matches[1];
            }
            if (preg_match("/^Location: (.*)$/", $_response, $matches)) {
                $location = $matches[1];
            }
            if (preg_match("/^[a|A]llow: (.*)$/", $_response, $matches)) {
                $allowVerbs = $matches[1];
            }
        }

        // process response code
        switch ( $code ) {
            // TODO: Add more explicit http status management
            case '200': // OK
                $_reason = ($redirectCount > 0) ? sprintf($PMF_LANG['ad_linkcheck_openurl_redirected'],PMF_String::htmlspecialchars($url)) : "";
                return array(true, $redirectCount, $_reason);
                break;
            case '301': // Moved Permanently (go recursive ?)
            case '302': // Found (go recursive ?)
                return $this->openURL($url, $location, $redirectCount + 1);
                break;
            case 400:   // Bad Request
                return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_ambiguous'].'<br />'.$httpStatusMsg, $code));
                break;
            case 404:   // Not found
                return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_not_found'], $urlParts['host']));
                break;
            case '300': // Multiple choices
            case '401': // Unauthorized (but it's there. right ?)
                return array(true, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_ambiguous'], $code));
                break;
            case '405': // Method Not Allowed
                // TODO: Add a fallback to use GET method, otherwise this link should be marked as bad
                return array(true, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_not_allowed'], $urlParts['host'], $allowVerbs));
                break;
            default:    // All other statuses
                return array(false, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_ambiguous'], $code));
                break;
        }

        return array(false, $redirectCount, '');
    }

    /**
     * Perform link validation to each URLs found
     *
     * @param   string $referenceuri
     * 
     * @result  mixed  array of [protocol][url][info] = value
     */
    function VerifyURLs($referenceuri = '')
    {
        $this->lastResult = array();

        foreach ($this->urlpool as $_type => $_value) {
            foreach ($_value as $_key => $_url) {
                if (!(isset($result[$_type][$_url]))) {
                    $_result              = array();
                    $_result['type']      = $_type;
                    $_result['rawurl']    = $_url;
                    $_result['reference'] = $referenceuri;
                    
                    // Expand uri into absolute URL.
                    $_absurl           = $this->makeAbsoluteURL($_url, $referenceuri);
                    $_result['absurl'] = $_absurl;
                    // If we should disallow this URL, mark as failed.
                    if (($_result['reason'] = $this->checkIfForceErrorLink($_absurl)) !== false) {
                        $_result['valid'] = false;
                    } else {
                        // If we should ignore this URL, mark as success
                        if (($_result['reason'] = $this->checkIfIgnoreLink($_absurl)) !== false) {
                            $_result['valid'] = true;
                        } else {
                            // See whether we can connect to this URL
                            list($_result['valid'], $_result['redirects'], $_result['reason']) = $this->openURL($_absurl);
                        }
                    }
                    $this->lastResult[$_type][$_url] = $_result;
                }
            }

        }
        return $this->lastResult;
    }

    /**
     * logs the current state of link to the specified entry
     *
     * @param integer $id
     * @param string  $artlang
     * @param string  $state (optional)
     * 
     * @return boolean true if operation successful, otherwise false
     */
    public function markEntry($id = 0, $artlang = '', $state = '')
    {
        if (($id < 1) || (trim($artlang) == "")) {
            return false;
        }

        if ($state == '') {
            $state = $this->getLinkStateString();
        }

        $query = sprintf("
            UPDATE 
                %sfaqdata 
            SET 
                links_state = '%s', links_check_date = %d 
            WHERE 
                id = %d 
            AND 
                lang='%s'",
            SQLPREFIX,
            $state,
            $_SERVER['REQUEST_TIME'],
            $id,
            $artlang);
        
        if ($this->db->query($query)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieves the oldest timestamp for stored link validation result
     *
     * @return integer
     */
    public function getURLValidateInterval()
    {
        $faqconfig   = PMF_Configuration::getInstance();
        $requestTime = 0;

        if ($faqconfig->get('main.urlValidateInterval') != '') {
            $requestTime = $_SERVER['REQUEST_TIME'] - $faqconfig->get('main.urlValidateInterval');
        } else {
            $requestTime = $_SERVER['REQUEST_TIME'] - 86400; // default in recheck links once a day unless explicitly requested.
        }
        
        return $requestTime;
    }

    /**
     * retrieves the number of entries that needs the links retested
     *
     * @result  int
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-09-29
     */
    function getUntestedEntriesCount()
    {
        $interval = $this->getURLValidateInterval();

        $query = "SELECT COUNT(*) FROM ".SQLPREFIX."faqdata WHERE links_check_date < ".$interval;
        $result = $this->db->query($query);
        $untestedCount = 0;
        while ($row = $this->db->fetch_row($result)) {
            list($untestedCount) = $row;
        }
        return $untestedCount;
    }

    /**
     * retrieves stored link state and validates timestamp
     *
     * @param   int     $id
     * @param   string  $artlang
     * @param   boolean $checkDate
     * @result  mixed   false if entry does not exist. true if status expired, otherwise last link state text
     */
    function getEntryState($id = 0, $artlang = '', $checkDate = false)
    {
        $interval = $this->getURLValidateInterval();
        $query    = sprintf("
            SELECT 
                links_state, links_check_date 
            FROM 
                %sfaqdata 
            WHERE 
                id = %d 
            AND 
                lang = '%s'",
            SQLPREFIX,
            $id,
            $this->db->escape_string($artlang));
            
        if ($result = $this->db->query($query)) {
            while ($row = $this->db->fetch_object($result)) {
                $_linkState = $row->links_state;
                if (trim($_linkState) == "") {
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
     * gets the HTML text that needs to be shown in entry listing
     *
     * @param   int    $id
     * @param   string $artlang
     * @result  string
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2005-09-29
     */
    function getEntryStateHTML($id = 0, $artlang = "")
    {
        global $PMF_LANG;

        // Check if feature is disabled.
        if ($this->isReady() == false) {
            //return '<img src="images/url-disabled.png">';
            return '<div class="url-disabled"><span>'.$PMF_LANG['ad_linkcheck_feedback_url-disabled'].'</span></div>';
        }

        // check if article entry exists (we should not need this)
        $src = $this->getEntryState($id, $artlang, false);
        if ($src === false) {
            //return '<img src="images/url-disabled.png">';
            return '<div class="url-disabled"><span>'.$PMF_LANG['ad_linkcheck_feedback_url-disabled'].'</span></div>';
        }

        if ($src === true) {
            $src = "noscript";
        }

        // define name for javascripting
        $imgId  = "imgurl_".$artlang."_".$id;
        $spanId = "spanurl_".$artlang."_".$id;
        $divId  = "divurl_".$artlang."_".$id;

        $onLoad = '';
        if ($this->getEntryState($id, $artlang, true) === true) {
            if (LINKVERIFIER_AUTOMATIC_CALL_ON_EDIT_FAQ) {
                $onLoad = " onload=\"verifyEntryURL(".$id.",'".$artlang."');\"";
            }
        }

        $output = sprintf('<div id="%s" class="url-%s">'
                 .'<span id="%s">'
                 .'<a href="javascript:onDemandVerifyURL(%d,\'%s\');">'
                 .$PMF_LANG['ad_linkcheck_feedback_url-'.$src].'</a></span>'
                 .'<img src="images/null.gif" id="%s"%s alt="" />'
                 .'</div>',
                 $divId,
                 $src,
                 $spanId,
                 $id,
                 $artlang,
                 $imgId,
                 $onLoad);
        return $output;
    }

    /**
     * gets the current status string for link check result
     *
     * "nolinks" - no links were found in contents
     * "linkok"  - link(s) were found and were all ok
     * "linkbad" - link(s) were found and at least one link was broken
     *
     * @result   string
     * @author   Minoru TODA <todam@netjapan.co.jp>
     * @access   public
     * @since    2005-09-29
     */
    function getLinkStateString()
    {
        $linkcount = 0;
        $errorcount = 0;

        foreach ($this->lastResult as $_type => $_value) {
            foreach ($_value as $_url => $value) {
                $linkcount++;
                if ($value['valid'] == false) {
                    $errorcount++;
                }
            }
        }

        if ($linkcount == 0) {
            return "nolinks";
        } else {
            if ($errorcount == 0) {
                return "linkok";
            } else {
                return "linkbad";
            }
        }
    }

    /**
    * Verifies specified article content and update links_state database entry
    *
    * @param   string  $contents
    * @param   integer $id
    * @param   string  $artlang
    * @param   boolean $cron
    * 
    * @result  string  HTML text, if $cron is false (default)
    */
    public function verifyArticleURL($contents = '', $id = 0, $artlang = '', $cron = false)
    {
        global $PMF_LANG;
        
        $faqconfig = PMF_Configuration::getInstance();
        
        if ($faqconfig->get('main.referenceURL') == '') {
            $output = $PMF_LANG['ad_linkcheck_noReferenceURL'];
            return ($cron ? '' : '<br /><br />'.$output);
        }

        if (trim('' == $faqconfig->get('main.referenceURL'))) {
            $output = $PMF_LANG['ad_linkcheck_noReferenceURL'];
            return ($cron ? '' : '<br /><br />'.$output);
        }

        if ($this->isReady() === false) {
            $output = $PMF_LANG['ad_linkcheck_noAllowUrlOpen'];
            return ($cron ? '' : '<br /><br />'.$output);
        }

        // Parse contents and verify URLs
        $this->parse_string($contents);
        $result = $this->VerifyURLs($faqconfig->get('main.referenceURL'));
        $this->markEntry($id, $artlang);

        // If no URLs found
        if ($result == false) {
            $output = sprintf('<h2>%s</h2><br />%s',
                $PMF_LANG['ad_linkcheck_checkResult'],
                $PMF_LANG['ad_linkcheck_noLinksFound']);
            return ($cron ? '' : utf8_decode($output));
        }

        //uncomment to see the result structure
        //print str_replace("\n","<br />",PMF_String::htmlspecialchars(print_r($result, true)));

        $failreasons = $inforeasons = array();
        $output      = "    <h2>".$PMF_LANG['ad_linkcheck_checkResult']."</h2>\n";
        $output     .= '    <table class="verifyArticleURL">'."\n";
        foreach ($result as $type => $_value) {
            $output .= "        <tr><td><strong>".PMF_String::htmlspecialchars($type)."</strong></td></tr>\n";
            foreach ($_value as $url => $value) {
                $_output  = '            <td />';
                $_output .= '            <td><a href="'.$value['absurl'].'" target="_blank">'.PMF_String::htmlspecialchars($value['absurl'])."</a></td>\n";
                $_output .= '            <td>';
                if (isset($value['redirects']) && ($value['redirects'] > 0)) {
                    $_redirects = "(".$value['redirects'].")";
                } else {
                    $_redirects = "";
                }
                if ($value['valid'] === true) {
                    $_classname = "urlsuccess";
                    $_output .= '<td class="'.$_classname.'">'.$PMF_LANG['ad_linkcheck_checkSuccess'].$_redirects.'</td>';
                    if ($value['reason'] != "") {
                        $inforeasons[] = sprintf($PMF_LANG['ad_linkcheck_openurl_infoprefix'],PMF_String::htmlspecialchars($value['absurl'])).$value['reason'];
                    }
                } else {
                    $_classname = "urlfail";
                    $_output .= '<td class="'.$_classname.'">'.$PMF_LANG['ad_linkcheck_checkFailed'].'</td>';
                    if ($value['reason'] != "") {
                        $failreasons[] = $value['reason'];
                    }
                }
                $_output .= '</td>';
                $output .= '        <tr class="'.$_classname.'">'."\n".$_output."\n";
                $output .= "        </tr>\n";
            }
        }
        $output .= "    </table>\n";

        if (count($failreasons) > 0) {
            $output .= "    <br />\n    <strong>".$PMF_LANG['ad_linkcheck_failReason']."</strong>\n    <ul>\n";
            foreach ($failreasons as $reason) {
                $output .= "        <li>".$reason."</li>\n";
            }
            $output .= "    </ul>\n";
        }

        if (count($inforeasons) > 0) {
            $output .= "    <br />\n    <strong>".$PMF_LANG['ad_linkcheck_infoReason']."</strong>\n    <ul>\n";
            foreach ($inforeasons as $reason) {
                $output .= "        <li>".$reason."</li>\n";
            }
            $output .= "    </ul>\n";
        }

        if ($cron) {
            return '';
        } else {
            return utf8_decode($output);
        }
    }

    /**
     * Add new entry into faqlinkverifyrules table
     *
     * @param   string $type
     * @param   string $url
     * @param   string $reason
     * @return  void
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addVerifyRule($type = '', $url = '', $reason = '')
    {
        if ($type != '' && $url != '') {
            $query = sprintf(
                        "INSERT INTO
                            %sfaqlinkverifyrules
                            (id, type, url, reason, enabled, locked, owner, dtInsertDate, dtUpdateDate)
                        VALUES
                            (%d, '%s', '%s', '%s', 'y', 'n', '%s', '%s', '%s')",
                        SQLPREFIX,
                        $this->db->nextID(SQLPREFIX."faqlinkverifyrules", "id"),
                        $this->db->escape_string($type),
                        $this->db->escape_string($url),
                        $this->db->escape_string($reason),
                        $this->db->escape_string($this->user->getLogin()),
                        $this->db->escape_string(date('YmdHis')),
                        $this->db->escape_string(date('YmdHis'))
                        );
            $this->db->query($query);
        }
    }
}



/**
 * Prints javascripts needed for AJAX linkverification in record listing
 *
 * @access  public
 * @author  Minoru TODA <todam@netjapan.co.jp>
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since   2005-08-01
 */

// Client-side Javascript needed for AJAX URL verification
function link_verifier_javascript()
{
    global $PMF_LANG;

    //TODO: ASSIGN STRINGS FOR THE <IMG ALT="">
    $ajaxphp = 'index.php?';
?>
<script type="text/javascript">
<!--
function getImageElement(id, lang)
{
    return $('#imgurl_' + lang + '_' + id);
}

function getSpanElement(id, lang)
{
    return $('#spanurl_' + lang + '_' + id);
}

function getDivElement(id, lang)
{
    return $('#divurl_' + lang + '_' + id);
}

function onDemandVerifyURL(id, lang, target)
{
    var target = getSpanElement(id, lang);
    var widthPx  = 780;
    var heigthPx = 450;
    var leftPx   = (screen.width  - widthPx)/2;
    var topPx    = (screen.height - heigthPx)/2;
    Fenster = window.open('<?php print $ajaxphp; ?>&action=ajax&ajax=onDemandURL&id=' + id + '&lang=' + lang, 'onDemandURLVerification', 'toolbar=no, location=no, status=no, menubar=no, width=' + widthPx + ', height=' + heigthPx + ', left=' + leftPx + ', top=' + topPx + ', resizable=yes, scrollbars=yes');
    Fenster.focus();

    verifyEntryURL(id, lang);
}

function verifyEntryURL(id, lang)
{
    //var target = getImageElement(id, lang);
    var target = getSpanElement(id, lang);

    // !!IMPORTANT!! DISABLE ONLOAD. If you do not do this, you will get infinite loop!
    getImageElement(id, lang).onload = "";

    //target.src = "images/url-checking.png";
    getDivElement(id, lang).className = "url-checking";
    target.innerHTML = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-checking']); ?>";

    var url = 'index.php';
    var pars = 'action=ajax&ajax=verifyURL&id=' + id + '&lang=' + lang;
    var myAjax = new jQuery.ajax({url: url,
                                 type: 'get',
                                 data: pars,
                                 complete: verifyEntryURL_success,
                                 error: verifyEntryURL_failure});

    function verifyEntryURL_success(XmlRequest)
    {
        //target.src = "images/url-" + XmlRequest.responseText + ".png";
        var allResponses = new Array();
        allResponses['batch1'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-batch1']); ?>";
        allResponses['batch2'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-batch2']); ?>";
        allResponses['batch3'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-batch3']); ?>";
        allResponses['checking'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-checking']); ?>";
        allResponses['disabled'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-disabled']); ?>";
        allResponses['linkbad'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-linkbad']); ?>";
        allResponses['linkok'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-linkok']); ?>";
        allResponses['noaccess'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-noaccess']); ?>";
        allResponses['noajax'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-noajax']); ?>";
        allResponses['nolinks'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-nolinks']); ?>";
        allResponses['noscript'] = "<?php print($PMF_LANG['ad_linkcheck_feedback_url-noscript']); ?>";
        getDivElement(id, lang).className = "url-" + XmlRequest.responseText;
        if (typeof(allResponses[XmlRequest.responseText]) == "undefined") {
            getDivElement(id, lang).className = "url-noajax ";
            target.html(allResponses['noajax']);
        } else {
            target.html(allResponses[XmlRequest.responseText]);
        }
    }

    function verifyEntryURL_failure(XmlRequest)
    {
        //target.src = "images/url-noaccess.png";
        getDivElement(id, lang).className = "url-noaccess";
        target.html("<?php print($PMF_LANG['ad_linkcheck_feedback_url-noaccess']); ?>");
    }

}
//-->
</script>
<?php
}

/**
 * Prints javascripts needed for AJAX verification on record add/save/clicked on listing
 *
 * @param   integer $id
 * @param   string  $lang
 * @access  public
 * @author  Minoru TODA <todam@netjapan.co.jp>
 * @since   2005-08-01
 */
function link_ondemand_javascript($id, $lang) {
?>
<script type="text/javascript">
<!--
function ajaxOnDemandVerify(id, lang)
{
    var target = $('#onDemandVerifyResult');
    var url = 'index.php';
    var pars = 'action=ajax&ajax=onDemandURL&id=' + id + '&lang=' + lang + '&lookup=1';
    var myAjax = new jQuery.ajax({url: url,
                                  type: 'get',
                                  data: pars,
                                  complete: ajaxOnDemandVerify_success,
                                  error: ajaxOnDemandVerify_failure});
    //TODO: Assign string
    target.innerHTML = 'Querying LinkVerifier...';

    function ajaxOnDemandVerify_success(XmlRequest)
    {
        target.html(XmlRequest.responseText);
    }

    function ajaxOnDemandVerify_failure(XmlRequest)
    {
        //TODO: Assign string
        target.html('LinkVerifier failed (url probe timed out?)');
    }
}


//-->
</script>

<div id="onDemandVerifyResult">
<NOSCRIPT>LinkVerifier feature disabled (Reason: Javascript not enabled)</NOSCRIPT>
</div>
<script type="text/javascript">
<!--
    ajaxOnDemandVerify(<?php print $id; ?>, '<?php print $lang; ?>');
//-->
</script>
<?php
}
