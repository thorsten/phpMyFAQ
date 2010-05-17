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

    switch ($level) {
        // Blocking errors
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            // Clear any output that has already been generated
            // TBD: it generally seems not useful unless when errors appear on
            //      coded HTTP streaming e.g. when creating PDF to be sent to users
            if (ob_get_length()) {
                //ob_clean();
            }
            // Output the error message
            echo $errorMessage;
            // Prevent processing any more PHP scripts
            exit();
            break;
        // Not blocking errors
        default:
            // Output the error message
            echo $errorMessage;
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
    
    foreach ($bannedIPs as $oneIPorNetwork) {
        if (checkForAddrMatchIpv4($ip, $oneIPorNetwork)) {
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

/**
 * Get search data weither as array or resource
 *
 * @param string $searchterm
 * @param boolean $asResource
 * @param string $cat
 * @param boolean $allLanguages
 * 
 * @return array|resource
 */
function getSearchData($searchterm, $asResource = false, $cat = '%', $allLanguages = true)
{
    $db       = PMF_Db::getInstance();
    $LANGCODE = PMF_Language::$language;
    $result   = null;  
    $num      = 0;
    $cond     = array(SQLPREFIX."faqdata.active" => "'yes'");

    if ($cat != '%') {
        $cond = array_merge(array(SQLPREFIX."faqcategoryrelations.category_id" => $cat), $cond);
    }

    if ((!$allLanguages) && (!is_numeric($searchterm))) {
        $cond = array_merge(array(SQLPREFIX."faqdata.lang" => "'".$LANGCODE."'"), $cond);
    }

    if (is_numeric($searchterm)) {
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
                        $searchterm,
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
                        $searchterm,
                        $cond);
    }

    if ($result) {
        $num = $db->numRows($result);
    }
    
    // Show the record with the solution ID directly
    // Sanity checks: if a valid Solution ID has been provided the result set
    //                will measure 1: this is true ONLY if the faq is not
    //                classified among more than 1 category
    if (is_numeric($searchterm) && ($searchterm >= PMF_SOLUTION_ID_START_VALUE) && ($num > 0)) {
        // Hack: before a redirection we must force the PHP session update for preventing data loss
        session_write_close();
        if (PMF_Configuration::getInstance()->get('main.enableRewriteRules')) {
            header('Location: '.PMF_Link::getSystemUri('/index.php').'/solution_id_'.$searchterm.'.html');
        } else {
            header('Location: '.PMF_Link::getSystemUri('/index.php').'/index.php?solution_id='.$searchterm);
        }
        exit();
    }

    if (0 == $num) {
        $keys = PMF_String::preg_split("/\s+/", $searchterm);
        $numKeys = count($keys);
        $where = '';
        for ($i = 0; $i < $numKeys; $i++) {
            if (PMF_String::strlen($where) != 0 ) {
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
    }

    return $asResource ? $result : $db->fetchAll($result);
}

/**
 * The main search function for the full text search
 *
 * TODO: add filter for (X)HTML tag names and attributes!
 *
 * @param   string  Text/Number (solution id)
 * @param   string  '%' to avoid any category filtering
 * @param   boolean true to search over all languages
 * @param   boolean true to disable the results paging
 * @param   boolean true to use it for Instant Response
 * @return  string
 * @access  public
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author  Adrianna Musiol <musiol@imageaccess.de>
 * @since   2002-09-16
 */
function searchEngine($searchterm, $cat = '%', $allLanguages = true, $hasMore = false, $instantRespnse = false)
{
    global $sids, $PMF_LANG, $plr, $LANGCODE, $faq, $current_user, $current_groups, $categoryLayout;

    $_searchterm = PMF_String::htmlspecialchars(stripslashes($searchterm), ENT_QUOTES, 'utf-8');
    $seite       = 1;
    $output      = '';
    $num         = 0;
    $searchItems = array();
    $langs       = (true == $allLanguages) ? '&amp;langs=all' : '';
    $seite       = PMF_Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
    $db          = PMF_Db::getInstance();
    $faqconfig   = PMF_Configuration::getInstance();

    $result = getSearchData(htmlentities($searchterm, ENT_COMPAT, 'utf-8'), true, $cat, $allLanguages);
    $num    = $db->numRows($result);

    if (0 == $num) {
        $output = $PMF_LANG['err_noArticles'];
    }

    $confPerPage = $faqconfig->get('main.numberOfRecordsPerPage');
    
    $pages = ceil($num / $confPerPage);
    $last  = $seite * $confPerPage;
    $first = $last - $confPerPage;
    if ($last > $num) {
        $last = $num;
    }

    if ($num > 0) {
        $output .= '<p>'.$plr->GetMsg('plmsgSearchAmount', $num);
        if ($hasMore && ($pages > 1)) {
            $output .= sprintf($PMF_LANG['msgInstantResponseMaxRecords'], $confPerPage);
        }
        $output .= "</p>\n";
        if (!$hasMore && ($pages > 1)) {
            $output .= "<p><strong>".$PMF_LANG["msgPage"].$seite." ".$PMF_LANG["msgVoteFrom"]." ".$plr->GetMsg('plmsgPagesTotal',$pages)."</strong></p>";
        }
        $output .= "<ul class=\"phpmyfaq_ul\">\n";

        $faqUser  = new PMF_Faq_User();
        $faqGroup = new PMF_Faq_Group();
        
        $counter = $displayedCounter = 0;
        while (($row = $db->fetchObject($result)) && $displayedCounter < $confPerPage) {
            $counter ++;
            if ($counter <= $first) {
                continue;
            }
            $displayedCounter++;

            $b_permission = false;
            //Groups Permission Check
            if ($faqconfig->get('main.permLevel') == 'medium') {
                $perm_group = $faqGroup->fetch($row->id);
                foreach ($current_groups as $value){
                    if ($value == $perm_group->group_id) {
                        $b_permission = true;
                    }
                }
            }
            if ($faqconfig->get('main.permLevel') == 'basic' || $b_permission) {
                $perm_user = $faqUser->fetch($row->id);
                foreach ($perm_user as $value) {
                    if ($value == -1) {
                        $b_permission = true;
                        break;
                    } elseif (((int)$value == $current_user)) {
                        $b_permission = true;
                        break;
                    } else {
                        $b_permission = false;
                    }
                }
            }
            
            if ($b_permission) {
                $rubriktext  = $categoryLayout->renderBreadcrumb(array($row->category_id));
                $thema       = PMF_Utils::chopString($row->thema, 15);
                $content     = PMF_Utils::chopString(strip_tags($row->content), 25);
                $searchterm  = str_replace(array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']', '"'), '', $searchterm);
                $searchterm  = preg_quote($searchterm, '/');
                $searchItems = explode(' ', $searchterm);

                if (PMF_String::strlen($searchItems[0]) > 1) {
                    foreach ($searchItems as $item) {
                        if (PMF_String::strlen($item) > 2) {
                            $thema = PMF_String::preg_replace_callback('/'
                                .'('.$item.'="[^"]*")|'
                                .'((href|src|title|alt|class|style|id|name|dir|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup)="[^"]*'.$item.'[^"]*")|'
                                .'('.$item.')'
                                .'/mis',
                                "highlight_no_links",
                                $thema );
                            $content = PMF_String::preg_replace_callback('/'
                                .'('.$item.'="[^"]*")|'
                                .'((href|src|title|alt|class|style|id|name|dir|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup)="[^"]*'.$item.'[^"]*")|'
                                .'('.$item.')'
                                .'/mis',
                                    "highlight_no_links",
                                $content);
                        }
                    }
                }

                // Print the link to the faq record
                $url = sprintf(
                    '?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang,
                    urlencode($_searchterm));

                if ($instantRespnse) {
                    $currentUrl = PMF_Link::getSystemRelativeUri('ajaxresponse.php').'index.php';
                } else {
                    $currentUrl = PMF_Link::getSystemRelativeUri();
                }
                $oLink            = new PMF_Link($currentUrl.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text      = $thema;
                $oLink->tooltip   = $row->thema;
                $output .=
                    '<li><strong>'.$rubriktext.'</strong>: '.$oLink->toHtmlAnchor().'<br />'
                    .'<div class="searchpreview"><strong>'.$PMF_LANG['msgSearchContent'].'</strong> '.$content.'...</div>'
                    .'<br /></li>'."\n";
            }
        }
        $output .= "</ul>\n";
    } else {
        $output = $PMF_LANG["err_noArticles"];
    }

    if (!$hasMore && ($num > $confPerPage)) {        
        if ($faqconfig->get('main.enableRewriteRules')) {
            $baseUrl = sprintf("search.html?search=%s&amp;seite=%d%s&amp;searchcategory=%d",
                            urlencode($_searchterm),
                            $seite,
                            $langs,
                            $cat);
        } else {
            $baseUrl = PMF_Link::getSystemRelativeUri() . '?'
                     . (empty($sids) ? '' : "$sids&amp;")
                     . 'action=search&amp;search=' . urlencode($_searchterm)
                     . '&amp;seite=' . $seite . $langs
                     . "&amp;searchcategory=" . $cat;
        }
                 
        $options = array('baseUrl'         => $baseUrl,
                         'total'           => $num,
                         'perPage'         => $confPerPage,
                         'pageParamName'   => 'seite',
                         'nextPageLinkTpl' => '<a href="{LINK_URL}">' . $PMF_LANG["msgNext"] . '</a>',
                         'prevPageLinkTpl' => '<a href="{LINK_URL}">' . $PMF_LANG["msgPrevious"] . '</a>',
                         'layoutTpl'       => '<p align="center"><strong>{LAYOUT_CONTENT}</strong></p>');
        
        $pagination = new PMF_Pagination($options);
        $output    .= $pagination->render();
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
function highlight_no_links(Array $matches)
{
    $itemAsAttrName  = $matches[1];
    $itemInAttrValue = $matches[2]; // $matches[3] is the attribute name
    $prefix          = isset($matches[3]) ? $matches[3] : '';
    $item            = isset($matches[4]) ? $matches[4] : '';
    $postfix         = isset($matches[5]) ? $matches[5] : '';
    
    if (!empty($item)) {
        return '<span class="highlight">'.$prefix.$item.$postfix.'</span>';
    }

    // Fallback: the original matched string
    return $matches[0];
}

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

    $ret[] = "\n-- Table: ".$table;

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
                    $p2[] = sprintf("'%s'", $db->escapeString($val));
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
