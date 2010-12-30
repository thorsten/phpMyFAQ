<?php
/**
 * This is the main functions file.
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
 * Portions created by Matthias Sommerfeld are Copyright (c) 2001-2010 blue
 * birdy, Berlin (http://bluebirdy.de). All Rights Reserved.
 * 
 * @category  phpMyFAQ
 * @package   Core
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matthias Sommerfeld <phlymail@phlylabs.de>
 * @author    Bastian Poettner <bastian@poettner.net>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Robin Wood <robin@digininja.org>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2001-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-02-18
 */

//
// DEBUGGING FUNCTIONS
//

/**
 * Function to get a pretty formatted output of a variable
 *
 * NOTE: Just for debugging!
 *
 * @param   object
 * @return  void
 * @access  public
 * @since   2004-11-27
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function dump($var)
{
    print '<pre>';
    var_dump($var);
    print '</pre>';
}

/**
 * debug_backtrace() wrapper function
 *
 * @param   $string
 * @return  string
 * @access  public
 * @since   2006-06-24
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function pmf_debug($string)
{
    // sometimes Zend Optimizer causes segfaults with debug_backtrace()
    if (extension_loaded('Zend Optimizer')) {
        $ret = "<pre>" . $string . "</pre><br />\n";
    } else {
        $debug = debug_backtrace();
        $ret   = '';
        if (isset($debug[2]['class'])) {
        	$ret  = $debug[2]['file'] . ":<br />";
            $ret .= $debug[2]['class'].$debug[1]['type'];
            $ret .= $debug[2]['function'] . '() in line ' . $debug[2]['line'];
            $ret .= ": <pre>" . $string . "</pre><br />\n";
        }
    }
    return $ret;
}

/**
 * phpMyFAQ custom error handler function, also to prevent the disclosure of
 * potential sensitive data.
 *
 * @access public
 * @param  int    $level    The level of the error raised.
 * @param  string $message  The error message.
 * @param  string $filename The filename that the error was raised in.
 * @param  int    $line     The line number the error was raised at.
 * @param  mixed  $context  It optionally contains an array of every variable
 *                          that existed in the scope the error was triggered in.
 * @since  2009-02-01
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 */
function pmf_error_handler($level, $message, $filename, $line, $context)
{
    // Sanity check
    // Note: when DEBUG mode is true we want to track any error!
    if (
        // 1. the @ operator sets the PHP's error_reporting() value to 0
           (!DEBUG && (0 == error_reporting()))
        // 2. Honor the value of PHP's error_reporting() function
        || (!DEBUG && (0 == ($level & error_reporting())))
        ) {
        // Do nothing
        return true;
    }

    // Cleanup potential sensitive data
    $filename = (DEBUG ? $filename : basename($filename));

    // Give an alias name to any PHP error level number
    // PHP 5.3.0+
    if (!defined('E_DEPRECATED')) {
        define('E_DEPRECATED', 8192);
    }
    // PHP 5.3.0+
    if (!defined('E_USER_DEPRECATED')) {
        define('E_USER_DEPRECATED', 16384);        
    }    
    $errorTypes = array(
        E_ERROR             => 'error',
        E_WARNING           => 'warning',
        E_PARSE             => 'parse error',
        E_NOTICE            => 'notice',
        E_CORE_ERROR        => 'code error',
        E_CORE_WARNING      => 'core warning',
        E_COMPILE_ERROR     => 'compile error',
        E_COMPILE_WARNING   => 'compile warning',
        E_USER_ERROR        => 'user error',
        E_USER_WARNING      => 'user warning',
        E_USER_NOTICE       => 'user notice',
        E_STRICT            => 'strict warning',
        E_RECOVERABLE_ERROR => 'recoverable error',
        E_DEPRECATED        => 'deprecated warning',
        E_USER_DEPRECATED   => 'user deprecated warning',
    );
    $errorType = 'unknown error';
    if (isset($errorTypes[$level])) {
        $errorType = $errorTypes[$level];
    }

    // Custom error message
    $errorMessage = <<<EOD
<br />
<b>phpMyFAQ $errorType</b> [$level]: $message in <b>$filename</b> on line <b>$line</b><br />
EOD;

    if (ini_get('display_errors')) {
        print $errorMessage;
    }
    if (ini_get('log_errors')) {
        error_log(sprintf('phpMyFAQ %s:  %s in %s on line %d', 
            $errorType, 
            $message, 
            $filename, 
            $line));
    }

    switch ($level) {
        // Blocking errors
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            // Prevent processing any more PHP scripts
            exit();
            break;
        // Not blocking errors
        default:
            break;
    }
    
    return true;
}

//
// GENERAL FUNCTIONS
//

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
 * Checks for an address match (IPv4 or Network)
 *
 * @param   string  IP Address
 * @param   string  Network Address (e.g.: a.b.c.d/255.255.255.0 or a.b.c.d/24) or IP Address
 * @return  boolean
 * @since   2006-01-23
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author  Kenneth Shaw <ken@expitrans.com>
 */
function checkForAddrMatchIpv4($ip, $network)
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
 * Performs a check if an IPv4 is banned
 * 
 * NOTE: This function does not support IPv6
 *
 * @param   string  IP
 * @return  boolean
 * @since   2003-06-06
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 */
function IPCheck($ip)
{
    if (strstr($ip, '::')) {
        // currently we cannot handle IPv6
        return true;
    }
    
    $listBannedIPs = PMF_Configuration::getInstance()->get('main.bannedIPs');
    $bannedIPs     = explode(' ', $listBannedIPs);
    
    foreach ($bannedIPs as $oneIPerNetwork) {
        if (checkForAddrMatchIpv4($ip, $oneIPerNetwork)) {
            return false;
        }
    }
    return true;
}

/**
 * This function returns the banned words dictionary as an array.
 *
 * @return  array
 * @access  public
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 */
function getBannedWords()
{
    $bannedTrimmedWords = array();
    $bannedWordsFile    = dirname(__FILE__).'/blockedwords.txt';
    $bannedWords        = array();

    // Read the dictionary
    if (file_exists($bannedWordsFile) && is_readable($bannedWordsFile)) {
        $bannedWords = file_get_contents($bannedWordsFile);
    }
    
    // Trim it
    foreach (explode("\n", $bannedWords) as $word) {
        $bannedTrimmedWords[] = trim($word);
    }

    return $bannedTrimmedWords;
}

/**
 * This function checks the content against a dab word list
 * if the banned word spam protection has been activated from the general PMF configuration.
 *
 * @param   string  $content
 * @return  bool
 * @access  public
 * @author  Katherine A. Bouton
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author  Peter Beauvain <pbeauvain@web.de>
 */
function checkBannedWord($content)
{
    // Sanity checks
    $content = trim($content);
    if (('' == $content) && (!PMF_Configuration::getInstance()->get('spam.checkBannedWords'))) {
        return true;
    }

    $bannedWords = getBannedWords();
    // We just search a match of, at least, one banned word into $content
    $content = PMF_String::strtolower($content);
    if (is_array($bannedWords)) {
        foreach ($bannedWords as $bannedWord) {
            if (PMF_String::strpos($content, PMF_String::strtolower($bannedWord)) !== false) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Get out the HTML code for the fieldset that insert the captcha code in a (public) form
 *
 * @param   string  Text of the HTML Legend element
 * @param   string  HTML code for the Captcha image
 * @param   string  Length of the Captcha code
 * @return  string
 * @since   2006-04-25
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 */
function printCaptchaFieldset($legend, $img, $length, $error = '')
{
    $html = '';

    if (PMF_Configuration::getInstance()->get('spam.enableCaptchaCode')) {
        $html = sprintf('<fieldset><legend>%s</legend>', $legend);
        $html .= '<div style="text-align:left;">';
        if ($error != '') {
            $html .= '<div class="error">' . $error . '</div>';
        }
        $html .= $img;
        $html .= '&nbsp; &nbsp;<input class="inputfield" type="text" name="captcha" id="captcha" value="" size="7" style="vertical-align: top; height: 35px; text-valign: middle; font-size: 20pt;" />';
        $html .= '</div></fieldset>';
    }

    return $html;
}

/**
 * This function returns the passed content with HTML hilighted banned words.
 *
 * @param   string  $content
 * @return  string
 * @access  public
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 */
function getHighlightedBannedWords($content)
{
    $bannedHTMLHiliWords = array();
    $bannedWords         = getBannedWords();

    // Build the RegExp array
    foreach ($bannedWords as $word) {
        $bannedHTMLHiliWords[] = "/(".quotemeta($word).")/ism";
    }
    // Use the CSS "highlight" class to highlight the banned words
    if (count($bannedHTMLHiliWords)>0) {
        return PMF_String::preg_replace($bannedHTMLHiliWords, "<span class=\"highlight\">\\1</span>", $content);
    } else {
        return $content;
    }
}

/**
 * Returns the number of anonymous users and registered ones.
 * These are the numbers of unique users who have perfomed
 * some activities within the last five minutes
 *
 * @param  integer $activityTimeWindow Optionally set the time window size in sec. 
 *                                     Default: 300sec, 5 minutes
 * @return array
 */
function getUsersOnline($activityTimeWindow = 300)
{
    $users = array(0 ,0);
    $db    = PMF_Db::getInstance();

    if (PMF_Configuration::getInstance()->get('main.enableUserTracking')) {
        $timeNow = ($_SERVER['REQUEST_TIME'] - $activityTimeWindow);
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
            $row      = $db->fetch_object($result);
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
            $row      = $db->fetch_object($result);
            $users[1] = $row->registered_users;
        }
    }

    return $users;
}

/******************************************************************************
 * Funktionen fuer Artikelseiten
 ******************************************************************************/

/**
 * Macht an den String nen / dran, falls keiner da ist
 * @@ Bastian, 2002-01-06
 */
function EndSlash($string)
{
    if (PMF_String::substr($string, PMF_String::strlen($string)-1, 1) != "/" ) {
        $string .= "/";
    }
    return $string;
}

//
// Various functions
//

/**
 * Adds a menu entry according to user permissions.
 * ',' stands for 'or', '*' stands for 'and'
 *
 * @param  string  $restrictions Restrictions
 * @param  string  $action       Action parameter
 * @param  string  $caption      Caption
 * @param  string  $active       Active
 * @access public
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * 
 * @return string
 */
function addMenuEntry($restrictions = '', $action = '', $caption = '', $active = '')
{
    global $PMF_LANG;

    $class = '';
    if ($active == $action) {
        $class = ' class="current"';
    }

    if ($action != '') {
        $action = "action=".$action;
    }

    if (isset($PMF_LANG[$caption])) {
        $_caption = $PMF_LANG[$caption];
    } else {
        $_caption = 'No string for '.$caption;
    }

    $output = sprintf('        <li><a%s href="?%s">%s</a></li>%s',
        $class,
        $action,
        $_caption,
        "\n");
           
    return evalPermStr($restrictions) ? $output : '';
}

/**
 * Parse and check a permission string
 * 
 * Permissions are glued with each other as follows
 * - '+' stands for 'or'
 * - '*' stands for 'and'
 * 
 * No braces will be parsed, only simple expressions
 * @example right1*right2+right3+right4*right5
 * 
 * @author Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @param string $restrictions
 * 
 * @return boolean
 */
function evalPermStr($restrictions)
{
    global $permission;
    
    if(false !== strpos($restrictions, '+')) {
    	$retval = false;
        foreach (explode('+', $restrictions) as $_restriction) {
			$retval = $retval || evalPermStr($_restriction);
			if($retval) {
				break;
			}
        }        
    } else if(false !== strpos($restrictions, '*')) {
    	$retval = true;
        foreach (explode('*', $restrictions) as $_restriction) {
            if(!isset($permission[$_restriction]) || !$permission[$_restriction]) {
                $retval = false;
                break;   
            }
        }  
    } else {
    	$retval = strlen($restrictions) > 0 && isset($permission[$restrictions]) && $permission[$restrictions];
    }
    
    return $retval;
}

/******************************************************************************
 * Funktionen fuer den Adminbereich
 ******************************************************************************/

/**
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


//
// Functions for backup
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
    $db = PMF_Db::getInstance();
   
    if (!$result = $db->query($query)) {
        return;
    }
    $ret = array();

    $ret[] = "\r\n-- Table: ".$table;

    while ($row = $db->fetch_assoc($result)) {
        $p1 = array();
        $p2 = array();
        foreach ($row as $key => $val) {
            $p1[] = $key;
            if ('rights' != $key && is_numeric($val)) {
                $p2[] = $val;
            } else {
                if (is_null($val)) {
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

/**
 * Align the prefix of the table name used in the PMF backup file,
 * from the (old) value of the system upon which the backup was performed
 * to the (new) prefix of the system upon which the backup will be restored.
 * This alignment will be perfomed ONLY upon those given SQL queries starting
 * with the given pattern.
 *
 * @param   $query              string
 * @param   $start_pattern      string
 * @param   $oldvalue           string
 * @param   $newvalue           string
 * @return  string
 * @access  public
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
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
 * Align the prefix of the table name used in the PMF backup file,
 * from the (old) value of the system upon which the backup was performed
 * to the (new) prefix of the system upon which the backup will be restored
 * This alignment will be performed upon all of the SQL query "patterns"
 * provided within the PMF backup file.
 *
 * @param   $query          string
 * @param   $oldvalue       string
 * @param   $newvalue       string
 * @return  string
 * @access  public
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 */
function alignTablePrefix($query, $oldvalue, $newvalue)
{
    // Align DELETE FROM <prefix.tablename>
    $query = alignTablePrefixByPattern($query, "DELETE FROM", $oldvalue, $newvalue);
    // Align INSERT INTO <prefix.tablename>
    $query = alignTablePrefixByPattern($query, "INSERT INTO", $oldvalue, $newvalue);

    return $query;
}
