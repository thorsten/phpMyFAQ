<?php
/**
* $Id: sitemap.google.php,v 1.2 2006-06-27 19:04:00 matteo Exp $
*
* The dynamic Google Sitemap builder
*
* http://[...]/sitemap.google.php
* http://[...]/sitemap.google.php?gz=1
* http://[...]/sitemap.xml
* http://[...]/sitemap.gz
* http://[...]/sitemap.xml.gz
*
* The Google Sitemap protocol is described here: http://www.google.com/webmasters/sitemaps/docs/en/protocol.html
*
* @package      phpMyFAQ
* @access       public
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @since        2006-06-26
* @copyright    (c) 2006 phpMyFAQ Team
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

// {{{ Constants
/**#@+
  * Google Sitemap specification related constants
  */
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_ALWAYS', 'always');
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_HOURLY', 'hourly');
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY', 'daily');
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_WEEKLY', 'weekly');
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_MONTHLY', 'monthly');
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_YEARLY', 'yearly');
define('PMF_SITEMAP_GOOGLE_CHANGEFREQ_NEVER', 'never');
define('PMF_SITEMAP_GOOGLE_MAX_URL_LENGTH', 2048);
define('PMF_SITEMAP_GOOGLE_MAX_URLS', 50000);
define('PMF_SITEMAP_GOOGLE_MAX_FILE_LENGTH', 10485760); // 10MB
define('PMF_SITEMAP_GOOGLE_PRIORITY_MIN', '0.0');
define('PMF_SITEMAP_GOOGLE_PRIORITY_MAX', '1.0');
define('PMF_SITEMAP_GOOGLE_PRIORITY_DEFAULT', '0.5');
/**#@-*/
/**#@+
  * HTTP parameters
  */
define('PMF_SITEMAP_GOOGLE_GET_GZIP', 'gz');
define('PMF_SITEMAP_GOOGLE_GET_INDEX', 'idx');
define('PMF_SITEMAP_GOOGLE_FILENAME', 'sitemap.xml');
define('PMF_SITEMAP_GOOGLE_FILENAME_GZ', 'sitemap.xml.gz');
define('PMF_SITEMAP_GOOGLE_INDEX_FILENAME', 'sitemap_index.xml');
/**#@-*/
/**#@+
  * System pages definitions
  */
define('PMF_ROOT_DIR', dirname(__FILE__));
/**#@-*/
// }}}

// {{{ Includes
require_once(PMF_ROOT_DIR.'/inc/Init.php');
require_once(PMF_ROOT_DIR.'/inc/Link.php');
require_once(PMF_ROOT_DIR.'/inc/Faq.php');
// }}}

// {{{ Functions
function buildSitemapNode($location, $lastmod = null, $changeFreq = null, $priority = null)
{
    if (!isset($lastmod)) {
        $lastmod = makeISO8601Date(time(), false);
    }
    if (!isset($changeFreq)) {
        $changeFreq = PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY;
    }
    $node =
         '<url>'
        .'<loc>'.htmlspecialchars(utf8_encode($location)).'</loc>'
        .'<lastmod>'.$lastmod.'</lastmod>'
        .'<changefreq>'.$changeFreq.'</changefreq>'
        .(isset($priority) ? '<priority>'.$priority.'</priority>' : '')
        .'</url>';

    return $node;
}

function printHTTPStatus404()
{
    if (    ('cgi' == substr(php_sapi_name(), 0, 3))
         || isset($_SERVER['ALL_HTTP'])
        )
    {
        header('Status: 404 Not Found');
    }
    else
    {
        header('HTTP/1.0 404 Not Found');
    }

    exit();
}
// }}}

//
// Future improvements
// WHEN a User PMF Sitemap will be:
//   a. bigger than 10MB (!)
//   b. w/ more than 50K URLs (!)
// we'll manage this issue using a Sitemap Index Files produced by this PHP code
// including Sitemap URLs always produced by this same PHP code (see PMF_SITEMAP_GOOGLE_GET_INDEX)
//

PMF_Init::cleanRequest();

$oFaq = new PMF_Faq($db, 'en');
// Load the faq
$items = $oFaq->getTopTenData(PMF_SITEMAP_GOOGLE_MAX_URLS -1);
$visitsMax = 0;
$visitMin  = 0;
if (count($items) > 0) {
    $visitsMax = $items[0]['visits'];
    $visitMin  = $items[count($items)-1]['visits'];
}

// Sitemap header
$sitemap =
     '<?xml version="1.0" encoding="UTF-8"?>'
    .'<urlset xmlns="http://www.google.com/schemas/sitemap/0.84"'
    .' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
    .' xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84'
    .' http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">';
// 1st entry: the faq server itself
$sitemap .= buildSitemapNode(PMF_Link::getSystemUri('/sitemap.google.php'),
                makeISO8601Date(time(), false),
                PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY,
                PMF_SITEMAP_GOOGLE_PRIORITY_MAX
            );

// nth entry: each faq
foreach ($items as $item) {
    $priority = PMF_SITEMAP_GOOGLE_PRIORITY_DEFAULT;
    if (($visitsMax - $visitMin) > 0) {
        $priority = sprintf('%.1f', PMF_SITEMAP_GOOGLE_PRIORITY_DEFAULT * (1 + (($item['visits'] - $visitMin)/($visitsMax - $visitMin))));
    }
    $sitemap .= buildSitemapNode(
                    // We use plain PMF urls w/o any SEO schema
                    PMF_Link::getSystemUri('/sitemap.google.php').str_replace($_SERVER['PHP_SELF'], '/index.php', $item['url']),
                    makeISO8601Date($item['date']),
                    // TODO: manage changefreq node with the info provided by faqchanges, IF this will not add a big load to the server (+1 query/faq)
                    PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY,
                    $priority
                );
}

$sitemap .= '</urlset>';

if (   isset($_GET[PMF_SITEMAP_GOOGLE_GET_GZIP])
    && is_numeric($_GET[PMF_SITEMAP_GOOGLE_GET_GZIP])
    && (1 == $_GET[PMF_SITEMAP_GOOGLE_GET_GZIP])
    ) {
    if (function_exists('gzencode'))
    {
        $sitemapGz = gzencode($sitemap);
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename="'.PMF_SITEMAP_GOOGLE_FILENAME_GZ.'"');
        header('Content-Length: '.strlen($sitemapGz));
        print $sitemapGz;
    }
    else
    {
        printHTTPStatus404();
    }
} else {
    header('Content-Type: text/xml');
    header('Content-Disposition: inline; filename="'.PMF_SITEMAP_GOOGLE_FILENAME.'"');
    header('Content-Length: '.strlen($sitemap));
    print $sitemap;
}

$db->dbclose();
