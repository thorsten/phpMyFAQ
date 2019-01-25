<?php

/**
 * The dynamic Google Sitemap builder.
 *
 * http://[...]/sitemap.xml.php
 * http://[...]/sitemap.xml.php?gz=1
 * http://[...]/sitemap.xml
 * http://[...]/sitemap.gz
 * http://[...]/sitemap.xml.gz
 *
 * The Google Sitemap protocol is described here:
 * http://www.google.com/webmasters/sitemaps/docs/en/protocol.html
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-26
 */

use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;

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

define('PMF_SITEMAP_GOOGLE_GET_GZIP', 'gz');
define('PMF_SITEMAP_GOOGLE_GET_INDEX', 'idx');
define('PMF_SITEMAP_GOOGLE_FILENAME', 'sitemap.xml');
define('PMF_SITEMAP_GOOGLE_FILENAME_GZ', 'sitemap.xml.gz');
define('PMF_SITEMAP_GOOGLE_INDEX_FILENAME', 'sitemap_index.xml');

define('PMF_ROOT_DIR', __DIR__);
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require __DIR__.'/src/Bootstrap.php';

//
// Initalizing static string wrapper
//
Strings::init('en');

if (false === $faqConfig->get('seo.enableXMLSitemap')) {
    exit();
}

// {{{ Functions
function buildSiteMapNode($location, $lastModified = null, $changeFreq = null, $priority = null)
{
    if (!isset($lastModified)) {
        $lastModified = Date::createIsoDate($_SERVER['REQUEST_TIME'], DATE_W3C, false);
    }
    if (!isset($changeFreq)) {
        $changeFreq = PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY;
    }
    $node =
         '<url>'
        .'<loc>'.Strings::htmlspecialchars($location).'</loc>'
        .'<lastmod>'.$lastModified.'</lastmod>'
        .'<changefreq>'.$changeFreq.'</changefreq>'
        .(isset($priority) ? '<priority>'.$priority.'</priority>' : '')
        .'</url>';

    return $node;
}

//
// Future improvements
// WHEN a User PMF Sitemap will be:
//   a. bigger than 10MB (!)
//   b. w/ more than 50K URLs (!)
// we'll manage this issue using a Sitemap Index Files produced by this PHP code
// including Sitemap URLs always produced by this same PHP code (see PMF_SITEMAP_GOOGLE_GET_INDEX)
//

$oFaq = new Faq($faqConfig);
// Load the faq
$items = $oFaq->getTopTenData(PMF_SITEMAP_GOOGLE_MAX_URLS - 1);
$visitsMax = 0;
$visitMin = 0;
if (count($items) > 0) {
    $visitsMax = $items[0]['visits'];
    $visitMin = $items[count($items) - 1]['visits'];
}

// Sitemap header
$siteMap =
     '<?xml version="1.0" encoding="UTF-8"?>'
    .'<urlset xmlns="http://www.google.com/schemas/sitemap/0.9"'
    .' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
    .' xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.9'
    .' http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">';
// 1st entry: the faq server itself
$siteMap .= buildSiteMapNode(
    $faqConfig->getDefaultUrl(),
    Date::createIsoDate($_SERVER['REQUEST_TIME'], DATE_W3C, false),
    PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY,
    PMF_SITEMAP_GOOGLE_PRIORITY_MAX
);

// nth entry: each faq
foreach ($items as $item) {
    $priority = PMF_SITEMAP_GOOGLE_PRIORITY_DEFAULT;
    if (($visitsMax - $visitMin) > 0) {
        $priority = sprintf('%.1f', PMF_SITEMAP_GOOGLE_PRIORITY_DEFAULT * (1 + (($item['visits'] - $visitMin) / ($visitsMax - $visitMin))));
    }
    // a. We use plain PMF urls w/o any SEO schema
    $link = str_replace($_SERVER['SCRIPT_NAME'], '/index.php', $item['url']);
    // b. We use SEO PMF urls
    if (PMF_SITEMAP_GOOGLE_USE_SEO) {
        if (isset($item['thema'])) {
            $oL = new Link($link, $faqConfig);
            $oL->itemTitle = $item['thema'];
            $link = $oL->toString();
        }
    }
    $siteMap .= buildSiteMapNode(
        $link,
        Date::createIsoDate($item['date'], DATE_W3C),
        // @todo: manage changefreq node with the info provided by faqchanges,
        // if this will not add a big load to the server (+1 query/faq)
        PMF_SITEMAP_GOOGLE_CHANGEFREQ_DAILY,
        $priority
    );
}

$siteMap .= '</urlset>';

$getGzip = Filter::filterInput(INPUT_GET, PMF_SITEMAP_GOOGLE_GET_GZIP, FILTER_VALIDATE_INT);
if (!is_null($getGzip) && (1 == $getGzip)) {
    if (function_exists('gzencode')) {
        $sitemapGz = gzencode($siteMap);
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename="'.PMF_SITEMAP_GOOGLE_FILENAME_GZ.'"');
        header('Content-Length: '.strlen($sitemapGz));
        echo $sitemapGz;
    } else {
        $http = new HttpHelper();
        $http->sendStatus(404);
    }
} else {
    header('Content-Type: text/xml');
    header('Content-Disposition: inline; filename="'.PMF_SITEMAP_GOOGLE_FILENAME.'"');
    header('Content-Length: '.strlen($siteMap));
    echo $siteMap;
}

$faqConfig->getDb()->close();
