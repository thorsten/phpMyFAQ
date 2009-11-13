<?php
/**
 * The dynamic Yahoo Sitemap builder.
 *
 * http://[...]/sitemap.yahoo.php
 * http://[...]/sitemap.yahoo.php?gz=1
 * http://[...]/urllist.txt
 * http://[...]/urllist.txt.gz
 *
 * The Yahoo Sitemap protocol seems to be a plain text file containing a list of URLs,
 * each URL at the start of a new line. The filename of the URL list file must be urllist.txt;
 * for a compressed file the name must be urllist.txt.gz.
 *
 * PHP Version 5.2.0
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
 * @category  phpMyFAQ
 * @package   SEO
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2006-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-26
 */

// TODO: Verify if EOL must be CRLF or LF
define('PMF_SITEMAP_YAHOO_END_OF_LINE', "\r\n");
// TODO: Verify if a maximum number of URLs is required
define('PMF_SITEMAP_YAHOO_MAX_URLS', 50000);

define('PMF_SITEMAP_YAHOO_GET_GZIP', 'gz');
define('PMF_SITEMAP_YAHOO_FILENAME', 'urllist.txt');
define('PMF_SITEMAP_YAHOO_FILENAME_GZ', 'urllist.txt.gz');

define('PMF_ROOT_DIR', dirname(__FILE__));

require PMF_ROOT_DIR .'/inc/Init.php';

//
// Initalizing static string wrapper
//
PMF_String::init('en');

PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$oFaq = new PMF_Faq();
// Load the faq
$items = $oFaq->getTopTenData(PMF_SITEMAP_YAHOO_MAX_URLS - 1);

$sitemap = '';
// 1st entry: the faq server itself
$sitemap .= PMF_Link::getSystemUri('/sitemap.yahoo.php').PMF_SITEMAP_YAHOO_END_OF_LINE;

// nth entry: each faq
foreach ($items as $item) {
    // a. We use plain PMF urls w/o any SEO schema
    $link = str_replace($_SERVER['PHP_SELF'], '/index.php', $item['url']);
    // b. We use SEO PMF urls
    if (PMF_SITEMAP_YAHOO_USE_SEO) {
        if (isset($item['thema'])) {
            $oL = new PMF_Link($link);
            $oL->itemTitle = $item['thema'];
            $link = $oL->toString();
        }
    }
    $sitemap .= PMF_Link::getSystemUri('/sitemap.yahoo.php').$link.PMF_SITEMAP_YAHOO_END_OF_LINE;
}

$getgezip = PMF_Filter::filterInput(INPUT_GET, PMF_SITEMAP_YAHOO_GET_GZIP, FILTER_VALIDATE_INT);
if (!is_null($getgezip) && (1 == $getgezip)) {
    if (function_exists('gzencode')) {
        $sitemapGz = gzencode($sitemap);
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename="'.PMF_SITEMAP_YAHOO_FILENAME_GZ.'"');
        header('Content-Length: '.strlen($sitemapGz));
        print $sitemapGz;
    } else {
        PMF_Helper_Http::getInstance()->printHTTPStatus404();
    }
} else {
    header('Content-Type: text/plain');
    header('Content-Disposition: inline; filename="'.PMF_SITEMAP_YAHOO_FILENAME.'"');
    header('Content-Length: '.strlen($sitemap));
    print $sitemap;
}

$db->dbclose();
