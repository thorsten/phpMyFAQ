<?php
/**
* $Id: Linkverifier.php,v 1.2 2006-07-29 10:18:02 matteo Exp $
*
* PMF_Linkverifier
*
* The PMF_Linkverifier (AKA link_verifier) class provides methods and functions for verifying URLs
*
* @author           Minoru TODA <todam@netjapan.co.jp>
* @package          link_verifyer
* @since            2005-08-01
* @copyright        (c) 2005-2006 NetJapan, Inc.
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
*/

/* Defines number of times linkverifier follows 302 response before failing.
 *
 * Suggested value is above 10 redirects
 */

if (!defined("LINKVERIFIER_MAX_REDIRECT_COUNT")) {
    define("LINKVERIFIER_MAX_REDIRECT_COUNT", 10);
}

/* Defines the number of seconds to wait for the remote server to respond
 *
 * Suggested value is 5 seconds
 */
if (!defined("LINKVERIFIER_CONNECT_TIMEOUT")) {
    define("LINKVERIFIER_CONNECT_TIMEOUT", 5);
}

/* Defines the number of seconds to wait for the remote server to send data
 *
 * Suggested value is 10 seconds
 */
if (!defined("LINKVERIFIER_RESPONSE_TIMEOUT")) {
    define("LINKVERIFIER_RESPONSE_TIMEOUT", 10);
}

class PMF_Linkverifier
{
    /* List of protocol and urls
     *
     * @var mixed
     */
    var $urlpool = array();

    /* List of prohibited prefixes and messages
     *
     * @var mixed
     */
    var $warnlists = array();

    /* List of URLs not to probe
     *
     * @var mixed
     */
    var $ignorelists = array();

    /* List of protocols we do not want to look at
     *
     * @var mixed
     */
    var $invalid_protocols = array();

    /* Last verify results (we might use it later)
     *
     * @var mixed
     */
    var $lastResult = array();

    /* List of hosts that are slow to resolve.
     *
     * @var mixed
     */
    var $slow_hosts = array();

    /**
     * Constructor
     *
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function PMF_Linkverifier()
    {
        global $PMF_LANG;

        $this->addIgnoreProtocol("ftp:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "ftp"));
        $this->addIgnoreProtocol("gopher:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "gopher"));
        $this->addIgnoreProtocol("mailto:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "mailto"));
        $this->addIgnoreProtocol("telnet:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "telnet"));
        $this->addIgnoreProtocol("https:", sprintf($PMF_LANG['ad_linkcheck_protocol_unsupported'], "https"));

    }


    /**
     * isReady
     *
     * returns whether linkverifier is ready to verify URLs.
     *
     * @result  boolean TRUE if ready to verify URLs, otherwise FALSE
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function isReady() {
        global $PMF_CONF;

        if (!(isset($PMF_CONF["referenceURL"]))) {
            return FALSE;
        }

        if ($PMF_CONF["referenceURL"] == "") {
            return FALSE;
        }

        //return (bool) ini_get('allow_url_fopen');
        return TRUE;
    }



    /**
     * resetPool
     *
     * Resets url pool for next batch of processing.
     *
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function resetPool() {
        $this->urlpool = array();
        $this->lastResult = array();
    }

    /**
     * addWarnlist
     *
     * This function adds entry to the internal warnlists.
     * Use this if you want to mark certain URLs unsable (like internal links of a dev-site).
     *
     * @param   string $urlprefix
     * @param   string $message
     * @return  boolean TRUE, if successfully added, otherwise FALSE
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */

    function addWarnlist($urlprefix = "", $message = "")
    {
        if ($urlprefix != "") {
            $this->warnlists[$urlprefix] = $message;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * addIgnoreList
     *
     * This function adds entry to the internal ignore list.
     * Some URL/sites makes PHP report 'connection failed', even when browsers can access them.
     * URLs on ignore lists always reports as successful connect.
     *
     * @param   string $url
     * @param   string $message
     * @return  boolean TRUE, if successfully added, otherwise FALSE
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */

    function addIgnorelist($url = "", $message = "")
    {
        if ($url != "") {
            $this->ignorelists[strtolower($url)] = $message;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * addIgnoreProtocol
     *
     * @param   string $protocol
     * @param   string $message
     * @return  boolean TRUE, if successfully added, otherwise FALSE
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */

    function addIgnoreProtocol($protocol = "", $message = "")
    {
        if ($protocol != "") {
            $this->invalid_protocols[strtolower($protocol)] = $message;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * load ignore/warnlists from database
     *
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-09-29
    */
    function loadConfigurationFromDB() {
        global $db;

        $query = "SELECT type, url, reason FROM ".SQLPREFIX."faqlinkverifyrules WHERE enabled = 'y'";
        $result = $db->query($query);
        while ($row = $db->fetch_object($result)) {
            switch (strtolower($row->type)) {
            case 'ignore':      $this->addIgnoreList($row->url, $row->reason);
                                break;
            case 'warn':        $this->addWarnlist($row->url, $row->reason);
                                break;
            }
        }
    }


    /**
     * checkIfIgnoreLink
     *
     * This function verifies whether a URL is in IgnoreList.
     *
     * @param   string $url
     * @return  mixed  $result FALSE if URL should NOT be ignored. !FALSE if ignored.
     * @access  private
     * @*author Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function checkIfIgnoreLink($url = "") {
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

        return FALSE;
    }

    /**
     * checkIfForceErrorLink
     *
     * This function verifies whether a URL should be forced as error (warnlist).
     *
     * @param   string $url
     * @return  mixed  $result FALSE if URL should NOT be failed.. !FALSE if qualifies as error.
     * @access  private
     * @*author Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */
    function checkIfForceErrorLink($url = "") {
        $url = strtolower($url);
        foreach ($this->warnlists as $_protocol => $_message) {
            if (strpos($url, $_protocol) === 0) {
                return $_message;
            }
        }

        return FALSE;
    }


    /**
     * makeAbsoluteURL
     *
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
            if (strpos($relativeuri, $_protocol) === 0) {
                return $relativeuri;
            }
        }

        // If relativeuri is absolute URI, don't process it.
        foreach (array("http://", "https://") as $_protocol) {
            if (strpos($relativeuri, $_protocol) === 0) {
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
        if (substr($relativeuri,0,1) == "/") {
            return $pathparts['scheme']."://".$pathparts['host'].$pathparts['port'].$relativeuri;
        } else {
            return $pathparts['scheme']."://".$pathparts['host'].$pathparts['port'].$pathparts['path']."/".$relativeuri;
        }
    }

    /**
     * parse_string
     *
     * This function parses HTML and extracts urls
     *
     * @param   string $string
     * @result  mixed  FALSE if URL is not found, otherwise returns the number of URLs found.
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */

    function parse_string($string = "")
    {
        $urlcount = 0;
        $types = array("href", "src", "url");

        while(list(,$type) = each($types)) {
        preg_match_all("|[^?&]$type\=\"?'?`?([[:alnum:]\:%?=;&@/\.\_\-\{\}]+)\"?'?`?|i", $string, $matches);
            $sz = sizeof($matches[1]);
            for ($i=0;$i < $sz;$i++) {
                $this->urlpool[$type][] = $matches[1][$i];
                $urlcount++;
            }
        }

        return ($urlcount == 0) ? FALSE : $urlcount;
    }
    /**
     * Checks whether a URL can be opened.
     *
     * if $redirect is specified, will handle Location: redirects.
     *
     * @param   string $url
     * @param   string $redirect
     * @param   string $loopsLeft
     * @result  boolean TRUE if connect successful. otherwise FALSE
     * @access  private
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */

    function openURL($url = "", $redirect = "", $redirectCount = 0)
    {
        global $PMF_LANG;

        // If prequisites fail
        if ($this->isReady() == FALSE) {
            return array(FALSE, $redirectCount, $PMF_LANG['ad_linkcheck_openurl_notready']);
        }

        // Recursing too much ?
        if (($redirectCount >= LINKVERIFIER_MAX_REDIRECT_COUNT) || ($url == $redirect)) {
            return array(FALSE, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_maxredirect'], LINKVERIFIER_MAX_REDIRECT_COUNT));
        }

        // If destination is blank, fail.
        if (trim($url) == "") {
            return array(FALSE, $redirectCount, $PMF_LANG['ad_linkcheck_openurl_urlisblank']);
        }

        if ($redirect != "") {
            $url = $this->makeAbsoluteURL($redirect, $url);
        }

        // parse URL
        $urlParts = @parse_url($url);
        foreach(array('scheme' => 'http',
                      'host' => $_SERVER['SERVER_NAME'],
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
            switch ( $urlParts['scheme'] ) {
                case 'https': $urlParts['port'] = '443'; break;
                case 'http': $urlParts['port'] = '80'; break;
                default: $urlParts['port'] = '80'; break;
            }
        }

        if ($urlParts['query'] != "") {
            $urlParts['query'] = "?".$urlParts['query'];
        }

        if ($urlParts['fragment'] != "") {
            $urlParts['fragment'] = "#".$urlParts['fragment'];
        }

        // Check whether we tried the host before
        if (isset($this->slow_hosts[$urlParts['host']])) {
            return array(FALSE, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_tooslow'],htmlspecialchars($urlParts['host'])));
        }

        // Check whether the hostname exists
        if (gethostbynamel($urlParts['host']) === FALSE) {
            // mark this host too slow to verify
            $this->slow_hosts[$urlParts['host']] = TRUE;
            return array(FALSE, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_nodns'], htmlspecialchars($urlParts['host'])));
        }

        $_response = "";
        // open socket for remote server with timeout (default: 5secs)
        $fp = fsockopen($urlParts['host'], $urlParts['port'], $errno, $errstr, LINKVERIFIER_CONNECT_TIMEOUT);
        if (!$fp) {
            // mark this host too slow to verify
            $this->slow_hosts[$urlParts['host']] = TRUE;
            return array(FALSE, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_tooslow'],htmlspecialchars($urlParts['host'])));
        }

        // wait for data with timeout (default: 10secs)
        stream_set_timeout($fp, LINKVERIFIER_RESPONSE_TIMEOUT, 0);
        $_url = $urlParts['path'].$urlParts['query'].$urlParts['fragment'];
        fputs($fp, "HEAD ".$_url." HTTP/1.0\r\nHost: ".$urlParts['host']."\r\n");
        fputs($fp, "\r\n");
        while (!feof($fp)) { $_response .= fread($fp, 4096); }
        fclose($fp);

        // parse response
        $code = 0;
        $location = $url;
        $response = explode("\r\n", $_response);
        foreach ($response as $_response) {
            if (preg_match("/^HTTP\/[^ ]+ ([01-9]+) .*$/", $_response, $matches)) {
                $code = $matches[1];
            }
            if (preg_match("/^Location: (.*)$/", $_response, $matches)) {
                $location = $matches[1];
            }
        }

        // process response code
        switch ( $code ) {
            case '301': // Moved Permanently (go recursive ?)
            case '302': // Found (go recursive ?)
                        return $this->openURL($url, $location, $redirectCount + 1);
                                    break;

            case '200': // OK
                        $_reason = ($redirectCount > 0) ? sprintf($PMF_LANG['ad_linkcheck_openurl_redirected'],htmlspecialchars($url)) : "";
                        return array(TRUE, $redirectCount, $_reason);
                        break;
            case '300': // Multiple choices
            case '401': // Unauthorized (but it's there. right ?)
                        return array(TRUE, $redirectCount, sprintf($PMF_LANG['ad_linkcheck_openurl_ambiguous'], $code));
                        break;
        }


        return array(FALSE, $redirectCount, '');
    }

    /**
     * Perform link validation to each URLs found
     *
     *
     * @param   string $referenceuri
     * @result  mixed  array of [protocol][url][info] = value
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-08-01
     */

    function VerifyURLs($referenceuri = "") {
        $this->lastResult = array();
        foreach ($this->urlpool as $_type => $_value) {
            foreach ($_value as $_key => $_url) {
                if (!(isset($result[$_type][$_url]))) {
                    $_result = array();
                    $_result['type'] = $_type;
                    $_result['rawurl'] = $_url;
                    $_result['reference'] = $referenceuri;
                    // Expand uri into absolute URL.
                    $_absurl = $this->makeAbsoluteURL($_url, $referenceuri);
                    $_result['absurl'] = $_absurl;
                    // If we should disallow this URL, mark as failed.
                    if (($_result['reason'] = $this->checkIfForceErrorLink($_absurl)) !== FALSE) {
                        $_result['valid'] = FALSE;
                    } else {
                        // If we should ignore this URL, mark as success
                        if (($_result['reason'] = $this->checkIfIgnoreLink($_absurl)) !== FALSE) {
                            $_result['valid'] = TRUE;
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
     * @param   int    $id
     * @param   string $artlang
     * @param   string $state (optional)
     * @result  boolean TRUE if operation successful, otherwise FALSE
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-09-29
     */

    function markEntry($id = 0, $artlang = "", $state = "") {
        global $db;

        if (($id < 1) || (trim($artlang) == "")) {
            return FALSE;
        }

        if ($state == "") {
            $state = $this->getLinkStateString();
        }

        $query = "UPDATE ".SQLPREFIX."faqdata SET linkState = '".$state."', linkCheckDate = ".time()." WHERE id = ".$id." AND lang='".$artlang."'";
        if ($db->query($query)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * retrieves the oldest timestamp for stored link validation result
     *
     * @result   int
     * @access   public
     * @author   Minoru TODA <todam@netjapan.co.jp>
     * @since    2005-09-29
     */

    function getURLValidateInterval() {
        global $PMF_CONF;

        if (isset($PMF_CONF['URLValidateInterval'])) {
            return time() - $PMF_CONF['URLValidateInterval'];
        } else {
            return time() - 86400; // default in recheck links once a day unless explicitly requested.
        }
    }

    /**
     * retrieves the number of entries that needs the links retested
     *
     * @result  int
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-09-29
     */


    function getUntestedEntriesCount() {
        global $db;

        $interval = $this->getURLValidateInterval();

        $query = "SELECT COUNT(*) FROM ".SQLPREFIX."faqdata WHERE linkCheckDate < ".$interval;
        $result = $db->query($query);
        $untestedCount = 0;
        while ($row = $db->fetch_row($result)) {
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
     * @result  mixed   FALSE if entry does not exist. TRUE if status expired, otherwise last link state text
     * @access  public
     * @author  Minoru TODA <todam@netjapan.co.jp>
     * @since   2005-09-29
     */

    function getEntryState($id = 0, $artlang = "", $checkDate = FALSE) {
        global $db, $PMF_CONF;

        $interval = $this->getURLValidateInterval();
        $query = "SELECT linkState, linkCheckDate FROM ".SQLPREFIX."faqdata WHERE id = ".$id." AND lang='".$artlang."'";
        if ($result = $db->query($query)) {
            while ($row = $db->fetch_object($result)) {
                $_linkState = $row->linkState;
                if (trim($_linkState) == "") { $_linkState = TRUE; }

                if ($row->linkCheckDate > $interval) {
                    return $_linkState;
                } else {
                    if ($checkDate == FALSE) {
                        return $_linkState;
                    } else {
                        return TRUE;
                    }
                }
            }
        } else {
            return FALSE;
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
     * @since   2005-09-29
     */

    function getEntryStateHTML($id = 0, $artlang = "") {
        // Check if feature is disabled.
        if ($this->isReady() == FALSE) {
            return '<img src="images/url-disabled.png">';
        }

        // check if article entry exists (we should not neeed this)
        $src = $this->getEntryState($id, $artlang, FALSE);
        if ($src === FALSE) {
            return '<img src="images/url-disabled.png">';
        }

        if ($src === TRUE) {
            $src = "noscript";
        }

        // define name for javascripting
        $name = "imgurl_".$artlang."_".$id;

        if ($this->getEntryState($id, $artlang, TRUE) === TRUE) {
            $onLoad = " onLoad = \"verifyEntryURL(".$id.",'".$artlang."'); \" ";
        } else { $onLoad = ""; }

        $output = '<img src="images/url-'.$src.'.png" id="'.$name.'" '.$onLoad.' >';
        $output = "<a href=\"javascript:onDemandVerifyURL(".$id.",'".$artlang."');\">".$output."</a>";
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

    function getLinkStateString() {
        $linkcount = 0;
        $errorcount = 0;

        foreach ($this->lastResult as $_type => $_value) {
            foreach ($_value as $_url => $value) {
                $linkcount++;
                if ($value['valid'] == FALSE) {
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

}


/**
 * Verifies specified article content and update linkstate database entry
 *
     * @param   string  $contents
 * @param   integer $id
 * @param   string  $artlang
 * @result  string  HTML text
 * @access  public
 * @author  Minoru TODA <todam@netjapan.co.jp>
 * @since   2005-08-01
 */
function verifyArticleURL($contents = "", $id = 0, $artlang = "") {
    global $PMF_CONF, $PMF_LANG;

    if (!(isset($PMF_CONF["referenceURL"]))) {
        return "<br /><br />".$PMF_LANG['ad_linkcheck_noReferenceURL'];
    }

    if (trim($PMF_CONF["referenceURL"] == "")) {
        return "<br /><br />".$PMF_LANG['ad_linkcheck_noReferenceURL'];
    }

    $linkverifier = new PMF_Linkverifier;
    if ($linkverifier->isReady() === FALSE) {
        return "<br /><br />".$PMF_LANG['ad_linkcheck_noAllowUrlOpen'];
    }

    // load list of URLs to ignore / fail
    $linkverifier->loadConfigurationFromDB();

    // Parse contents and verify URLs
    $linkverifier->parse_string($contents);
    $result = $linkverifier->VerifyURLs($PMF_CONF["referenceURL"]);
    $linkverifier->markEntry($id, $artlang);

    // If no URLs found
    if ($result == FALSE) {
        $output  = '<h2>'.$PMF_LANG['ad_linkcheck_checkResult'].'</h2>';
        $output .= '<br />'.$PMF_LANG['ad_linkcheck_noLinksFound'];
        return $output;
    }


    //uncomment to see the result structure
    //print str_replace("\n","<br />",htmlspecialchars(print_r($result, TRUE)));

    $failreasons = array();
    $inforeasons = array();
    $output = "    <h2>".$PMF_LANG['ad_linkcheck_checkResult']."</h2>\n";
    $output .= '    <table class="verifyArticleURL">'."\n";
    foreach ($result as $type => $_value) {
        $output .= "        <tr><td><strong>".htmlspecialchars($type)."</strong></td></tr>\n";
        foreach ($_value as $url => $value) {

            $_output  = '            <td /><td>'.htmlspecialchars($value['rawurl'])."</td>\n";
            $_output .= '            <td><a href="'.$value['absurl'].'" target="_blank">'.htmlspecialchars($value['absurl'])."</a></td>\n";
            $_output .= '            <td>';
            if (isset($value['redirects']) && ($value['redirects'] > 0)) {
                $_redirects = "(".$value['redirects'].")";
            } else {
                $_redirects = "";
            }
            if ($value['valid'] === TRUE) {
                $_classname = "urlsuccess";
                $_output .= '<td class="'.$_classname.'">'.$PMF_LANG['ad_linkcheck_checkSuccess'].$_redirects.'</td>';
                if ($value['reason'] != "") {
                    $inforeasons[] = sprintf($PMF_LANG['ad_linkcheck_openurl_infoprefix'],htmlspecialchars($value['absurl'])).$value['reason'];
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


    return $output;

}

/**
 * Prints javascripts needed for AJAX linkverification in record listing
 *
 * @access  public
 * @author  Minoru TODA <todam@netjapan.co.jp>
 * @since   2005-08-01
 */

// Client-side Javascript needed for AJAX URL verification
function link_verifier_javascript() {
    global $linkext;

    //TODO: ASSIGN STRINGS FOR THE <IMG ALT="">
    $ajaxphp = $_SERVER["PHP_SELF"].'?';
?>
<script type="text/javascript">
<!--
function getImageElement(id, lang) {
    return $('imgurl_' + lang + '_' + id);
}

function onDemandVerifyURL(id, lang, target) {
    var target = getImageElement(id, lang);
    var widthPx  = 780;
    var heigthPx = 450;
    var leftPx   = (screen.width  - widthPx)/2;
    var topPx    = (screen.height - heigthPx)/2;
    Fenster = window.open('<?php print $ajaxphp; ?>&aktion=ajax&ajax=onDemandURL&id=' + id + '&lang=' + lang, 'onDemandURLVerification', 'toolbar=no, location=no, status=no, menubar=no, width=' + widthPx + ', height=' + heigthPx + ', left=' + leftPx + ', top=' + topPx + ', resizable=yes, scrollbars=yes');
    Fenster.focus();

    verifyEntryURL(id, lang);
}


function verifyEntryURL(id, lang) {
    var target = getImageElement(id, lang);

    // !!IMPORTANT!! DISABLE ONLOAD. If you do not do this, you will get infinite loop!
    target.onload="";

    target.src = "images/url-checking.png";

    var url = 'index.php';
    var pars = 'aktion=ajax&ajax=verifyURL&id=' + id + '&lang=' + lang;
    var myAjax = new Ajax.Request( url, {method: 'get', parameters: pars, onComplete: verifyEntryURL_success, onFailure: verifyEntryURL_failure} );

    function verifyEntryURL_success(XmlRequest) {
        target.src = "images/url-" + XmlRequest.responseText + ".png";
    }

    function verifyEntryURL_failure(XmlRequest) {
        target.src = "images/url-noaccess.png";
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

function ajaxOnDemandVerify(id, lang) {
    var target = $('onDemandVerifyResult');
    var url = 'index.php';
    var pars = 'aktion=ajax&ajax=onDemandURL&id=' + id + '&lang=' + lang + '&lookup=1';
    var myAjax = new Ajax.Request( url, {method: 'get', parameters: pars, onComplete: ajaxOnDemandVerify_success, onFailure: ajaxOnDemandVerify_failure} );
    //TODO: Assign string
    target.innerHTML = 'Querying LinkVerifier...';

    function ajaxOnDemandVerify_success(XmlRequest) {
        target.innerHTML = XmlRequest.responseText;
    }

    function ajaxOnDemandVerify_failure(XmlRequest) {
        //TODO: Assign string
        target.innerHTML = 'LinkVerifier failed (url probe timed out?)';
    }
}


//-->
</script>

<div id="onDemandVerifyResult">
<NOSCRIPT>
LinkVerifier feature disabled (Reason: Javascript not enabled)
</NOSCRIPT>
</div>
<script type="text/javascript">
<!--
    ajaxOnDemandVerify(<?php print $id; ?>, '<?php print $lang; ?>');
//-->
</script>
<?php
}
?>
