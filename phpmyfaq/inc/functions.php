<?php
/**
 * $Id: functions.php,v 1.175 2007-03-29 12:18:19 thorstenr Exp $
 *
 * This is the main functions file!
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matthias Sommerfeld <phlymail@phlylabs.de>
 * @author      Bastian Poettner <bastian@poettner.net>
 * @author      Meikel Katzengreis <meikel@katzengreis.com>
 * @author      Robin Wood <robin@digininja.org>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since       2001-02-18
 * @copyright   (c) 2001-2007 phpMyFAQ Team
 *
 * Portions created by Matthias Sommerfeld are Copyright (c) 2001-2004 blue
 * birdy, Berlin (http://bluebirdy.de). All Rights Reserved.
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

//
// DEBUGGING FUNCTIONS
//

/**
* Function to get a pretty formatted output of a variable
*
* NOTE: Just for debugging!
*
* @param    object
* @return   void
* @access   public
* @since    2004-11-27
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function pmf_dump($var)
{
    print '<pre>';
    print_r($var);
    print '</pre>';
}

/**
 * debug_backtrace() wrapper function
 *
 * @param    $string
 * @return   string
 * @access   public
 * @since    2006-06-24
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function pmf_debug($string)
{
    $debug = debug_backtrace();

    $ret = '';
    if (isset($debug[2]['class'])) {
        $ret  = $debug[2]['class'].$debug[1]['type'];
        $ret .= $debug[2]['function'] . '() in line ' . $debug[2]['line'];
        $ret .= ": <pre>" . $string . "</pre><br />\n";
    }

    return $ret;
}

/**
 * microtime wrapper function (PHP 5 like)
 *
 * @return  float
 * @access  public
 * @since   2006-09-17
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function pmf_microtime_float()
{
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}

//
// UTILITIES
//

/**
 * getAvailableLanguages()
 *
 * This function returns the available languages
 *
 * @param   mixed
 * @return  boolean
 * @access  public
 * @since   2006-07-02
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function isPMFSessionID($text)
{
    if (null == $text) {
        return false;
    }
    if (preg_match('/^[0-9a-z]{32}$/i', $text)) {
        return true;
    }

    return false;
}

//
// GENERAL FUNCTIONS
//

/**
 * getAvailableLanguages()
 *
 * This function returns the available languages
 *
 * @return  array
 * @access  public
 * @since   2006-03-21
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function getAvailableLanguages()
{
    global $languageCodes;

    $search = array("language_", ".php");
    $languages = array();

    if ($dir = @opendir(dirname(dirname(__FILE__)).'/lang/')) {
        while (false !== ($file = @readdir($dir))) {
            if (($file != ".") && ($file != "..") && (!is_dir($file))) {
                $languageFiles[] = strtoupper(str_replace($search, "", trim($file)));
            }
        }
        closedir($dir);
        foreach ($languageFiles as $lang) {
            // Check if the file is related to a (real) language before using it
            if (array_key_exists($lang, $languageCodes)) {
                $languages[strtolower($lang)] = $languageCodes[$lang];
            }
        }
        // Sort the languages list
        asort($languages);
        reset($languages);
    }

    return $languages;
}

/**
 * selectLanguages()
 *
 * This function displays the <select> box for the available languages
 * optionally filtered by excluding some provided languages
 *
 * @param   string
 * @param   boolean
 * @param   array
 * @return  string
 * @access  public
 * @since   2003-12-12
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function selectLanguages($default, $submitOnChange = false, $excludedLanguages = array())
{
    global $languageCodes;

    $onChange = ($submitOnChange ? ' onchange="this.form.submit();"' : '');
    $output = "<select class=\"language\" name=\"language\" id=\"language\" size=\"1\"".$onChange.">\n";
    $languages = getAvailableLanguages();

    if (count($languages) > 0) {
        foreach ($languages as $lang => $value) {
            if (!in_array($lang, $excludedLanguages)) {
                $output .= "\t<option value=\"".$lang."\"";
                if ($lang == $default) {
                    $output .= " selected=\"selected\"";
                }
                $output .=  ">".$value."</option>\n";
            }
        }
    } else {
        $output .= "\t<option value=\"en\">".$languageCodes["EN"]."</option>";
    }
    $output .= "</select>\n";

    return $output;
}

/**
 * languageOptions()
 *
 * Function for displaying all languages in <option>
 *
 * @param   string  the languange to be selected
 * @param   bool    print only the passed language?
 * @param   bool    print the <language file> instead of the <language code> as value?
 * @return  string
 * @access  public
 * @since   2004-06-01
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function languageOptions($lang = "", $onlyThisLang = false, $fileLanguageValue = false)
{
    $output = "";

    foreach (getAvailableLanguages() as $key => $value) {
        if ($onlyThisLang) {
            if (strtolower($key) == $lang) {
                if ($fileLanguageValue) {
                    $output .= "\t<option value=\"language_".strtolower($lang).".php\"";
                } else {
                    $output .= "\t<option value=\"".strtolower($lang)."\"";
                }
                $output .= " selected=\"selected\"";
                $output .= ">".$value."</option>\n";
                break;
            }
        }
        else {
            if ($fileLanguageValue) {
                $output .= "\t<option value=\"language_".strtolower($key).".php\"";
            } else {
                $output .= "\t<option value=\"".strtolower($key)."\"";
            }
            if (strtolower($key) == $lang) {
                $output .= " selected=\"selected\"";
            }
            $output .= ">".$value."</option>\n";
        }
    }

    return $output;
}

/**
 * Returns all sorting possibilities for FAQ records
 *
 * @param   string  $current
 * @return  string
 * @access  public
 * @since   2007-03-10
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function sortingOptions($current)
{
    global $PMF_LANG;

    $options = array('id', 'thema', 'visits', 'datum', 'author');
    $output = '';

    foreach ($options as $value) {
        printf('<option value="%s"%s>%s</option>',
            $value,
            ($value == $current) ? ' selected="selected"' : '',
            $PMF_LANG['ad_conf_order_'.$value]);
    }

    return $output;
}

/**
* Converts the phpMyFAQ date format to a format similar to ISO 8601 standard
*
* @param    string
* @return   date
* @since    2001-04-30
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function makeDate($date)
{
    $offset = (60 * 60) * (PMF_DATETIME_TIMEZONE / 100);
    $current = strtotime(substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2)." ".substr($date,8,2).":".substr($date,10,2));
    $timestamp = $current + $offset;
    return date("Y-m-d H:i", $timestamp);
}

/**
* Converts the phpMyFAQ/Unix date format to the format given by the PHP date format
*
* @param    object  date
* @param    string  the PHP format of date
* @param    boolean true if the passed date is in phpMyFAQ format, false if in Unix timestamp format
* @return   string  date in the requested format
* @since    2006-06-26
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function makeDateByFormat($date, $type, $phpmyfaq = true)
{
    $offset = (60 * 60) * (PMF_DATETIME_TIMEZONE / 100);
    if ($phpmyfaq) {
        $current = strtotime(substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2)." ".substr($date,8,2).":".substr($date,10,2).":".substr($date,12,2));
    } else {
        $current = $date;
    }
    $timestamp = $current + $offset;

    return gmdate($type, $timestamp);
}

/**
* Converts the Unix date format to the format needed to view a comment entry
*
* @param    object  date
* @return   string  formatted date for the comment
* @since    2006-08-21
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function makeCommentDate($date)
{
    // TODO: Time is needed for studying the impact of timezone settings in the system.
    //       No issue is arised if users of a PMF service has the timezone equals to that of the server.
    //       We have the solution, makeDateByFormat(), and we'll wait also for
    //       feedbacks from the Community before applying it in all date outputs.
    // $TZ = ' '.(PMF_DATETIME_TIMEZONE == '0' ? 'GMT': PMF_DATETIME_TIMEZONE);
    // return makeDateByFormat($date, 'Y-m-d H:i', false).$TZ;

    return date('Y-m-d H:i', $date);
}

/**
* Converts the phpMyFAQ/Unix date format to the RFC 822 format
*
* @param    string  date
* @param    boolean true if the passed date is in phpMyFAQ format, false if in Unix timestamp format
* @return   string  RFC 822 date
* @since    2005-10-03
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function makeRFC822Date($date, $phpmyfaq = true)
{
    $rfc822TZ = ' '.(PMF_DATETIME_TIMEZONE == '0' ? 'GMT': PMF_DATETIME_TIMEZONE);

    return makeDateByFormat($date, 'D, d M Y H:i:s', $phpmyfaq).$rfc822TZ;
}

/**
* Converts the phpMyFAQ/Unix date format to the ISO 8601 format
*
* See the spec here: http://www.w3.org/TR/NOTE-datetime
*
* @param    string  date
* @param    boolean true if the passed date is in phpMyFAQ format, false if in Unix timestamp format
* @return   string  ISO 8601 date
* @since    2005-10-03
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function makeISO8601Date($date, $phpmyfaq = true)
{
    $iso8601TZD = (PMF_DATETIME_TIMEZONE == '0' ? 'Z': substr(PMF_DATETIME_TIMEZONE, 0, 3).':'.substr(PMF_DATETIME_TIMEZONE, 3));

    return makeDateByFormat($date, 'Y-m-d\TH:i:s', $phpmyfaq).$iso8601TZD;
}

/**
* Returns an array of country codes for a specific FAQ record ID, specific category ID
* or all languages used by FAQ records , categories
*
* @param    integer
* @param    string  specifies table
* @return   array
* @since    2003-03-17
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function check4Language($id, $table='faqdata')
{
    global $db;
    $output = array();

    if (isset($id)) {
       if ($id == 0) {
          // get languages for all ids
          $distinct = ' DISTINCT ';
          $where = '';
       }
       else {
          // get languages for specified id
          $distinct = '';
          $where = " WHERE id = ".$id;
       }

       $query = sprintf("
           SELECT %s
              lang
           FROM
               %s%s
               %s",
           $distinct,
           SQLPREFIX,
           $table,
           $where);
       $result = $db->query($query);

       if ($db->num_rows($result) > 0) {
           while ($row = $db->fetch_object($result)) {
               $output[] = $row->lang;
           }
       }
    }

    return $output;
}

/**
 * safeEmail()
 *
 * If the email spam protection has been activated from the general PMF configuration
 * this function converts an email address e.g. from "user@example.org" to "user_AT_example_DOT_org"
 * Otherwise it will return the plain email address.
 *
 * @param    string
 * @return   string
 * @since    2003-04-17
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function safeEmail($email)
{
    global $PMF_CONF;

    if (isset($PMF_CONF['spamEnableSafeEmail']) && $PMF_CONF['spamEnableSafeEmail']) {
        return str_replace(array('@', '.'), array('_AT_', '_DOT_'), $email);
    } else {
        return $email;
    }
}

/**
 * check4AddrMatch()
 *
 * Checks for an address match (IP or Network)
 *
 * @param    string  IP Address
 * @param    string  Network Address (e.g.: a.b.c.d/255.255.255.0 or a.b.c.d/24) or IP Address
 * @return   boolean
 * @since    2006-01-23
 * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author   Kenneth Shaw <ken@expitrans.com>
 */
function check4AddrMatch($ip, $network)
{
    // See also ip2long PHP online manual: Kenneth Shaw
    // coded a network matching function called net_match.
    // We use here his way of doing bit-by-bit network comparison
    $matched = false;

    // Start applying the discovering of the network mask
    $ip_arr = explode('/', $network);

    $network_long = ip2long($ip_arr[0]);
    $ip_long      = ip2long($ip);

    if (!isset($ip_arr[1])) {
        // $network seems to be a simple ip address, instead of a network address
        $matched = ($network_long == $ip_long);
    } else {
        // $network seems to be a real network address
        $x = ip2long($ip_arr[1]);
        // Evaluate the netmask: <Network Mask> or <CIDR>
        $mask = ( long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]));
        $matched = ( ($ip_long & $mask) == ($network_long & $mask) );
    }

    return $matched;
}

/**
 * Performs a check if an IP is banned
 *
 * @param   string  IP
 * @return  boolean
 * @since   2003-06-06
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function IPCheck($ip)
{
    global $faqconfig;

    $bannedIPs = explode(' ', $faqconfig->get('main.bannedIPs'));
    foreach ($bannedIPs as $oneIPorNetwork) {
        if (check4AddrMatch($ip, $oneIPorNetwork)) {
            return false;
        }
    }
    return true;
}

/**
 * getBannedWords()
 *
 * This function returns the banned words dictionary as an array.
 *
 * @return  array
 * @access  public
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function getBannedWords()
{
    $bannedTrimmedWords = array();
    $bannedWordsFile = dirname(__FILE__).'/blockedwords.txt';
    $bannedWords     = array();

    // Read the dictionary
    if (file_exists($bannedWordsFile) && is_readable($bannedWordsFile)) {
        $bannedWords = @file($bannedWordsFile);
    }
    // Trim it
    foreach ($bannedWords as $word) {
        $bannedTrimmedWords[] = trim($word);
    }

    return $bannedTrimmedWords;
}

/**
 * checkBannedWord()
 *
 * This function checks the content against a dab word list
 * if the banned word spam protection has been activated from the general PMF configuration.
 *
 * @param   string  $content
 * @return  bool
 * @access  public
 * @author  Katherine A. Bouton
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author  Peter Beauvain <pbeauvain@web.de>
 */
function checkBannedWord($content)
{
    global $PMF_CONF;

    // Sanity checks
    $content = trim($content);
    if (    ('' == $content)
         || (!isset($PMF_CONF['spamCheckBannedWords']))
         || (isset($PMF_CONF['spamCheckBannedWords']) && (!$PMF_CONF['spamCheckBannedWords']))
         ) {
        return true;
    }

    $bannedWords = getBannedWords();
    // We just search a match of, at least, one banned word into $content
    $content = strtolower($content);
    if (is_array($bannedWords)) {
        foreach ($bannedWords as $bannedWord) {
            if (strpos($content, strtolower($bannedWord)) !== false) {
                return false;
            }
        }
    }

    return true;
}

/**
 * printCaptchaFieldset
 *
 * Get out the HTML code for the fieldset that insert the captcha code in a (public) form
 *
 * @param    string  Text of the HTML Legend element
 * @param    string  HTML code for the Captcha image
 * @param    string  Length of the Captcha code
 * @return   string
 * @since    2006-04-25
 * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function printCaptchaFieldset($legend, $img, $length)
{
    global $PMF_CONF;

    $html = '';

    if (isset($PMF_CONF['spamEnableCatpchaCode']) && $PMF_CONF['spamEnableCatpchaCode']) {
        $html .= '<fieldset><legend>'.$legend.'</legend>';
        $html .= '<div style="text-align:center;">'.$img;
        $html .= '<input class="inputfield" style="vertical-align: top;" type="text" name="captcha" id="captcha" value="" size="'.$length.'" />';
        $html .= '</div></fieldset>';
    }

    return $html;
}

/**
 * checkCaptchaCode()
 *
 * This function checks the provided captcha code
 * if the captcha code spam protection has been activated from the general PMF configuration.
 *
 * @return  bool
 * @access  public
 * @since   2006-04-25
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function checkCaptchaCode()
{
    global $PMF_CONF, $captcha;

    if (isset($PMF_CONF['spamEnableCatpchaCode']) && $PMF_CONF['spamEnableCatpchaCode']) {
        return (isset($_POST['captcha']) && ($captcha->validateCaptchaCode($_POST['captcha'])));
    } else {
        return true;
    }
}

/**
 * getHighlightedBannedWords()
 *
 * This function returns the passed content with HTML hilighted banned words.
 *
 * @param   string  $content
 * @return  string
 * @access  public
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function getHighlightedBannedWords($content)
{
    $bannedHTMLHiliWords = array();
    $bannedWords = getBannedWords();

    // Build the RegExp array
    foreach ($bannedWords as $word) {
        $bannedHTMLHiliWords[] = "/(".quotemeta($word).")/ism";
    }
    // Use the CSS "highlight" class to highlight the banned words
    if (count($bannedHTMLHiliWords)>0) {
        return preg_replace($bannedHTMLHiliWords, "<span class=\"highlight\">\\1</span>", $content);
    }
    else {
        return $content;
    }
}

/**
 * Adds PHP syntax highlighting to your pre tags
 *
 * @param   string  $content
 * @return  string
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since   2004-12-25
 */
function hilight($content)
{
    $string = $content[2];

    $string = str_replace("&lt;?php", " ", $string);
    $string = str_replace("?&gt;", " ", $string);

    if (!ereg('^<\\?', $string) || !ereg('^&lt;\\?', $string)) {
        $string = "<?php\n".$string."\n?>";
    }

    $string = implode("\n", explode("<br />", $string));
    $string = highlight_string($string, true);
    $string = eregi_replace('^.*<pre>',  '', $string);
    $string = eregi_replace('</pre>.*$', '', $string);
    $string = str_replace("\n", "", $string);
    $string = str_replace("&nbsp;", " ", $string);

    // Making the PHP generated stuff XHTML compatible
    $string = preg_replace('/<FONT COLOR="/i', '<span style="color:', $string);
    $string = preg_replace('/<\/FONT>/i', '</span>', $string);

    return $string;
}

//
// USERTRACKING FUNCTIONS
//

/**
 * Trackt den User und zeichnet die Bewegungen auf
 *
 * @param    string
 * @param    integer
 * @return   void
 * @since    2001-02-18
 * @since    Bastian Poettner <bastian@poettner.net>
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function Tracking($action, $id = 0)
{
    global $db, $PMF_CONF, $sid, $user;

    if (isset($PMF_CONF["tracking"])) {
        if (isset($_GET["sid"])) {
            $sid = $_GET["sid"];
        }
        if (isset($_COOKIE['pmf_sid'])) {
            $sid = $_COOKIE['pmf_sid'];
        }
        if ($action == "old_session") {
            $sid = null;
        }
        if (!isset($sid)) {
            $sid = $db->nextID(SQLPREFIX."faqsessions", "sid");
            // HACK: be sure that pmf_sid cookie contains the current $sid
            if (isset($_COOKIE["pmf_sid"]) && ((int)$_COOKIE['pmf_sid'] != $sid)) {
                setcookie('pmf_sid', $sid, time() + 3600);
            }
            $db->query("
                    INSERT INTO
                        ".SQLPREFIX."faqsessions
                        (sid, user_id, ip, time)
                    VALUES
                        (".$sid.", ".($user ? $user->getUserId() : '-1').", '".$_SERVER["REMOTE_ADDR"]."', ".time().")"
                    );
        }
        $fp = @fopen("./data/tracking".date("dmY"), "a+b");
        if (function_exists("stream_encoding")) {
            stream_encoding($fp, "iso-8859-1");
        }
        if ($fp) {
            $flanz = "0";
            while (!flock($fp, LOCK_EX) && $flanz < 6) {
                wait(500);
                $flanz++;
            }
            if ($flanz >= 6) {
                fclose($fp);
            } elseif ((!empty($_SERVER["HTTP_REFERER"])) || ($action == "new_session")) {
                if (!isset($_SERVER["HTTP_REFERER"])) {
                    $_SERVER["HTTP_REFERER"] = "";
                }
                if (!isset($_SERVER["QUERY_STRING"])) {
                    $_SERVER["QUERY_STRING"] = "";
                }
                fputs($fp, $sid.";".str_replace(";", ",",$action).";".$id.";".$_SERVER["REMOTE_ADDR"].";".str_replace(";", ",", $_SERVER["QUERY_STRING"]).";".str_replace(";", ",", $_SERVER["HTTP_REFERER"]).";".str_replace(";", ",", urldecode($_SERVER["HTTP_USER_AGENT"])).";".time().";\n");
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }
}

/**
 * An OS independent function like usleep
 *
 * @param    integer
 * @return   void
 * @since    2004-05-30
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function wait($usecs)
{
    $temp = gettimeofday();
    $start = (int)$temp["usec"];
    while(1) {
        $temp = gettimeofday();
        $stop = (int)$temp["usec"];
        if ($stop - $start >= $usecs) {
            break;
        }
    }
}

/*
 * Testet ob die SID zu der IP passt |  @@ Bastian, 2001-04-07
 * Last Update: @@ Thorsten, 2003-03-06
 * Last Update: @@ Matteo, 2006-03-13
 */
function CheckSID($sid, $ip)
{
    global $db, $user;

    if ($db->num_rows($db->query("
                            SELECT
                                sid
                            FROM
                                ".SQLPREFIX."faqsessions
                            WHERE
                                    sid = ".$sid."
                                AND ip = '".$ip."'
                                AND time > ".(time()-86400))) < 1
                            ) {
        // No sid found (maybe someone is refering to an old one): create a new one
        Tracking("old_session", $sid);
    }
    else {
        // Update the current sid in the db according also to the current user authentication status
        $db->query("
                UPDATE
                    ".SQLPREFIX."faqsessions
                SET
                    time = ".time().",
                    user_id = ".($user ? $user->getUserId() : '-1')."
                WHERE
                        sid = ".$sid."
                    AND ip = '".$ip."'"
                );
    }
}

/*
 * Returns the number of anonymous users and registered ones.
 * These are the numbers of unique users who have perfomed
 * some activities within the last five minutes
 *
 * @param    integer    Optionally set the time window size in sec. Default: 300sec, 5 minutes
 * @return   array
 * @access   public
 * @since    2004-07-17
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function getUsersOnline($activityTimeWindow = 300)
{
    global $db, $PMF_CONF;
    $users = array(0 ,0);

    if (isset($PMF_CONF['tracking']) && $PMF_CONF['tracking']) {
        $timeNow = (time() - $activityTimeWindow);
        // Count all sids within the time window
        // TODO: add a new field in faqsessions in order to find out only sids of anonymous users
        $result = $db->query("
                    SELECT
                        count(sid) AS anonymous_users
                    FROM
                        ".SQLPREFIX."faqsessions
                    WHERE
                            user_id = -1
                        AND time > ".$timeNow);
        if (isset($result)) {
            $row = $db->fetch_object($result);
            $users[0] = $row->anonymous_users;
        }
        // Count all faquser records within the time window
        $result = $db->query("
                    SELECT
                        count(session_id) AS registered_users
                    FROM
                        ".SQLPREFIX."faquser
                    WHERE
                        session_timestamp > ".$timeNow);
        if (isset($result)) {
            $row = $db->fetch_object($result);
            $users[1] = $row->registered_users;
        }
    }

    return $users;
}



/******************************************************************************
 * Funktionen fuer die Startseite
 ******************************************************************************/

/*
 * Kuerzt einen String auf eine bestimmte Anzahl von Woertern | @@ Thorsten - 2002-08-26
 * Last Update: @@ Thorsten, 2004-06-09
 */
function makeShorterText($str, $char)
{
    $str = ereg_replace("[[:space:]]+", " ", $str);
    $arrStr = explode(" ", $str);
    $shortStr = "";
    $num = count($arrStr);
    if ($num > $char) {
        for ($j = 0; $j <= $char; $j++) {
            $shortStr .= $arrStr[$j]." ";
            }
        $shortStr .= "...";
        }
    else {
        $shortStr = $str;
        }
    return $shortStr;
}



/******************************************************************************
 * Funktionen fuer Artikelseiten
 ******************************************************************************/

/*
 * Macht an den String nen / dran, falls keiner da ist
 * @@ Bastian, 2002-01-06
 */
function EndSlash($string)
{
    if (substr($string, strlen($string)-1, 1) != "/" ) {
        $string .= "/";
    }
    return $string;
}

/**
 * safeHTML()
 *
 * Function to remove all HTML tags but not <strong>, <em>, <u>, und <a>
 *
 * @param   string  $html
 * @return  string  $html
 * @access  public
 * @since   2003-02-23
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function safeHTML($html)
{
    $html = strip_tags($html, "<strong><em><i><u><a><br>");
    $html = str_replace('<a ', '<a rel="nofollow" ', $html);
    return $html;
}

/*
 * Funktion wandelt HTML Entities zurueck | @@ Thorsten, 2003-03-27
 * Last Update: @@ Thorsten, 2003-03-27
 */
function unhtmlentities($html)
{
    $translation_table = get_html_translation_table (HTML_ENTITIES);
    $translation_table = array_flip ($translation_table);
    $content = strtr ($html, $translation_table);
    return preg_replace('/\&\#([0-9]+)\;/me', "chr('\\1')", $content);
}

/*
 * Syntax check for e-mails | @@ Thorsten, 2004-01-19
 * Last Update: @@ Thorsten, 2004-06-27
 */
function checkEmail($sender)
{
    global $IDN;
    $sender = $IDN->decode($sender);
    $pattern =  "#^[-!\#$%&\"*+\\./\d=?A-Z^_|'a-z{|}~]+".
                "@".
                "[-!\#$%&\"*+\\./\d=?A-Z^_|'a-z{|}~]+\.".
                "[-!\#$%&\"*+\\./\d=?A-Z^_|'a-z{|}~]+$#";
    if (preg_match($pattern, $sender)) {
        return true;
    } else {
        return false;
    }
}

/*
 * Decode MIME header elements in e-mails | @@ Matthias Sommerfeld
 * (c) 2001-2004 blue birdy, Berlin (http://bluebirdy.de)
 * used with permission
 * Last Update: @@ Thorsten, 2004-07-17
 */
function encode_iso88591($coded = "", $cmode = "g")
{
    if ("g" == $cmode) {
        $coded = str_replace(" ", "_", quoted_printable_encode($coded));
        $zeilen = explode("\r\n", $coded);
        $coded = "";
        foreach ($zeilen as $key => $value) {
            if (!$value) {
                continue;
                }
            if ($key > 0) {
                $coded .= "\t";
                }
            $coded .= '=?iso-8859-1?Q?'.$value.'?='."\r\n";
            }
        return rtrim($coded);
        }
    elseif ("@" == $cmode) {
        $zeilen = explode("\r\n", $coded);
        $coded = "";
        foreach ($zeilen as $key => $value) {
            if (!$value) {
                continue;
                }
            if ($key > 0) {
                $coded .= "\t";
                }
            if (function_exists('mb_encode_mimeheader')) {
                $coded .= mb_encode_mimeheader($value)."\r\n";
            } else {
                $words = explode(" ", $value, 2);
                foreach ($words as $k => $word) {
                    if (preg_match("/[\x80-\xff]/", $word) && preg_match("/\(|\)/", $word)) {
                        $words[$k] = preg_replace (
                            "/^(\()?([^\)]+)(\))?$/ie",
                            "'(=?iso-8859-1?Q?'.rtrim(quoted_printable_encode(str_replace(' ', '_', '\\2'))).'?=)'",
                            $word);
                    }
                }
                $coded .= join(" ", $words)."\r\n";
            }
        }
        return rtrim($coded);
    } else {
        $zeilen = explode("\r\n", $coded);
        $coded = '';
        foreach ($zeilen as $key => $value) {
            if (!$value) {
                continue;
                }
            if ($key > 0) {
                $coded .= "\t";
                }
            $words = null;
            if (function_exists('mb_encode_mimeheader')) {
                $coded .= mb_encode_mimeheader( $value)."\r\n";
            } else {
                $words = null;
                $words = explode(" ", $value);
                foreach ($words as $k => $word) {
                    if (preg_match('/[\x80-\xff]/', $word)) {
                        $words[$k] = '=?iso-8859-1?Q?'.rtrim(quoted_printable_encode($word)).'?=';
                    }
                }
            }
            $coded .= join(' ', $words)."\r\n";
        }
        return rtrim($coded);
    }
}

/*
 * Decode MIME header elements in e-mails | @@ Matthias Sommerfeld
 * (c) 2001-2004 blue birdy, Berlin (http://bluebirdy.de)
 * used with permission
 * Last Update: @@ Thorsten, 2004-07-17
 */
function quoted_printable_encode($return = '')
{
    // Ersetzen der lt. RFC 1521 noetigen Zeichen
    $return = preg_replace('/([^\t\x20\x2E\041-\074\076-\176])/ie', "sprintf('=%2X',ord('\\1'))", $return);
    $return = preg_replace('!=\ ([A-F0-9])!', '=0\\1', $return);
    // Einfuegen von QP-Breaks (=\r\n)
    if (strlen($return) > 75) {
        $length = strlen($return); $offset = 0;
        do {
            $step = 76;
            $add_mode = (($offset+$step) < $length) ? 1 : 0;
            $auszug = substr($return, $offset, $step);
            if (preg_match('!\=$!', $auszug))   $step = 75;
            if (preg_match('!\=.$!', $auszug))  $step = 74;
            if (preg_match('!\=..$!', $auszug)) $step = 73;
            $auszug = substr($return, $offset, $step);
            $offset += $step;
            $schachtel .= $auszug;
            if (1 == $add_mode) $schachtel.= '='."\r\n";
            } while ($offset < $length);
        $return = $schachtel;
        }
    $return = preg_replace('!\.$!', '. ', $return);
    return preg_replace('!(\r\n|\r|\n)$!', '', $return)."\r\n";
}


/******************************************************************************
 * Funktionen fuer den XML-Export
 ******************************************************************************/

/**
* generateXHTMLFile()
*
* This function generates a plain XHTML file with all entries.
*
* @return   boolean
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @since    2004-11-13
*/
function generateXHTMLFile()
{
    global $db, $PMF_CONF, $PMF_LANG;

    require_once('Template.php');

    $category = new PMF_Category();
    $category->transform(0);
    $old = 0;

    $newest = array();

    //
    // get the 5 newest entries
    //
    $query = "SELECT DISTINCT ".SQLPREFIX."faqdata.id FROM ".SQLPREFIX."faqdata, ".SQLPREFIX."faqvisits WHERE ".SQLPREFIX."faqdata.id = ".SQLPREFIX."faqvisits.id AND ".SQLPREFIX."faqdata.lang = ".SQLPREFIX."faqvisits.lang AND ".SQLPREFIX."faqdata.active = 'yes' ORDER BY ".SQLPREFIX."faqdata.datum desc";
      $result = $db->query($query);
      if ($num = $db->num_rows($result) > 0) {
        $i = 1;
        $oldId = 0;
          while ($row = $db->fetch_object($result) && $i <= 5) {
              $newest[] = $row->id;
              $i++;
          }
      }

    $result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id');

    $xhtml = '';

    if ($db->num_rows($result) > 0) {
        while ($row = $db->fetch_object($result)) {
            if ($row->category_id != $old) {
                if ( !empty($old) ) {
                    $headlinks .= "\n</ul>\n";
                    $xhtml     .= "<p><a href=\"#top\">".$PMF_LANG['to_top']."</a></p>\n";
                }
                /* add current line to index */
                $headlinks .= '<p><strong>'.$PMF_LANG["html_head_cat"].' <a href="#cat'.$row->category_id.'">'.$category->getPath($row->category_id)."</a></strong></p>\n<ul>\n";
                $xhtml .= "\n<a name=\"cat".$row->category_id."\">&nbsp;</a><h1>".$category->getPath($row->category_id)."</h1>\n";
            }
            $xhtml .= '<h2>'.$marker.'<a name="'.$row->id.'">'.$row->thema.'</h2>';
            $xhtml .= '<p>'.$row->content.'</p>';
            $xhtml .= '<p>'.$PMF_LANG["msgAuthor"].$row->author.'<br />';
            $xhtml .= 'ID: '.$row->solution_id.'<br />';
            $xhtml .= $PMF_LANG['ad_entry_revision'].': 1.'.$row->revision_id.'<br />';
            $xhtml .= $PMF_LANG["msgLastUpdateArticle"].makeDate($row->datum).'</p>';
            $xhtml .= '<hr style="width: 90%;" />';
            $old = $row->category_id;
            /* add entry to index */
            $headlinks .= "\t<li>$marker<a href=\"#$row->id\">$row->thema</a></li>\n";
        }
    }

    $tpl = new phpmyfaqTemplate(array('html' => '../template/htmlexport.tpl'));

    /* get main template, set main variables */
    $tpl->processTemplate ('html', array(
                'title'             => htmlentities($PMF_CONF['title']),
                'header'            => htmlentities($PMF_CONF['title']),
                'metaCharset'       => htmlentities($PMF_LANG['metaCharset']),
                'metaDescription'   => htmlentities($PMF_CONF['main.metaDescription']),
                'metaKeywords'      => htmlentities($PMF_CONF['main.metaKeywords']),
                'metaPublisher'     => htmlentities($PMF_CONF['main.metaPublisher']),
                'metaLanguage'      => $PMF_LANG['metaLanguage'],
                'metaCharset'       => $PMF_LANG['metaCharset'],
                'entrylinks'        => $headlinks,
                'writeContent'      => $xhtml,
                'copyright'         => 'powered by <a href="http://www.phpmyfaq.de" target="_blank">phpMyFAQ</a> '.$PMF_CONF['version']
        ));

    /* parse without printing */
    $xhtml = $tpl->getTemplateContents();

    if ($fp = fopen("../xml/phpmyfaq.html","w")) {
        fputs($fp, $xhtml);
        fclose($fp);
    }
}

/*
 * Funktion zum Exportieren aller Artikel in eine XML-Datei | @@ Thorsten, 2003-03-03
 * Last Update: @@ Thorsten, 2004-05-07
 */
function generateXMLFile()
{
    global $db, $category, $PMF_CONF, $PMF_LANG;

    $result = $db->query('SELECT '.SQLPREFIX.'faqdata.id AS id, '.SQLPREFIX.'faqdata.lang AS lang, '.SQLPREFIX.'faqdata.solution_id AS solution_id, '.SQLPREFIX.'faqdata.revision_id AS revision_id, '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id, '.SQLPREFIX.'faqdata.thema AS thema, '.SQLPREFIX.'faqdata.content AS content, '.SQLPREFIX.'faqdata.keywords AS keywords, '.SQLPREFIX.'faqdata.author AS author, '.SQLPREFIX.'faqdata.datum AS datum FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id');

    if ($db->num_rows($result) > 0) {
        $my_xml_output = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n";
        $my_xml_output .= "<!-- XML-Output by phpMyFAQ ".$PMF_CONF["version"]." | Date: ".makeDate(date("YmdHis"))." -->\n";
        $my_xml_output .= "<phpmyfaq xmlns=\"http://www.phpmyfaq.de/phpmyfaq\">\n";
        $xml_fp = fopen("../xml/phpmyfaq.xml","w");
        while ($row = $db->fetch_object($result)) {
            $xml_content = wordwrap($row->content);
            $xml_rubrik = $category->categoryName[$row->category_id]['name'];
            $xml_thema = wordwrap($row->thema, 60);
            $xml_content = trim(htmlspecialchars(wordwrap($xml_content, 60)));

            if (is_writeable("../xml/")) {
                $my_xml_output .= "\t<article id=\"".$row->id."\" solution_id=\"".$row->solution_id."\" revision_id=\"1.".$row->revision_id."\">\n";
                $my_xml_output .= "\t<language>".$row->lang."</language>\n";
                $my_xml_output .= "\t<category>".PMF_htmlentities(strip_tags($xml_rubrik), ENT_NOQUOTES, $PMF_LANG['metaCharset'])."</category>\n";
                if (isset($row->keywords) && $row->keywords != '') {
                    $my_xml_output .= "\t<keywords>".$row->keywords."</keywords>\n";
                }
                else {
                    $my_xml_output .= "\t<keywords />\n";
                }
                $my_xml_output .= "\t<theme>".PMF_htmlentities(strip_tags($xml_thema), ENT_NOQUOTES, $PMF_LANG['metaCharset'])."</theme>\n";
                $my_xml_output .= "\t<content xmlns=\"http://www.w3.org/TR/REC-html40\">".strip_tags($xml_content)."</content>\n";
                if ($row->author) {
                    $my_xml_output .= "\t<author>".PMF_htmlentities(strip_tags($row->author), ENT_NOQUOTES, $PMF_LANG['metaCharset'])."</author>\n";
                }
                else {
                    $my_xml_output .= "\t<author />\n";
                }
                $my_xml_output .= "\t<date>".makeDate($row->datum)."</date>\n";
                $my_xml_output .= "\t</article>\n\n";
            }
        }
        $my_xml_output .= "</phpmyfaq>";
        fputs($xml_fp, $my_xml_output);
        fclose($xml_fp);
    }
}

/**
* This function generates a plain XHTML file with all entries.
*
* @return   boolean
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @author   Johann-Peter Hartmann <hartmann@mayflower.de>
* @since    2004-12-23
*/
function generateDocBookExport()
{
    global $db, $category, $PMF_CONF, $PMF_LANG;

    $output = '<?xml version="1.0"?>
<!DOCTYPE book PUBLIC "-//Norman Walsh//DTD DocBk XML V3.1.4//EN" "http://nwalsh.com/docbook/xml/3.1.4/db3xml.dtd">
<book id="phpmyfaq" lang="'.$PMF_LANG['metaLanguage'].'">
    <bookinfo>
        <title>'.htmlspecialchars($PMF_CONF['title']).'</title>
        <author>
            <firstname></firstname>
            <surname></surname>
        </author>
        <date>'.makeDate('Y-m-d', time()).'</date>
        <abstract>
            <para>'.htmlspecialchars($PMF_CONF['main.metaDescription']).'</para>
        </abstract>
    </bookinfo>
';

    // get all categories
    $result_chapters = $db->query("SELECT id, name FROM ".SQLPREFIX."faqcategories");
    // get all faqs
    $result_faqs = $db->query("SELECT category_id, record_id, id FROM ".SQLPREFIX."faqcategoryrelations");
    // get all data
    $result_data = $db->query("SELECT * FROM ".SQLPREFIX."faqdata d, ".SQLPREFIX."faqcategoryrelations r where d.id = r.record_id");

    // chapters
    $chapters = array();
    while ($row = $db->fetch_object($result_chapters)) {
        $chapters[$row->id]['title'] = $row->name;
        $chapters[$row->id]['faqs'] = array();
    }

    // faqs
    while ($row = $db->fetch_object($result_faqs)) {
        $chapters[$row->category_id]['faqs'][$row->id] = array();
    }

    // data
    while ($row = $db->fetch_object($result_data)) {
        $chapters[$row->category_id]['faqs'][$row->id][$row->record_id] =
            array(
                'author' => $row->author,
                'date'   => $row->datum,
                'thema'  => $row->thema,
                'para'   => $row->content
            );
    }

    // output
    foreach($chapters as $c_key=>$c_value){

        $output .= '<chapter id="'.$c_key.'">';
        $output .= "\n";
        $output .= '<title>'.$c_value['title'].'</title>';
        $output .= "\n";

        foreach($c_value['faqs'] as $f_id => $data){
            foreach($data as $d_id => $posting){
                $output .= '<sect1 id="'.$d_id.'">';
                $output .= "\n";

                $output .= '<author>';
                $output .= $posting['author'];
                $output .= '</author>';
                $output .= "\n";

                $output .= '<date>';
                $output .= $posting['date'];
                $output .= '</date>';
                $output .= "\n";

                $output .= '<title>';
                $output .= $posting['thema'];
                $output .= '</title>';
                $output .= "\n";

                $output .= '<para>';
                $output .= $posting['para'];
                $output .= '</para>';
                $output .= "\n";

                $output .= '</sect1>';
                $output .= "\n";
            }
        }

        $output .= '</chapter>';
    }
    $output .= "</book>";

    // write xml file
    $xml_fp = fopen("../xml/docbook.xml","w");
    fputs($xml_fp, $output);
    fclose($xml_fp);
}

/**
 * The main search function for the full text search
 *
 * TODO: add filter for (X)HTML tag names and attributes!
 *
 * @param    string     Text/Number (solution id)
 * @param    string     '%' to avoid any category filtering
 * @param    boolean    True to search over all languages
 * @param    boolean    True to disable the results paging
 * @return   string
 * @access   public
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since    2002-09-16
 */
function searchEngine($begriff, $cat = '%', $allLanguages = true, $hasMore = false)
{
    global $db, $sids, $category, $PMF_LANG, $PMF_CONF, $LANGCODE;

    $_begriff    = $begriff;
    $seite       = '';
    $output      = '';
    $num = 0;
    $searchItems = array();

    if (isset($_REQUEST["seite"])) {
        $seite = $_REQUEST["seite"];
    } else {
        $seite = 1;
    }

    $cond = array(SQLPREFIX."faqdata.active" => "'yes'");
    if ($cat != '%') {
        $cond = array_merge(array(SQLPREFIX."faqcategoryrelations.category_id" => $cat), $cond);
    }
    if ((!$allLanguages) && (!is_numeric($begriff))) {
        $cond = array_merge(array(SQLPREFIX."faqdata.lang" => "'".$LANGCODE."'"), $cond);
    }

    if (is_numeric($begriff)) {
        // search for the solution_id
        $result = $db->search(SQLPREFIX.'faqdata',
                        array(SQLPREFIX.'faqdata.id AS id',
                              SQLPREFIX.'faqdata.lang AS lang',
                              SQLPREFIX.'faqdata.solution_id AS solution_id',
                              SQLPREFIX.'faqcategoryrelations.category_id AS category_id',
                              SQLPREFIX.'faqdata.thema AS thema',
                              SQLPREFIX.'faqdata.content AS content'),
                        SQLPREFIX.'faqcategoryrelations',
                        array(SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id',
                              SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang'),
                        array(SQLPREFIX.'faqdata.solution_id'),
                        $begriff,
                        $cond);
    } else {
        $result = $db->search(SQLPREFIX."faqdata",
                        array(SQLPREFIX."faqdata.id AS id",
                              SQLPREFIX."faqdata.lang AS lang",
                              SQLPREFIX."faqcategoryrelations.category_id AS category_id",
                              SQLPREFIX."faqdata.thema AS thema",
                              SQLPREFIX."faqdata.content AS content"),
                        SQLPREFIX."faqcategoryrelations",
                        array(SQLPREFIX."faqdata.id = ".SQLPREFIX."faqcategoryrelations.record_id",
                              SQLPREFIX."faqdata.lang = ".SQLPREFIX."faqcategoryrelations.record_lang"),
                        array(SQLPREFIX."faqdata.thema",
                              SQLPREFIX."faqdata.content",
                              SQLPREFIX."faqdata.keywords"),
                        $begriff,
                        $cond);
    }

    if ($result) {
        $num = $db->num_rows($result);
    }

    // Show the record with the solution ID directly
    // Sanity checks: if a valid Solution ID has been provided the result set
    //                will measure 1: this is true ONLY if the faq is not
    //                classified among more than 1 category
    if (is_numeric($begriff) && ($begriff >= PMF_SOLUTION_ID_START_VALUE) && ($num > 0)) {
        // Hack: before a redirection we must force the PHP session update for preventing data loss
        session_write_close();
        if (isset($PMF_CONF['main.enableRewriteRules']) && $PMF_CONF['main.enableRewriteRules']) {
            header('Location: '.PMF_Link::getSystemUri('/index.php').'/solution_id_'.$begriff.'.html');
        } else {
            header('Location: '.PMF_Link::getSystemUri('/index.php').'/index.php?solution_id='.$begriff);
        }
        exit();
    }

    if (0 == $num) {
        $keys = preg_split("/\s+/", $begriff);
        $numKeys = count($keys);
        $where = '';
        for ($i = 0; $i < $numKeys; $i++) {
            if (strlen($where) != 0 ) {
                $where = $where." OR ";
            }
            $where = $where.'('.SQLPREFIX."faqdata.thema LIKE '%".$keys[$i]."%' OR ".SQLPREFIX."faqdata.content LIKE '%".$keys[$i]."%' OR ".SQLPREFIX."faqdata.keywords LIKE '%".$keys[$i]."%')";
            if (is_numeric($cat)) {
                $where .= ' AND '.SQLPREFIX.'faqcategoryrelations.category_id = '.$cat;
            }
            if (!$allLanguages) {
                $where .= ' AND '.SQLPREFIX."faqdata.lang = '".$LANGCODE."'";
            }
        }

        $where = " WHERE (".$where.") AND ".SQLPREFIX."faqdata.active = 'yes'";
        $query = 'SELECT '.SQLPREFIX.'faqdata.id AS id, '.SQLPREFIX.'faqdata.lang AS lang, '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id, '.SQLPREFIX.'faqdata.thema AS thema, '.SQLPREFIX.'faqdata.content AS content FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang '.$where;
        $result = $db->query($query);
        $num = $db->num_rows($result);
    }

    if (0 == $num) {
        $output = $PMF_LANG['err_noArticles'];
    }

    $pages = ceil($num / $PMF_CONF['numRecordsPage']);
    $last = $seite * $PMF_CONF['numRecordsPage'];
    $first = $last - $PMF_CONF['numRecordsPage'];
    if ($last > $num) {
        $last = $num;
    }

    if ($num > 0) {
        if ($num == "1") {
            $output .= '<p>'.$num.$PMF_LANG["msgSearchAmount"]."</p>\n";
        } else {
            $output .= '<p>'.$num.$PMF_LANG["msgSearchAmounts"];
            if ($hasMore && ($pages > 1)) {
                $output .= sprintf(
                    $PMF_LANG['msgInstantResponseMaxRecords'],
                    $PMF_CONF['numRecordsPage']
                );
            }
            $output .= "</p>\n";
        }
        if (!$hasMore && ($pages > 1)) {
            $output .= "<p><strong>".$PMF_LANG["msgPage"].$seite." ".$PMF_LANG["msgVoteFrom"]." ".$pages." ".$PMF_LANG["msgPages"]."</strong></p>";
        }
        $output .= "<ul class=\"phpmyfaq_ul\">\n";

        $counter = 0;
        $displayedCounter = 0;
        while (($row = $db->fetch_object($result)) && $displayedCounter < $PMF_CONF['numRecordsPage']) {
            $counter ++;
            if ($counter <= $first) {
                continue;
            }
            $displayedCounter++;

            $rubriktext = $category->getPath($row->category_id);
            $thema = PMF_htmlentities(chopString($row->thema, 15),ENT_NOQUOTES, $PMF_LANG['metaCharset']);
            $content = chopString(strip_tags($row->content), 25);
            $begriff = str_replace(array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'), '', $begriff);
            $begriff = preg_quote($begriff, '/');
            $searchItems = explode(' ', $begriff);

            if (strlen($searchItems[0]) > 1) {
                foreach ($searchItems as $item) {
                    $thema = preg_replace_callback('/'
                        .'('.$item.'="[^"]*")|'
                        .'((href|src|title|alt|class|style|id|name|dir|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup)="[^"]*'.$item.'[^"]*")|'
                        .'('.$item.')'
                        .'/mis',
                        "highlight_no_links",
                        $thema
                    );
                    $content = preg_replace_callback('/'
                        .'('.$item.'="[^"]*")|'
                        .'((href|src|title|alt|class|style|id|name|dir|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup)="[^"]*'.$item.'[^"]*")|'
                        .'('.$item.')'
                        .'/mis',
                        "highlight_no_links",
                        $content
                    );
                }
            }

            // Print the link to the faq record
            $url = sprintf(
                '%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                $sids,
                $row->category_id,
                $row->id,
                $row->lang,
                $begriff
            );
            // kick out ajaxresponse.php
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri('ajaxresponse.php').'?'.$url);
            $oLink->itemTitle = $row->thema;
            $oLink->text = $thema;
            $oLink->tooltip = $row->thema;
            $output .=
                '<li><strong>'.$rubriktext.'</strong>: '.$oLink->toHtmlAnchor().'</li><br />'
                .'<div class="searchpreview"><strong>'.$PMF_LANG['msgSearchContent'].'</strong> '.$content.'...</div>'
                .'<br /></li>'."\n";
        }
        $output .= "</ul>\n";
    } else {
        $output = $PMF_LANG["err_noArticles"];
    }

    if (!$hasMore && ($num > $PMF_CONF['numRecordsPage'])) {
        $output .= "<p align=\"center\"><strong>";
        $vor = $seite - 1;
        $next = $seite + 1;
        if ($vor != 0) {
            if (isset($PMF_CONF['main.enableRewriteRules']) && $PMF_CONF['main.enableRewriteRules']) {
                $output .= "[ <a href=\"search.html?search=".urlencode($_begriff)."&amp;seite=".$vor."\">".$PMF_LANG["msgPrevious"]."</a> ]";
            } else {
                $output .= "[ <a href=\"index.php?".$sids."action=search&amp;search=".urlencode($_begriff)."&amp;seite=".$vor."\">".$PMF_LANG["msgPrevious"]."</a> ]";
            }
        }
        $output .= " ";
        if ($next <= $pages) {
            $url = $sids.'&amp;action=search&amp;search='.urlencode($_begriff).'&amp;seite='.$next;
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle = '';
            $oLink->text = $PMF_LANG["msgNext"];
            $oLink->tooltip = $PMF_LANG["msgNext"];
            $output .= '[ '.$oLink->toHtmlAnchor().' ]';
        }
        $output .= "</strong></p>";
    }

    return $output;
}



/**
 * Callback function for filtering HTML from URLs and images
 *
 * @param   array
 * @access  public
 * @return  string
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matthias Sommerfeld <phlymail@phlylabs.de>
 * @author  Johannes Schlueter <johannes@php.net>
 * @since   2003-07-14
 */
function highlight_no_links($string)
{
    static $attributes = array(
        'href', 'src', 'title', 'alt', 'class', 'style', 'id', 'name',
        'face', 'size', 'dir', 'onclick', 'ondblclick', 'onmousedown',
        'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout',
        'onkeypress', 'onkeydown', 'onkeyup'
        );
    foreach ($string as $str) {
        $_str = explode('=', strtolower(ltrim($str)), 1);
        if (in_array($_str, $attributes)) {
            return $_str;
        } elseif ('' == $str) {
            return '';
        } else {
            return '<span class="highlight">'.$str.'</span>';
        }
    }
}



/*
 * This functions chops a string | @@ Thorsten, 2003-12-16
 * Last Update: @@ Thorsten, 2003-12-16
 */
function chopString($string, $words)
{
    $str = "";
    $pieces = explode(" ", $string);
    $num = count($pieces);
    if ($words > $num) {
        $words = $num;
    }
    for ($i = 0; $i < $words; $i++) {
        $str .= $pieces[$i]." ";
    }
    return $str;
}

//
// FUNCTIONS FOR THE OPEN QUESTIONS
//

/**
* printOpenQuestions()
*
* Prints the open questions as a XHTML table
*
* @return   string
* @access   public
* @since    2002-09-17
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function printOpenQuestions()
{
    global $db, $sids, $category, $PMF_LANG;

    $query =   'SELECT
                    COUNT(*) AS num
                FROM '.SQLPREFIX.'faqquestions
                WHERE
                    is_visible != \'Y\'';
    $result = $db->query($query);
    $row = $db->fetch_object($result);
    $numOfInvisibles = $row->num;

    if ($numOfInvisibles > 0) {
        $extraout = "\t<tr>\n\t\t<td colspan=\"3\"><hr>".$PMF_LANG['msgQuestionsWaiting'].$numOfInvisibles."</td>\n\t</tr>\n";
    } else {
        $extraout = '';
    }

    $query =   'SELECT
                    id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date
                FROM
                    '.SQLPREFIX.'faqquestions
                WHERE
                    is_visible = \'Y\'
                ORDER BY
                    ask_date ASC';
    $result = $db->query($query);
    $output = '';
    if ($db->num_rows($result) > 0) {
        while ($row = $db->fetch_object($result)) {
            $output .= "\t<tr class=\"openquestions\">\n";
            $output .= "\t\t<td valign=\"top\" nowrap=\"nowrap\">".makeDate($row->ask_date)."<br /><a href=\"mailto:".safeEmail($row->ask_usermail)."\">".$row->ask_username."</a></td>\n";
            $output .= "\t\t<td valign=\"top\"><strong>".$category->categoryName[$row->ask_rubrik]['name'].":</strong><br />".strip_tags($row->ask_content)."</td>\n";
            $output .= "\t\t<td valign=\"top\"><a href=\"".$_SERVER['PHP_SELF']."?".$sids."action=add&amp;question=".$row->id."&amp;cat=".$row->ask_rubrik."\">".$PMF_LANG['msg2answer']."</a></td>\n";
            $output .= "\t</tr>\n";
        }
    } else {
        $output = "\t<tr>\n\t\t<td colspan=\"3\">".$PMF_LANG['msgNoQuestionsAvailable']."</td>\n\t</tr>\n";
    }

    return $output.$extraout;
}

//
// Various functions
//

/**
 * PMF_htmlentities
 *
 * This is a wrapper for htmlspecialchars() with a check on valid charsets.
 *
 * @param   string
 * @param   string
 * @param   string
 * @return  string
 * @access  private
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function PMF_htmlentities($string, $quote_style = ENT_COMPAT, $charset = 'iso-8859-1')
{
    $acceptedCharsets = array('iso-8859-1', 'iso-8859-15', 'utf-8', 'cp866', 'ibm866', '866', 'cp1251', 'windows-1251', 'win-1251', '1251', 'cp1252', 'windows-1252', '1252', 'koi8-r', 'koi8-ru', 'koi8r', 'big5', '950', 'gb2312', '936', 'big5-hkscs', 'shift_jis', 'sjis', '932', 'euc-jp', 'eucjp');

    if (in_array(strtolower($charset), $acceptedCharsets)) {
        return htmlspecialchars($string, ENT_NOQUOTES, $charset);
    } else {
        return htmlspecialchars($string);
    }
}

/******************************************************************************
 * Funktionen fuer die Benutzerauthentifizierung und Rechtevergabe
 ******************************************************************************/

/**
 * Adds a menu entry according to user permissions
 *
 * @param unknown_type $restrictions
 * @param unknown_type $action
 * @param unknown_type $caption
 */
function addMenuEntry($restrictions = '', $action = '', $caption = '')
{
    global $permission, $PMF_LANG;

    if ($action != '') {
        $action = "action=".$action;
    }

    if (isset($PMF_LANG[$caption])) {
        $_caption = $PMF_LANG[$caption];
    } else {
        $_caption = 'No string for '.$caption;
    }

    $output = '        <li><a href="?'.$action.'">'.$_caption."</a></li>\n";
    if ($restrictions == '') {
        print $output;
        return;
    }

    foreach (explode(',', $restrictions) as $_restriction) {
        if (isset($permission[$_restriction]) && $permission[$_restriction]) {
            print $output;
            return;
        }
    }
}


/**
 * Administrator logging
 *
 * @param   string
 * @return  void
 * @access  public
 * @since   2001-02-18
 * @author  Bastian Poettner <bastian@poettner.net>
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function adminlog($text)
{
    global $db, $PMF_CONF, $auth, $user;

    if (isset($PMF_CONF['main.enableAdminLog']) && $auth && isset($user)) {
        $query = sprintf(
                'INSERT INTO
                    %sfaqadminlog
                    (id, time, usr, text, ip)
                VALUES (%d, %d, %d, %s, %s)',
                    SQLPREFIX,
                    $db->nextID(SQLPREFIX.'faqadminlog', 'id'),
                    time(),
                    $user->userdata->get('user_id'),
                    "'".nl2br($text)."'",
                    "'".$_SERVER['REMOTE_ADDR']."'"
                    );

        $db->query($query);
    }
}

/*
 * Checkt, ob eine SQL-Tabelle leer ist | @@ Thorsten 2002-01-10
 * Last Update: @@ Thorsten, 2003-03-24
 */
function emptyTable($table)
{
    global $db;
    if ($db->num_rows($db->query("SELECT * FROM ".$table)) < 1) {
        return true;
    } else {
        return false;
    }
}



/******************************************************************************
 * Funktionen fuer den Adminbereich
 ******************************************************************************/

/*
 * Funktion zum generieren vom "Umblaettern" | @@ Bastian, 2002-01-03
 * Last Update: @@ Thorsten, 2004-05-07
 */
function PageSpan($code, $start, $end, $akt)
{
    global $PMF_LANG;
    if ($akt > $start) {
        $out = str_replace("<NUM>", $akt-1, $code).$PMF_LANG["msgPreviusPage"]."</a> | ";
    } else {
        $out = "";
    }
    for ($h = $start; $h<=$end; $h++) {
        if ($h > $start) {
            $out .= ", ";
        }
        if ($h != $akt) {
            $out .= str_replace("<NUM>", $h, $code).$h."</a>";
        } else {
            $out .= $h;
        }
    }
    if ($akt < $end) {
        $out .= " | ".str_replace("<NUM>", $akt+1, $code).$PMF_LANG["msgNextPage"]."</a>";
    }
    $out = $PMF_LANG["msgPageDoublePoint"].$out;
    return $out;
}

/*
 * Bastelt aus den Dateinamen des Tracking einen Timestamp | @@ Bastian, 2002-01-05
 * Last Update: @@ Thorsten, 2002-09-19
 * Last Update: @@ Matteo, 2006-06-13
 */
function FileToDate($file, $endOfDay = false)
{
    if (strlen($file) >= 16) {
        $tag = substr($file, 8, 2);
        $mon = substr($file, 10, 2);
        $yea = substr($file, 12, 4);
        if (!$endOfDay) {
            $tim = mktime(0, 0, 0, $mon, $tag, $yea);
        }
        else {
            $tim = mktime(23, 59, 59, $mon, $tag, $yea);
        }
        return $tim;
    } else {
        return -1;
    }
}

/*
 * Bastelt nen Timestamp ausm Datum | @@ Bastian, 2001-04-09
 * Last Update: @@ Thorsten - 2002-09-27
 */
function mkts($datum,$zeit)
{
    if (strlen($datum) > 0) {
        $tag = substr($datum,0,strpos($datum,"."));
        $datum = substr($datum,(strpos($datum,".")+1),strlen($datum));
        $monat = substr($datum,0,strpos($datum,"."));
        $datum = substr($datum,(strpos($datum,".")+1),strlen($datum));
        $jahr = $datum;
    } else {
        $tag = date("d");
        $monat = date("m");
        $jahr = date("Y");
    } if (strlen($zeit) > 0) {
        $stunde = substr($zeit,0,strpos($zeit,":"));
        $zeit = substr($zeit,(strpos($zeit,":")+1),strlen($zeit));
        $minute = substr($zeit,0,strpos($zeit,":"));
        $zeit = substr($zeit,(strpos($zeit,":")+1),strlen($zeit));
        $sekunde = $zeit;
    } else {
        $stunde = date("H");
        $minute = date("i");
        $sekunde = date("s");
    }
    return mktime($stunde, $minute, $sekunde, $monat, $tag, $jahr);
}



//
// Add re-built PHP functions, which we need and not included in PHP 4.3.0
//

/**
* Creates an array by using one array for keys and another for its values
* NOTE: This function is included in PHP 5.0.0 and later
*
* @param    array
* @param    array
* @return   array
* @access   public
* @since    2004-07-23
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
if (!function_exists("array_combine")) {
    function array_combine($keys, $values)
    {
        $keys = array_values($keys);
        $values  = array_values($values);
        $combined = array ();
        $num = count($values);
        for ($i = 0; $i < $num; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }
        return $combined;
    }
}



//
// Functions for backup and SQL security
//

/**
 * This function builds the the queries for the backup
 *
 * @param    string      query
 * @param    string      table name
 * @return   array
 * @access   public
 * @author   Meikel Katzengreis <meikel@katzengreis.com>
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since    2003-03-24
 */
function build_insert($query, $table)
{
    global $db;
    if (!$result = $db->query($query)) {
        return;
    }
    $ret = array();
    $ret[] = "\n-- Table: ".$table;
    while ($row = $db->fetch_assoc ($result)) {
        $p1 = array();
        $p2 = array();
        foreach ($row as $key => $val) {
            $p1[] = $key;
            $val = trim($val);
            if ('rights' != $key && is_numeric($val)) {
                $p2[] = $val;
            } else {
                if (strlen($val) == 0) {
                    $p2[] = 'NULL';
                } else {
                    $p2[] = sprintf("'%s'", $db->escape_string($val));
                }
            }
        }
        $ret[] = "INSERT INTO ".$table." (".implode(",", $p1).") VALUES (".implode(",", $p2).");";
    }
    return $ret;
}

/*
 * Funktion zum Escapen von SQL Queries | @@ Thorsten, 2003-11-13
 * Last Update: @@ Thorsten, 2004-07-07
 */
function safeSQL($string)
{
    $str = "";
    $length = strlen($string);
    for ($i = 0; $i < $length; $i++) {
        $char = $string[$i];
        switch ($char) {
            case "'":   $str .= "\'"; break;
            case "\\":  $str .= "\\\\"; break;
            case "\n":  $str .= "\\n"; break;
            case "\r":  $str .= "\\r"; break;
            default:    $str .= $char;
        }
    }
    return $str;
}

/**
* alignTablePrefixByPattern()
*
* Align the prefix of the table name used in the PMF backup file,
* from the (old) value of the system upon which the backup was performed
* to the (new) prefix of the system upon which the backup will be restored.
* This alignment will be perfomed ONLY upon those given SQL queries starting
* with the given pattern.
*
* @param    $query                  string
* @param    $start_pattern      string
* @param    $oldvalue               string
* @param    $newvalue              string
* @return   string
* @access   public
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function alignTablePrefixByPattern($query, $start_pattern, $oldvalue, $newvalue)
{
    $ret = $query;

    preg_match_all("/^".$start_pattern."\s+(\w+)(\s+|$)/i", $query, $matches);
    if (isset($matches[1][0])) {
        $oldtablefullname = $matches[1][0];
        $newtablefullname = $newvalue.substr($oldtablefullname, strlen($oldvalue));
        $ret = str_replace($oldtablefullname, $newtablefullname, $query);
    }

    return $ret;
}

/**
* alignTablePrefix()
*
* Align the prefix of the table name used in the PMF backup file,
* from the (old) value of the system upon which the backup was performed
* to the (new) prefix of the system upon which the backup will be restored
* This alignment will be performed upon all of the SQL query "patterns"
* provided within the PMF backup file.
*
* @param    $query                  string
* @param    $oldvalue               string
* @param    $newvalue              string
* @return   string
* @access   public
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function alignTablePrefix($query, $oldvalue, $newvalue)
{
    // Align DELETE FROM <prefix.tablename>
    $query = alignTablePrefixByPattern($query, "DELETE FROM", $oldvalue, $newvalue);
    // Align INSERT INTO <prefix.tablename>
    $query = alignTablePrefixByPattern($query, "INSERT INTO", $oldvalue, $newvalue);

    return $query;
}

/**
* fixslashes
*
* This function takes care of safely removing slashes
* not really needed for escaping characters on PMF 1.5.3 and above
* (see instead PMF DB population on 1.5.2 and below)
*
* @param    string
* @return   string
* @access   public
* @since    2005-11-28
* @author   Matteo Scaramuccia <matteo@scaramuccia.com>
*/
function fixslashes($text)
{
    $fixedtext = $text;
    // Check if the line must be fixed...
    if (!(false === strpos($fixedtext, "\\"))) {
        // ... and fix it!
        $fixedtext = preg_replace("/(\\\\)([^\w\s\\\\])/", "$2", $fixedtext);
    }

    return $fixedtext;
}


//
// LDAP FUNCTIONS
//

/**
 * Returns the user name from REMOTE_USER
 *
 * @return   string
 * @access   public
 * @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function getShortUserName()
{
    if (isset($_ENV['REMOTE_USER'])) {
        return $_ENV['REMOTE_USER'];
    } elseif (isset($_SERVER['REMOTE_USER'])) {
        return $_SERVER['REMOTE_USER'];
    } else {
        return '';
    }
}

/**
 * Returns the full user name from LDAP if available
 *
 * @return   string
 * @access   public
 * @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function getFullUserName()
{
    global $faqconfig;
    if ($faqconfig->get('main.ldapSupport')) {
        global $ldap;
        return $ldap->ldap_getCompleteName(getShortUserName());
    } else {
        return getShortUserName();
    }
}

/**
 * Returns the full user name from LDAP if available
 *
 * @return   string
 * @access   public
 * @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function getEmailAddress()
{
    global $faqconfig;
    if ($faqconfig->get('main.ldapSupport')) {
        global $ldap;
        return $ldap->ldap_getMail(getShortUserName());
    } else {
        return '';
    }
}

/**
 * Print the HTML for the date time window
 *
 * @param   string  $key
 * @param   array   $date
 * @return  array
 * @access  public
 * @since   2006-07-23
 * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
 */
function printDateTimeInput($key, $date)
{
    $html = '';
    // YYYY
    $html .= '<div style="float: left;"><select name="'.$key.'YYYY"><option value="">----</option>';
    for ($i = 2007; $i < 2011; $i++) {
        $html .= '<option value="'.$i.'"'.( (int)$date['YYYY'] == $i ? ' selected="selected"' : '').'>'.$i.'</option>';
    }
    $html .= '</select>&nbsp;-&nbsp;</div>';
    // MM
    $html .= '<div style="float: left;"><select name="'.$key.'MM"><option value="">--</option>';
    for ($i = 1; $i < 13; $i++) {
        $html .= '<option value="'.$i.'"'.( (int)($date['MM'] == $i) && ('' != $date['YYYY']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
    }
    $html .= '</select>&nbsp;-&nbsp;</div>';
    // DD
    $html .= '<div style="float: left;"><select name="'.$key.'DD"><option value="">--</option>';
    for ($i = 1; $i < 32; $i++) {
        $html .= '<option value="'.$i.'"'.( (int)($date['DD'] == $i) && ('' != $date['MM']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
    }
    $html .= '</select>&nbsp;&nbsp;&nbsp;</div>';
    // HH
    $html .= '<div style="float: left;"><select name="'.$key.'HH"><option value="">--</option>';
    for ($i = 0; $i < 24; $i++) {
        $html .= '<option value="'.$i.'"'.( (int)($date['HH'] == $i) && ('' != $date['DD']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
    }
    $html .= '</select>&nbsp;:&nbsp;</div>';
    // mm
    $html .= '<div style="float: left;"><select name="'.$key.'mm"><option value="">--</option>';
    for ($i = 0; $i < 60; $i++) {
        $html .= '<option value="'.$i.'"'.( (int)($date['mm'] == $i) && ('' != $date['HH']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
    }
    $html .= '</select>&nbsp;:&nbsp;</div>';
    // ss
    $html .= '<div style="float: left;"><select name="'.$key.'ss"><option value="">--</option>';
    for ($i = 0; $i < 60; $i++) {
        $html .= '<option value="'.$i.'"'.( (int)($date['ss'] == $i) && ('' != $date['mm']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
    }
    $html .= '</select></div>';

    return $html;
}
